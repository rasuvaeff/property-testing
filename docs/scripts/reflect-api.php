<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

$srcDir = __DIR__ . '/../../src';

/** @return list<string> */
function findPhpFiles(string $dir): array
{
    $files = [];
    foreach (scandir($dir) as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $path = $dir . '/' . $entry;
        if (is_dir($path)) {
            $files = [...$files, ...findPhpFiles($path)];
        } elseif (str_ends_with($entry, '.php')) {
            $files[] = $path;
        }
    }

    return $files;
}

function classNameFromPath(string $srcDir, string $path): string
{
    $relative = substr($path, strlen($srcDir) + 1, -4); // strip src/ prefix and .php suffix
    $relative = str_replace('/', '\\', $relative);

    return 'Rasuvaeff\\PropertyTesting\\' . $relative;
}

$report = [];

foreach (findPhpFiles($srcDir) as $path) {
    $className = classNameFromPath($srcDir, $path);
    if (!class_exists($className) && !interface_exists($className)) {
        continue;
    }

    $reflection = new ReflectionClass($className);
    $docComment = $reflection->getDocComment();
    $isApi = $docComment !== false && str_contains($docComment, '@api');

    $methods = [];
    foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if ($method->getDeclaringClass()->getName() !== $className) {
            continue; // inherited (e.g. RuntimeException::getMessage()), not this class's contract
        }
        if ($method->isConstructor()) {
            continue; // reported as constructorPromotedProperties below
        }

        $params = [];
        foreach ($method->getParameters() as $param) {
            $params[] = [
                'name' => $param->getName(),
                'type' => $param->getType()?->__toString(),
            ];
        }

        $methods[] = [
            'name' => $method->getName(),
            'static' => $method->isStatic(),
            'params' => $params,
            'returnType' => $method->getReturnType()?->__toString(),
        ];
    }

    $constructorPromotedProperties = [];
    $constructor = $reflection->getConstructor();
    if ($constructor !== null) {
        foreach ($constructor->getParameters() as $param) {
            if (!$param->isPromoted()) {
                continue;
            }
            $property = $reflection->getProperty($param->getName());
            if (!$property->isPublic()) {
                continue;
            }
            $constructorPromotedProperties[] = [
                'name' => $property->getName(),
                'type' => $property->getType()?->__toString(),
                'readonly' => $property->isReadOnly(),
            ];
        }
    }

    $declaredPublicProperties = [];
    foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
        if ($property->getDeclaringClass()->getName() !== $className) {
            continue;
        }
        if (in_array($property->getName(), array_column($constructorPromotedProperties, 'name'), true)) {
            continue;
        }
        $declaredPublicProperties[] = [
            'name' => $property->getName(),
            'type' => $property->getType()?->__toString(),
            'readonly' => $property->isReadOnly(),
        ];
    }

    $report[] = [
        'class' => $className,
        'kind' => $reflection->isInterface() ? 'interface' : ($reflection->isEnum() ? 'enum' : 'class'),
        'isApi' => $isApi,
        'extends' => ($reflection->getParentClass() ?: null)?->getName(),
        'publicProperties' => [...$constructorPromotedProperties, ...$declaredPublicProperties],
        'publicMethods' => $methods,
    ];
}

usort($report, static fn(array $a, array $b): int => $a['class'] <=> $b['class']);

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

$srcDir = __DIR__ . '/../../src';

/**
 * Splits a docblock into its free-text summary/description (everything
 * before the first @tag) and any @param descriptions, keyed by parameter
 * name — reflection alone (class/method signatures) throws this prose away,
 * and it is usually where the actual "what does this do" lives.
 *
 * @return array{summary: string, params: array<string, string>}
 */
function parseDocComment(string|false $docComment): array
{
    if ($docComment === false) {
        return ['summary' => '', 'params' => []];
    }

    $clean = [];
    foreach (explode("\n", $docComment) as $line) {
        $line = trim($line);
        $line = preg_replace('#^/\*\*#', '', $line);
        $line = preg_replace('#\*/$#', '', $line);
        $line = preg_replace('#^\*\s?#', '', $line) ?? '';
        $clean[] = rtrim($line);
    }
    while ($clean !== [] && $clean[0] === '') {
        array_shift($clean);
    }
    while ($clean !== [] && end($clean) === '') {
        array_pop($clean);
    }

    $summaryLines = [];
    $params = [];
    $currentParam = null;
    $buffer = [];

    $flush = static function () use (&$currentParam, &$buffer, &$params): void {
        if ($currentParam === null) {
            return;
        }
        $text = trim(implode(' ', $buffer));
        if ($text !== '') {
            $params[$currentParam] = $text;
        }
        $buffer = [];
    };

    foreach ($clean as $line) {
        if (preg_match('/^@param\s+\S+\s+\$(\w+)\s*(.*)$/', $line, $m) === 1) {
            $flush();
            $currentParam = $m[1];
            $buffer = $m[2] !== '' ? [$m[2]] : [];

            continue;
        }
        if (preg_match('/^@\w+/', $line) === 1) {
            $flush();
            $currentParam = 'done'; // any non-null sentinel: stop collecting into $summaryLines
            $buffer = [];

            continue;
        }
        if ($currentParam === null) {
            $summaryLines[] = $line;
        } elseif ($currentParam !== 'done') {
            $buffer[] = $line;
        }
    }
    $flush();

    $summary = trim(implode("\n", $summaryLines));
    $summary = preg_replace('/\n{3,}/', "\n\n", $summary) ?? $summary;
    $summary = preg_replace('/\{@(?:see|link)\s+([^}]+)\}/', '$1', $summary) ?? $summary;

    foreach ($params as $name => $text) {
        $params[$name] = preg_replace('/\{@(?:see|link)\s+([^}]+)\}/', '$1', $text) ?? $text;
    }

    return ['summary' => $summary, 'params' => $params];
}

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
    // A real `@api` TAG at the start of a docblock line — not any mention of
    // the token in prose (an @internal class legitimately says it "becomes
    // @api after the split", and that must not classify it as public API).
    $isApi = $docComment !== false && preg_match('/^\s*\*\s*@api\b/m', $docComment) === 1;
    $classDoc = parseDocComment($docComment);

    $methods = [];
    foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if ($method->getDeclaringClass()->getName() !== $className) {
            continue; // inherited (e.g. RuntimeException::getMessage()), not this class's contract
        }
        if ($method->isConstructor()) {
            continue; // reported as constructorPromotedProperties below
        }

        $methodDoc = parseDocComment($method->getDocComment());

        $params = [];
        foreach ($method->getParameters() as $param) {
            $params[] = [
                'name' => $param->getName(),
                'type' => $param->getType()?->__toString(),
                'description' => $methodDoc['params'][$param->getName()] ?? '',
            ];
        }

        $methods[] = [
            'name' => $method->getName(),
            'static' => $method->isStatic(),
            'params' => $params,
            'returnType' => $method->getReturnType()?->__toString(),
            'summary' => $methodDoc['summary'],
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
        'summary' => $classDoc['summary'],
        'extends' => ($reflection->getParentClass() ?: null)?->getName(),
        'publicProperties' => [...$constructorPromotedProperties, ...$declaredPublicProperties],
        'publicMethods' => $methods,
    ];
}

usort($report, static fn(array $a, array $b): int => $a['class'] <=> $b['class']);

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

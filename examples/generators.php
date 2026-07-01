<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Rasuvaeff\PropertyTesting\Gen;
use Rasuvaeff\PropertyTesting\Random;

/**
 * Tours the generators added in 1.1.0 without the Testo runner: sampling,
 * boundary-biased numbers, and the new value/structure generators.
 */

// Gen::sample — eyeball what a generator produces for a fixed seed.
echo 'intBetween(1, 6) sample: ' . implode(', ', Gen::sample(Gen::intBetween(1, 6), 8, 42)) . "\n";

// Numeric generators are boundary-biased: edge values appear far more often than
// uniform sampling would produce them.
$random = new Random(7);
$int = Gen::intBetween(0, 1000);
$edges = 0;
for ($i = 0; $i < 100; ++$i) {
    if (in_array($int->generate($random), [0, 1, 1000], true)) {
        ++$edges;
    }
}
echo "boundary hits in 100 draws: {$edges} (uniform would be ~0)\n";

// New value generators.
$random = new Random(1);
echo 'uuid: ' . Gen::uuid()->generate($random) . "\n";
echo 'datetime: ' . Gen::datetime()->generate($random)->format(DATE_ATOM) . "\n";

// dictOf — string-keyed map; record — fixed shape keyed by field name.
$dictionary = Gen::dictOf(Gen::stringOf(3, 5), Gen::intBetween(1, 9))->generate($random);
echo 'dictOf entries: ' . count($dictionary) . "\n";

$user = Gen::record([
    'age' => Gen::intBetween(0, 120),
    'active' => Gen::bool(),
])->generate($random);
echo "record: age={$user['age']}, active=" . ($user['active'] ? 'true' : 'false') . "\n";

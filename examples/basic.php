<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Rasuvaeff\PropertyTesting\Gen;
use Rasuvaeff\PropertyTesting\Random;

/**
 * This example shows the three pieces of property-based testing in isolation,
 * without the Testo runner: a generator, a property that holds, and a property
 * that is falsified and then shrunk to a minimal counterexample.
 */

$random = new Random(42);

// A property that holds: the sum of two non-negative integers is non-negative.
$left = Gen::intBetween(0, 1000);
$right = Gen::intBetween(0, 1000);

$violated = false;

for ($run = 0; $run < 100; ++$run) {
    $a = $left->generate($random);
    $b = $right->generate($random);

    if ($a + $b < 0) {
        $violated = true;
    }
}

echo $violated
    ? "sum-is-nonnegative: FAILED unexpectedly\n"
    : "sum-is-nonnegative: held for 100 runs\n";

// A property that is falsified: "every integer is even". Shrinking lands on the
// smallest odd value the generator happened to produce (clamped to the range).
$ints = Gen::intBetween(0, 1000);

$failing = null;
for ($run = 0; $run < 100; ++$run) {
    $value = $ints->generate($random);

    if ($value % 2 !== 0) {
        $failing = $value;

        break;
    }
}

if ($failing === null) {
    echo "all-even: never falsified\n";
} else {
    echo "all-even: falsified with original value $failing\n";

    $minimal = $failing;
    foreach ($ints->shrink($failing) as $candidate) {
        if ($candidate % 2 !== 0) {
            $minimal = $candidate;
        }
    }
    echo "all-even: shrunk to minimal odd value $minimal\n";
}

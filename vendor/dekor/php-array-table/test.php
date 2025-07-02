<?php
/**
 * THIS FILE IS FOR DEV TESTS ONLY
 */

use dekor\ArrayToTextTable;
use dekor\formatters\ColorFormatter;

require __DIR__ . '/vendor/autoload.php';

$data = [
    ['test' => 1],
    ['test' => -1],
];

$builder = new ArrayToTextTable($data);
$builder->applyFormatter(new ColorFormatter(['test' => fn ($value) => $value > 0 ? 'Red' : 'Green']));

echo $builder->render() . PHP_EOL;

<?php

use dekor\ArrayToTextTableException;
use dekor\formatters\AlignFormatter;
use dekor\formatters\ColorFormatter;
use dekor\formatters\SprintfFormatter;
use PHPUnit\Framework\TestCase;
use dekor\ArrayToTextTable;

class ColorTest extends TestCase
{
    /**
     * @dataProvider getCases
     */
    public function testCorrectBuilding($data, $expectResult)
    {
        $builder = new ArrayToTextTable($data);
        $builder->applyFormatter(new ColorFormatter(['test' => fn ($value) => $value > 0 ? 'Red' : 'Green']));

        $this->assertEquals($expectResult, $builder->render());
    }

    public static function getCases()
    {
        return [
            [
                'data' => [
                    ['test' => 1],
                    ['test' => -1],
                ],
                'expected' =>
                    '+------+' . PHP_EOL .
                    '| test |' . PHP_EOL .
                    '+------+' . PHP_EOL .
                    '|[0;31m 1    [0m|' . PHP_EOL .
                    '|[0;32m -1   [0m|' . PHP_EOL .
                    '+------+',
            ],
        ];
    }

    public function testInCorrectBuilding()
    {
        $data = [['test' => 1]];

        $builder = new ArrayToTextTable($data);
        $builder->applyFormatter(new AlignFormatter(['test' => 'imposible']));

        $this->expectException(ArrayToTextTableException::class);
        $builder->render();
    }
}
<?php

use dekor\ArrayToTextTableException;
use dekor\formatters\AlignFormatter;
use PHPUnit\Framework\TestCase;
use dekor\ArrayToTextTable;

class AlignFormatterTest extends TestCase
{
    /**
     * @dataProvider getCases
     */
    public function testCorrectBuilding($data, $expectResult)
    {
        $builder = new ArrayToTextTable($data);
        $builder->applyFormatter(new AlignFormatter(['center' => 'center', 'right' => 'right']));

        $this->assertEquals($expectResult, $builder->render());
    }

    public static function getCases()
    {
        return [
            [
                'data' => [
                    [
                        'left' => 1,
                        'center' => 'Denis Koronets',
                        'right' => 2.89,
                    ],
                    [
                        'left' => 2,
                        'center' => 'Dummy one',
                        'right' => 14.33,
                    ],
                ],
                'expected' =>
                    '+------+----------------+-------+' . PHP_EOL .
                    '| left | center         | right |' . PHP_EOL .
                    '+------+----------------+-------+' . PHP_EOL .
                    '| 1    | Denis Koronets |  2.89 |' . PHP_EOL .
                    '| 2    |   Dummy one    | 14.33 |' . PHP_EOL .
                    '+------+----------------+-------+',
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
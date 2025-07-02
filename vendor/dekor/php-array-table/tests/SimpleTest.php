<?php

use dekor\ArrayToTextTableException;
use PHPUnit\Framework\TestCase;
use dekor\ArrayToTextTable;

class SimpleTest extends TestCase
{
    /**
     * @dataProvider getCases
     */
    public function testCorrectBuilding($data, $expectResult)
    {
        $builder = new ArrayToTextTable($data);

        $this->assertEquals($expectResult, $builder->render());
    }

    public static function getCases()
    {
        return [
            [
                'data' => [
                    [
                        'id' => 1,
                        'name' => 'Denis Koronets',
                        'role' => 'php developer',
                    ],
                    [
                        'id' => 2,
                        'name' => 'Maxim Ambroskin',
                        'role' => 'java developer',
                    ],
                    [
                        'id' => 3,
                        'name' => 'Andrew Sikorsky',
                        'role' => 'php developer',
                    ]
                ],
                'expected' =>
                    '+----+-----------------+----------------+' . PHP_EOL .
                    '| id | name            | role           |' . PHP_EOL .
                    '+----+-----------------+----------------+' . PHP_EOL .
                    '| 1  | Denis Koronets  | php developer  |' . PHP_EOL .
                    '| 2  | Maxim Ambroskin | java developer |' . PHP_EOL .
                    '| 3  | Andrew Sikorsky | php developer  |' . PHP_EOL .
                    '+----+-----------------+----------------+',
            ],
            [
                'data' => [
                    [
                        'singleColumn' => 'test value',
                    ],
                ],
                'expected' =>
                    '+--------------+' . PHP_EOL .
                    '| singleColumn |' . PHP_EOL .
                    '+--------------+' . PHP_EOL .
                    '| test value   |' . PHP_EOL .
                    '+--------------+',
            ],
            [
                'data' => [
                    [
                        'id' => 1,
                        'name' => 'Денис Коронец',
                        'role' => 'Тест кириллических символов',
                    ],
                ],
                'expected' =>
                    '+----+---------------+-----------------------------+' . PHP_EOL .
                    '| id | name          | role                        |' . PHP_EOL .
                    '+----+---------------+-----------------------------+' . PHP_EOL .
                    '| 1  | Денис Коронец | Тест кириллических символов |' . PHP_EOL .
                    '+----+---------------+-----------------------------+',
            ],
            [
                'data' => [
                    [
                        'id' => 1,
                        'name' => 'Денис Коронец',
                        'role' => 'Тест кириллических символов',
                    ],
                    '---',
                    [
                        'id' => 2,
                        'name' => 'Артем Малеев',
                        'role' => 'Тест кириллических символов 2',
                    ],
                ],
                'expected' =>
                    '+----+---------------+-------------------------------+' . PHP_EOL .
                    '| id | name          | role                          |' . PHP_EOL .
                    '+----+---------------+-------------------------------+' . PHP_EOL .
                    '| 1  | Денис Коронец | Тест кириллических символов   |' . PHP_EOL .
                    '+----+---------------+-------------------------------+' . PHP_EOL .
                    '| 2  | Артем Малеев  | Тест кириллических символов 2 |' . PHP_EOL .
                    '+----+---------------+-------------------------------+',
            ],
        ];
    }

    public function testInCorrectDataBuilding()
    {
        $data = [['test' => []]];

        $builder = new ArrayToTextTable($data);

        $this->expectException(ArrayToTextTableException::class);
        $builder->render();
    }
}
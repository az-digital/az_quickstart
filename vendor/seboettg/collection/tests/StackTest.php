<?php
/*
 * Copyright (C) 2018 Sebastian Böttger <seboettg@gmail.com>
 * You may use, distribute and modify this code under the
 * terms of the MIT license.
 *
 * You should have received a copy of the MIT license with
 * this file. If not, please visit: https://opensource.org/licenses/mit-license.php
 */

namespace Seboettg\Collection\Test;

use PHPUnit\Framework\TestCase;
use Seboettg\Collection\Stack;

/**
 * Class StackTest
 * @package Seboettg\Collection\Test
 * @author Sebastian Böttger <boettger@hebis.uni-frankfurt.de>
 */
class StackTest extends TestCase
{
    /**
     * @var Stack
     */
    protected $stack;

    public function setUp(): void
    {
        $this->stack = new Stack();
    }

    public function testPush()
    {

        $this->assertEquals(0, $this->stack->count());
        $this->pushElements();
        $this->assertEquals(3, $this->stack->count());
    }

    public function testPop()
    {
        $this->pushElements();
        $this->assertEquals(3, $this->stack->count());
        $this->assertEquals("c", $this->stack->pop());
        $this->assertEquals(2, $this->stack->count());
    }

    public function testPeek()
    {
        $this->pushElements();
        $this->assertEquals(3, $this->stack->count());
        $this->assertEquals("c", $this->stack->peek());
        $this->assertEquals(3, $this->stack->count());
    }

    public function testSearch()
    {
        $this->pushElements();
        $this->assertEquals(0, $this->stack->search("d"));
        $this->assertEquals(3, $this->stack->search("a"));
        $this->assertEquals(2, $this->stack->search("b"));
        $this->assertEquals(1, $this->stack->search("c"));

    }

    private function pushElements()
    {
        $this->stack
            ->push("a")
            ->push("b")
            ->push("c");
    }
}

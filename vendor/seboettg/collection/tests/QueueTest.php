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
use Seboettg\Collection\Queue;
use Seboettg\Collection\Queue\QueueInterface;


/**
 * Class QueueTest
 * @package Seboettg\Collection\Test
 * @author Sebastian Böttger <boettger@hebis.uni-frankfurt.de>
 */
class QueueTest extends TestCase
{
    /**
     * @var QueueInterface
     */
    protected $queue;

    public function setUp(): void
    {
        $this->queue = new Queue();

        $a = new \stdClass();
        $a->val = "a";
        $b = new \stdClass();
        $b->val = "b";
        $c = new \stdClass();
        $c->val = "c";

        $this->queue->enqueue($c)
                    ->enqueue($b)
                    ->enqueue($a);
    }

    public function testDequeue()
    {
        $this->assertEquals("c", $this->queue->dequeue()->val);
        $this->assertEquals("b", $this->queue->dequeue()->val);
        $this->assertEquals(1, $this->queue->count());
        $d = new \stdClass();
        $d->val = "d";
        $this->queue->enqueue($d);
        $this->assertEquals(2, $this->queue->count());
        $this->assertEquals("a", $this->queue->dequeue()->val);
    }

    public function testEnqueue()
    {
        $queue = new Queue();
        $queue->enqueue("a")
              ->enqueue("b")
              ->enqueue("c");

        $this->assertEquals("a", $queue->dequeue());
        $this->assertEquals("b", $queue->dequeue());
        $this->assertEquals("c", $queue->dequeue());
    }

    public function testCount()
    {
        $this->assertEquals(3, $this->queue->count());
        $this->queue->dequeue();
        $this->queue->dequeue();
        $this->queue->dequeue();
        $this->assertEquals(0, $this->queue->count());
        $null = $this->queue->dequeue();
        $this->assertEquals(null, $null);
        $this->assertEquals(0, $this->queue->count());
    }
}

<?php
declare(strict_types=1);
/*
 * Copyright (C) 2018 Sebastian Böttger <seboettg@gmail.com>
 * You may use, distribute and modify this code under the
 * terms of the MIT license.
 *
 * You should have received a copy of the MIT license with
 * this file. If not, please visit: https://opensource.org/licenses/mit-license.php
 */

namespace Seboettg\Collection\Queue;

use Countable;
use Seboettg\Collection\CollectionInterface;

/**
 * Interface QueueInterface
 * @package Seboettg\Collection\Queue
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
interface QueueInterface extends CollectionInterface, Countable
{

    /**
     * Adds an element to the queue
     * @param $element
     * @return mixed
     */
    public function enqueue($element);

    /**
     * Dequeues an element from the queue
     * @return mixed
     */
    public function dequeue();
}

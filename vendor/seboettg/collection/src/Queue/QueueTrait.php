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

/**
 * Trait QueueTrait
 * @property $array Base array of this data structure
 * @package Seboettg\Collection\Queue
 */
trait QueueTrait
{
    /**
     * {@inheritdoc}
     */
    public function enqueue($item)
    {
        $this->array = array_merge([$item], $this->array);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function dequeue()
    {
        return array_pop($this->array);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->array);
    }
}

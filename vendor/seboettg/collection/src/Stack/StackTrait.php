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

namespace Seboettg\Collection\Stack;

/**
 * Trait StackTrait
 * @package Seboettg\Collection
 * @author Sebastian Böttger <seboettg@gmail.com>
 * @property $array Base array of this data structure
 */
trait StackTrait
{
    /**
     * {@inheritdoc}
     */
    public function push($item)
    {
        $this->array[] = $item;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function pop()
    {
        return array_pop($this->array);
    }

    /**
     * {@inheritdoc}
     */
    public function peek()
    {
        return end($this->array);
    }

    /**
     * {@inheritdoc}
     */
    public function search($element)
    {
        $pos = array_search($element, $this->array);
        if ($pos === false) {
            return 0;
        }
        $count = $this->count();
        return $count - $pos;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->array);
    }
}

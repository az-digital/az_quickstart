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

namespace Seboettg\Collection\ArrayList;

use ArrayIterator;

/**
 * Trait ArrayAccessTrait
 * @package Seboettg\Collection\ArrayList
 * @property $array Base array of this data structure
 */
trait ArrayAccessTrait
{
    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new ArrayIterator($this->array);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset The offset to retrieve.
     *
     * @return mixed Can return all value types.
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return isset($this->array[$offset]) ? $this->array[$offset] : null;
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value The value to set.
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->array[$offset] = $value;
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->array[$offset]);
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset The offset to unset.
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->array[$offset]);
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->array);
    }
}

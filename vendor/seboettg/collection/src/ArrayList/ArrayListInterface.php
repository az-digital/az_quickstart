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

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Seboettg\Collection\CollectionInterface;
use Traversable;

/**
 * Interface ArrayListInterface
 * @package Seboettg\Collection\ArrayList
 */
interface ArrayListInterface extends CollectionInterface, Traversable, IteratorAggregate, ArrayAccess, Countable, ToArrayInterface
{

    /**
     * returns element with key $key
     * @param $key
     * @return mixed|null
     */
    public function get($key);

    /**
     * Inserts or replaces the element at the specified position in this list with the specified element.
     *
     * @param $key
     * @param $element
     * @return ArrayListInterface
     */
    public function set($key, $element): ArrayListInterface;

    /**
     * Returns the value of the array element that's currently being pointed to by the
     * internal pointer. It does not move the pointer in any way. If the
     * internal pointer points beyond the end of the elements list or the array is
     * empty, current returns false.
     *
     * @return mixed|false
     */
    public function current();

    /**
     * Advance the internal array pointer of an array.
     * Returns the array value in the next place that's pointed to by the
     * internal array pointer, or false if there are no more elements.
     *
     * @return mixed|false
     */
    public function next();

    /**
     * Rewind the internal array pointer.
     * Returns the array value in the previous place that's pointed to by
     * the internal array pointer, or false if there are no more
     *
     * @return mixed|false
     */
    public function prev();

    /**
     * @param array $array
     * @return ArrayListInterface
     */
    public function replace(array $array): ArrayListInterface;

    /**
     * Appends the passed element to the end of this list.
     *
     * @param $element
     * @return ArrayListInterface
     */
    public function append($element): ArrayListInterface;

    /**
     * Inserts the specified element at the specified position in this list. If another element already exist at the
     * specified position the affected positions will transformed into a numerated array. As well the existing element
     * as the specified element will be appended to this array.
     *
     * @param $key
     * @param $element
     * @return ArrayListInterface
     */
    public function add($key, $element): ArrayListInterface;

    /**
     * Removes the element at the specified position in this list.
     *
     * @param $key
     * @return ArrayListInterface
     */
    public function remove($key): ArrayListInterface;

    /**
     * Returns true if an element exists on the specified position.
     *
     * @param mixed $key
     * @return bool
     */
    public function hasKey($key): bool;

    /**
     * Returns true if the passed element already exists in this list, otherwise false.
     *
     * @param string $element
     * @return bool
     */
    public function hasElement($element): bool;

    /**
     * Returns the first element in this list
     * @return mixed
     */
    public function first();

    /**
     * Returns the last element in this list
     * @return mixed
     */
    public function last();

    /**
     * alias of replace function
     * @param array $array
     * @return ArrayListInterface
     */
    public function setArray(array $array): ArrayListInterface;

    /**
     * flush array list
     *
     * @return ArrayListInterface
     */
    public function clear(): ArrayListInterface;

    /**
     * Shuffles this list (randomizes the order of the elements in). It uses the PHP function shuffle
     * @see http://php.net/manual/en/function.shuffle.php
     * @return ArrayListInterface
     */
    public function shuffle(): ArrayListInterface;

    /**
     * returns a clone of this ArrayList, filtered by the given closure function
     * @param callable $filterFunction
     * @return ArrayListInterface
     */
    public function filter(callable $filterFunction): ArrayListInterface;

    /**
     * returns a clone of this ArrayList, filtered by the given array keys
     * @param array $keys
     * @return ArrayListInterface
     */
    public function filterByKeys(array $keys): ArrayListInterface;

    /**
     * Merges the elements of the passed list together with this list so that the values of the passed list are appended
     * to the end of the this list
     * @param ArrayListInterface $list
     * @return void
     */
    public function merge(ArrayListInterface $list): void;

    /**
     * returns a new ArrayList containing all the elements of this ArrayList after applying the callback function to each one.
     * @param callable $mapFunction
     * @return ArrayListInterface
     */
    public function map(callable $mapFunction): ArrayListInterface;

    /**
     * Same as <code>map</code> but removes null values from the new list
     * @param callable $mapFunction
     * @return ArrayListInterface
     */
    public function mapNotNull(callable $mapFunction): ArrayListInterface;

    /**
     * Returns a new ArrayList containing an one-dimensional array of all elements of this ArrayList. Keys are going lost.
     * @return ArrayListInterface
     */
    public function flatten(): ArrayListInterface;

    /**
     * Expects a callable function which collects the elements of this list and returns any object. The callable
     * function gets passed the entire array of the list
     *
     * @param callable $collectionFunction
     * @return mixed
     */
    public function collect(callable $collectionFunction);

    /**
     * Tries to convert each element of the list to a string and concatenates them with given delimiter.
     * Throws a <code>NotConvertibleToStringException</code> if any of the objects in the list is not a string or is not
     * convertible to string.
     *
     * @param string $delimiter
     * @throws NotConvertibleToStringException
     * @return string
     */
    public function collectToString(string $delimiter): string;
}

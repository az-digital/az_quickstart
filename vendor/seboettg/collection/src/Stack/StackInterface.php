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

use Countable;
use Seboettg\Collection\CollectionInterface;

/**
 * Interface StackInterface
 * @package Seboettg\Collection\Stack
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
interface StackInterface extends CollectionInterface, Countable
{

    /**
     * Pushes an element onto the top of this stack. This has exactly the same effect as:
     * @param mixed $element
     * @return StackInterface
     */
    public function push($element);

    /**
     * Removes the element at the top of this stack and returns that object as the value of this function.
     * @return mixed
     */
    public function pop();

    /**
     * Returns the element at the top of this stack without removing it from the stack.
     * @return mixed
     */
    public function peek();

    /**
     * Returns the position where an element is on this stack. If the passed element occurs as an element in this stack,
     * this method returns the distance from the top of the stack of the occurrence nearest the top of the stack;
     * the topmost element on the stack is considered to be at distance 1. If the passed element does not occur in this
     * stack, this method returns 0.
     *
     * @param $element
     * @return int
     */
    public function search($element);
}

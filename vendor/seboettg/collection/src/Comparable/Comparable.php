<?php
declare(strict_types=1);
/*
 * Copyright (C) 2016 Sebastian Böttger <seboettg@gmail.com>
 * You may use, distribute and modify this code under the
 * terms of the MIT license.
 *
 * You should have received a copy of the MIT license with
 * this file. If not, please visit: https://opensource.org/licenses/mit-license.php
 */

namespace Seboettg\Collection\Comparable;

/**
 * Comparable Interface for elements as part of an ArrayList.
 *
 * This interface imposes a total ordering on the objects of each class that implements it. This ordering is referred
 * to as the class's natural ordering, and the class's compareTo method is referred to as its natural comparison method.
 *
 * @package Seboettg\Collection
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
interface Comparable
{
    /**
     * Compares this object with the specified object for order. Returns a negative integer, zero, or a positive
     * integer as this object is less than, equal to, or greater than the specified object.
     *
     * The implementor must ensure sgn(x.compareTo(y)) == -sgn(y.compareTo(x)) for all x and y.
     *
     * @param Comparable $b
     * @return int
     */
    public function compareTo(Comparable $b): int;
}

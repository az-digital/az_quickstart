<?php

/*
 * Copyright (C) 2016 Sebastian Böttger <seboettg@gmail.com>
 * You may use, distribute and modify this code under the
 * terms of the MIT license.
 *
 * You should have received a copy of the MIT license with
 * this file. If not, please visit: https://opensource.org/licenses/mit-license.php
 */

namespace Seboettg\Collection\Test;

use PHPUnit\Framework\TestCase;
use Seboettg\Collection\ArrayList;
use Seboettg\Collection\Collections;
use Seboettg\Collection\Comparable\Comparable;
use Seboettg\Collection\Comparable\Comparator;

class CollectionsTest extends TestCase
{

    /**
     * @var ArrayList
     */
    private $numeratedArrayList;

    public function setUp(): void
    {
        $this->numeratedArrayList = new ArrayList(
            new Element("a", "aa"),
            new Element("b", "bb"),
            new Element("c", "cc"),
            new Element("k", "kk"),
            new Element("d", "dd")
        );
    }

    public function testSort()
    {
        $lte = false;
        Collections::sort($this->numeratedArrayList, new MyAscendingComparator());
        $arr = $this->numeratedArrayList;
        for ($i = 0; $i < $arr->count() - 1; ++$i) {
            $lte = (ord($arr[$i]->getAttr1()) <= ord($arr[$i++]->getAttr1()));
            if (!$lte) {
                break;
            }
        }
        $this->assertTrue($lte);
    }

    public function testSortCustomOrder()
    {
        $order = ["d", "k", "a", "b", "c"];
        Collections::sort($this->numeratedArrayList, new MyCustomOrderComparator(Comparator::ORDER_CUSTOM, $order));
        for ($i = 0; $i < count($order); ++$i) {
            /** @var Element $elem */
            $elem = $this->numeratedArrayList->get($i);
            $this->assertTrue($order[$i] === $elem->getAttr1());
        }
    }

}

class MyAscendingComparator extends Comparator
{
    /**
     * Compares its two arguments for order. Returns a negative integer, zero, or a positive integer as the first
     * argument is less than, equal to, or greater than the second.
     *
     * @param Comparable $a
     * @param Comparable $b
     * @return int
     */
    public function compare(Comparable $a, Comparable $b): int
    {
        return $a->compareTo($b);
    }
}

class MyDescendingComparator extends Comparator
{
    /**
     * Compares its two arguments for order. Returns a negative integer, zero, or a positive integer as the first
     * argument is less than, equal to, or greater than the second.
     *
     * @param Comparable $a
     * @param Comparable $b
     * @return int
     */
    public function compare(Comparable $a, Comparable $b): int
    {
        return $b->compareTo($a);
    }
}

class MyCustomOrderComparator extends Comparator
{

    /**
     * Compares its two arguments for order. Returns a negative integer, zero, or a positive integer as the first
     * argument is less than, equal to, or greater than the second.
     *
     * @param Comparable $a
     * @param Comparable $b
     * @return int
     */
    public function compare(Comparable $a, Comparable $b): int
    {
        /**
         * @var Element $a
         * @var Element $b
         */
        return (array_search($a->getAttr1(), $this->customOrder) >= array_search($b->getAttr1(), $this->customOrder)) ? 1 : -1;
    }
}

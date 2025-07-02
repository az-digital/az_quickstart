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

use Exception;
use PHPUnit\Framework\TestCase;
use Seboettg\Collection\ArrayList;
use Seboettg\Collection\Collections;
use Seboettg\Collection\Comparable\Comparable;
use Seboettg\Collection\Comparable\Comparator;
use Seboettg\Collection\Stack;

use function Seboettg\Collection\ArrayList\strval;

class ArrayListTest extends TestCase
{

    /**
     * @var ArrayList
     */
    private $numeratedArrayList;

    /**
     * @var ArrayList
     */
    private $hashMap;


    public function setUp(): void
    {
        $this->numeratedArrayList = new ArrayList(
            new Element("a", "aa"),
            new Element("b", "bb"),
            new Element("c", "cc"),
            new Element("k", "kk"),
            new Element("d", "dd")
        );

        $this->hashMap = new ArrayList();
        $this->hashMap->setArray([
            "c" => new Element("c"),
            "a" => new Element("a"),
            "h" => new Element("h")
        ]);

    }

    public function testCurrent()
    {
        $this->assertTrue($this->numeratedArrayList->current()->getAttr2() === "aa");
        $arrayList = new ArrayList();
        $this->assertFalse($arrayList->current());
    }

    public function testNext()
    {
        $this->assertTrue($this->numeratedArrayList->next()->getAttr2() === "bb");
    }

    public function testPrev()
    {
        $this->numeratedArrayList->next();
        $this->assertEquals("aa", $this->numeratedArrayList->prev()->getAttr2());
        $this->assertFalse($this->numeratedArrayList->prev());
    }

    public function testAppend()
    {
        $i = $this->numeratedArrayList->count();
        $this->numeratedArrayList->append(new Element("3", "33"));
        $j = $this->numeratedArrayList->count();
        $this->assertEquals($i + 1, $j);
        /** @var Element $eI */
        $eI = $this->numeratedArrayList->toArray()[$i];
        $this->assertEquals("3", $eI->getAttr1());
    }

    public function testSet()
    {
        $this->hashMap->set("c", new Element("ce"));
        /** @var Element $ce */
        $ce = $this->hashMap->toArray()['c'];
        $this->assertEquals("ce", $ce->getAttr1());
    }

    public function testCompareTo()
    {
        $arr = $this->hashMap->toArray();
        usort($arr, function (Comparable $a, Comparable $b) {
            return $a->compareTo($b);
        });
        /** @var Element $e0 */
        $e0 = $arr[0];
        /** @var Element $e1 */
        $e1 = $arr[1];
        /** @var Element $e2 */
        $e2 = $arr[2];
        $this->assertEquals("a", $e0->getAttr1());
        $this->assertEquals("c", $e1->getAttr1());
        $this->assertEquals("h", $e2->getAttr1());
    }

    public function testReplace()
    {
        $this->hashMap->replace($this->numeratedArrayList->toArray());
        $keys = array_keys($this->hashMap->toArray());
        foreach ($keys as $key) {
            $this->assertIsInt($key);
            $this->assertNotEmpty($this->hashMap->get($key));
        }
    }

    public function testClear()
    {
        $this->assertTrue($this->hashMap->count() > 0);
        $this->assertEquals(0, $this->hashMap->clear()->count());
    }

    public function testSetArray()
    {
        $this->hashMap->setArray($this->numeratedArrayList->toArray());
        $keys = array_keys($this->hashMap->toArray());
        foreach ($keys as $key) {
            $this->assertIsInt($key);
            $this->assertNotEmpty($this->hashMap->get($key));
        }
    }

    public function testShuffle()
    {
        $this->numeratedArrayList
            ->append(new Element("x", "xx"))
            ->append(new Element("y", "yy"))
            ->append(new Element("z", "zz"));

        Collections::sort($this->numeratedArrayList, new class extends Comparator{
            public function compare(Comparable $a, Comparable $b): int {
                return $a->compareTo($b);
            }
        });
        $lte = false;
        for ($i = 0; $i < $this->numeratedArrayList->count() - 1; ++$i) {
            /** @var Element $elemI */
            $elemI = $this->numeratedArrayList->get($i);
            /** @var Element $elemtI1 */
            $elemtI1 = $this->numeratedArrayList->get($i + 1);
            $lte = ($elemI->getAttr1() <= $elemtI1->getAttr1());
            if (!$lte) {
                break;
            }
        }
        //each element on position $i is smaller than or equal to the element on position $i+1
        $this->assertTrue($lte);

        $arr1 = $this->numeratedArrayList->toArray();
        $this->numeratedArrayList->shuffle();
        $arr2 = $this->numeratedArrayList->toArray(); //shuffled array

        $equal = false;
        // at least one element has another position as before
        for ($i = 0; $i < count($arr1); ++$i) {
            /** @var Element $elem1 */
            $elem1 = $arr1[$i];
            /** @var Element $elem2 */
            $elem2 = $arr2[$i];
            $equal = ($elem1->getAttr1() == $elem2->getAttr1());
            if (!$equal) {
                break;
            }
        }
        $this->assertFalse($equal);
    }


    public function testHasKey()
    {
        $this->assertTrue($this->numeratedArrayList->hasKey(0));
        $this->assertTrue($this->hashMap->hasKey("c"));
    }

    public function testHasValue()
    {
        $list = new ArrayList("a", "b", "c");

        $this->assertTrue($list->hasElement("a"));
    }

    /**
     * @throws Exception
     */
    public function testGetIterator()
    {
        $it = $this->numeratedArrayList->getIterator();

        foreach ($it as $key => $e) {
            $this->assertTrue(is_int($key));
            $this->assertInstanceOf("Seboettg\\Collection\\Test\\Element", $e);
        }
    }

    public function testRemove()
    {
        $list = new ArrayList([
            "a",
            "b",
            "c"
        ]);

        $list->append("d");
        $this->assertTrue($list->hasElement("d"));
        $list->remove(0);
        $this->assertFalse($list->hasElement("a"));
    }

    public function testOffsetGet()
    {
        $this->assertNotEmpty($this->numeratedArrayList[0]);
        $this->assertEmpty($this->numeratedArrayList[333]);
    }

    public function testOffsetSet()
    {
        $pos = $this->numeratedArrayList->count();
        $this->numeratedArrayList[$pos] = new Element($pos, $pos . $pos);
        $arr = $this->numeratedArrayList->toArray();
        /** @var Element $elem */
        $elem = $arr[$pos];
        $this->assertNotEmpty($elem);
        $this->assertEquals($pos, $elem->getAttr1());
    }

    public function testOffestExist()
    {
        $this->assertTrue(isset($this->hashMap['a']));
        $this->assertFalse(isset($this->numeratedArrayList[111]));
    }

    public function testOffsetUnset()
    {
        $list = (new ArrayList())->setArray(['a' => 'aa', 'b' => 'bb']);
        unset($list['a']);
        $this->assertFalse($list->hasKey('a'));
        $this->assertTrue($list->hasKey('b'));
    }

    public function testAdd()
    {
        $list = (new ArrayList())->setArray(['a' => 'aa', 'b' => 'bb', 'c' => 'cc']);
        $list->add('d', 'dd');
        $this->assertEquals('dd', $list->get('d'));
        $list->add('d', 'ddd');

        $dl = $list->get('d');
        $this->assertTrue(is_array($dl));
        $this->assertEquals('dd', $dl[0]);
        $this->assertEquals('ddd', $dl[1]);
    }

    public function testFirst()
    {
        $this->assertEquals("a", $this->numeratedArrayList->first()->getAttr1());
        $this->assertEquals("d", $this->numeratedArrayList->last()->getAttr1());
    }

    public function testFilter()
    {
        // filter elements that containing values with attr1 'c' or 'h'
        $arrayList = $this->hashMap->filter(function (Element $elem) {
            return $elem->getAttr1() === 'c' || $elem->getAttr1() === 'h';
        });

        $this->assertTrue($arrayList->hasKey('c'));
        $this->assertTrue($arrayList->hasKey('h'));
        $this->assertFalse($arrayList->hasKey('a'));
        $this->assertEquals('c', $arrayList->get('c')->getAttr1());
        $this->assertEquals('h', $arrayList->get('h')->getAttr1());
    }

    public function testFilterByKeys()
    {
        $arrayList = $this->numeratedArrayList->filterByKeys([0, 3]);
        $this->assertFalse($arrayList->hasKey(1));
        $this->assertEquals(2, $arrayList->count());
        $this->assertEquals("a", $arrayList->current()->getAttr1());
        $this->assertEquals("k", $arrayList->next()->getAttr1());
    }

    public function testMap()
    {
        $cubic = function($i) {
            return $i * $i * $i;
        };
        $list = new ArrayList(1, 2, 3, 4, 5);
        $cubicList = $list->map($cubic);
        $this->assertEquals(new ArrayList(1, 8, 27, 64, 125), $cubicList);

        $list = new ArrayList('a', 'b', 'c');
        $toUpper = $list->map(function($item) {return ucfirst($item);});
        $this->assertEquals(new ArrayList('A', 'B', 'C'), $toUpper);
    }

    public function testMapNotNull()
    {
        $list = new ArrayList(1, 2, 3, 4, 5);
        $this->assertEquals(new ArrayList(1, 3, 5), $list->mapNotNull(function($item) {
            return $item % 2 !== 0 ? $item : null;
        }));
    }

    public function testFlatten()
    {
        $list = new ArrayList([['a', 'b'], 'c']);
        $this->assertEquals(['a', 'b', 'c'], $list->flatten()->toArray());
        $list = new ArrayList(["term" => ['a', 'b'], 'c']);
        $this->assertEquals(['a', 'b', 'c'], $list->flatten()->toArray());
    }

    public function testMerge()
    {
        $array = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
        $abc = array_slice($array, 0, 3);
        $defgh = array_slice($array, 3);
        $first = new ArrayList(...$abc);
        $second = new ArrayList(...$defgh);
        $first->merge($second);
        $this->assertEquals(count($array), $first->count());
        $this->assertEquals($first->toArray(), $array);
    }

    public function testCollect()
    {
        $arrayList = new ArrayList('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h');
        /** @var Stack $stack */
        $stack = $arrayList
            ->collect(function(array $list) {
                $result = new Stack();
                foreach ($list as $item) {
                    $result->push($item);
                }
                return $result;
            });
        $this->assertEquals(8, $stack->count());
        $this->assertTrue('h' == $stack->pop());
    }

    /**
     * @throws ArrayList\NotConvertibleToStringException
     */
    public function testCollectToString()
    {
        $arrayList = new ArrayList('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h');
        $result = $arrayList->collectToString(", ");
        $this->assertEquals("a, b, c, d, e, f, g, h", $result);
    }

    /**
     * @throws ArrayList\NotConvertibleToStringException
     */
    public function testCollectToStringWithDoubleValues()
    {
        $arrayList = new ArrayList(1.0, 1.1, 1.2, 1.3);
        $result = $arrayList->collectToString("; ");
        $this->assertEquals("1.0; 1.1; 1.2; 1.3", $result);
    }

    /**
     * @throws ArrayList\NotConvertibleToStringException
     */
    public function testCollectToStringWithToStringObjects()
    {
        $arrayList = new ArrayList(new StringableObject(2), new StringableObject(3.1), new StringableObject(true));
        $result = $arrayList->collectToString("; ");
        $this->assertEquals("2; 3.1; true", $result);
    }

    public function testShouldThrowExceptionWhenCollectToStringIsCalledOnListWithNotStringableObjects()
    {
        $arrayList = new ArrayList(new Element("0", "a"), new Element("1", "b"), new Element("2", "c"));
        $this->expectException(ArrayList\NotConvertibleToStringException::class);
        $arrayList->collectToString("; ");
    }
}

class Element implements Comparable
{

    /**
     * @var string
     */
    private $attr1;

    /**
     * @var string
     */
    private $attr2;

    public function __construct(string $attr1, string $attr2 = "")
    {
        $this->attr1 = $attr1;
        $this->attr2 = $attr2;
    }

    /**
     * @return mixed
     */
    public function getAttr1(): string
    {
        return $this->attr1;
    }

    /**
     * @param string $attr1
     */
    public function setAttr1(string $attr1)
    {
        $this->attr1 = $attr1;
    }

    /**
     * @return string
     */
    public function getAttr2(): string
    {
        return $this->attr2;
    }

    /**
     * @param string $attr2
     */
    public function setAttr2(string $attr2)
    {
        $this->attr2 = $attr2;
    }

    /**
     * Compares this object with the specified object for order. Returns a negative integer, zero, or a positive
     * integer as this object is less than, equal to, or greater than the specified object.
     *
     * The implementor must ensure sgn(x.compareTo(y)) == -sgn(y.compareTo(x)) for all x and y.
     *
     * @param Comparable $b
     * @return int
     */
    public function compareTo(Comparable $b): int
    {
        /** @var Element $b */
        return strcmp($this->attr1, $b->getAttr1());
    }
}

class StringableObject {
    private $value;
    public function __construct($value)
    {
        $this->value = $value;
    }
    public function setValue($value) {
        $this->value = $value;
    }
    public function getValue($value) {
        return $value;
    }
    public function toString(): string {
        return strval($this->value);
    }
    public function __toString(): string {
        return $this->toString();
    }
}

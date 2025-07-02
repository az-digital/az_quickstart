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

namespace Seboettg\Collection;

use Seboettg\Collection\ArrayList\ArrayListInterface;
use Seboettg\Collection\ArrayList\ArrayListTrait;

/**
 * ArrayList is a useful wrapper class for an array, similar to Java's ArrayList
 * @package Seboettg\Collection
 *
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
class ArrayList implements ArrayListInterface
{
    use ArrayListTrait;

    /**
     * @var array
     */
    protected $array;

    /**
     * ArrayList constructor.
     * @param array $data
     */
    public function __construct(...$data)
    {
        $this->array = $data;
    }
}

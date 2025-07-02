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

namespace Seboettg\Collection;

use Seboettg\Collection\Queue\QueueInterface;
use Seboettg\Collection\Queue\QueueTrait;

/**
 * Class Queue
 * @package Seboettg\Collection
 * @author Sebastian Böttger <boettger@hebis.uni-frankfurt.de>
 */
class Queue implements QueueInterface
{
    use QueueTrait;

    /**
     * @var array
     */
    protected $array;

    /**
     * Queue constructor.
     */
    public function __construct()
    {
        $this->array = [];
    }
}

<?php
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2017 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc;

use MyCLabs\Enum\Enum;

/**
 * RenderingState defines the mode in which mode the processor currently works.
 * There are three modes:
 * - Rendering
 * - Sorting
 * - Substitution
 *
 * @package Seboettg\CiteProc
 * @author Sebastian Böttger <seboettg@gmail.com>
 * @method static RENDERING()
 * @method static SORTING()
 * @method static SUBSTITUTION()
 */
class RenderingState extends Enum
{
    const RENDERING = "rendering";

    const SORTING = "sorting";

    const SUBSTITUTION = "substitution";
}

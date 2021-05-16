<?php

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * Tree annotation for Tree behavioral extension
 *
 * @Annotation
 * @Target("CLASS")
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class Tree extends Annotation
{
    /** @var string */
    public $type = 'nested';

    /** @var string */
    public $activateLocking = false;

    /** @var int */
    public $lockingTimeout = 3;

    /** @var string */
    public $identifierMethod;
}

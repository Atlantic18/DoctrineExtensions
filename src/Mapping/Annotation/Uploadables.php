<?php

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Gedmo\Uploadable\Mapping\Validator;

/**
 * Uploadables annotation for multiple Uploadable definitions
 *
 * @Annotation
 * @Target("CLASS")
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class Uploadables extends Annotation
{
    /** @var array<Uploadable> @Required */
    public $configurations = array();
}

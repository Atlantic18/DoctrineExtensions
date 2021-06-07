<?php

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * UploadableFileName Annotation for Uploadable behavioral extension
 *
 * @Annotation
 * @Target("PROPERTY")
 *
 * @author tiger-seo <tiger.seo@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class UploadableFileName extends Annotation
{
    public $identifier = '_default';
}

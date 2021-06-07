<?php

namespace Gedmo\Uploadable\FilenameGenerator;

/**
 * FilenameGeneratorSha1
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class FilenameGeneratorSha1 implements FilenameGeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public static function generate($filename, $extension, $object = null, $identifier = null)
    {
        return sha1(uniqid(((null == $identifier)?'':$identifier).$filename.$extension, true)).$extension;
    }
}

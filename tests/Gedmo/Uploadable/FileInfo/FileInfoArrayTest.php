<?php

namespace Gedmo\Uploadable\FileInfo;

/**
 * These are tests for the FileInfoArray class of the Uploadable behavior
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class FileInfoArrayTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructorIfKeysAreNotValidOrSomeAreMissingThrowException()
    {
        $this->expectException('RuntimeException');
        $fileInfo = new FileInfoArray([]);
    }
}

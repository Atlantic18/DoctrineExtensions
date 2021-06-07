<?php

namespace Uploadable\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @Gedmo\Uploadable(
 *     pathMethod="getPath",
 *     filenameGenerator="Gedmo\Uploadable\FakeFilenameGenerator",
 *     appendNumber=true
 * )
 */
class FileWithCustomFilenameGenerator
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(name="path", type="string", nullable=true)
     * @Gedmo\UploadableFilePath
     */
    private $filePath;

    public function getId()
    {
        return $this->id;
    }

    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;
    }

    public function getFilePath()
    {
        return $this->filePath;
    }

    public function getPath()
    {
        return __DIR__.'/../../../../temp/uploadable';
    }
}

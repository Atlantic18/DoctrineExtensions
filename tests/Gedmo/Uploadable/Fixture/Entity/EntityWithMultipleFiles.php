<?php

namespace Uploadable\Fixture\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @Gedmo\Uploadables(configurations={
 *   @Gedmo\Uploadable(identifier="first", allowOverwrite=true, pathMethod="getPath"),
 *   @Gedmo\Uploadable(identifier="second", allowOverwrite=true, pathMethod="getPath", callback="callbackMethod2")
 * })
 *
 */
class EntityWithMultipleFiles
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(name="title", type="string", nullable=true)
     */
    private $title;

    /**
     * @ORM\Column(name="path1", type="string")
     * @Gedmo\UploadableFilePath(identifier="first")
     */
    private $filePath1;

    /**
     * @ORM\Column(name="path2", type="string")
     * @Gedmo\UploadableFilePath(identifier="second")
     */
    private $filePath2;

    /**
     * @ORM\Column(name="name1", type="string")
     * @Gedmo\UploadableFileName(identifier="first")
     */
    private $fileName1;

    /**
     * @ORM\Column(name="name2", type="string")
     * @Gedmo\UploadableFileName(identifier="second")
     */
    private $fileName2;

    public $callback1WasCalled = false;

    public $callback2WasCalled = false;

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setFilePath1($filePath1)
    {
        $this->filePath1 = $filePath1;
    }

    public function getFilePath1()
    {
        return $this->filePath1;
    }

    public function setFilePath2($filePath1)
    {
        $this->filePath2 = $filePath1;
    }

    public function getFilePath2()
    {
        return $this->filePath2;
    }

    /**
     * @return mixed
     */
    public function getFileName1()
    {
        return $this->fileName1;
    }

    /**
     * @param mixed $fileName1
     */
    public function setFileName1($fileName1)
    {
        $this->fileName1 = $fileName1;
    }

    /**
     * @return mixed
     */
    public function getFileName2()
    {
        return $this->fileName2;
    }

    /**
     * @param mixed $fileName2
     */
    public function setFileName2($fileName2)
    {
        $this->fileName2 = $fileName2;
    }

    /**
     * @return boolean
     */
    public function isCallback1WasCalled()
    {
        return $this->callback1WasCalled;
    }

    /**
     * @param boolean $callback1WasCalled
     */
    public function setCallback1WasCalled($callback1WasCalled)
    {
        $this->callback1WasCalled = $callback1WasCalled;
    }

    /**
     * @return boolean
     */
    public function isCallback2WasCalled()
    {
        return $this->callback2WasCalled;
    }

    /**
     * @param boolean $callback2WasCalled
     */
    public function setCallback2WasCalled($callback2WasCalled)
    {
        $this->callback2WasCalled = $callback2WasCalled;
    }

    public function callbackMethod1()
    {
        $this->callback1WasCalled = true;
    }

    public function callbackMethod2()
    {
        $this->callback2WasCalled = true;
    }

    public function getPath()
    {
        return __DIR__.'/../../../../temp/uploadable';
    }
}

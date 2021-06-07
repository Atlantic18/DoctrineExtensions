<?php

namespace Gedmo\Uploadable\Event;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Uploadable\FileInfo\FileInfoInterface;
use Gedmo\Uploadable\UploadableListener;

/**
 * Abstract Base Event to be extended by Uploadable events
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class UploadableBaseEventArgs extends EventArgs
{
    /**
     * The instance of the Uploadable listener that fired this event
     *
     * @var \Gedmo\Uploadable\UploadableListener
     */
    private $uploadableListener;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * The Uploadable entity
     *
     * @var object
     */
    private $entity;

    /**
     * The configuration of the Uploadable extension for this entity class
     *
     * @var array
     */
    private $extensionConfiguration;

    /**
     * @var \Gedmo\Uploadable\FileInfo\FileInfoInterface
     */
    private $fileInfo;

    /**
     * @var string - Is the file being created, updated or removed?
     *             This value can be: CREATE, UPDATE or DELETE
     */
    private $action;

    /**
     * @var string $identifier - The identifier this upload belongs to.
     */
    private $identifier;

    /**
     * @param object $entity
     * @param string $action
     */
    public function __construct(UploadableListener $listener, EntityManagerInterface $em, array $config, FileInfoInterface $fileInfo, $entity, $action, $identifier = '_default')
    {
        $this->uploadableListener = $listener;
        $this->em = $em;
        $this->config = $config;
        $this->fileInfo = $fileInfo;
        $this->entity = $entity;
        $this->action = $action;
        $this->identifier = $identifier;
    }

    /**
     * Retrieve the associated listener
     *
     * @return \Gedmo\Uploadable\UploadableListener
     */
    public function getListener()
    {
        return $this->uploadableListener;
    }

    /**
     * Retrieve associated EntityManager
     *
     * @return \Doctrine\ORM\EntityManagerInterface
     */
    public function getEntityManager()
    {
        return $this->em;
    }

    /**
     * Retrieve associated Entity
     *
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Retrieve associated Uploadable extension configuration
     *
     * @return array
     */
    public function getExtensionConfiguration()
    {
        return $this->extensionConfiguration;
    }

    /**
     * Retrieve the FileInfo associated with this entity.
     *
     * @return \Gedmo\Uploadable\FileInfo\FileInfoInterface
     */
    public function getFileInfo()
    {
        return $this->fileInfo;
    }

    /**
     * Retrieve the action being performed to the entity: CREATE, UPDATE or DELETE
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Retrieve the identifier this upload belongs to
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

}

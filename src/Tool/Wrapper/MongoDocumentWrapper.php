<?php

namespace Gedmo\Tool\Wrapper;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Proxy\Proxy;

/**
 * Wraps document or proxy for more convenient
 * manipulation
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MongoDocumentWrapper extends AbstractWrapper
{
    /**
     * Document identifier
     *
     * @var mixed
     */
    private $identifier;

    /**
     * True if document or proxy is loaded
     *
     * @var bool
     */
    private $initialized = false;

    /**
     * Wrap document
     *
     * @param object $document
     */
    public function __construct($document, DocumentManager $dm)
    {
        $this->om = $dm;
        $this->object = $document;
        $this->meta = $dm->getClassMetadata(get_class($this->object));
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyValue($property)
    {
        $this->initialize();

        return $this->meta->getReflectionProperty($property)->getValue($this->object);
    }

    /**
     * {@inheritdoc}
     */
    public function getRootObjectName()
    {
        return $this->meta->rootDocumentName;
    }

    /**
     * {@inheritdoc}
     */
    public function setPropertyValue($property, $value)
    {
        $this->initialize();
        $this->meta->getReflectionProperty($property)->setValue($this->object, $value);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasValidIdentifier()
    {
        return (bool) $this->getIdentifier();
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier($single = true, $flatten = false)
    {
        if (!$this->identifier) {
            if ($this->object instanceof Proxy) {
                $uow = $this->om->getUnitOfWork();
                if ($uow->isInIdentityMap($this->object)) {
                    $this->identifier = (string) $uow->getDocumentIdentifier($this->object);
                } else {
                    $this->initialize();
                }
            }
            if (!$this->identifier) {
                $this->identifier = (string) $this->getPropertyValue($this->meta->identifier);
            }
        }

        return $this->identifier;
    }

    /**
     * Initialize the document if it is proxy
     * required when is detached or not initialized
     */
    protected function initialize()
    {
        if (!$this->initialized) {
            if ($this->object instanceof Proxy) {
                $uow = $this->om->getUnitOfWork();
                if (!$this->object->__isInitialized__) {
                    $persister = $uow->getDocumentPersister($this->meta->name);
                    $identifier = null;
                    if ($uow->isInIdentityMap($this->object)) {
                        $identifier = $this->getIdentifier();
                    } else {
                        // this may not happen but in case
                        $reflProperty = new \ReflectionProperty($this->object, 'identifier');
                        $reflProperty->setAccessible(true);
                        $identifier = $reflProperty->getValue($this->object);
                    }
                    $this->object->__isInitialized__ = true;
                    $persister->load($identifier, $this->object);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isEmbeddedAssociation($field)
    {
        return $this->getMetadata()->isSingleValuedEmbed($field);
    }
}

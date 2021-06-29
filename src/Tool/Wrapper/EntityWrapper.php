<?php

namespace Gedmo\Tool\Wrapper;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Proxy\Proxy;

/**
 * Wraps entity or proxy for more convenient
 * manipulation
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class EntityWrapper extends AbstractWrapper
{
    /**
     * Entity identifier
     *
     * @var array
     */
    private $identifier;

    /**
     * True if entity or proxy is loaded
     *
     * @var bool
     */
    private $initialized = false;

    /**
     * Wrap entity
     *
     * @param object $entity
     */
    public function __construct($entity, EntityManagerInterface $em)
    {
        $this->om = $em;
        $this->object = $entity;
        $this->meta = $em->getClassMetadata(get_class($this->object));
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
        return null !== $this->getIdentifier();
    }

    /**
     * {@inheritdoc}
     */
    public function getRootObjectName()
    {
        return $this->meta->rootEntityName;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier($single = true, $flatten = false)
    {
        if (null === $this->identifier) {
            $uow = $this->om->getUnitOfWork();
            $this->identifier = $uow->isInIdentityMap($this->object)
                ? $uow->getEntityIdentifier($this->object)
                : $this->meta->getIdentifierValues($this->object);
            if ((is_array($this->identifier) && empty($this->identifier))) {
                $this->identifier = null;
            }
        }
        if (is_array($this->identifier)) {
            if ($single) {
                return reset($this->identifier);
            }
            if ($flatten) {
                $id = $this->identifier;
                foreach ($id as $i => $value) {
                    if (is_object($value) && $this->om->getMetadataFactory()->hasMetadataFor(ClassUtils::getClass($value))) {
                        $id[$i] = (new EntityWrapper($value, $this->om))->getIdentifier(false, true);
                    }
                }

                return implode(' ', $id);
            }
        }

        return $this->identifier;
    }

    /**
     * Initialize the entity if it is proxy
     * required when is detached or not initialized
     */
    protected function initialize()
    {
        if (!$this->initialized) {
            if ($this->object instanceof Proxy) {
                if (!$this->object->__isInitialized__) {
                    $this->object->__load();
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isEmbeddedAssociation($field)
    {
        return false;
    }
}

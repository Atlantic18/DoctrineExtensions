<?php

namespace Gedmo\Mapping\Event\Adapter;

use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Mapping\Event\AdapterInterface;
use Gedmo\Exception\RuntimeException;
use Doctrine\Common\EventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;

/**
 * Doctrine event adapter for ORM specific
 * event arguments
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ORM implements AdapterInterface
{
    /**
     * @var \Doctrine\Common\EventArgs
     */
    private $args;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * {@inheritdoc}
     */
    public function setEventArgs(EventArgs $args)
    {
        $this->args = $args;
    }

    /**
     * {@inheritdoc}
     */
    public function getDomainObjectName()
    {
        return 'Entity';
    }

    /**
     * {@inheritdoc}
     */
    public function getManagerName()
    {
        return 'ORM';
    }

    /**
     * {@inheritdoc}
     */
    public function getRootObjectClass($meta)
    {
        return $meta->rootEntityName;
    }

    /**
     * {@inheritdoc}
     */
    public function __call($method, $args)
    {
        if (is_null($this->args)) {
            throw new RuntimeException("Event args must be set before calling its methods");
        }
        $method = str_replace('Object', $this->getDomainObjectName(), $method);

        return call_user_func_array(array($this->args, $method), $args);
    }

    /**
     * Set the entity manager
     *
     * @param \Doctrine\ORM\EntityManagerInterface $em
     */
    public function setEntityManager(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectManager()
    {
        if (!is_null($this->em)) {
            return $this->em;
        }

        return $this->__call('getEntityManager', array());
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectState($uow, $object)
    {
        return $uow->getEntityState($object);
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectChangeSet($uow, $object)
    {
        return $uow->getEntityChangeSet($object);
    }

    /**
     * {@inheritdoc}
     */
    public function getSingleIdentifierFieldName($meta)
    {
        return $meta->getSingleIdentifierFieldName();
    }

    /**
     * {@inheritdoc}
     */
    public function recomputeSingleObjectChangeSet($uow, $meta, $object)
    {
        $uow->recomputeSingleEntityChangeSet($meta, $object);
    }

    /**
     * {@inheritdoc}
     */
    public function getScheduledObjectUpdates($uow)
    {
        return $uow->getScheduledEntityUpdates();
    }

    /**
     * {@inheritdoc}
     */
    public function getScheduledObjectInsertions($uow)
    {
        return $uow->getScheduledEntityInsertions();
    }

    /**
     * {@inheritdoc}
     */
    public function getScheduledObjectDeletions($uow)
    {
        return $uow->getScheduledEntityDeletions();
    }

    /**
     * {@inheritdoc}
     */
    public function setOriginalObjectProperty($uow, $oid, $property, $value)
    {
        $uow->setOriginalEntityProperty($oid, $property, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function clearObjectChangeSet($uow, $oid)
    {
        $uow->clearEntityChangeSet($oid);
    }

    /**
     * Creates a ORM specific LifecycleEventArgs.
     *
     * @param object                               $document
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     *
     * @return \Doctrine\ORM\Event\LifecycleEventArgs
     */
    public function createLifecycleEventArgsInstance($document, $entityManager)
    {
        return new LifecycleEventArgs($document, $entityManager);
    }
}

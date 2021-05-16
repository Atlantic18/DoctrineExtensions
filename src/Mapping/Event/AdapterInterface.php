<?php

namespace Gedmo\Mapping\Event;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\UnitOfWork;

/**
 * Doctrine event adapter interface is used
 * to retrieve common functionality for Doctrine
 * events
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface AdapterInterface
{
    /**
     * Set the eventargs
     */
    public function setEventArgs(EventArgs $args);

    /**
     * Call specific method on event args
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public function __call($method, $args);

    /**
     * Get the name of domain object
     *
     * @return string
     */
    public function getDomainObjectName();

    /**
     * Get the name of used manager for this
     * event adapter
     *
     * @return string
     */
    public function getManagerName();

    /**
     * Get the root object class, handles inheritance
     *
     * @param \Doctrine\Persistence\Mapping\ClassMetadata $meta
     *
     * @return string
     */
    public function getRootObjectClass($meta);

    /**
     * Get used object manager
     *
     * @return \Doctrine\Persistence\ObjectManager
     */
    public function getObjectManager();

    /**
     * Get object state
     *
     * @param UnitOfWork $uow
     * @param object     $object
     *
     * @return int the document state
     */
    public function getObjectState($uow, $object);

    /**
     * Get the object changeset from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @param object     $object
     *
     * @return array
     */
    public function getObjectChangeSet($uow, $object);

    /**
     * Get the single identifier field name
     *
     * @param \Doctrine\Persistence\Mapping\ClassMetadata $meta
     *
     * @return string
     */
    public function getSingleIdentifierFieldName($meta);

    /**
     * Recompute the single object changeset from a UnitOfWork
     *
     * @param UnitOfWork                                  $uow
     * @param \Doctrine\Persistence\Mapping\ClassMetadata $meta
     * @param object                                      $object
     *
     * @return void
     */
    public function recomputeSingleObjectChangeSet($uow, $meta, $object);

    /**
     * Get the scheduled object updates from a UnitOfWork
     *
     * @param UnitOfWork $uow
     *
     * @return array
     */
    public function getScheduledObjectUpdates($uow);

    /**
     * Get the scheduled object insertions from a UnitOfWork
     *
     * @param UnitOfWork $uow
     *
     * @return array
     */
    public function getScheduledObjectInsertions($uow);

    /**
     * Get the scheduled object deletions from a UnitOfWork
     *
     * @param UnitOfWork $uow
     *
     * @return array
     */
    public function getScheduledObjectDeletions($uow);

    /**
     * Sets a property value of the original data array of an object
     *
     * @param UnitOfWork $uow
     * @param string     $oid
     * @param string     $property
     * @param mixed      $value
     *
     * @return void
     */
    public function setOriginalObjectProperty($uow, $oid, $property, $value);

    /**
     * Clears the property changeset of the object with the given OID.
     *
     * @param UnitOfWork $uow
     * @param string     $oid the object's OID
     */
    public function clearObjectChangeSet($uow, $oid);
}

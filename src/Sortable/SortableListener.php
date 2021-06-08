<?php

namespace Gedmo\Sortable;

use Doctrine\Common\Comparable;
use Doctrine\Common\EventArgs;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Proxy;
use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\Sortable\Mapping\Event\SortableAdapter;

/**
 * The SortableListener maintains a sort index on your entities
 * to enable arbitrary sorting.
 *
 * This behavior can impact the performance of your application
 * since it does some additional calculations on persisted objects.
 *
 * @author Lukas Botsch <lukas.botsch@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SortableListener extends MappedEventSubscriber
{
    private $relocations = [];
    private $persistenceNeeded = false;
    private $maxPositions = [];

    /**
     * Specifies the list of events to listen
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            'onFlush',
            'loadClassMetadata',
            'prePersist',
            'postPersist',
            'preUpdate',
            'postRemove',
            'postFlush',
        ];
    }

    /**
     * Maps additional metadata
     */
    public function loadClassMetadata(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $this->loadMetadataForObjectClass($ea->getObjectManager(), $args->getClassMetadata());
    }

    /**
     * Collect position updates on objects being updated during flush
     * if they require changing.
     *
     * Persisting of positions is done later during prePersist, preUpdate and postRemove
     * events, otherwise the queries won't be executed within the transaction.
     *
     * The synchronization of the objects in memory is done in postFlush. This
     * ensures that the positions have been successfully persisted to database.
     */
    public function onFlush(EventArgs $args)
    {
        $this->persistenceNeeded = true;

        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $uow = $om->getUnitOfWork();

        // process all objects being deleted
        foreach ($ea->getScheduledObjectDeletions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($config = $this->getConfiguration($om, $meta->name)) {
                $this->processDeletion($ea, $config, $meta, $object);
            }
        }

        // process all objects being updated
        foreach ($ea->getScheduledObjectUpdates($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($config = $this->getConfiguration($om, $meta->name)) {
                $this->processUpdate($ea, $config, $meta, $object);
            }
        }

        // process all objects being inserted
        foreach ($ea->getScheduledObjectInsertions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($config = $this->getConfiguration($om, $meta->name)) {
                $this->processInsert($ea, $config, $meta, $object);
            }
        }
    }

    /**
     * Update maxPositions as needed
     */
    public function prePersist(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));

        if ($config = $this->getConfiguration($om, $meta->name)) {
            // Get groups
            $groups = $this->getGroups($meta, $config, $object);

            // Get hash
            $hash = $this->getHash($groups, $config);

            // Get max position
            if (!isset($this->maxPositions[$hash])) {
                $this->maxPositions[$hash] = $this->getMaxPosition($ea, $meta, $config, $object);
            }
        }
    }

    public function postPersist(EventArgs $args)
    {
        // persist position updates here, so that the update queries
        // are executed within transaction
        $this->persistRelocations($this->getEventAdapter($args));
    }

    public function preUpdate(EventArgs $args)
    {
        // persist position updates here, so that the update queries
        // are executed within transaction
        $this->persistRelocations($this->getEventAdapter($args));
    }

    public function postRemove(EventArgs $args)
    {
        // persist position updates here, so that the update queries
        // are executed within transaction
        $this->persistRelocations($this->getEventAdapter($args));
    }

    /**
     * Computes node positions and updates the sort field in memory and in the db
     *
     * @param ClassMetadata $meta
     * @param object        $object
     */
    protected function processInsert(SortableAdapter $ea, array $config, $meta, $object)
    {
        $em = $ea->getObjectManager();

        $old = $meta->getReflectionProperty($config['position'])->getValue($object);
        $newPosition = $meta->getReflectionProperty($config['position'])->getValue($object);

        if (is_null($newPosition)) {
            $newPosition = -1;
        }

        // Get groups
        $groups = $this->getGroups($meta, $config, $object);

        // Get hash
        $hash = $this->getHash($groups, $config);

        // Get max position
        if (!isset($this->maxPositions[$hash])) {
            $this->maxPositions[$hash] = $this->getMaxPosition($ea, $meta, $config, $object);
        }

        // Compute position if it is negative
        if ($newPosition < 0) {
            $newPosition += $this->maxPositions[$hash] + 2; // position == -1 => append at end of list
            if ($newPosition < 0) {
                $newPosition = 0;
            }
        }

        // Set position to max position if it is too big
        $newPosition = min([$this->maxPositions[$hash] + 1, $newPosition]);

        // Compute relocations
        // New inserted entities should not be relocated by position update, so we exclude it.
        // Otherwise they could be relocated unintentionally.
        $relocation = [$hash, $config['useObjectClass'], $groups, $newPosition, -1, +1, [$object]];

        // Apply existing relocations
        $applyDelta = 0;
        if (isset($this->relocations[$hash])) {
            foreach ($this->relocations[$hash]['deltas'] as $delta) {
                if ($delta['start'] <= $newPosition
                        && ($delta['stop'] > $newPosition || $delta['stop'] < 0)) {
                    $applyDelta += $delta['delta'];
                }
            }
        }
        $newPosition += $applyDelta;

        // Add relocations
        call_user_func_array([$this, 'addRelocation'], $relocation);

        // Set new position
        if ($old < 0 || is_null($old)) {
            $this->setFieldValue($ea, $object, $config['position'], $old, $newPosition);
        }
    }

    /**
     * Computes node positions and updates the sort field in memory and in the db
     *
     * @param ClassMetadata $meta
     * @param object        $object
     */
    protected function processUpdate(SortableAdapter $ea, array $config, $meta, $object)
    {
        $em = $ea->getObjectManager();
        $uow = $em->getUnitOfWork();

        $changed = false;
        $groupHasChanged = false;
        $changeSet = $ea->getObjectChangeSet($uow, $object);

        // Get groups
        $groups = $this->getGroups($meta, $config, $object);

        // handle old groups
        $oldGroups = $groups;
        foreach (array_keys($groups) as $group) {
            if (array_key_exists($group, $changeSet)) {
                $changed = true;
                $oldGroups[$group] = $changeSet[$group][0];
            }
        }

        if ($changed) {
            $oldHash = $this->getHash($oldGroups, $config);
            $this->maxPositions[$oldHash] = $this->getMaxPosition($ea, $meta, $config, $object, $oldGroups);
            if (array_key_exists($config['position'], $changeSet)) {
                $oldPosition = $changeSet[$config['position']][0];
            } else {
                $oldPosition = $meta->getReflectionProperty($config['position'])->getValue($object);
            }
            $this->addRelocation($oldHash, $config['useObjectClass'], $oldGroups, $oldPosition + 1, $this->maxPositions[$oldHash] + 1, -1);
            $groupHasChanged = true;
        }

        // Get hash
        $hash = $this->getHash($groups, $config);

        // Get max position
        if (!isset($this->maxPositions[$hash])) {
            $this->maxPositions[$hash] = $this->getMaxPosition($ea, $meta, $config, $object);
        }

        if (array_key_exists($config['position'], $changeSet)) {
            if ($changed && -1 === $this->maxPositions[$hash]) {
                // position has changed
                // the group of element has changed
                // and the target group has no children before
                $oldPosition = -1;
                $newPosition = -1;
            } else {
                // position was manually updated
                $oldPosition = $changeSet[$config['position']][0];
                $newPosition = $changeSet[$config['position']][1];
                $changed = $changed || $oldPosition != $newPosition;
            }
        } elseif ($changed) {
            $newPosition = $oldPosition;
        }

        if ($groupHasChanged) {
            $oldPosition = -1;
        }
        if (!$changed) {
            return;
        }

        // Compute position if it is negative
        if ($newPosition < 0) {
            if (-1 === $oldPosition) {
                $newPosition += $this->maxPositions[$hash] + 2; // position == -1 => append at end of list
            } else {
                $newPosition += $this->maxPositions[$hash] + 1; // position == -1 => append at end of list
            }

            if ($newPosition < 0) {
                $newPosition = 0;
            }
        } elseif ($newPosition > $this->maxPositions[$hash]) {
            $newPosition = $groupHasChanged ? $this->maxPositions[$hash] + 1 : $this->maxPositions[$hash];
        } else {
            $newPosition = min([$this->maxPositions[$hash], $newPosition]);
        }

        // Compute relocations
        /*
        CASE 1: shift backwards
        |----0----|----1----|----2----|----3----|----4----|
        |--node1--|--node2--|--node3--|--node4--|--node5--|
        Update node4: setPosition(1)
        --> Update position + 1 where position in [1,3)
        |--node1--|--node4--|--node2--|--node3--|--node5--|
        CASE 2: shift forward
        |----0----|----1----|----2----|----3----|----4----|
        |--node1--|--node2--|--node3--|--node4--|--node5--|
        Update node2: setPosition(3)
        --> Update position - 1 where position in (1,3]
        |--node1--|--node3--|--node4--|--node2--|--node5--|
        */
        $relocation = null;
        if (-1 === $oldPosition) {
            // special case when group changes
            $relocation = [$hash, $config['useObjectClass'], $groups, $newPosition, -1, +1];
        } elseif ($newPosition < $oldPosition) {
            $relocation = [$hash, $config['useObjectClass'], $groups, $newPosition, $oldPosition, +1];
        } elseif ($newPosition > $oldPosition) {
            $relocation = [$hash, $config['useObjectClass'], $groups, $oldPosition + 1, $newPosition + 1, -1];
        }

        // Apply existing relocations
        $applyDelta = 0;
        if (isset($this->relocations[$hash])) {
            foreach ($this->relocations[$hash]['deltas'] as $delta) {
                if ($delta['start'] <= $newPosition
                        && ($delta['stop'] > $newPosition || $delta['stop'] < 0)) {
                    $applyDelta += $delta['delta'];
                }
            }
        }
        $newPosition += $applyDelta;

        if ($relocation) {
            // Add relocation
            call_user_func_array([$this, 'addRelocation'], $relocation);
        }

        // Set new position
        $this->setFieldValue($ea, $object, $config['position'], $oldPosition, $newPosition);
    }

    /**
     * Computes node positions and updates the sort field in memory and in the db
     *
     * @param ClassMetadata $meta
     * @param object        $object
     */
    protected function processDeletion(SortableAdapter $ea, array $config, $meta, $object)
    {
        $position = $meta->getReflectionProperty($config['position'])->getValue($object);

        // Get groups
        $groups = $this->getGroups($meta, $config, $object);

        // Get hash
        $hash = $this->getHash($groups, $config);

        // Get max position
        if (!isset($this->maxPositions[$hash])) {
            $this->maxPositions[$hash] = $this->getMaxPosition($ea, $meta, $config, $object);
        }

        // Add relocation
        $this->addRelocation($hash, $config['useObjectClass'], $groups, $position, -1, -1);
    }

    /**
     * Persists relocations to database.
     */
    protected function persistRelocations(SortableAdapter $ea)
    {
        if (!$this->persistenceNeeded) {
            return;
        }

        $em = $ea->getObjectManager();
        foreach ($this->relocations as $hash => $relocation) {
            $config = $this->getConfiguration($em, $relocation['name']);
            foreach ($relocation['deltas'] as $delta) {
                if ($delta['start'] > $this->maxPositions[$hash] || 0 == $delta['delta']) {
                    continue;
                }
                $ea->updatePositions($relocation, $delta, $config);
            }
        }

        $this->persistenceNeeded = false;
    }

    /**
     * Sync objects in memory
     */
    public function postFlush(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $em = $ea->getObjectManager();

        $updatedObjects = [];

        foreach ($this->relocations as $hash => $relocation) {
            $config = $this->getConfiguration($em, $relocation['name']);
            foreach ($relocation['deltas'] as $delta) {
                if ($delta['start'] > $this->maxPositions[$hash] || 0 == $delta['delta']) {
                    continue;
                }

                $meta = $em->getClassMetadata($relocation['name']);

                // now walk through the unit of work in memory objects and sync those
                $uow = $em->getUnitOfWork();
                foreach ($uow->getIdentityMap() as $className => $objects) {
                    // for inheritance mapped classes, only root is always in the identity map
                    if ($className !== $ea->getRootObjectClass($meta) || !$this->getConfiguration($em, $className)) {
                        continue;
                    }
                    foreach ($objects as $object) {
                        if ($object instanceof Proxy && !$object->__isInitialized__) {
                            continue;
                        }

                        $changeSet = $ea->getObjectChangeSet($uow, $object);

                        // if the entity's position is already changed, stop now
                        if (array_key_exists($config['position'], $changeSet)) {
                            continue;
                        }

                        // if the entity's group has changed, we stop now
                        $groups = $this->getGroups($meta, $config, $object);
                        foreach (array_keys($groups) as $group) {
                            if (array_key_exists($group, $changeSet)) {
                                continue 2;
                            }
                        }

                        $oid = spl_object_hash($object);
                        $pos = $meta->getReflectionProperty($config['position'])->getValue($object);
                        $matches = $pos >= $delta['start'];
                        $matches = $matches && ($delta['stop'] <= 0 || $pos < $delta['stop']);
                        $value = reset($relocation['groups']);
                        while ($matches && ($group = key($relocation['groups']))) {
                            $gr = $meta->getReflectionProperty($group)->getValue($object);
                            if (null === $value) {
                                $matches = null === $gr;
                            } elseif (is_object($gr) && is_object($value) && $gr !== $value) {
                                // Special case for equal objects but different instances.
                                // If the object implements Comparable interface we can use its compareTo method
                                // Otherwise we fallback to normal object comparison
                                $matches = $gr instanceof Comparable ? $gr->compareTo($value) : $gr == $value;
                            } else {
                                $matches = $gr === $value;
                            }
                            $value = next($relocation['groups']);
                        }
                        if ($matches) {
                            // We cannot use `$this->setFieldValue()` here, because it will create a change set, that will
                            // prevent from other relocations being executed on this object.
                            // We just update the object value and will create the change set later.
                            if (!isset($updatedObjects[$oid])) {
                                $updatedObjects[$oid] = [
                                    'object' => $object,
                                    'field' => $config['position'],
                                    'oldValue' => $pos,
                                ];
                            }
                            $updatedObjects[$oid]['newValue'] = $pos + $delta['delta'];

                            $meta->getReflectionProperty($config['position'])->setValue($object, $updatedObjects[$oid]['newValue']);
                        }
                    }
                }
            }

            foreach ($updatedObjects as $updateData) {
                $this->setFieldValue($ea, $updateData['object'], $updateData['field'], $updateData['oldValue'], $updateData['newValue']);
            }

            // Clear relocations
            unset($this->relocations[$hash]);
            unset($this->maxPositions[$hash]); // unset only if relocations has been processed
        }
    }

    protected function getHash($groups, array $config)
    {
        $data = $config['useObjectClass'];
        foreach ($groups as $group => $val) {
            if ($val instanceof \DateTime) {
                $val = $val->format('c');
            } elseif (is_object($val)) {
                $val = spl_object_hash($val);
            }
            $data .= $group.$val;
        }

        return md5($data);
    }

    protected function getMaxPosition(SortableAdapter $ea, $meta, $config, $object, array $groups = [])
    {
        $em = $ea->getObjectManager();
        $uow = $em->getUnitOfWork();
        $maxPos = null;

        // Get groups
        if (!count($groups)) {
            $groups = $this->getGroups($meta, $config, $object);
        }

        // Get hash
        $hash = $this->getHash($groups, $config);

        // Check for cached max position
        if (isset($this->maxPositions[$hash])) {
            return $this->maxPositions[$hash];
        }

        // Check for groups that are associations. If the value is an object and is
        // scheduled for insert, it has no identifier yet and is obviously new
        // see issue #226
        foreach ($groups as $val) {
            if (is_object($val) && ($uow->isScheduledForInsert($val) || !$em->getMetadataFactory()->isTransient(ClassUtils::getClass($val)) && $uow::STATE_MANAGED !== $ea->getObjectState($uow, $val))) {
                return -1;
            }
        }

        $maxPos = $ea->getMaxPosition($config, $meta, $groups);
        if (is_null($maxPos)) {
            $maxPos = -1;
        }

        return (int) $maxPos;
    }

    /**
     * Add a relocation rule
     *
     * @param string $hash    The hash of the sorting group
     * @param string $class   The object class
     * @param array  $groups  The sorting groups
     * @param int    $start   Inclusive index to start relocation from
     * @param int    $stop    Exclusive index to stop relocation at
     * @param int    $delta   The delta to add to relocated nodes
     * @param array  $exclude Objects to be excluded from relocation
     */
    protected function addRelocation($hash, $class, $groups, $start, $stop, $delta, array $exclude = [])
    {
        if (!array_key_exists($hash, $this->relocations)) {
            $this->relocations[$hash] = ['name' => $class, 'groups' => $groups, 'deltas' => []];
        }

        try {
            $newDelta = ['start' => $start, 'stop' => $stop, 'delta' => $delta, 'exclude' => $exclude];
            array_walk($this->relocations[$hash]['deltas'], function (&$val, $idx, $needle) {
                if ($val['start'] == $needle['start'] && $val['stop'] == $needle['stop']) {
                    $val['delta'] += $needle['delta'];
                    $val['exclude'] = array_merge($val['exclude'], $needle['exclude']);
                    throw new \Exception('Found delta. No need to add it again.');
                // For every deletion relocation add newly created object to the list of excludes
                // otherwise position update queries will run for created objects as well.
                } elseif (-1 == $val['delta'] && 1 == $needle['delta']) {
                    $val['exclude'] = array_merge($val['exclude'], $needle['exclude']);
                }
            }, $newDelta);
            $this->relocations[$hash]['deltas'][] = $newDelta;
        } catch (\Exception $e) {
        }
    }

    /**
     * @param array         $config
     * @param ClassMetadata $meta
     * @param object        $object
     *
     * @return array
     */
    protected function getGroups($meta, $config, $object)
    {
        $groups = [];
        if (isset($config['groups'])) {
            foreach ($config['groups'] as $group) {
                $groups[$group] = $meta->getReflectionProperty($group)->getValue($object);
            }
        }

        return $groups;
    }

    /**
     * {@inheritdoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }
}

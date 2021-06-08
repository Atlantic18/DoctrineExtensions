<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventArgs;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Mapping\MappedEventSubscriber;

/**
 * The tree listener handles the synchronization of
 * tree nodes. Can implement different
 * strategies on handling the tree.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TreeListener extends MappedEventSubscriber
{
    /**
     * Tree processing strategies for object classes
     *
     * @var array
     */
    private $strategies = [];

    /**
     * List of strategy instances
     *
     * @var array
     */
    private $strategyInstances = [];

    /**
     * List of used classes on flush
     *
     * @var array
     */
    private $usedClassesOnFlush = [];

    /**
     * Specifies the list of events to listen
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            'prePersist',
            'preRemove',
            'preUpdate',
            'onFlush',
            'loadClassMetadata',
            'postPersist',
            'postUpdate',
            'postRemove',
        ];
    }

    /**
     * Get the used strategy for tree processing
     *
     * @param string $class
     *
     * @return Strategy
     */
    public function getStrategy(ObjectManager $om, $class)
    {
        if (!isset($this->strategies[$class])) {
            $config = $this->getConfiguration($om, $class);
            if (!$config) {
                throw new \Gedmo\Exception\UnexpectedValueException("Tree object class: {$class} must have tree metadata at this point");
            }
            $managerName = 'UnsupportedManager';
            if ($om instanceof \Doctrine\ORM\EntityManagerInterface) {
                $managerName = 'ORM';
            } elseif ($om instanceof \Doctrine\ODM\MongoDB\DocumentManager) {
                $managerName = 'ODM\\MongoDB';
            }
            if (!isset($this->strategyInstances[$config['strategy']])) {
                $strategyClass = $this->getNamespace().'\\Strategy\\'.$managerName.'\\'.ucfirst($config['strategy']);

                if (!class_exists($strategyClass)) {
                    throw new \Gedmo\Exception\InvalidArgumentException($managerName." TreeListener does not support tree type: {$config['strategy']}");
                }
                $this->strategyInstances[$config['strategy']] = new $strategyClass($this);
            }
            $this->strategies[$class] = $config['strategy'];
        }

        return $this->strategyInstances[$this->strategies[$class]];
    }

    /**
     * Looks for Tree objects being updated
     * for further processing
     */
    public function onFlush(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $uow = $om->getUnitOfWork();

        // check all scheduled updates for TreeNodes
        foreach ($ea->getScheduledObjectInsertions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($this->getConfiguration($om, $meta->name)) {
                $this->usedClassesOnFlush[$meta->name] = null;
                $this->getStrategy($om, $meta->name)->processScheduledInsertion($om, $object, $ea);
                $ea->recomputeSingleObjectChangeSet($uow, $meta, $object);
            }
        }

        foreach ($ea->getScheduledObjectUpdates($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($this->getConfiguration($om, $meta->name)) {
                $this->usedClassesOnFlush[$meta->name] = null;
                $this->getStrategy($om, $meta->name)->processScheduledUpdate($om, $object, $ea);
            }
        }

        foreach ($ea->getScheduledObjectDeletions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($this->getConfiguration($om, $meta->name)) {
                $this->usedClassesOnFlush[$meta->name] = null;
                $this->getStrategy($om, $meta->name)->processScheduledDelete($om, $object);
            }
        }

        foreach ($this->getStrategiesUsedForObjects($this->usedClassesOnFlush) as $strategy) {
            $strategy->onFlushEnd($om, $ea);
        }
    }

    /**
     * Updates tree on Node removal
     */
    public function preRemove(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));

        if ($this->getConfiguration($om, $meta->name)) {
            $this->getStrategy($om, $meta->name)->processPreRemove($om, $object);
        }
    }

    /**
     * Checks for persisted Nodes
     */
    public function prePersist(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));

        if ($this->getConfiguration($om, $meta->name)) {
            $this->getStrategy($om, $meta->name)->processPrePersist($om, $object);
        }
    }

    /**
     * Checks for updated Nodes
     */
    public function preUpdate(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));

        if ($this->getConfiguration($om, $meta->name)) {
            $this->getStrategy($om, $meta->name)->processPreUpdate($om, $object);
        }
    }

    /**
     * Checks for pending Nodes to fully synchronize
     * the tree
     */
    public function postPersist(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));

        if ($this->getConfiguration($om, $meta->name)) {
            $this->getStrategy($om, $meta->name)->processPostPersist($om, $object, $ea);
        }
    }

    /**
     * Checks for pending Nodes to fully synchronize
     * the tree
     */
    public function postUpdate(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));

        if ($this->getConfiguration($om, $meta->name)) {
            $this->getStrategy($om, $meta->name)->processPostUpdate($om, $object, $ea);
        }
    }

    /**
     * Checks for pending Nodes to fully synchronize
     * the tree
     */
    public function postRemove(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));

        if ($this->getConfiguration($om, $meta->name)) {
            $this->getStrategy($om, $meta->name)->processPostRemove($om, $object, $ea);
        }
    }

    /**
     * Mapps additional metadata
     */
    public function loadClassMetadata(EventArgs $eventArgs)
    {
        $ea = $this->getEventAdapter($eventArgs);
        $om = $ea->getObjectManager();
        $meta = $eventArgs->getClassMetadata();
        $this->loadMetadataForObjectClass($om, $meta);
        if (isset(self::$configurations[$this->name][$meta->name]) && self::$configurations[$this->name][$meta->name]) {
            $this->getStrategy($om, $meta->name)->processMetadataLoad($om, $meta);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * Get the list of strategy instances used for
     * given object classes
     *
     * @return Strategy[]
     */
    protected function getStrategiesUsedForObjects(array $classes)
    {
        $strategies = [];
        foreach (array_keys($classes) as $name) {
            if (isset($this->strategies[$name]) && !isset($strategies[$this->strategies[$name]])) {
                $strategies[$this->strategies[$name]] = $this->strategyInstances[$this->strategies[$name]];
            }
        }

        return $strategies;
    }
}

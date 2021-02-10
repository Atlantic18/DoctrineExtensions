<?php

namespace Gedmo\SoftDeleteable;

use Doctrine\Common\EventArgs;
use Doctrine\DBAL\Types\Type;
use Doctrine\MongoDB\Connection as MongoDBConnection;
use Doctrine\ODM\MongoDB\Types\DateType as MongoDBDateType;
use Doctrine\ODM\MongoDB\UnitOfWork as MongoDBUnitOfWork;
use Gedmo\Mapping\MappedEventSubscriber;

/**
 * SoftDeleteable listener
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SoftDeleteableListener extends MappedEventSubscriber
{
    /**
     * Pre soft-delete event
     *
     * @var string
     */
    const PRE_SOFT_DELETE = 'preSoftDelete';

    /**
     * Post soft-delete event
     *
     * @var string
     */
    const POST_SOFT_DELETE = 'postSoftDelete';

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            'loadClassMetadata',
            'onFlush',
        ];
    }

    /**
     * If it's a SoftDeleteable object, update the "deletedAt" field
     * and skip the removal of the object
     *
     * @return void
     */
    public function onFlush(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $uow = $om->getUnitOfWork();
        $evm = $om->getEventManager();

        //getScheduledDocumentDeletions
        foreach ($ea->getScheduledObjectDeletions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            $config = $this->getConfiguration($om, $meta->name);

            if (isset($config['softDeleteable']) && $config['softDeleteable']) {
                $reflProp = $meta->getReflectionProperty($config['fieldName']);
                $oldValue = $reflProp->getValue($object);
// These conflicts are not resolved in order to check if the tests pass without these changes.
//<<<<<<< HEAD:src/SoftDeleteable/SoftDeleteableListener.php
                $date = $ea->getDateValue($meta, $config['fieldName']);
//=======
//                if ($om->getConnection() instanceof MongoDBConnection) {
//                    $date = MongoDBDateType::getDateTime('now');
//                } else {
//                    $date = Type::getType($config['type'])->convertToPHPValue('now', $om->getConnection()->getDatabasePlatform());
//                }
//>>>>>>> c4e77dd0... Add support for "date*_immutable" types:lib/Gedmo/SoftDeleteable/SoftDeleteableListener.php

                if (isset($config['hardDelete']) && $config['hardDelete'] && $oldValue instanceof \DateTimeInterface && $oldValue <= $date) {
                    continue; // want to hard delete
                }

                $evm->dispatchEvent(
                    self::PRE_SOFT_DELETE,
                    $ea->createLifecycleEventArgsInstance($object, $om)
                );

                $reflProp->setValue($object, $date);

                $om->persist($object);
                $uow->propertyChanged($object, $config['fieldName'], $oldValue, $date);
                if ($uow instanceof MongoDBUnitOfWork && !method_exists($uow, 'scheduleExtraUpdate')) {
                    $ea->recomputeSingleObjectChangeSet($uow, $meta, $object);
                } else {
                    $uow->scheduleExtraUpdate($object, [
                        $config['fieldName'] => [$oldValue, $date],
                    ]);
                }

                $evm->dispatchEvent(
                    self::POST_SOFT_DELETE,
                    $ea->createLifecycleEventArgsInstance($object, $om)
                );
            }
        }
    }

    /**
     * Maps additional metadata
     *
     * @return void
     */
    public function loadClassMetadata(EventArgs $eventArgs)
    {
        $ea = $this->getEventAdapter($eventArgs);
        $this->loadMetadataForObjectClass($ea->getObjectManager(), $eventArgs->getClassMetadata());
    }

    /**
     * {@inheritdoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }
}

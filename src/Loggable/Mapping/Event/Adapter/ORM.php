<?php

namespace Gedmo\Loggable\Mapping\Event\Adapter;

use Gedmo\Loggable\Mapping\Event\LoggableAdapter;
use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM;
use Gedmo\Tool\Wrapper\EntityWrapper;

/**
 * Doctrine event adapter for ORM adapted
 * for Loggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ORM extends BaseAdapterORM implements LoggableAdapter
{
    /**
     * {@inheritdoc}
     */
    public function getDefaultLogEntryClass()
    {
        return 'Gedmo\\Loggable\\Entity\\LogEntry';
    }

    /**
     * {@inheritdoc}
     */
    public function isPostInsertGenerator($meta)
    {
        return $meta->idGenerator->isPostInsertGenerator();
    }

    /**
     * {@inheritdoc}
     */
    public function getNewVersion($meta, $object)
    {
        $em = $this->getObjectManager();
        $objectMeta = $em->getClassMetadata(get_class($object));
        $wrapper = new EntityWrapper($object, $em);
        $objectId = $wrapper->getIdentifier(false, true);

        $dql = "SELECT MAX(log.version) FROM {$meta->name} log";
        $dql .= ' WHERE log.objectId = :objectId';
        $dql .= ' AND log.objectClass = :objectClass';

        $q = $em->createQuery($dql);
        $q->setParameters([
            'objectId' => $objectId,
            'objectClass' => $objectMeta->name,
        ]);

        return $q->getSingleScalarResult() + 1;
    }
}

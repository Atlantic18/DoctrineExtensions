<?php

namespace Gedmo\Loggable\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;
use Gedmo\Loggable\LoggableListener;
use Gedmo\Tool\Wrapper\EntityWrapper;

/**
 * The LogEntryRepository has some useful functions
 * to interact with log entries.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class LogEntryRepository extends EntityRepository
{
    /**
     * Currently used loggable listener
     *
     * @var LoggableListener
     */
    private $listener;

    /**
     * Loads all log entries for the given entity
     *
     * @param object $entity
     *
     * @return AbstractLogEntry[]
     */
    public function getLogEntries($entity)
    {
        $q = $this->getLogEntriesQuery($entity);

        return $q->getResult();
    }

    /**
     * Get the query for loading of log entries
     *
     * @param object $entity
     *
     * @return Query
     */
    public function getLogEntriesQuery($entity)
    {
        $wrapped = new EntityWrapper($entity, $this->_em);
        $objectClass = $wrapped->getMetadata()->name;
        $meta = $this->getClassMetadata();
        $dql = "SELECT log FROM {$meta->name} log";
        $dql .= ' WHERE log.objectId = :objectId';
        $dql .= ' AND log.objectClass = :objectClass';
        $dql .= ' ORDER BY log.version DESC';

        $objectId = (string) $wrapped->getIdentifier(false, true);
        $q = $this->_em->createQuery($dql);
        $q->setParameters(compact('objectId', 'objectClass'));

        return $q;
    }

    /**
     * Reverts given $entity to $revision by
     * restoring all fields from that $revision.
     * After this operation you will need to
     * persist and flush the $entity.
     *
     * @param object $entity
     * @param int    $version
     *
     * @throws \Gedmo\Exception\UnexpectedValueException
     *
     * @return void
     */
    public function revert($entity, $version = 1)
    {
        $wrapped = new EntityWrapper($entity, $this->_em);
        $objectMeta = $wrapped->getMetadata();
        $objectClass = $objectMeta->name;
        $meta = $this->getClassMetadata();
        $dql = "SELECT log FROM {$meta->name} log";
        $dql .= ' WHERE log.objectId = :objectId';
        $dql .= ' AND log.objectClass = :objectClass';
        $dql .= ' AND log.version <= :version';
        $dql .= ' ORDER BY log.version ASC';

        $objectId = (string) $wrapped->getIdentifier(false, true);
        $q = $this->_em->createQuery($dql);
        $q->setParameters(compact('objectId', 'objectClass', 'version'));
        $logs = $q->getResult();

        if ($logs) {
            $config = $this->getLoggableListener()->getConfiguration($this->_em, $objectMeta->name);
            $fields = $config['versioned'];
            $filled = false;
            while (($log = array_pop($logs)) && !$filled) {
                if ($data = $log->getData()) {
                    foreach ($data as $field => $value) {
                        if (in_array($field, $fields)) {
                            $this->mapValue($objectMeta, $field, $value);
                            $wrapped->setPropertyValue($field, $value);
                            unset($fields[array_search($field, $fields)]);
                        }
                    }
                }
                $filled = 0 === count($fields);
            }
            /*if (count($fields)) {
                throw new \Gedmo\Exception\UnexpectedValueException('Could not fully revert the entity to version: '.$version);
            }*/
        } else {
            throw new \Gedmo\Exception\UnexpectedValueException('Could not find any log entries under version: '.$version);
        }
    }

    /**
     * @param string $field
     * @param mixed  $value
     */
    protected function mapValue(ClassMetadata $objectMeta, $field, &$value)
    {
        if (!$objectMeta->isSingleValuedAssociation($field)) {
            return;
        }

        $mapping = $objectMeta->getAssociationMapping($field);
        $value = $value ? $this->_em->getReference($mapping['targetEntity'], $value) : null;
    }

    /**
     * Get the currently used LoggableListener
     *
     * @throws \Gedmo\Exception\RuntimeException - if listener is not found
     *
     * @return LoggableListener
     */
    private function getLoggableListener()
    {
        if (is_null($this->listener)) {
            foreach ($this->_em->getEventManager()->getListeners() as $event => $listeners) {
                foreach ($listeners as $hash => $listener) {
                    if ($listener instanceof LoggableListener) {
                        $this->listener = $listener;
                        break;
                    }
                }
                if ($this->listener) {
                    break;
                }
            }

            if (is_null($this->listener)) {
                throw new \Gedmo\Exception\RuntimeException('The loggable listener could not be found');
            }
        }

        return $this->listener;
    }
}

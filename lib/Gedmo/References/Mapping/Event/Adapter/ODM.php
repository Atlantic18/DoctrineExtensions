<?php

namespace Gedmo\References\Mapping\Event\Adapter;

use Doctrine\Common\Proxy\Proxy;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Proxy\Proxy as MongoDBProxy;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Proxy\Proxy as ORMProxy;
use Gedmo\Mapping\Event\Adapter\ODM as BaseAdapterODM;
use Gedmo\References\Mapping\Event\ReferencesAdapter;

/**
 * Doctrine event adapter for ODM references behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ODM extends BaseAdapterODM implements ReferencesAdapter
{
    /**
     * @inheritDoc
     */
    public function getIdentifier($om, $object, $single = true)
    {
        if ($om instanceof DocumentManager) {
            return $this->extractIdentifier($om, $object, $single);
        }

        if ($om instanceof EntityManager) {
            if ($object instanceof ORMProxy) {
                $id = $om->getUnitOfWork()->getEntityIdentifier($object);
            } else {
                $meta = $om->getClassMetadata(get_class($object));
                $id = array();
                foreach ($meta->identifier as $name) {
                    $id[$name] = $meta->getReflectionProperty($name)->getValue($object);
                    // return null if one of identifiers is missing
                    if (!$id[$name]) {
                        return null;
                    }
                }
            }

            if ($single) {
                $id = current($id);
            }

            return $id;
        }
    }

    /**
     * @inheritDoc
     */
    public function getSingleReference($om, $class, $identifier)
    {
        $this->throwIfNotEntityManager($om);
        $meta = $om->getClassMetadata($class);

        if (!$meta->isInheritanceTypeNone()) {
            return $om->find($class, $identifier);
        }

        return $om->getReference($class, $identifier);
    }

    /**
     * @inheritDoc
     */
    public function getSingleObject($om, $class, $identifier, $disabledFilters = [])
    {
        $this->throwIfNotEntityManager($om);
        /** @var EntityManager $om */

        $filters = $om->getFilters();
        $disabled = [];

        foreach ($disabledFilters as $filter) {
            if ($filters->isEnabled($filter)) {
                $filters->disable($filter);
                $disabled[] = $filter;
            }
        }

        $object = $om->find($class, $identifier);

        if ($object instanceof Proxy) {
            $object->__load();
        }

        foreach ($disabled as $filter) {
            $filters->enable($filter);
        }

        return $object;
    }

    /**
     * @inheritDoc
     */
    public function extractIdentifier($om, $object, $single = true)
    {
        $meta = $om->getClassMetadata(get_class($object));
        if ($object instanceof MongoDBProxy) {
            $id = $om->getUnitOfWork()->getDocumentIdentifier($object);
        } else {
            $id = $meta->getReflectionProperty($meta->identifier)->getValue($object);
        }

        if ($single || !$id) {
            return $id;
        } else {
            return array($meta->identifier => $id);
        }
    }

    /**
     * Override so we don't get an exception. We want to allow this.
     */
    private function throwIfNotEntityManager(EntityManager $em)
    {
    }
}

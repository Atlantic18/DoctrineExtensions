<?php

namespace Gedmo\References\Mapping\Event\Adapter;

use Doctrine\ODM\MongoDB\DocumentManager as MongoDocumentManager;
use Doctrine\ODM\MongoDB\Proxy\Proxy as MongoDBProxy;
use Doctrine\ODM\PHPCR\DocumentManager as PhpcrDocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Proxy\Proxy as ORMProxy;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM;
use Gedmo\References\Mapping\Event\ReferencesAdapter;

/**
 * Doctrine event adapter for ORM references behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ORM extends BaseAdapterORM implements ReferencesAdapter
{
    /**
     * {@inheritdoc}
     */
    public function getIdentifier($om, $object, $single = true)
    {
        if ($om instanceof EntityManagerInterface) {
            return $this->extractIdentifier($om, $object, $single);
        }

        if ($om instanceof MongoDocumentManager) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($object instanceof MongoDBProxy) {
                $id = $om->getUnitOfWork()->getDocumentIdentifier($object);
            } else {
                $id = $meta->getReflectionProperty($meta->identifier)->getValue($object);
            }

            if ($single || !$id) {
                return $id;
            }

            return [$meta->identifier => $id];
        }

        if ($om instanceof PhpcrDocumentManager) {
            $meta = $om->getClassMetadata(get_class($object));
            $id = $meta->getReflectionProperty($meta->identifier)->getValue($object);

            if ($single || !$id) {
                return $id;
            }

            return [$meta->identifier => $id];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSingleReference($om, $class, $identifier)
    {
        $this->throwIfNotDocumentManager($om);
        $meta = $om->getClassMetadata($class);

        if ($om instanceof MongoDocumentManager && !$meta->isInheritanceTypeNone()) {
            return $om->find($class, $identifier);
        }

        return $om->getReference($class, $identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function extractIdentifier($om, $object, $single = true)
    {
        if ($object instanceof ORMProxy) {
            $id = $om->getUnitOfWork()->getEntityIdentifier($object);
        } else {
            $meta = $om->getClassMetadata(get_class($object));
            $id = [];
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

    /**
     * Override so we don't get an exception. We want to allow this.
     */
    private function throwIfNotDocumentManager($dm)
    {
        if (!($dm instanceof MongoDocumentManager) && !($dm instanceof PhpcrDocumentManager)) {
            throw new InvalidArgumentException(sprintf('Expected a %s or %s instance but got "%s"', 'Doctrine\ODM\MongoDB\DocumentManager', 'Doctrine\ODM\PHPCR\DocumentManager', is_object($dm) ? get_class($dm) : gettype($dm)));
        }
    }
}

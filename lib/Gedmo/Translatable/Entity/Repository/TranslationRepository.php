<?php

namespace Gedmo\Translatable\Entity\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Translatable\TranslatableListener;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Mapping\ClassMetadata;
use Gedmo\Tool\Wrapper\EntityWrapper;
use Gedmo\Translatable\Mapping\Event\Adapter\ORM as TranslatableAdapterORM;
use Doctrine\DBAL\Types\Type;

/**
 * The TranslationRepository has some useful functions
 * to interact with translations.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslationRepository extends EntityRepository
{
    /**
     * Current TranslatableListener instance used
     * in EntityManager
     *
     * @var TranslatableListener
     */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    public function __construct(EntityManagerInterface $em, ClassMetadata $class)
    {
        if ($class->getReflectionClass()->isSubclassOf('Gedmo\Translatable\Entity\MappedSuperclass\AbstractPersonalTranslation')) {
            throw new \Gedmo\Exception\UnexpectedValueException('This repository is useless for personal translations');
        }
        parent::__construct($em, $class);
    }

    /**
     * Makes additional translation of $entity $field into $locale
     * using $value
     *
     * @param object $entity
     * @param string $field
     * @param string $locale
     * @param mixed  $value
     *
     * @throws \Gedmo\Exception\InvalidArgumentException
     *
     * @return static
     */
    public function translate($entity, $field, $locale, $value)
    {
        $meta = $this->_em->getClassMetadata(get_class($entity));
        $listener = $this->getTranslatableListener();
        $config = $listener->getConfiguration($this->_em, $meta->name);
        if (!isset($config['fields']) || !in_array($field, $config['fields'])) {
            throw new \Gedmo\Exception\InvalidArgumentException("Entity: {$meta->name} does not translate field - {$field}");
        }
        $needsPersist = true;
        if ($locale === $listener->getTranslatableLocale($entity, $meta, $this->getEntityManager())) {
            $meta->getReflectionProperty($field)->setValue($entity, $value);
            $this->_em->persist($entity);
        } else {
            if (isset($config['translationClass'])) {
                $class = $config['translationClass'];
            } else {
                $ea = new TranslatableAdapterORM();
                $class = $listener->getTranslationClass($ea, $config['useObjectClass']);
            }
            $foreignKey = $meta->getReflectionProperty($meta->getSingleIdentifierFieldName())->getValue($entity);
            $objectClass = $config['useObjectClass'];
            $transMeta = $this->_em->getClassMetadata($class);
            $trans = $this->findOneBy(compact('locale', 'objectClass', 'field', 'foreignKey'));
            if (!$trans) {
                $trans = $transMeta->newInstance();
                $transMeta->getReflectionProperty('foreignKey')->setValue($trans, $foreignKey);
                $transMeta->getReflectionProperty('objectClass')->setValue($trans, $objectClass);
                $transMeta->getReflectionProperty('field')->setValue($trans, $field);
                $transMeta->getReflectionProperty('locale')->setValue($trans, $locale);
            }
            if ($listener->getDefaultLocale() != $listener->getTranslatableLocale($entity, $meta, $this->getEntityManager()) &&
                $locale === $listener->getDefaultLocale()) {
                $listener->setTranslationInDefaultLocale(spl_object_hash($entity), $field, $trans);
                $needsPersist = $listener->getPersistDefaultLocaleTranslation();
            }
            $type = Type::getType($meta->getTypeOfField($field));
            $transformed = $type->convertToDatabaseValue($value, $this->_em->getConnection()->getDatabasePlatform());
            $transMeta->getReflectionProperty('content')->setValue($trans, $transformed);
            if ($needsPersist) {
                if ($this->_em->getUnitOfWork()->isInIdentityMap($entity)) {
                    $this->_em->persist($trans);
                } else {
                    $oid = spl_object_hash($entity);
                    $listener->addPendingTranslationInsert($oid, $trans);
                }
            }
        }

        return $this;
    }

    /**
     * Loads all translations with all translatable
     * fields from the given entity
     *
     * @param object $entity Must implement Translatable
     *
     * @return array list of translations in locale groups
     */
    public function findTranslations($entity)
    {
        $result = array();
        $wrapped = new EntityWrapper($entity, $this->_em);
        if ($wrapped->hasValidIdentifier()) {
            $entityId = $wrapped->getIdentifier();
            $config = $this
                ->getTranslatableListener()
                ->getConfiguration($this->_em, $wrapped->getMetadata()->name);

            if (!$config) {
                return $result;
            }

            $entityClass = $config['useObjectClass'];
            $translationMeta = $this->getClassMetadata(); // table inheritance support

            $translationClass = isset($config['translationClass']) ?
                $config['translationClass'] :
                $translationMeta->rootEntityName;

            $qb = $this->_em->createQueryBuilder();
            $qb->select('trans.content, trans.field, trans.locale')
                ->from($translationClass, 'trans')
                ->where('trans.foreignKey = :entityId', 'trans.objectClass = :entityClass')
                ->orderBy('trans.locale');
            $q = $qb->getQuery();
            $data = $q->execute(
                compact('entityId', 'entityClass'),
                Query::HYDRATE_ARRAY
            );

            if ($data && is_array($data) && count($data)) {
                foreach ($data as $row) {
                    $result[$row['locale']][$row['field']] = $row['content'];
                }
            }
        }

        return $result;
    }

    /**
     * Find the entity $class by the translated field.
     * Result is the first occurrence of translated field.
     * Query can be slow, since there are no indexes on such
     * columns
     *
     * @param string $field
     * @param string $value
     * @param string $class
     *
     * @return object - instance of $class or null if not found
     */
    public function findObjectByTranslatedField($field, $value, $class)
    {
        $entity = null;
        $meta = $this->_em->getClassMetadata($class);
        $translationMeta = $this->getClassMetadata(); // table inheritance support
        if ($meta->hasField($field)) {
            $dql = "SELECT trans.foreignKey FROM {$translationMeta->rootEntityName} trans";
            $dql .= ' WHERE trans.objectClass = :class';
            $dql .= ' AND trans.field = :field';
            $dql .= ' AND trans.content = :value';
            $q = $this->_em->createQuery($dql);
            $q->setParameters(compact('class', 'field', 'value'));
            $q->setMaxResults(1);
            $result = $q->getArrayResult();
            $id = count($result) ? $result[0]['foreignKey'] : null;

            if ($id) {
                $entity = $this->_em->find($class, $id);
            }
        }

        return $entity;
    }

    /**
     * Loads all translations with all translatable
     * fields by a given entity primary key
     *
     * @param mixed $id - primary key value of an entity
     *
     * @return array
     */
    public function findTranslationsByObjectId($id)
    {
        $result = array();
        if ($id) {
            $translationMeta = $this->getClassMetadata(); // table inheritance support
            $qb = $this->_em->createQueryBuilder();
            $qb->select('trans.content, trans.field, trans.locale')
                ->from($translationMeta->rootEntityName, 'trans')
                ->where('trans.foreignKey = :entityId')
                ->orderBy('trans.locale');
            $q = $qb->getQuery();
            $data = $q->execute(
                array('entityId' => $id),
                Query::HYDRATE_ARRAY
            );

            if ($data && is_array($data) && count($data)) {
                foreach ($data as $row) {
                    $result[$row['locale']][$row['field']] = $row['content'];
                }
            }
        }

        return $result;
    }

    /**
     * Get the currently used TranslatableListener
     *
     * @throws \Gedmo\Exception\RuntimeException - if listener is not found
     *
     * @return TranslatableListener
     */
    protected function getTranslatableListener()
    {
        if (!$this->listener) {
            foreach ($this->_em->getEventManager()->getListeners() as $event => $listeners) {
                foreach ($listeners as $hash => $listener) {
                    if ($listener instanceof TranslatableListener) {
                        return $this->listener = $listener;
                    }
                }
            }

            throw new \Gedmo\Exception\RuntimeException('The translation listener could not be found');
        }

        return $this->listener;
    }
}

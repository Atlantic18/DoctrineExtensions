<?php

namespace Gedmo\SoftDeleteable\Filter;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Gedmo\SoftDeleteable\SoftDeleteableListener;

/**
 * The SoftDeleteableFilter adds the condition necessary to
 * filter entities which were deleted "softly"
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Patrik Votoček <patrik@votocek.cz>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SoftDeleteableFilter extends SQLFilter
{
    /**
     * @var SoftDeleteableListener
     */
    protected $listener;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var string[bool]
     */
    protected $disabled = [];

    /**
     * @param string $targetTableAlias
     *
     * @return string
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        $class = $targetEntity->getName();
        if (array_key_exists($class, $this->disabled) && true === $this->disabled[$class]) {
            return '';
        } elseif (array_key_exists($targetEntity->rootEntityName, $this->disabled) && true === $this->disabled[$targetEntity->rootEntityName]) {
            return '';
        }

        $config = $this->getListener()->getConfiguration($this->getEntityManager(), $targetEntity->name);

        if (!isset($config['softDeleteable']) || !$config['softDeleteable']) {
            return '';
        }

        $conn = $this->getEntityManager()->getConnection();
        $platform = $conn->getDatabasePlatform();
        $column = $targetEntity->getQuotedColumnName($config['fieldName'], $platform);

        $addCondSql = $platform->getIsNullExpression($targetTableAlias.'.'.$column);
        if (isset($config['timeAware']) && $config['timeAware']) {
            $addCondSql = "({$addCondSql} OR {$targetTableAlias}.{$column} > {$platform->getCurrentTimestampSQL()})";
        }

        return $addCondSql;
    }

    /**
     * @param string $class
     */
    public function disableForEntity($class)
    {
        $this->disabled[$class] = true;
        // Make sure the hash (@see SQLFilter::__toString()) for this filter will be changed to invalidate the query cache.
        $this->setParameter(sprintf('disabled_%s', $class), true);
    }

    /**
     * @param string $class
     */
    public function enableForEntity($class)
    {
        $this->disabled[$class] = false;
        // Make sure the hash (@see SQLFilter::__toString()) for this filter will be changed to invalidate the query cache.
        $this->setParameter(sprintf('disabled_%s', $class), false);
    }

    /**
     * @return SoftDeleteableListener
     *
     * @throws \RuntimeException
     */
    protected function getListener()
    {
        if (null === $this->listener) {
            $em = $this->getEntityManager();
            $evm = $em->getEventManager();

            foreach ($evm->getListeners() as $listeners) {
                foreach ($listeners as $listener) {
                    if ($listener instanceof SoftDeleteableListener) {
                        $this->listener = $listener;

                        break 2;
                    }
                }
            }

            if (null === $this->listener) {
                throw new \RuntimeException('Listener "SoftDeleteableListener" was not added to the EventManager!');
            }
        }

        return $this->listener;
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        if (null === $this->entityManager) {
            $getEntityManager = \Closure::bind(function (): EntityManagerInterface {
                return $this->em;
            }, $this, parent::class);

            $this->entityManager = $getEntityManager();
        }

        return $this->entityManager;
    }
}

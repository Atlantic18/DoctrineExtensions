<?php

namespace Gedmo\Tree\Entity\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Tool\Wrapper\EntityWrapper;
use Gedmo\Tree\RepositoryInterface;
use Gedmo\Tree\RepositoryUtils;
use Gedmo\Tree\RepositoryUtilsInterface;
use Gedmo\Tree\TreeListener;

abstract class AbstractTreeRepository extends EntityRepository implements RepositoryInterface
{
    /**
     * Tree listener on event manager
     *
     * @var TreeListener
     */
    protected $listener = null;

    /**
     * Repository utils
     */
    protected $repoUtils = null;

    /**
     * {@inheritdoc}
     */
    public function __construct(EntityManagerInterface $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
    }

    /**
     * @return TreeListener
     */
    protected function getTreeListener()
    {
        if (null === $this->listener) {
            $treeListener = null;

            foreach ($this->getEntityManager()->getEventManager()->getListeners() as $listeners) {
                foreach ($listeners as $listener) {
                    if ($listener instanceof TreeListener) {
                        $treeListener = $listener;
                        break;
                    }
                }

                if ($treeListener) {
                    break;
                }
            }

            if (is_null($treeListener)) {
                throw new \Gedmo\Exception\InvalidMappingException('Tree listener was not found on your entity manager, it must be hooked into the event manager');
            }

            $this->listener = $treeListener;
        }

        return $this->listener;
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getQueryBuilder()
    {
        return $this->getEntityManager()->createQueryBuilder();
    }

    /**
     * Sets the RepositoryUtilsInterface instance
     *
     * @return static
     */
    public function setRepoUtils(RepositoryUtilsInterface $repoUtils)
    {
        $this->repoUtils = $repoUtils;

        return $this;
    }

    /**
     * Returns the RepositoryUtilsInterface instance
     *
     * @return \Gedmo\Tree\RepositoryUtilsInterface|null
     */
    public function getRepoUtils()
    {
        if (null === $this->repoUtils) {
            $listener = $this->getTreeListener();

            if (!$this->validate()) {
                throw new \Gedmo\Exception\InvalidMappingException('This repository cannot be used for tree type: '.$treeListener->getStrategy($em, $class->name)->getName());
            }

            $this->repoUtils = new RepositoryUtils($this->_em, $this->getClassMetadata(), $this->getTreeListener(), $this);
        }

        return $this->repoUtils;
    }

    /**
     * {@inheritdoc}
     */
    public function childCount($node = null, $direct = false)
    {
        $meta = $this->getClassMetadata();

        if (is_object($node)) {
            if (!($node instanceof $meta->name)) {
                throw new InvalidArgumentException('Node is not related to this repository');
            }

            $wrapped = new EntityWrapper($node, $this->_em);

            if (!$wrapped->hasValidIdentifier()) {
                throw new InvalidArgumentException('Node is not managed by UnitOfWork');
            }
        }

        $qb = $this->getChildrenQueryBuilder($node, $direct);

        // We need to remove the ORDER BY DQL part since some vendors could throw an error
        // in count queries
        $dqlParts = $qb->getDQLParts();

        // We need to check first if there's an ORDER BY DQL part, because resetDQLPart doesn't
        // check if its internal array has an "orderby" index
        if (isset($dqlParts['orderBy'])) {
            $qb->resetDQLPart('orderBy');
        }

        $aliases = $qb->getRootAliases();
        $alias = $aliases[0];

        $qb->select('COUNT('.$alias.')');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @see \Gedmo\Tree\RepositoryUtilsInterface::childrenHierarchy
     */
    public function childrenHierarchy($node = null, $direct = false, array $options = [], $includeNode = false)
    {
        return $this->getRepoUtils()->childrenHierarchy($node, $direct, $options, $includeNode);
    }

    /**
     * @see \Gedmo\Tree\RepositoryUtilsInterface::buildTree
     */
    public function buildTree(array $nodes, array $options = [])
    {
        return $this->getRepoUtils()->buildTree($nodes, $options);
    }

    /**
     * @see \Gedmo\Tree\RepositoryUtilsInterface::buildTreeArray
     */
    public function buildTreeArray(array $nodes)
    {
        return $this->getRepoUtils()->buildTreeArray($nodes);
    }

    /**
     * @see \Gedmo\Tree\RepositoryUtilsInterface::setChildrenIndex
     */
    public function setChildrenIndex($childrenIndex)
    {
        $this->getRepoUtils()->setChildrenIndex($childrenIndex);
    }

    /**
     * @see \Gedmo\Tree\RepositoryUtilsInterface::getChildrenIndex
     */
    public function getChildrenIndex()
    {
        return $this->getRepoUtils()->getChildrenIndex();
    }

    /**
     * Checks if current repository is right
     * for currently used tree strategy
     *
     * @return bool
     */
    abstract protected function validate();

    /**
     * Get all root nodes query builder
     *
     * @param string - Sort by field
     * @param string - Sort direction ("asc" or "desc")
     *
     * @return \Doctrine\ORM\QueryBuilder - QueryBuilder object
     */
    abstract public function getRootNodesQueryBuilder($sortByField = null, $direction = 'asc');

    /**
     * Get all root nodes query
     *
     * @param string - Sort by field
     * @param string - Sort direction ("asc" or "desc")
     *
     * @return \Doctrine\ORM\Query - Query object
     */
    abstract public function getRootNodesQuery($sortByField = null, $direction = 'asc');

    /**
     * Returns a QueryBuilder configured to return an array of nodes suitable for buildTree method
     *
     * @param object $node        - Root node
     * @param bool   $direct      - Obtain direct children?
     * @param array  $options     - Options
     * @param bool   $includeNode - Include node in results?
     *
     * @return \Doctrine\ORM\QueryBuilder - QueryBuilder object
     */
    abstract public function getNodesHierarchyQueryBuilder($node = null, $direct = false, array $options = [], $includeNode = false);

    /**
     * Returns a Query configured to return an array of nodes suitable for buildTree method
     *
     * @param object $node        - Root node
     * @param bool   $direct      - Obtain direct children?
     * @param array  $options     - Options
     * @param bool   $includeNode - Include node in results?
     *
     * @return \Doctrine\ORM\Query - Query object
     */
    abstract public function getNodesHierarchyQuery($node = null, $direct = false, array $options = [], $includeNode = false);

    /**
     * Get list of children followed by given $node. This returns a QueryBuilder object
     *
     * @param object $node        - if null, all tree nodes will be taken
     * @param bool   $direct      - true to take only direct children
     * @param string $sortByField - field name to sort by
     * @param string $direction   - sort direction : "ASC" or "DESC"
     * @param bool   $includeNode - Include the root node in results?
     *
     * @return \Doctrine\ORM\QueryBuilder - QueryBuilder object
     */
    abstract public function getChildrenQueryBuilder($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false);

    /**
     * Get list of children followed by given $node. This returns a Query
     *
     * @param object $node        - if null, all tree nodes will be taken
     * @param bool   $direct      - true to take only direct children
     * @param string $sortByField - field name to sort by
     * @param string $direction   - sort direction : "ASC" or "DESC"
     * @param bool   $includeNode - Include the root node in results?
     *
     * @return \Doctrine\ORM\Query - Query object
     */
    abstract public function getChildrenQuery($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false);
}

<?php

namespace Gedmo\Tree\Document\MongoDB\Repository;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Gedmo\Tree\RepositoryInterface;
use Gedmo\Tree\RepositoryUtils;
use Gedmo\Tree\RepositoryUtilsInterface;

abstract class AbstractTreeRepository extends DocumentRepository implements RepositoryInterface
{
    /**
     * Tree listener on event manager
     *
     * @var AbstractTreeListener
     */
    protected $listener = null;

    /**
     * Repository utils
     */
    protected $repoUtils = null;

    /**
     * {@inheritdoc}
     */
    public function __construct(DocumentManager $em, UnitOfWork $uow, ClassMetadata $class)
    {
        parent::__construct($em, $uow, $class);
    }

    /**
     * @return TreeListener
     */
    public function getTreeListener()
    {
        if (null === $this->listener) {
            $treeListener = null;

            foreach ($this->dm->getEventManager()->getListeners() as $listeners) {
                foreach ($listeners as $listener) {
                    if ($listener instanceof \Gedmo\Tree\TreeListener) {
                        $treeListener = $listener;
                        break;
                    }
                }
                if ($treeListener) {
                    break;
                }
            }

            if (is_null($treeListener)) {
                throw new \Gedmo\Exception\InvalidMappingException('This repository can be attached only to ODM MongoDB tree listener');
            }

            $this->listener = $treeListener;
        }
        
        return $this->listener;
    }
    
    /**
     * Sets the RepositoryUtilsInterface instance
     *
     * @return $this
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
                throw new \Gedmo\Exception\InvalidMappingException('This repository cannot be used for tree type: ' . $treeListener->getStrategy($em, $class->name)->getName());
            }

            $this->repoUtils = new RepositoryUtils($this->dm, $this->getClassMetadata(), $listener, $this);
        }

        return $this->repoUtils;
    }

    /**
     * {@inheritdoc}
     */
    public function childrenHierarchy($node = null, $direct = false, array $options = [], $includeNode = false)
    {
        return $this->getRepoUtils()->childrenHierarchy($node, $direct, $options, $includeNode);
    }

    /**
     * {@inheritdoc}
     */
    public function buildTree(array $nodes, array $options = [])
    {
        return $this->getRepoUtils()->buildTree($nodes, $options);
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
     * {@inheritdoc}
     */
    public function buildTreeArray(array $nodes)
    {
        return $this->getRepoUtils()->buildTreeArray($nodes);
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
     * @return \Doctrine\MongoDB\Query\Builder - QueryBuilder object
     */
    abstract public function getRootNodesQueryBuilder($sortByField = null, $direction = 'asc');

    /**
     * Get all root nodes query
     *
     * @param string - Sort by field
     * @param string - Sort direction ("asc" or "desc")
     *
     * @return \Doctrine\MongoDB\Query\Query - Query object
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
     * @return \Doctrine\MongoDB\Query\Builder - QueryBuilder object
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
     * @return \Doctrine\MongoDB\Query\Query - Query object
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
     * @return \Doctrine\MongoDB\Query\Builder - QueryBuilder object
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
     * @return \Doctrine\MongoDB\Query\Query - Query object
     */
    abstract public function getChildrenQuery($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false);
}

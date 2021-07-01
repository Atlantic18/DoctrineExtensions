<?php

namespace Gedmo\Tree\Document\MongoDB\Repository;

use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Tool\Wrapper\MongoDocumentWrapper;
use Gedmo\Tree\Strategy;
use MongoDB\BSON\Regex;

/**
 * The MaterializedPathRepository has some useful functions
 * to interact with MaterializedPath tree. Repository uses
 * the strategy used by listener
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MaterializedPathRepository extends AbstractTreeRepository
{
    /**
     * Get tree query builder
     *
     * @param object $rootNode
     *
     * @return \Doctrine\ODM\MongoDB\Query\Builder
     */
    public function getTreeQueryBuilder($rootNode = null)
    {
        return $this->getChildrenQueryBuilder($rootNode, false, null, 'asc', true);
    }

    /**
     * Get tree query
     *
     * @param object $rootNode
     *
     * @return \Doctrine\ODM\MongoDB\Query\Query
     */
    public function getTreeQuery($rootNode = null)
    {
        return $this->getTreeQueryBuilder($rootNode)->getQuery();
    }

    /**
     * Get tree
     *
     * @param object $rootNode
     */
    public function getTree($rootNode = null): Iterator
    {
        return $this->getTreeQuery($rootNode)->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function getRootNodesQueryBuilder($sortByField = null, $direction = 'asc')
    {
        return $this->getChildrenQueryBuilder(null, true, $sortByField, $direction);
    }

    /**
     * {@inheritdoc}
     */
    public function getRootNodesQuery($sortByField = null, $direction = 'asc')
    {
        return $this->getRootNodesQueryBuilder($sortByField, $direction)->getQuery();
    }

    /**
     * {@inheritdoc}
     */
    public function getRootNodes($sortByField = null, $direction = 'asc')
    {
        return $this->getRootNodesQuery($sortByField, $direction)->execute();
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

            $wrapped = new MongoDocumentWrapper($node, $this->dm);

            if (!$wrapped->hasValidIdentifier()) {
                throw new InvalidArgumentException('Node is not managed by UnitOfWork');
            }
        }

        $qb = $this->getChildrenQueryBuilder($node, $direct);

        $qb->count();

        return (int) $qb->getQuery()->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function getChildrenQueryBuilder($node = null, $direct = false, $sortByField = null, $direction = 'asc', $includeNode = false)
    {
        $meta = $this->getClassMetadata();
        $config = $this->getTreeListener()->getConfiguration($this->dm, $meta->name);
        $separator = preg_quote($config['path_separator']);
        $qb = $this->dm->createQueryBuilder()
            ->find($meta->name);
        $regex = false;

        if (is_object($node) && $node instanceof $meta->name) {
            $node = new MongoDocumentWrapper($node, $this->dm);
            $nodePath = preg_quote($node->getPropertyValue($config['path']));

            if ($direct) {
                $regex = sprintf('^%s([^%s]+%s)'.($includeNode ? '?' : '').'$',
                     $nodePath,
                     $separator,
                     $separator);
            } else {
                $regex = sprintf('^%s(.+)'.($includeNode ? '?' : ''),
                     $nodePath);
            }
        } elseif ($direct) {
            $regex = sprintf('^([^%s]+)'.($includeNode ? '?' : '').'%s$',
                $separator,
                $separator);
        }

        if ($regex) {
            $qb->field($config['path'])->equals(new Regex($regex));
        }

        $qb->sort(is_null($sortByField) ? $config['path'] : $sortByField, 'asc' === $direction ? 'asc' : 'desc');

        return $qb;
    }

    /**
     * G{@inheritdoc}
     */
    public function getChildrenQuery($node = null, $direct = false, $sortByField = null, $direction = 'asc', $includeNode = false)
    {
        return $this->getChildrenQueryBuilder($node, $direct, $sortByField, $direction, $includeNode)->getQuery();
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren($node = null, $direct = false, $sortByField = null, $direction = 'asc', $includeNode = false)
    {
        return $this->getChildrenQuery($node, $direct, $sortByField, $direction, $includeNode)->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function getNodesHierarchyQueryBuilder($node = null, $direct = false, array $options = [], $includeNode = false)
    {
        $sortBy = [
            'field' => null,
            'dir' => 'asc',
        ];

        if (isset($options['childSort'])) {
            $sortBy = array_merge($sortBy, $options['childSort']);
        }

        return $this->getChildrenQueryBuilder($node, $direct, $sortBy['field'], $sortBy['dir'], $includeNode);
    }

    /**
     * {@inheritdoc}
     */
    public function getNodesHierarchyQuery($node = null, $direct = false, array $options = [], $includeNode = false)
    {
        return $this->getNodesHierarchyQueryBuilder($node, $direct, $options, $includeNode)->getQuery();
    }

    /**
     * {@inheritdoc}
     */
    public function getNodesHierarchy($node = null, $direct = false, array $options = [], $includeNode = false)
    {
        $query = $this->getNodesHierarchyQuery($node, $direct, $options, $includeNode);
        $query->setHydrate(false);

        return $query->toArray();
    }

    /**
     * {@inheritdoc}
     */
    protected function validate()
    {
        return Strategy::MATERIALIZED_PATH === $this->getTreeListener()->getStrategy($this->dm, $this->getClassMetadata()->name)->getName();
    }
}

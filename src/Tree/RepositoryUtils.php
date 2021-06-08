<?php

namespace Gedmo\Tree;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Exception\InvalidArgumentException;

class RepositoryUtils implements RepositoryUtilsInterface
{
    /** @var \Doctrine\Persistence\Mapping\ClassMetadata */
    protected $meta;

    /** @var \Gedmo\Tree\TreeListener */
    protected $listener;

    /** @var \Doctrine\Persistence\ObjectManager */
    protected $om;

    /** @var \Gedmo\Tree\RepositoryInterface */
    protected $repo;

    /**
     * This index is used to hold the children of a node
     * when using any of the buildTree related methods.
     *
     * @var string
     */
    protected $childrenIndex = '__children';

    public function __construct(ObjectManager $om, ClassMetadata $meta, $listener, $repo)
    {
        $this->om = $om;
        $this->meta = $meta;
        $this->listener = $listener;
        $this->repo = $repo;
    }

    public function getClassMetadata()
    {
        return $this->meta;
    }

    /**
     * {@inheritdoc}
     */
    public function childrenHierarchy($node = null, $direct = false, array $options = [], $includeNode = false)
    {
        $meta = $this->getClassMetadata();

        if (null !== $node) {
            if ($node instanceof $meta->name) {
                $wrapperClass = $this->om instanceof \Doctrine\ORM\EntityManagerInterface ?
                    '\Gedmo\Tool\Wrapper\EntityWrapper' :
                    '\Gedmo\Tool\Wrapper\MongoDocumentWrapper';
                $wrapped = new $wrapperClass($node, $this->om);
                if (!$wrapped->hasValidIdentifier()) {
                    throw new InvalidArgumentException('Node is not managed by UnitOfWork');
                }
            }
        } else {
            $includeNode = true;
        }

        // Gets the array of $node results. It must be ordered by depth
        $nodes = $this->repo->getNodesHierarchy($node, $direct, $options, $includeNode);

        return $this->repo->buildTree($nodes, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function buildTree(array $nodes, array $options = [])
    {
        $meta = $this->getClassMetadata();
        $nestedTree = $this->repo->buildTreeArray($nodes);

        $default = [
            'decorate' => false,
            'rootOpen' => '<ul>',
            'rootClose' => '</ul>',
            'childOpen' => '<li>',
            'childClose' => '</li>',
            'nodeDecorator' => function ($node) use ($meta) {
                // override and change it, guessing which field to use
                if ($meta->hasField('title')) {
                    $field = 'title';
                } elseif ($meta->hasField('name')) {
                    $field = 'name';
                } else {
                    throw new InvalidArgumentException('Cannot find any representation field');
                }

                return $node[$field];
            },
        ];
        $options = array_merge($default, $options);
        // If you don't want any html output it will return the nested array
        if (!$options['decorate']) {
            return $nestedTree;
        }

        if (!count($nestedTree)) {
            return '';
        }

        $childrenIndex = $this->childrenIndex;

        $build = function ($tree) use (&$build, &$options, $childrenIndex) {
            $output = is_string($options['rootOpen']) ? $options['rootOpen'] : $options['rootOpen']($tree);
            foreach ($tree as $node) {
                $output .= is_string($options['childOpen']) ? $options['childOpen'] : $options['childOpen']($node);
                $output .= $options['nodeDecorator']($node);
                if ((is_array($node[$childrenIndex]) || $node[$childrenIndex] instanceof \Countable ? count($node[$childrenIndex]) : 0) > 0) {
                    $output .= $build($node[$childrenIndex]);
                }
                $output .= is_string($options['childClose']) ? $options['childClose'] : $options['childClose']($node);
            }

            return $output.(is_string($options['rootClose']) ? $options['rootClose'] : $options['rootClose']($tree));
        };

        return $build($nestedTree);
    }

    /**
     * {@inheritdoc}
     */
    public function buildTreeArray(array $nodes)
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->om, $meta->name);
        $nestedTree = [];
        $l = 0;

        if (count($nodes) > 0) {
            // Node Stack. Used to help building the hierarchy
            $stack = [];
            foreach ($nodes as $child) {
                $item = $child;
                $item[$this->childrenIndex] = [];
                // Number of stack items
                $l = count($stack);
                // Check if we're dealing with different levels
                while ($l > 0 && $stack[$l - 1][$config['level']] >= $item[$config['level']]) {
                    array_pop($stack);
                    --$l;
                }
                // Stack is empty (we are inspecting the root)
                if (0 == $l) {
                    // Assigning the root child
                    $i = count($nestedTree);
                    $nestedTree[$i] = $item;
                    $stack[] = &$nestedTree[$i];
                } else {
                    // Add child to parent
                    $i = is_array($stack[$l - 1][$this->childrenIndex]) || $stack[$l - 1][$this->childrenIndex] instanceof \Countable ? count($stack[$l - 1][$this->childrenIndex]) : 0;
                    $stack[$l - 1][$this->childrenIndex][$i] = $item;
                    $stack[] = &$stack[$l - 1][$this->childrenIndex][$i];
                }
            }
        }

        return $nestedTree;
    }

    /**
     * {@inheritdoc}
     */
    public function setChildrenIndex($childrenIndex)
    {
        $this->childrenIndex = $childrenIndex;
    }

    /**
     * {@inheritdoc}
     */
    public function getChildrenIndex()
    {
        return $this->childrenIndex;
    }
}

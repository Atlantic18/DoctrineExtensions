<?php

namespace Gedmo\Tree\Strategy\ORM;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Version;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Common\Persistence\ObjectManager;
use Gedmo\Tree\Strategy;
use Gedmo\Tree\TreeListener;
use Gedmo\Tool\Wrapper\AbstractWrapper;
use Gedmo\Exception\RuntimeException;
use Gedmo\Mapping\Event\AdapterInterface;

/**
 * This strategy makes tree act like
 * a closure table.
 *
 * @author Gustavo Adrian <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Closure implements Strategy
{
    /**
     * TreeListener
     *
     * @var TreeListener
     */
    protected $listener = null;

    /**
     * List of pending Nodes, which needs to
     * be post processed because of having a parent Node
     * which requires some additional calculations
     *
     * @var array
     */
    private $pendingChildNodeInserts = array();

    /**
     * List of nodes which has their parents updated, but using
     * new nodes. They have to wait until their parents are inserted
     * on DB to make the update
     *
     * @var array
     */
    private $pendingNodeUpdates = array();

    /**
     * List of pending Nodes, which needs their "level"
     * field value set
     *
     * @var array
     */
    private $pendingNodesLevelProcess = array();

    /**
     * {@inheritdoc}
     */
    public function __construct(TreeListener $listener)
    {
        $this->listener = $listener;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Strategy::CLOSURE;
    }

    /**
     * {@inheritdoc}
     */
    public function processMetadataLoad($em, $meta)
    {
        $config = $this->listener->getConfiguration($em, $meta->name);
        $closureMetadata = $em->getClassMetadata($config['closure']);
        $cmf = $em->getMetadataFactory();

        if (!$closureMetadata->hasAssociation('ancestor')) {
            // create ancestor mapping
            $ancestorMapping = array(
                'fieldName' => 'ancestor',
                'id' => false,
                'joinColumns' => array(
                    array(
                        'name' => 'ancestor',
                        'referencedColumnName' => 'id',
                        'unique' => false,
                        'nullable' => false,
                        'onDelete' => 'CASCADE',
                        'onUpdate' => null,
                        'columnDefinition' => null,
                    ),
                ),
                'inversedBy' => null,
                'targetEntity' => $meta->name,
                'cascade' => null,
                'fetch' => ClassMetadataInfo::FETCH_LAZY,
            );
            $closureMetadata->mapManyToOne($ancestorMapping);
            if (Version::compare('2.3.0-dev') <= 0) {
                $closureMetadata->reflFields['ancestor'] = $cmf
                    ->getReflectionService()
                    ->getAccessibleProperty($closureMetadata->name, 'ancestor')
                ;
            }
        }

        if (!$closureMetadata->hasAssociation('descendant')) {
            // create descendant mapping
            $descendantMapping = array(
                'fieldName' => 'descendant',
                'id' => false,
                'joinColumns' => array(
                    array(
                        'name' => 'descendant',
                        'referencedColumnName' => 'id',
                        'unique' => false,
                        'nullable' => false,
                        'onDelete' => 'CASCADE',
                        'onUpdate' => null,
                        'columnDefinition' => null,
                    ),
                ),
                'inversedBy' => null,
                'targetEntity' => $meta->name,
                'cascade' => null,
                'fetch' => ClassMetadataInfo::FETCH_LAZY,
            );
            $closureMetadata->mapManyToOne($descendantMapping);
            if (Version::compare('2.3.0-dev') <= 0) {
                $closureMetadata->reflFields['descendant'] = $cmf
                    ->getReflectionService()
                    ->getAccessibleProperty($closureMetadata->name, 'descendant')
                ;
            }
        }
        // create unique index on ancestor and descendant
        $indexName = substr(strtoupper("IDX_".md5($closureMetadata->name)), 0, 20);
        $closureMetadata->table['uniqueConstraints'][$indexName] = array(
            'columns' => array(
                $this->getJoinColumnFieldName($em->getClassMetadata($config['closure'])->getAssociationMapping('ancestor')),
                $this->getJoinColumnFieldName($em->getClassMetadata($config['closure'])->getAssociationMapping('descendant')),
            ),
        );
        // this one may not be very useful
        $indexName = substr(strtoupper("IDX_".md5($meta->name.'depth')), 0, 20);
        $closureMetadata->table['indexes'][$indexName] = array(
            'columns' => array('depth'),
        );
        if ($cacheDriver = $cmf->getCacheDriver()) {
            $cacheDriver->save($closureMetadata->name."\$CLASSMETADATA", $closureMetadata, null);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onFlushEnd($em, AdapterInterface $ea)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function processPrePersist($em, $node)
    {
        $this->pendingChildNodeInserts[spl_object_hash($em)][spl_object_hash($node)] = $node;
    }

    /**
     * {@inheritdoc}
     */
    public function processPreUpdate($em, $node)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function processPreRemove($em, $node)
    {
    }

     /**
     * {@inheritdoc}
     */
    public function processScheduledInsertion($em, $node, AdapterInterface $ea)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function processScheduledDelete($em, $entity)
    {
    }

    protected function getJoinColumnFieldName($association)
    {
        if (count($association['joinColumnFieldNames']) > 1) {
            throw new RuntimeException('More association on field '.$association['fieldName']);
        }

        return array_shift($association['joinColumnFieldNames']);
    }

    /**
     * {@inheritdoc}
     */
    public function processPostUpdate($em, $entity, AdapterInterface $ea)
    {
        $meta = $em->getClassMetadata(get_class($entity));
        $config = $this->listener->getConfiguration($em, $meta->name);

        // Process TreeLevel field value
        if (!empty($config)) {
            $this->setLevelFieldOnPendingNodes($em);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processPostRemove($em, $entity, AdapterInterface $ea)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function processPostPersist($em, $entity, AdapterInterface $ea)
    {
        $uow = $em->getUnitOfWork();
        $emHash = spl_object_hash($em);

        while ($node = array_shift($this->pendingChildNodeInserts[$emHash])) {
            $meta = $em->getClassMetadata(get_class($node));
            $config = $this->listener->getConfiguration($em, $meta->name);

            $identifier = $meta->getSingleIdentifierFieldName();
            $nodeId = $meta->getReflectionProperty($identifier)->getValue($node);
            $parent = $meta->getReflectionProperty($config['parent'])->getValue($node);

            $closureClass = $config['closure'];
            $closureMeta = $em->getClassMetadata($closureClass);
            $closureTable = $closureMeta->getTableName();

            $ancestorColumnName = $this->getJoinColumnFieldName($em->getClassMetadata($config['closure'])->getAssociationMapping('ancestor'));
            $descendantColumnName = $this->getJoinColumnFieldName($em->getClassMetadata($config['closure'])->getAssociationMapping('descendant'));
            $depthColumnName = $em->getClassMetadata($config['closure'])->getColumnName('depth');

            $entries = array(
                array(
                    $ancestorColumnName => $nodeId,
                    $descendantColumnName => $nodeId,
                    $depthColumnName => 0,
                ),
            );

            if ($parent) {
                $dql = "SELECT c, a FROM {$closureMeta->name} c";
                $dql .= " JOIN c.ancestor a";
                $dql .= " WHERE c.descendant = :parent";
                $q = $em->createQuery($dql);
                $q->setParameters(compact('parent'));
                $ancestors = $q->getArrayResult();

                foreach ($ancestors as $ancestor) {
                    $entries[] = array(
                        $ancestorColumnName => $ancestor['ancestor'][$identifier],
                        $descendantColumnName => $nodeId,
                        $depthColumnName => $ancestor['depth'] + 1,
                    );
                }

                if (isset($config['level'])) {
                    $this->pendingNodesLevelProcess[$nodeId] = $node;
                }
            } elseif (isset($config['level'])) {
                $uow->scheduleExtraUpdate($node, array($config['level'] => array(null, 1)));
                $ea->setOriginalObjectProperty($uow, spl_object_hash($node), $config['level'], 1);
                $levelProp = $meta->getReflectionProperty($config['level']);
                $levelProp->setValue($node, 1);
            }

            foreach ($entries as $closure) {
                if (!$em->getConnection()->insert($closureTable, $closure)) {
                    throw new RuntimeException('Failed to insert new Closure record');
                }
            }
        }

        // Process pending node updates
        if (!empty($this->pendingNodeUpdates)) {
            foreach ($this->pendingNodeUpdates as $info) {
                $this->updateNode($em, $info['node'], $info['oldParent']);
            }

            $this->pendingNodeUpdates = array();
        }

        // Process TreeLevel field value
        $this->setLevelFieldOnPendingNodes($em);
    }

    /**
     * Process pending entities to set their "level" value
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $em
     */
    protected function setLevelFieldOnPendingNodes(ObjectManager $em)
    {
        if (!empty($this->pendingNodesLevelProcess)) {
            $first = array_slice($this->pendingNodesLevelProcess, 0, 1);
            $first = array_shift($first);
            $meta = $em->getClassMetadata(get_class($first));
            unset($first);
            $identifier = $meta->getIdentifier();
            $mapping = $meta->getFieldMapping($identifier[0]);
            $config = $this->listener->getConfiguration($em, $meta->name);
            $closureClass = $config['closure'];
            $closureMeta = $em->getClassMetadata($closureClass);
            $uow = $em->getUnitOfWork();

            foreach ($this->pendingNodesLevelProcess as $node) {
                $children = $em->getRepository($meta->name)->children($node);

                foreach ($children as $child) {
                    $this->pendingNodesLevelProcess[AbstractWrapper::wrap($child, $em)->getIdentifier()] = $child;
                }
            }

            // Avoid type conversion performance penalty
            $type = 'integer' === $mapping['type'] ? Connection::PARAM_INT_ARRAY : Connection::PARAM_STR_ARRAY;

            // We calculate levels for all nodes
            $sql = 'SELECT c.descendant, MAX(c.depth) + 1 AS levelNum ';
            $sql .= 'FROM '.$closureMeta->getTableName().' c ';
            $sql .= 'WHERE c.descendant IN (?) ';
            $sql .= 'GROUP BY c.descendant';

            $levelsAssoc = $em->getConnection()->executeQuery($sql, array(array_keys($this->pendingNodesLevelProcess)), array($type))->fetchAll(\PDO::FETCH_NUM);

            //create key pair array with resultset
            $levels = array();
            foreach( $levelsAssoc as $level )
            {
                $levels[$level[0]] = $level[1];
            }
            $levelsAssoc = null;

            // Now we update levels
            foreach ($this->pendingNodesLevelProcess as $nodeId => $node) {
                // Update new level
                $level = $levels[$nodeId];
                $levelProp = $meta->getReflectionProperty($config['level']);
                $uow->scheduleExtraUpdate(
                    $node,
                    array($config['level'] => array(
                        $levelProp->getValue($node), $level,
                    ))
                );
                $levelProp->setValue($node, $level);
                $uow->setOriginalEntityProperty(spl_object_hash($node), $config['level'], $level);
            }

            $this->pendingNodesLevelProcess = array();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processScheduledUpdate($em, $node, AdapterInterface $ea)
    {
        $meta = $em->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($em, $meta->name);
        $uow = $em->getUnitOfWork();
        $changeSet = $uow->getEntityChangeSet($node);

        if (array_key_exists($config['parent'], $changeSet)) {
            // If new parent is new, we need to delay the update of the node
            // until it is inserted on DB
            $parent = $changeSet[$config['parent']][1] ? AbstractWrapper::wrap($changeSet[$config['parent']][1], $em) : null;

            if ($parent && !$parent->getIdentifier()) {
                $this->pendingNodeUpdates[spl_object_hash($node)] = array(
                    'node'      => $node,
                    'oldParent' => $changeSet[$config['parent']][0],
                );
            } else {
                $this->updateNode($em, $node, $changeSet[$config['parent']][0]);
            }
        }
    }

    /**
     * Update node and closures
     *
     * @param EntityManagerInterface $em
     * @param object        $node
     * @param object        $oldParent
     */
    public function updateNode(EntityManagerInterface $em, $node, $oldParent)
    {
        $wrapped = AbstractWrapper::wrap($node, $em);
        $meta = $wrapped->getMetadata();
        $config = $this->listener->getConfiguration($em, $meta->name);
        $closureMeta = $em->getClassMetadata($config['closure']);

        $nodeId = $wrapped->getIdentifier();
        $parent = $wrapped->getPropertyValue($config['parent']);
        $table = $closureMeta->getTableName();
        $conn = $em->getConnection();
        // ensure integrity
        if ($parent) {
            $dql = "SELECT COUNT(c) FROM {$closureMeta->name} c";
            $dql .= " WHERE c.ancestor = :node";
            $dql .= " AND c.descendant = :parent";
            $q = $em->createQuery($dql);
            $q->setParameters(compact('node', 'parent'));
            if ($q->getSingleScalarResult()) {
                throw new \Gedmo\Exception\UnexpectedValueException("Cannot set child as parent to node: {$nodeId}");
            }
        }

        if ($oldParent) {
            $subQuery = "SELECT c2.id FROM {$table} c1";
            $subQuery .= " JOIN {$table} c2 ON c1.descendant = c2.descendant";
            $subQuery .= " WHERE c1.ancestor = :nodeId AND c2.depth > c1.depth";

            $ids = $conn->executeQuery($subQuery, compact('nodeId'))->fetchAll(\PDO::FETCH_COLUMN);
            if ($ids) {            
                // using subquery directly, sqlite acts unfriendly
                $query = "DELETE FROM {$table} WHERE id IN (".implode(', ', $ids).")";
                if (!empty($ids) && !$conn->executeUpdate($query)) {
                    throw new RuntimeException('Failed to remove old closures');
                }
            }
        }

        if ($parent) {
            $wrappedParent = AbstractWrapper::wrap($parent, $em);
            $parentId = $wrappedParent->getIdentifier();
            $query = "SELECT c1.ancestor, c2.descendant, (c1.depth + c2.depth + 1) AS depth";
            $query .= " FROM {$table} c1, {$table} c2";
            $query .= " WHERE c1.descendant = :parentId";
            $query .= " AND c2.ancestor = :nodeId";

            $closures = $conn->fetchAll($query, compact('nodeId', 'parentId'));

            foreach ($closures as $closure) {
                if (!$conn->insert($table, $closure)) {
                    throw new RuntimeException('Failed to insert new Closure record');
                }
            }
        }

        if (isset($config['level'])) {
            $this->pendingNodesLevelProcess[$nodeId] = $node;
        }
    }
}

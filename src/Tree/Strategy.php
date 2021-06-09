<?php

namespace Gedmo\Tree;

use Doctrine\Persistence\ObjectManager;
use Gedmo\Mapping\Event\AdapterInterface;

interface Strategy
{
    /**
     * NestedSet strategy
     */
    public const NESTED = 'nested';

    /**
     * Closure strategy
     */
    public const CLOSURE = 'closure';

    /**
     * Materialized Path strategy
     */
    public const MATERIALIZED_PATH = 'materializedPath';

    /**
     * Get the name of strategy
     *
     * @return string
     */
    public function getName();

    /**
     * Initialize strategy with tree listener
     */
    public function __construct(TreeListener $listener);

    /**
     * Operations after metadata is loaded
     *
     * @param ObjectManager $om
     * @param object        $meta
     */
    public function processMetadataLoad($om, $meta);

    /**
     * Operations on tree node insertion
     *
     * @param ObjectManager    $om     - object manager
     * @param object           $object - node
     * @param AdapterInterface $ea     - event adapter
     *
     * @return void
     */
    public function processScheduledInsertion($om, $object, AdapterInterface $ea);

    /**
     * Operations on tree node updates
     *
     * @param ObjectManager    $om     - object manager
     * @param object           $object - node
     * @param AdapterInterface $ea     - event adapter
     *
     * @return void
     */
    public function processScheduledUpdate($om, $object, AdapterInterface $ea);

    /**
     * Operations on tree node delete
     *
     * @param ObjectManager $om     - object manager
     * @param object        $object - node
     *
     * @return void
     */
    public function processScheduledDelete($om, $object);

    /**
     * Operations on tree node removal
     *
     * @param ObjectManager $om     - object manager
     * @param object        $object - node
     *
     * @return void
     */
    public function processPreRemove($om, $object);

    /**
     * Operations on tree node persist
     *
     * @param ObjectManager $om     - object manager
     * @param object        $object - node
     *
     * @return void
     */
    public function processPrePersist($om, $object);

    /**
     * Operations on tree node update
     *
     * @param ObjectManager $om     - object manager
     * @param object        $object - node
     *
     * @return void
     */
    public function processPreUpdate($om, $object);

    /**
     * Operations on tree node insertions
     *
     * @param ObjectManager    $om     - object manager
     * @param object           $object - node
     * @param AdapterInterface $ea     - event adapter
     *
     * @return void
     */
    public function processPostPersist($om, $object, AdapterInterface $ea);

    /**
     * Operations on tree node updates
     *
     * @param ObjectManager    $om     - object manager
     * @param object           $object - node
     * @param AdapterInterface $ea     - event adapter
     *
     * @return void
     */
    public function processPostUpdate($om, $object, AdapterInterface $ea);

    /**
     * Operations on tree node removals
     *
     * @param ObjectManager    $om     - object manager
     * @param object           $object - node
     * @param AdapterInterface $ea     - event adapter
     *
     * @return void
     */
    public function processPostRemove($om, $object, AdapterInterface $ea);

    /**
     * Operations on the end of flush process
     *
     * @param ObjectManager    $om - object manager
     * @param AdapterInterface $ea - event adapter
     *
     * @return void
     */
    public function onFlushEnd($om, AdapterInterface $ea);
}

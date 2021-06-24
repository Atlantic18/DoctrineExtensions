<?php

namespace Gedmo\Tree\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Gedmo\Tree\RepositoryInterface;
use Gedmo\Tree\Traits\Repository\ORM\TreeRepositoryTrait;

abstract class AbstractTreeRepository extends EntityRepository implements RepositoryInterface
{
    use TreeRepositoryTrait;

    /**
     * {@inheritdoc}
     */
    public function __construct(EntityManager $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);

        $this->initializeTreeRepository($em, $class);
    }
}

<?php

namespace Gedmo\Tree\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Gedmo\Tree\RepositoryInterface;
use Gedmo\Tree\Traits\Repository\ORM\ClosureTreeRepositoryTrait;

/**
 * The ClosureTreeRepository has some useful functions
 * to interact with Closure tree. Repository uses
 * the strategy used by listener
 *
 * @author Gustavo Adrian <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ClosureTreeRepository extends EntityRepository implements RepositoryInterface
{
    use ClosureTreeRepositoryTrait;

    /** Alias for the level value used in the subquery of the getNodesHierarchy method */
    const SUBQUERY_LEVEL = 'level';

    /**
     * {@inheritdoc}
     */
    public function __construct(EntityManager $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);

        $this->initializeTreeRepository($em, $class);
    }
}

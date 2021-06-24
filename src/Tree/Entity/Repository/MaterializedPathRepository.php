<?php

namespace Gedmo\Tree\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Gedmo\Tree\RepositoryInterface;
use Gedmo\Tree\Traits\Repository\ORM\MaterializedPathRepositoryTrait;

/**
 * The MaterializedPathRepository has some useful functions
 * to interact with MaterializedPath tree. Repository uses
 * the strategy used by listener
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MaterializedPathRepository extends EntityRepository implements RepositoryInterface
{
    use MaterializedPathRepositoryTrait;

    /**
     * {@inheritdoc}
     */
    public function __construct(EntityManager $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);

        $this->initializeTreeRepository($em, $class);
    }
}

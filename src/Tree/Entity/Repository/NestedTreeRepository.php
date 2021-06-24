<?php

namespace Gedmo\Tree\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Gedmo\Tree\RepositoryInterface;
use Gedmo\Tree\Traits\Repository\ORM\NestedTreeRepositoryTrait;

/**
 * The NestedTreeRepository has some useful functions
 * to interact with NestedSet tree. Repository uses
 * the strategy used by listener
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 * @method persistAsFirstChild($node)
 * @method persistAsFirstChildOf($node, $parent)
 * @method persistAsLastChild($node)
 * @method persistAsLastChildOf($node, $parent)
 * @method persistAsNextSibling($node)
 * @method persistAsNextSiblingOf($node, $sibling)
 * @method persistAsPrevSibling($node)
 * @method persistAsPrevSiblingOf($node, $sibling)
 */
class NestedTreeRepository extends EntityRepository implements RepositoryInterface
{
    use NestedTreeRepositoryTrait;

    /**
     * {@inheritdoc}
     */
    public function __construct(EntityManager $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);

        $this->initializeTreeRepository($em, $class);
    }

    /**
     * {@inheritdoc}
     */
    public function __call($method, $args)
    {
        $result = $this->callTreeUtilMethods($method, $args);

        if (null !== $result)
        {
            return $result;
        }

        return parent::__call($method, $args);
    }
}

<?php

namespace Gedmo\Sortable\Entity\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Gedmo\Sortable\SortableListener;

/**
 * Sortable Repository
 *
 * @author Lukas Botsch <lukas.botsch@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SortableRepository extends EntityRepository
{
    /**
     * Sortable listener on event manager
     *
     * @var SortableListener
     */
    protected $listener = null;

    protected $config = null;
    protected $meta = null;

    public function __construct(EntityManagerInterface $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
        $sortableListener = null;
        foreach ($em->getEventManager()->getListeners() as $event => $listeners) {
            foreach ($listeners as $hash => $listener) {
                if ($listener instanceof SortableListener) {
                    $sortableListener = $listener;
                    break;
                }
            }
            if ($sortableListener) {
                break;
            }
        }

        if (is_null($sortableListener)) {
            throw new \Gedmo\Exception\InvalidMappingException('This repository can be attached only to ORM sortable listener');
        }

        $this->listener = $sortableListener;
        $this->meta = $this->getClassMetadata();
        $this->config = $this->listener->getConfiguration($this->_em, $this->meta->name);
    }

    public function getBySortableGroupsQuery(array $groupValues = [])
    {
        return $this->getBySortableGroupsQueryBuilder($groupValues)->getQuery();
    }

    public function getBySortableGroupsQueryBuilder(array $groupValues = [])
    {
        $groups = isset($this->config['groups']) ? array_combine(array_values($this->config['groups']), array_keys($this->config['groups'])) : [];
        foreach (array_keys($groupValues) as $name) {
            if (!in_array($name, $this->config['groups'])) {
                throw new \InvalidArgumentException('Sortable group "'.$name.'" is not defined in Entity '.$this->meta->name);
            }
            unset($groups[$name]);
        }
        if ((is_array($groups) || $groups instanceof \Countable ? count($groups) : 0) > 0) {
            throw new \InvalidArgumentException('You need to specify values for the following groups to select by sortable groups: '.implode(', ', array_keys($groups)));
        }

        $qb = $this->createQueryBuilder('n');
        $qb->orderBy('n.'.$this->config['position']);
        $i = 1;
        foreach ($groupValues as $group => $value) {
            $qb->andWhere('n.'.$group.' = :group'.$i)
               ->setParameter('group'.$i, $value);
            ++$i;
        }

        return $qb;
    }

    public function getBySortableGroups(array $groupValues = [])
    {
        $query = $this->getBySortableGroupsQuery($groupValues);

        return $query->getResult();
    }
}

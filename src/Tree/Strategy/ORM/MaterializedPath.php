<?php

namespace Gedmo\Tree\Strategy\ORM;

use Gedmo\Tool\Wrapper\AbstractWrapper;
use Gedmo\Tree\Strategy\AbstractMaterializedPath;

/**
 * This strategy makes tree using materialized path strategy
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MaterializedPath extends AbstractMaterializedPath
{
    /**
     * {@inheritdoc}
     */
    public function removeNode($om, $meta, $config, $node)
    {
        $uow = $om->getUnitOfWork();
        $wrapped = AbstractWrapper::wrap($node, $om);

        $path = addcslashes($wrapped->getPropertyValue($config['path']), '%');

        $separator = $config['path_ends_with_separator'] ? null : $config['path_separator'];

        // Remove node's children
        $qb = $om->createQueryBuilder();
        $qb->select('e')
            ->from($config['useObjectClass'], 'e')
            ->where($qb->expr()->like('e.'.$config['path'], $qb->expr()->literal($path.$separator.'%')));

        if (isset($config['level'])) {
            $lvlField = $config['level'];
            $lvl = $wrapped->getPropertyValue($lvlField);
            if (!empty($lvl)) {
                $qb->andWhere($qb->expr()->gt('e.'.$lvlField, $qb->expr()->literal($lvl)));
            }
        }

        $results = $qb->getQuery()
            ->execute();

        foreach ($results as $node) {
            $uow->scheduleForDelete($node);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren($om, $meta, $config, $path)
    {
        $path = addcslashes($path, '%');
        $qb = $om->createQueryBuilder($config['useObjectClass']);
        $qb->select('e')
            ->from($config['useObjectClass'], 'e')
            ->where($qb->expr()->like('e.'.$config['path'], $qb->expr()->literal($path.'%')))
            ->andWhere('e.'.$config['path'].' != :path')
            ->orderBy('e.'.$config['path'], 'asc');      // This may save some calls to updateNode
        $qb->setParameter('path', $path);

        return $qb->getQuery()
            ->execute();
    }
}

<?php

namespace Gedmo\SoftDeleteable\Query\TreeWalker\Exec;

use Doctrine\ORM\Query\Exec\MultiTableDeleteExecutor as BaseMultiTableDeleteExecutor;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * This class is used when a DELETE DQL query is called for entities
 * that are part of an inheritance tree
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

class MultiTableDeleteExecutor extends BaseMultiTableDeleteExecutor
{
    /**
     * {@inheritDoc}
     */
    public function __construct(Node $AST, $sqlWalker, ClassMetadataInfo $meta, AbstractPlatform $platform, array $config)
    {
        parent::__construct($AST, $sqlWalker);

        foreach ($this->_sqlStatements as $index => $stmt) {
            $matches = array();
            preg_match('/DELETE FROM (\w+) .+/', $stmt, $matches);

            if (isset($matches[1]) && $meta->getQuotedTableName($platform) === $matches[1]) {
                $sqlStatements[$index] = str_replace('DELETE FROM', 'UPDATE', $stmt);
                $sqlStatements[$index] = str_replace(
                    'WHERE',
                    'SET '.$config['fieldName'].' = '.$platform->getCurrentTimestampSQL().' WHERE',
                    $sqlStatements[$index]
                );
            } else {
                // We have to avoid the removal of registers of child entities of a SoftDeleteable entity
                unset($this->_sqlStatements[$index]);
            }
        }
    }
}

<?php

namespace Gedmo\Tree\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver;
use Gedmo\Mapping\Driver\File;
use Gedmo\Tree\Mapping\Validator;

/**
 * This is a yaml mapping driver for Tree
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specifically for Tree
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Yaml extends File implements Driver
{
    /**
     * File extension
     *
     * @var string
     */
    protected $_extension = '.dcm.yml';

    /**
     * List of tree strategies available
     *
     * @var array
     */
    private $strategies = [
        'nested',
        'closure',
        'materializedPath',
    ];

    /**
     * {@inheritdoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        $mapping = $this->_getMapping($meta->name);
        $validator = new Validator();

        if (isset($mapping['gedmo'])) {
            $classMapping = $mapping['gedmo'];
            if (isset($classMapping['tree']['type'])) {
                $strategy = $classMapping['tree']['type'];
                if (!in_array($strategy, $this->strategies)) {
                    throw new InvalidMappingException("Tree type: $strategy is not available.");
                }
                $config['strategy'] = $strategy;
                $config['activate_locking'] = $classMapping['tree']['activateLocking'] ?? false;
                $config['locking_timeout'] = isset($classMapping['tree']['lockingTimeout']) ?
                    (int) $classMapping['tree']['lockingTimeout'] : 3;

                if ($config['locking_timeout'] < 1) {
                    throw new InvalidMappingException('Tree Locking Timeout must be at least of 1 second.');
                }
            }
            if (isset($classMapping['tree']['closure'])) {
                if (!$class = $this->getRelatedClassName($meta, $classMapping['tree']['closure'])) {
                    throw new InvalidMappingException("Tree closure class: {$classMapping['tree']['closure']} does not exist.");
                }
                $config['closure'] = $class;
            }
        }

        if (isset($mapping['id'])) {
            foreach ($mapping['id'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo']) && in_array('treePathSource', $fieldMapping['gedmo'])) {
                    if (!$validator->isValidFieldForPathSource($meta, $field)) {
                        throw new InvalidMappingException("Tree PathSource field - [{$field}] type is not valid. It can be any of the integer variants, double, float or string in class - {$meta->name}");
                    }
                    $config['path_source'] = $field;
                }
            }
        }

        if (isset($mapping['fields'])) {
            foreach ($mapping['fields'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo'])) {
                    if (in_array('treeLeft', $fieldMapping['gedmo'])) {
                        if (!$validator->isValidField($meta, $field)) {
                            throw new InvalidMappingException("Tree left field - [{$field}] type is not valid and must be 'integer' in class - {$meta->name}");
                        }
                        $config['left'] = $field;
                    } elseif (in_array('treeRight', $fieldMapping['gedmo'])) {
                        if (!$validator->isValidField($meta, $field)) {
                            throw new InvalidMappingException("Tree right field - [{$field}] type is not valid and must be 'integer' in class - {$meta->name}");
                        }
                        $config['right'] = $field;
                    } elseif (in_array('treeLevel', $fieldMapping['gedmo'])) {
                        if (!$validator->isValidField($meta, $field)) {
                            throw new InvalidMappingException("Tree level field - [{$field}] type is not valid and must be 'integer' in class - {$meta->name}");
                        }
                        $config['level'] = $field;
                    } elseif (in_array('treeRoot', $fieldMapping['gedmo'])) {
                        if (!$validator->isValidFieldForRoot($meta, $field)) {
                            throw new InvalidMappingException("Tree root field - [{$field}] type is not valid and must be any of the 'integer' types or 'string' in class - {$meta->name}");
                        }
                        $config['root'] = $field;
                    } elseif (in_array('treePath', $fieldMapping['gedmo']) || isset($fieldMapping['gedmo']['treePath'])) {
                        if (!$validator->isValidFieldForPath($meta, $field)) {
                            throw new InvalidMappingException("Tree Path field - [{$field}] type is not valid. It must be string or text in class - {$meta->name}");
                        }

                        $treePathInfo = $fieldMapping['gedmo']['treePath'] ?? $fieldMapping['gedmo'][array_search('treePath', $fieldMapping['gedmo'])];

                        $separator = is_array($treePathInfo) && isset($treePathInfo['separator']) ? $treePathInfo['separator'] : '|';

                        if (strlen($separator) > 1) {
                            throw new InvalidMappingException("Tree Path field - [{$field}] Separator {$separator} is invalid. It must be only one character long.");
                        }

                        $appendId = is_array($treePathInfo) && isset($treePathInfo['appendId']) ? $treePathInfo['appendId'] : null;

                        if (is_array($treePathInfo) && isset($treePathInfo['startsWithSeparator'])) {
                            $startsWithSeparator = $treePathInfo['startsWithSeparator'];
                        } else {
                            $startsWithSeparator = false;
                        }

                        if (is_array($treePathInfo) && isset($treePathInfo['endsWithSeparator'])) {
                            $endsWithSeparator = $treePathInfo['endsWithSeparator'];
                        } else {
                            $endsWithSeparator = true;
                        }

                        $config['path'] = $field;
                        $config['path_separator'] = $separator;
                        $config['path_append_id'] = $appendId;
                        $config['path_starts_with_separator'] = $startsWithSeparator;
                        $config['path_ends_with_separator'] = $endsWithSeparator;
                    } elseif (in_array('treePathSource', $fieldMapping['gedmo'])) {
                        if (!$validator->isValidFieldForPathSource($meta, $field)) {
                            throw new InvalidMappingException("Tree PathSource field - [{$field}] type is not valid. It can be any of the integer variants, double, float or string in class - {$meta->name}");
                        }
                        $config['path_source'] = $field;
                    } elseif (in_array('treePathHash', $fieldMapping['gedmo'])) {
                        if (!$validator->isValidFieldForPathSource($meta, $field)) {
                            throw new InvalidMappingException("Tree PathHash field - [{$field}] type is not valid and must be 'string' in class - {$meta->name}");
                        }
                        $config['path_hash'] = $field;
                    } elseif (in_array('treeLockTime', $fieldMapping['gedmo'])) {
                        if (!$validator->isValidFieldForLocktime($meta, $field)) {
                            throw new InvalidMappingException("Tree LockTime field - [{$field}] type is not valid. It must be \"date\" in class - {$meta->name}");
                        }
                        $config['lock_time'] = $field;
                    } elseif (in_array('treeParent', $fieldMapping['gedmo'])) {
                        $config['parent'] = $field;
                    }
                }
            }
        }

        if (isset($config['activate_locking']) && $config['activate_locking'] && !isset($config['lock_time'])) {
            throw new InvalidMappingException('You need to map a date|datetime|timestamp field as the tree lock time field to activate locking support.');
        }

        if (isset($mapping['manyToOne'])) {
            foreach ($mapping['manyToOne'] as $field => $relationMapping) {
                if (isset($relationMapping['gedmo'])) {
                    if (in_array('treeParent', $relationMapping['gedmo'])) {
                        if (!$rel = $this->getRelatedClassName($meta, $relationMapping['targetEntity'])) {
                            throw new InvalidMappingException("Unable to find ancestor/parent child relation through ancestor field - [{$field}] in class - {$meta->name}");
                        }
                        $config['parent'] = $field;
                    }
                    if (in_array('treeRoot', $relationMapping['gedmo'])) {
                        if (!$rel = $this->getRelatedClassName($meta, $relationMapping['targetEntity'])) {
                            throw new InvalidMappingException("Unable to find root-descendant relation through root field - [{$field}] in class - {$meta->name}");
                        }
                        $config['root'] = $field;
                    }
                }
            }
        }

        if (!$meta->isMappedSuperclass && $config) {
            if (isset($config['strategy'])) {
                if (is_array($meta->identifier) && count($meta->identifier) > 1) {
                    throw new InvalidMappingException("Tree does not support composite identifiers in class - {$meta->name}");
                }
                $method = 'validate'.ucfirst($config['strategy']).'TreeMetadata';
                $validator->$method($meta, $config);
            } else {
                throw new InvalidMappingException("Cannot find Tree type for class: {$meta->name}");
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function _loadMappingFile($file)
    {
        return \Symfony\Component\Yaml\Yaml::parse(file_get_contents($file));
    }
}

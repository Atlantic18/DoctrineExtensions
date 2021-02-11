<?php

namespace Gedmo\Tree\Mapping\Driver;

use Gedmo\Mapping\Driver\Xml as BaseXml;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Tree\Mapping\Validator;

/**
 * This is a xml mapping driver for Tree
 * behavioral extension. Used for extraction of extended
 * metadata from xml specifically for Tree
 * extension.
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Miha Vrhovnik <miha.vrhovnik@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Xml extends BaseXml
{
    /**
     * List of tree strategies available
     *
     * @var array
     */
    private $strategies = array(
        'nested',
        'closure',
        'materializedPath',
    );

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        /**
         * @var \SimpleXmlElement $xml
         */
        $xml = $this->_getMapping($meta->name);
        $xmlDoctrine = $xml;
        $xml = $xml->children(self::GEDMO_NAMESPACE_URI);
        $validator = new Validator();

        if (isset($xml->tree) && $this->_isAttributeSet($xml->tree, 'type')) {
            $strategy = $this->_getAttribute($xml->tree, 'type');
            if (!in_array($strategy, $this->strategies)) {
                throw new InvalidMappingException("Tree type: $strategy is not available.");
            }
            $config['strategy'] = $strategy;
            $config['activate_locking'] = $this->_getAttribute($xml->tree, 'activate-locking') === 'true' ? true : false;

            if ($lockingTimeout = $this->_getAttribute($xml->tree, 'locking-timeout')) {
                $config['locking_timeout'] = (int) $lockingTimeout;

                if ($config['locking_timeout'] < 1) {
                    throw new InvalidMappingException("Tree Locking Timeout must be at least of 1 second.");
                }
            } else {
                $config['locking_timeout'] = 3;
            }
        }
        if (isset($xml->{'tree-closure'}) && $this->_isAttributeSet($xml->{'tree-closure'}, 'class')) {
            $class = $this->_getAttribute($xml->{'tree-closure'}, 'class');
            if (!$cl = $this->getRelatedClassName($meta, $class)) {
                throw new InvalidMappingException("Tree closure class: {$class} does not exist.");
            }
            $config['closure'] = $cl;
        }
        if (isset($xmlDoctrine->field)) {
            foreach ($xmlDoctrine->field as $mapping) {
                $mappingDoctrine = $mapping;
                $mapping = $mapping->children(self::GEDMO_NAMESPACE_URI);

                $field = $this->_getAttribute($mappingDoctrine, 'name');
                if (isset($mapping->{'tree-left'})) {
                    if (!$validator->isValidField($meta, $field)) {
                        throw new InvalidMappingException("Tree left field - [{$field}] type is not valid and must be 'integer' in class - {$meta->name}");
                    }
                    $config['left'] = $field;
                } elseif (isset($mapping->{'tree-right'})) {
                    if (!$validator->isValidField($meta, $field)) {
                        throw new InvalidMappingException("Tree right field - [{$field}] type is not valid and must be 'integer' in class - {$meta->name}");
                    }
                    $config['right'] = $field;
                } elseif (isset($mapping->{'tree-root'})) {
                    if (!$validator->isValidFieldForRoot($meta, $field)) {
                        throw new InvalidMappingException("Tree root field - [{$field}] type is not valid and must be any of the 'integer' types or 'string' in class - {$meta->name}");
                    }
                    $config['root'] = $field;
                } elseif (isset($mapping->{'tree-level'})) {
                    if (!$validator->isValidField($meta, $field)) {
                        throw new InvalidMappingException("Tree level field - [{$field}] type is not valid and must be 'integer' in class - {$meta->name}");
                    }
                    $config['level'] = $field;
                } elseif (isset($mapping->{'tree-path'})) {
                    if (!$validator->isValidFieldForPath($meta, $field)) {
                        throw new InvalidMappingException("Tree Path field - [{$field}] type is not valid. It must be string or text in class - {$meta->name}");
                    }

                    $separator = $this->_getAttribute($mapping->{'tree-path'}, 'separator');

                    if (strlen($separator) > 1) {
                        throw new InvalidMappingException("Tree Path field - [{$field}] Separator {$separator} is invalid. It must be only one character long.");
                    }

                    $appendId = $this->_getAttribute($mapping->{'tree-path'}, 'append_id');

                    if (!$appendId) {
                        $appendId = true;
                    } else {
                        $appendId = strtolower($appendId) == 'false' ? false : true;
                    }

                    $startsWithSeparator = $this->_getAttribute($mapping->{'tree-path'}, 'starts_with_separator');

                    if (!$startsWithSeparator) {
                        $startsWithSeparator = false;
                    } else {
                        $startsWithSeparator = strtolower($startsWithSeparator) == 'false' ? false : true;
                    }

                    $endsWithSeparator = $this->_getAttribute($mapping->{'tree-path'}, 'ends_with_separator');

                    if (!$endsWithSeparator) {
                        $endsWithSeparator = true;
                    } else {
                        $endsWithSeparator = strtolower($endsWithSeparator) == 'false' ? false : true;
                    }

                    $config['path'] = $field;
                    $config['path_separator'] = $separator;
                    $config['path_append_id'] = $appendId;
                    $config['path_starts_with_separator'] = $startsWithSeparator;
                    $config['path_ends_with_separator'] = $endsWithSeparator;
                } elseif (isset($mapping->{'tree-path-source'})) {
                    if (!$validator->isValidFieldForPathSource($meta, $field)) {
                        throw new InvalidMappingException("Tree PathSource field - [{$field}] type is not valid. It can be any of the integer variants, double, float or string in class - {$meta->name}");
                    }
                    $config['path_source'] = $field;
                } elseif (isset($mapping->{'tree-path-hash'})) {
                    if (!$validator->isValidFieldForPathSource($meta, $field)) {
                        throw new InvalidMappingException("Tree PathHash field - [{$field}] type is not valid and must be 'string' in class - {$meta->name}");
                    }
                    $config['path_hash'] = $field;
                } elseif (isset($mapping->{'tree-lock-time'})) {
                    if (!$validator->isValidFieldForLockTime($meta, $field)) {
                        throw new InvalidMappingException("Tree LockTime field - [{$field}] type is not valid. It must be \"date\" in class - {$meta->name}");
                    }
                    $config['lock_time'] = $field;
                }
            }
        }

        if (isset($config['activate_locking']) && $config['activate_locking'] && !isset($config['lock_time'])) {
            throw new InvalidMappingException("You need to map a date field as the tree lock time field to activate locking support.");
        }

        if ($xmlDoctrine->getName() == 'mapped-superclass') {
            if (isset($xmlDoctrine->{'many-to-one'})) {
                foreach ($xmlDoctrine->{'many-to-one'} as $manyToOneMapping) {
                    /**
                     * @var \SimpleXMLElement $manyToOneMapping
                     */
                    $manyToOneMappingDoctrine = $manyToOneMapping;
                    $manyToOneMapping = $manyToOneMapping->children(self::GEDMO_NAMESPACE_URI);
                    if (isset($manyToOneMapping->{'tree-parent'})) {
                        $field = $this->_getAttribute($manyToOneMappingDoctrine, 'field');
                        $targetEntity = $meta->associationMappings[$field]['targetEntity'];
                        if (!$cl = $this->getRelatedClassName($meta, $targetEntity)) {
                            throw new InvalidMappingException("Unable to find ancestor/parent child relation through ancestor field - [{$field}] in class - {$meta->name}");
                        }
                        $config['parent'] = $field;
                    }
                    if (isset($manyToOneMapping->{'tree-root'})) {
                        $field = $this->_getAttribute($manyToOneMappingDoctrine, 'field');
                        $targetEntity = $meta->associationMappings[$field]['targetEntity'];
                        if (!$cl = $this->getRelatedClassName($meta, $targetEntity)) {
                            throw new InvalidMappingException("Unable to find root descendant relation through root field - [{$field}] in class - {$meta->name}");
                        }
                        $config['root'] = $field;
                    }
                }
            } elseif (isset($xmlDoctrine->{'reference-one'})) {
                foreach ($xmlDoctrine->{'reference-one'} as $referenceOneMapping) {
                    /**
                     * @var \SimpleXMLElement $referenceOneMapping
                     */
                    $referenceOneMappingDoctrine = $referenceOneMapping;
                    $referenceOneMapping = $referenceOneMapping->children(self::GEDMO_NAMESPACE_URI);
                    if (isset($referenceOneMapping->{'tree-parent'})) {
                        $field = $this->_getAttribute($referenceOneMappingDoctrine, 'field');
                        if (!$cl = $this->getRelatedClassName($meta, $this->_getAttribute($referenceOneMappingDoctrine, 'target-document'))) {
                            throw new InvalidMappingException("Unable to find ancestor/parent child relation through ancestor field - [{$field}] in class - {$meta->name}");
                        }
                        $config['parent'] = $field;
                    }
                    if (isset($referenceOneMapping->{'tree-root'})) {
                        $field = $this->_getAttribute($referenceOneMappingDoctrine, 'field');
                        if (!$cl = $this->getRelatedClassName($meta, $this->_getAttribute($referenceOneMappingDoctrine, 'target-document'))) {
                            throw new InvalidMappingException("Unable to find root descendant relation through root field - [{$field}] in class - {$meta->name}");
                        }
                        $config['root'] = $field;
                    }
                }
            }
        } elseif ($xmlDoctrine->getName() == 'entity') {
            if (isset($xmlDoctrine->{'many-to-one'})) {
                foreach ($xmlDoctrine->{'many-to-one'} as $manyToOneMapping) {
                    /**
                     * @var \SimpleXMLElement $manyToOneMapping
                     */
                    $manyToOneMappingDoctrine = $manyToOneMapping;
                    $manyToOneMapping = $manyToOneMapping->children(self::GEDMO_NAMESPACE_URI);
                    if (isset($manyToOneMapping->{'tree-parent'})) {
                        $field = $this->_getAttribute($manyToOneMappingDoctrine, 'field');
                        $targetEntity = $meta->associationMappings[$field]['targetEntity'];
                        if (!$cl = $this->getRelatedClassName($meta, $targetEntity)) {
                            throw new InvalidMappingException("Unable to find ancestor/parent child relation through ancestor field - [{$field}] in class - {$meta->name}");
                        }
                        $config['parent'] = $field;
                    }
                    if (isset($manyToOneMapping->{'tree-root'})) {
                        $field = $this->_getAttribute($manyToOneMappingDoctrine, 'field');
                        $targetEntity = $meta->associationMappings[$field]['targetEntity'];
                        if (!$cl = $this->getRelatedClassName($meta, $targetEntity)) {
                            throw new InvalidMappingException("Unable to find root descendant relation through root field - [{$field}] in class - {$meta->name}");
                        }
                        $config['root'] = $field;
                    }
                }
            }
        } elseif ($xmlDoctrine->getName() == 'document') {
            if (isset($xmlDoctrine->{'reference-one'})) {
                foreach ($xmlDoctrine->{'reference-one'} as $referenceOneMapping) {
                    /**
                     * @var \SimpleXMLElement $referenceOneMapping
                     */
                    $referenceOneMappingDoctrine = $referenceOneMapping;
                    $referenceOneMapping = $referenceOneMapping->children(self::GEDMO_NAMESPACE_URI);
                    if (isset($referenceOneMapping->{'tree-parent'})) {
                        $field = $this->_getAttribute($referenceOneMappingDoctrine, 'field');
                        if (!$cl = $this->getRelatedClassName($meta, $this->_getAttribute($referenceOneMappingDoctrine, 'target-document'))) {
                            throw new InvalidMappingException("Unable to find ancestor/parent child relation through ancestor field - [{$field}] in class - {$meta->name}");
                        }
                        $config['parent'] = $field;
                    }
                    if (isset($referenceOneMapping->{'tree-root'})) {
                        $field = $this->_getAttribute($referenceOneMappingDoctrine, 'field');
                        if (!$cl = $this->getRelatedClassName($meta, $this->_getAttribute($referenceOneMappingDoctrine, 'target-document'))) {
                            throw new InvalidMappingException("Unable to find root descendant relation through root field - [{$field}] in class - {$meta->name}");
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
}

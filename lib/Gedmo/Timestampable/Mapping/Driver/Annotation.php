<?php

namespace Gedmo\Timestampable\Mapping\Driver;

use Gedmo\Mapping\Driver\AbstractAnnotationDriver,
    Doctrine\Common\Annotations\AnnotationReader,
    Gedmo\Exception\InvalidMappingException;

/**
 * This is an annotation mapping driver for Timestampable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specificaly for Timestampable
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation extends AbstractAnnotationDriver
{
    /**
     * Annotation field is timestampable
     */
    const TIMESTAMPABLE = 'Gedmo\\Mapping\\Annotation\\Timestampable';

    /**
     * List of types which are valid for timestamp
     *
     * @var array
     */
    protected $validTypes = array(
        'date',
        'time',
        'datetime',
        'datetimetz',
        'timestamp',
        'zenddate',
        'vardatetime',
        'integer'
    );

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config) {
        $class = $this->getMetaReflectionClass($meta);
        // property annotations
        foreach ($class->getProperties() as $property) {
            if ($meta->isMappedSuperclass && !$property->isPrivate() ||
                $meta->isInheritedField($property->name) ||
                isset($meta->associationMappings[$property->name]['inherited'])
            ) {
                continue;
            }
            if ($timestampable = $this->reader->getPropertyAnnotation($property, self::TIMESTAMPABLE)) {
                $field = $property->getName();
                if (!$meta->hasField($field)) {
                    throw new InvalidMappingException("Unable to find timestampable [{$field}] as mapped property in entity - {$meta->name}");
                }
                if (!$this->isValidField($meta, $field)) {
                    throw new InvalidMappingException("Field - [{$field}] type is not valid and must be 'date', 'datetime' or 'time' in class - {$meta->name}");
                }
                if (!in_array($timestampable->on, array('update', 'create', 'change'))) {
                    throw new InvalidMappingException("Field - [{$field}] trigger 'on' is not one of [update, create, change] in class - {$meta->name}");
                }
                if ($timestampable->on == 'change') {
                    if (!(isset($timestampable->field) xor isset($timestampable->fields))) {
                        throw new InvalidMappingException("Invalid parameters on property - {$field}, one of 'field' or 'fields' (not both) must be set on [change] trigger in class - {$meta->name}");
                    }
                    $field = array(
                        'field' => $field,
                        'trackedField' => $timestampable->field,
                        'trackedFields' => $timestampable->fields,
                        'value' => $timestampable->value,
                    );
                }
                // properties are unique and mapper checks that, no risk here
                $config[$timestampable->on][] = $field;
            }
        }
    }
}

<?php

namespace Gedmo\Blameable\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver\AbstractAnnotationDriver;

/**
 * This is an annotation mapping driver for Blameable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specifically for Blameable
 * extension.
 *
 * @author David Buchmann <mail@davidbu.ch>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation extends AbstractAnnotationDriver
{
    /**
     * Annotation field is blameable
     */
    public const BLAMEABLE = 'Gedmo\\Mapping\\Annotation\\Blameable';

    /**
     * List of types which are valid for blame
     *
     * @var array
     */
    protected $validTypes = [
        'one',
        'string',
        'int',
    ];

    /**
     * {@inheritdoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        $class = $this->getMetaReflectionClass($meta);
        // property annotations
        foreach ($class->getProperties() as $property) {
            if ($meta->isMappedSuperclass && !$property->isPrivate() ||
                $meta->isInheritedField($property->name) ||
                isset($meta->associationMappings[$property->name]['inherited'])
            ) {
                continue;
            }
            if ($blameable = $this->reader->getPropertyAnnotation($property, self::BLAMEABLE)) {
                $field = $property->getName();

                if (!$meta->hasField($field) && !$meta->hasAssociation($field)) {
                    throw new InvalidMappingException("Unable to find blameable [{$field}] as mapped property in entity - {$meta->name}");
                }
                if ($meta->hasField($field)) {
                    if (!$this->isValidField($meta, $field)) {
                        throw new InvalidMappingException("Field - [{$field}] type is not valid and must be 'string' or a one-to-many relation in class - {$meta->name}");
                    }
                } else {
                    // association
                    if (!$meta->isSingleValuedAssociation($field)) {
                        throw new InvalidMappingException("Association - [{$field}] is not valid, it must be a one-to-many relation or a string field - {$meta->name}");
                    }
                }
                if (!in_array($blameable->on, ['update', 'create', 'change'])) {
                    throw new InvalidMappingException("Field - [{$field}] trigger 'on' is not one of [update, create, change] in class - {$meta->name}");
                }
                if ('change' == $blameable->on) {
                    if (!isset($blameable->field)) {
                        throw new InvalidMappingException("Missing parameters on property - {$field}, field must be set on [change] trigger in class - {$meta->name}");
                    }
                    if (is_array($blameable->field) && isset($blameable->value)) {
                        throw new InvalidMappingException('Blameable extension does not support multiple value changeset detection yet.');
                    }
                    $field = [
                        'field' => $field,
                        'trackedField' => $blameable->field,
                        'value' => $blameable->value,
                    ];
                }
                // properties are unique and mapper checks that, no risk here
                $config[$blameable->on][] = $field;
            }
        }
    }
}

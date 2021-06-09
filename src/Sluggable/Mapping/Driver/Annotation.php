<?php

namespace Gedmo\Sluggable\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Annotation\SlugHandler;
use Gedmo\Mapping\Annotation\SlugHandlerOption;
use Gedmo\Mapping\Driver\AbstractAnnotationDriver;

/**
 * This is an annotation mapping driver for Sluggable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specifically for Sluggable
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation extends AbstractAnnotationDriver
{
    /**
     * Annotation to identify field as one which holds the slug
     * together with slug options
     */
    public const SLUG = 'Gedmo\\Mapping\\Annotation\\Slug';

    /**
     * SlugHandler extension annotation
     */
    public const HANDLER = 'Gedmo\\Mapping\\Annotation\\SlugHandler';

    /**
     * SlugHandler option annotation
     */
    public const HANDLER_OPTION = 'Gedmo\\Mapping\\Annotation\\SlugHandlerOption';

    /**
     * List of types which are valid for slug and sluggable fields
     *
     * @var array
     */
    protected $validTypes = [
        'string',
        'text',
        'integer',
        'int',
        'datetime',
        'datetimetz',
        'citext',
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
            $config = $this->retrieveSlug($meta, $config, $property, '');
        }

        // Embedded entity
        if (property_exists($meta, 'embeddedClasses') && $meta->embeddedClasses) {
            foreach ($meta->embeddedClasses as $propertyName => $embeddedClassInfo) {
                $embeddedClass = new \ReflectionClass($embeddedClassInfo['class']);
                foreach ($embeddedClass->getProperties() as $embeddedProperty) {
                    $config = $this->retrieveSlug($meta, $config, $embeddedProperty, $propertyName);
                }
            }
        }

        return $config;
    }

    /**
     * @param $meta
     * @param $property
     * @param $fieldNamePrefix
     *
     * @return array
     */
    private function retrieveSlug($meta, array &$config, $property, $fieldNamePrefix)
    {
        $fieldName = $fieldNamePrefix ? ($fieldNamePrefix.'.'.$property->getName()) : $property->getName();
        // slug property
        if ($slug = $this->reader->getPropertyAnnotation($property, self::SLUG)) {
            if (!$meta->hasField($fieldName)) {
                throw new InvalidMappingException("Unable to find slug [{$fieldName}] as mapped property in entity - {$meta->name}");
            }
            if (!$this->isValidField($meta, $fieldName)) {
                throw new InvalidMappingException("Cannot use field - [{$fieldName}] for slug storage, type is not valid and must be 'string' or 'text' in class - {$meta->name}");
            }
            // process slug handlers
            $handlers = [];
            if (is_array($slug->handlers) && $slug->handlers) {
                foreach ($slug->handlers as $handler) {
                    if (!$handler instanceof SlugHandler) {
                        throw new InvalidMappingException("SlugHandler: {$handler} should be instance of SlugHandler annotation in entity - {$meta->name}");
                    }
                    if (!strlen($handler->class)) {
                        throw new InvalidMappingException("SlugHandler class: {$handler->class} should be a valid class name in entity - {$meta->name}");
                    }
                    $class = $handler->class;
                    $handlers[$class] = [];
                    foreach ((array) $handler->options as $option) {
                        if (!$option instanceof SlugHandlerOption) {
                            throw new InvalidMappingException("SlugHandlerOption: {$option} should be instance of SlugHandlerOption annotation in entity - {$meta->name}");
                        }
                        if (!strlen($option->name)) {
                            throw new InvalidMappingException("SlugHandlerOption name: {$option->name} should be valid name in entity - {$meta->name}");
                        }
                        $handlers[$class][$option->name] = $option->value;
                    }
                    $class::validate($handlers[$class], $meta);
                }
            }
            // process slug fields
            if (empty($slug->fields) || !is_array($slug->fields)) {
                throw new InvalidMappingException("Slug must contain at least one field for slug generation in class - {$meta->name}");
            }
            foreach ($slug->fields as $slugField) {
                $slugFieldWithPrefix = $fieldNamePrefix ? ($fieldNamePrefix.'.'.$slugField) : $slugField;
                if (!$meta->hasField($slugFieldWithPrefix)) {
                    throw new InvalidMappingException("Unable to find slug [{$slugFieldWithPrefix}] as mapped property in entity - {$meta->name}");
                }
                if (!$this->isValidField($meta, $slugFieldWithPrefix)) {
                    throw new InvalidMappingException("Cannot use field - [{$slugFieldWithPrefix}] for slug storage, type is not valid and must be 'string' or 'text' in class - {$meta->name}");
                }
            }
            if (!is_bool($slug->updatable)) {
                throw new InvalidMappingException("Slug annotation [updatable], type is not valid and must be 'boolean' in class - {$meta->name}");
            }
            if (!is_bool($slug->unique)) {
                throw new InvalidMappingException("Slug annotation [unique], type is not valid and must be 'boolean' in class - {$meta->name}");
            }
            if (!empty($meta->identifier) && $meta->isIdentifier($fieldName) && !(bool) $slug->unique) {
                throw new InvalidMappingException("Identifier field - [{$fieldName}] slug must be unique in order to maintain primary key in class - {$meta->name}");
            }
            if (false === $slug->unique && $slug->unique_base) {
                throw new InvalidMappingException("Slug annotation [unique_base] can not be set if unique is unset or 'false'");
            }
            if ($slug->unique_base && !$meta->hasField($slug->unique_base) && !$meta->hasAssociation($slug->unique_base)) {
                throw new InvalidMappingException("Unable to find [{$slug->unique_base}] as mapped property in entity - {$meta->name}");
            }
            $sluggableFields = [];
            foreach ($slug->fields as $field) {
                $sluggableFields[] = $fieldNamePrefix ? ($fieldNamePrefix.'.'.$field) : $field;
            }

            // set all options
            $config['slugs'][$fieldName] = [
                'fields' => $sluggableFields,
                'slug' => $fieldName,
                'style' => $slug->style,
                'dateFormat' => $slug->dateFormat,
                'updatable' => $slug->updatable,
                'unique' => $slug->unique,
                'unique_base' => $slug->unique_base,
                'separator' => $slug->separator,
                'prefix' => $slug->prefix,
                'suffix' => $slug->suffix,
                'handlers' => $handlers,
            ];
        }

        return $config;
    }
}

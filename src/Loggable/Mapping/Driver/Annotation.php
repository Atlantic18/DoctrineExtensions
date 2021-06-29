<?php

namespace Gedmo\Loggable\Mapping\Driver;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver\AbstractAnnotationDriver;

/**
 * This is an annotation mapping driver for Loggable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specifically for Loggable
 * extension.
 *
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation extends AbstractAnnotationDriver
{
    /**
     * Annotation to define that this object is loggable
     */
    const LOGGABLE = 'Gedmo\\Mapping\\Annotation\\Loggable';

    /**
     * Annotation to define that this property is versioned
     */
    const VERSIONED = 'Gedmo\\Mapping\\Annotation\\Versioned';

    /**
     * {@inheritdoc}
     */
    public function validateFullMetadata(ClassMetadata $meta, array $config)
    {
        if ($config && $meta instanceof \Doctrine\ODM\MongoDB\Mapping\ClassMetadata && is_array($meta->identifier) && count($meta->identifier) > 1) {
            throw new InvalidMappingException("Loggable does not support composite identifiers in class - {$meta->name}");
        }
        if (isset($config['versioned']) && !isset($config['loggable'])) {
            throw new InvalidMappingException("Class must be annotated with Loggable annotation in order to track versioned fields in class - {$meta->name}");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        $class = $this->getMetaReflectionClass($meta);
        // class annotations
        if ($annot = $this->reader->getClassAnnotation($class, self::LOGGABLE)) {
            $config['loggable'] = true;
            if ($annot->logEntryClass) {
                if (!$cl = $this->getRelatedClassName($meta, $annot->logEntryClass)) {
                    throw new InvalidMappingException("LogEntry class: {$annot->logEntryClass} does not exist.");
                }
                $config['logEntryClass'] = $cl;
            }
        }

        // property annotations
        foreach ($class->getProperties() as $property) {
            $field = $property->getName();
            if ($meta->isMappedSuperclass && !$property->isPrivate()) {
                continue;
            }

            // versioned property
            if ($this->reader->getPropertyAnnotation($property, self::VERSIONED)) {
                if (!$this->isMappingValid($meta, $field)) {
                    throw new InvalidMappingException("Cannot apply versioning to field [{$field}] as it is collection in object - {$meta->name}");
                }
                if (isset($meta->embeddedClasses[$field])) {
                    $this->inspectEmbeddedForVersioned($field, $config, $meta);
                    continue;
                }
                // fields cannot be overrided and throws mapping exception
                if (!(isset($config['versioned']) && in_array($field, $config['versioned']))) {
                    $config['versioned'][] = $field;
                }
            }
        }

        if (!$meta->isMappedSuperclass && $config) {
            if ($meta instanceof \Doctrine\ODM\MongoDB\Mapping\ClassMetadata && is_array($meta->identifier) && count($meta->identifier) > 1) {
                throw new InvalidMappingException("Loggable does not support composite identifiers in class - {$meta->name}");
            }
            if ($this->isClassAnnotationInValid($meta, $config)) {
                throw new InvalidMappingException("Class must be annotated with Loggable annotation in order to track versioned fields in class - {$meta->name}");
            }
        }
    }

    /**
     * @param string $field
     *
     * @return bool
     */
    protected function isMappingValid(ClassMetadata $meta, $field)
    {
        return false == $meta->isCollectionValuedAssociation($field);
    }

    /**
     * @return bool
     */
    protected function isClassAnnotationInValid(ClassMetadata $meta, array &$config)
    {
        return isset($config['versioned']) && !isset($config['loggable']) && (!isset($meta->isEmbeddedClass) || !$meta->isEmbeddedClass);
    }

    /**
     * Searches properties of embedded object for versioned fields
     *
     * @param string $field
     */
    private function inspectEmbeddedForVersioned($field, array &$config, \Doctrine\ORM\Mapping\ClassMetadata $meta)
    {
        $сlass = new \ReflectionClass($meta->embeddedClasses[$field]['class']);

        // property annotations
        foreach ($сlass->getProperties() as $property) {
            // versioned property
            if ($this->reader->getPropertyAnnotation($property, self::VERSIONED)) {
                $embeddedField = $field.'.'.$property->getName();
                $config['versioned'][] = $embeddedField;

                if (isset($meta->embeddedClasses[$embeddedField])) {
                    $this->inspectEmbeddedForVersioned($embeddedField, $config, $meta);
                }
            }
        }
    }
}

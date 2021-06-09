<?php

namespace Gedmo\ReferenceIntegrity\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver\AbstractAnnotationDriver;
use Gedmo\ReferenceIntegrity\Mapping\Validator;

/**
 * This is an annotation mapping driver for ReferenceIntegrity
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specifically for ReferenceIntegrity
 * extension.
 *
 * @author Evert Harmeling <evert.harmeling@freshheads.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation extends AbstractAnnotationDriver
{
    /**
     * Annotation to identify the fields which manages the reference integrity
     */
    public const REFERENCE_INTEGRITY = 'Gedmo\\Mapping\\Annotation\\ReferenceIntegrity';

    /**
     * ReferenceIntegrityAction extension annotation
     */
    public const ACTION = 'Gedmo\\Mapping\\Annotation\\ReferenceIntegrityAction';

    /**
     * {@inheritdoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        $validator = new Validator();
        $reflClass = $this->getMetaReflectionClass($meta);

        foreach ($reflClass->getProperties() as $reflProperty) {
            if ($referenceIntegrity = $this->reader->getPropertyAnnotation($reflProperty, self::REFERENCE_INTEGRITY)) {
                $property = $reflProperty->getName();
                if (!$meta->hasField($property)) {
                    throw new InvalidMappingException(sprintf('Unable to find reference integrity [%s] as mapped property in entity - %s', $property, $meta->name));
                }

                $fieldMapping = $meta->getFieldMapping($property);
                if (!isset($fieldMapping['mappedBy'])) {
                    throw new InvalidMappingException(sprintf("'mappedBy' should be set on '%s' in '%s'", $property, $meta->name));
                }

                if (!in_array($referenceIntegrity->value, $validator->getIntegrityActions())) {
                    throw new InvalidMappingException(sprintf('Field - [%s] does not have a valid integrity option, [%s] in class - %s', $property, implode(', ', $validator->getIntegrityActions()), $meta->name));
                }

                $config['referenceIntegrity'][$property] = $referenceIntegrity->value;
            }
        }
    }
}

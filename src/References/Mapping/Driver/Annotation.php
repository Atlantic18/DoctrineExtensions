<?php

namespace Gedmo\References\Mapping\Driver;

use Gedmo\Mapping\Driver\AnnotationDriverInterface;

/**
 * This is an annotation mapping driver for References
 * behavioral extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation implements AnnotationDriverInterface
{
    /**
     * Annotation to mark field as reference to one
     */
    public const REFERENCE_ONE = 'Gedmo\\Mapping\\Annotation\\ReferenceOne';

    /**
     * Annotation to mark field as reference to many
     */
    public const REFERENCE_MANY = 'Gedmo\\Mapping\\Annotation\\ReferenceMany';

    /**
     * Annotation to mark field as reference to many
     */
    public const REFERENCE_MANY_EMBED = 'Gedmo\\Mapping\\Annotation\\ReferenceManyEmbed';

    private $annotations = [
        'referenceOne' => self::REFERENCE_ONE,
        'referenceMany' => self::REFERENCE_MANY,
        'referenceManyEmbed' => self::REFERENCE_MANY_EMBED,
    ];

    /**
     * Annotation reader instance
     *
     * @var object
     */
    private $reader;

    /**
     * original driver if it is available
     */
    protected $_originalDriver = null;

    /**
     * {@inheritdoc}
     */
    public function setAnnotationReader($reader)
    {
        $this->reader = $reader;
    }

    /**
     * {@inheritdoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        $class = $meta->getReflectionClass();
        foreach ($this->annotations as $key => $annotation) {
            $config[$key] = [];
            foreach ($class->getProperties() as $property) {
                if ($meta->isMappedSuperclass && !$property->isPrivate() ||
                    $meta->isInheritedField($property->name) ||
                    isset($meta->associationMappings[$property->name]['inherited'])
                ) {
                    continue;
                }

                if ($reference = $this->reader->getPropertyAnnotation($property, $annotation)) {
                    $config[$key][$property->getName()] = [
                        'field' => $property->getName(),
                        'type' => $reference->type,
                        'class' => $reference->class,
                        'identifier' => $reference->identifier,
                        'mappedBy' => $reference->mappedBy,
                        'inversedBy' => $reference->inversedBy,
                    ];
                }
            }
        }
    }

    /**
     * Passes in the mapping read by original driver
     *
     * @param $driver
     *
     * @return void
     */
    public function setOriginalDriver($driver)
    {
        $this->_originalDriver = $driver;
    }
}

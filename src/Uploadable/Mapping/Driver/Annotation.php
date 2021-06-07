<?php

namespace Gedmo\Uploadable\Mapping\Driver;

use Gedmo\Mapping\Annotation\Uploadable;
use Gedmo\Mapping\Driver\AbstractAnnotationDriver;
use Gedmo\Uploadable\Mapping\Validator;

/**
 * This is an annotation mapping driver for Uploadable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specifically for Uploadable
 * extension.
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation extends AbstractAnnotationDriver
{
    /**
     * Annotation to define that this object is uploadable
     */
    const UPLOADABLES = 'Gedmo\\Mapping\\Annotation\\Uploadables';
    const UPLOADABLE = 'Gedmo\\Mapping\\Annotation\\Uploadable';
    const UPLOADABLE_FILE_MIME_TYPE = 'Gedmo\\Mapping\\Annotation\\UploadableFileMimeType';
    const UPLOADABLE_FILE_NAME = 'Gedmo\\Mapping\\Annotation\\UploadableFileName';
    const UPLOADABLE_FILE_PATH = 'Gedmo\\Mapping\\Annotation\\UploadableFilePath';
    const UPLOADABLE_FILE_SIZE = 'Gedmo\\Mapping\\Annotation\\UploadableFileSize';

    /**
     * {@inheritdoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        $class = $this->getMetaReflectionClass($meta);

        // class annotations
        if ($annot = $this->reader->getClassAnnotation($class, self::UPLOADABLES)) {
            foreach ($annot->configurations as $uploadable) {
                /* @var $uploadable \Gedmo\Mapping\Annotation\Uploadable */
                $config[$uploadable->identifier] = $this->readUploadableMetadata($class, $uploadable, $meta);
            }
        } else if ($annot = $this->reader->getClassAnnotation($class, self::UPLOADABLE)) {
            $config[$annot->identifier] = $this->readUploadableMetadata($class, $annot, $meta);
        }

        /*
        // Code in case we need to identify entities which are not Uploadables, but have associations
        // with other Uploadable entities

        } else {
            // We need to check if this class has a relation with Uploadable entities
            $associations = $meta->getAssociationMappings();

            foreach ($associations as $field => $association) {
                $refl = new \ReflectionClass($association['targetEntity']);

                if ($annot = $this->reader->getClassAnnotation($refl, self::UPLOADABLE)) {
                    $config['hasUploadables'] = true;

                    if (!isset($config['uploadables'])) {
                        $config['uploadables'] = array();
                    }

                    $config['uploadables'][] = array(
                        'class'         => $association['targetEntity'],
                        'property'      => $association['fieldName']
                    );
                }
            }
        }*/

        $this->validateFullMetadata($meta, $config);
    }

    protected function readUploadableMetadata(
        \ReflectionClass $class,
        Uploadable $annot,
        $meta)
    {
        $config = array();
        $config['uploadable'] = true;
        $config['allowOverwrite'] = $annot->allowOverwrite;
        $config['appendNumber'] = $annot->appendNumber;
        $config['path'] = $annot->path;
        $config['pathMethod'] = $annot->pathMethod;
        $config['fileMimeTypeField'] = false;
        $config['fileNameField'] = false;
        $config['filePathField'] = false;
        $config['fileSizeField'] = false;
        $config['callback'] = $annot->callback;
        $config['filenameGenerator'] = $annot->filenameGenerator;
        $config['maxSize'] = (float) $annot->maxSize;
        $config['allowedTypes'] = $annot->allowedTypes;
        $config['disallowedTypes'] = $annot->disallowedTypes;

        foreach ($class->getProperties() as $prop) {
            if ($propAnnot = $this->reader->getPropertyAnnotation($prop, self::UPLOADABLE_FILE_MIME_TYPE)) {
                if ($propAnnot->identifier == $annot->identifier) {
                    $config['fileMimeTypeField'] = $prop->getName();
                }
            }

            if ($propAnnot = $this->reader->getPropertyAnnotation($prop, self::UPLOADABLE_FILE_NAME)) {
                if ($propAnnot->identifier == $annot->identifier) {
                    $config['fileNameField'] = $prop->getName();
                }
            }

            if ($propAnnot = $this->reader->getPropertyAnnotation($prop, self::UPLOADABLE_FILE_PATH)) {
                if ($propAnnot->identifier == $annot->identifier) {
                    $config['filePathField'] = $prop->getName();
                }
            }

            if ($propAnnot = $this->reader->getPropertyAnnotation($prop, self::UPLOADABLE_FILE_SIZE)) {
                if ($propAnnot->identifier == $annot->identifier) {
                    $config['fileSizeField'] = $prop->getName();
                }
            }
        }

        Validator::validateConfiguration($meta, $config);
        return $config;
    }
}

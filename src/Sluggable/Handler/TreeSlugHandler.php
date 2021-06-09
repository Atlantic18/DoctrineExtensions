<?php

namespace Gedmo\Sluggable\Handler;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Sluggable\Mapping\Event\SluggableAdapter;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tool\Wrapper\AbstractWrapper;

/**
 * Sluggable handler which slugs all parent nodes
 * recursively and synchronizes on updates. For instance
 * category tree slug could look like "food/fruits/apples"
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TreeSlugHandler implements SlugHandlerWithUniqueCallbackInterface
{
    public const SEPARATOR = '/';

    /**
     * @var ObjectManager
     */
    protected $om;

    /**
     * @var SluggableListener
     */
    protected $sluggable;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var string
     */
    private $suffix;

    /**
     * True if node is being inserted
     *
     * @var bool
     */
    private $isInsert = false;

    /**
     * Transliterated parent slug
     *
     * @var string
     */
    private $parentSlug;

    /**
     * Used path separator
     *
     * @var string
     */
    private $usedPathSeparator;

    /**
     * {@inheritdoc}
     */
    public function __construct(SluggableListener $sluggable)
    {
        $this->sluggable = $sluggable;
    }

    /**
     * {@inheritdoc}
     */
    public function onChangeDecision(SluggableAdapter $ea, array &$config, $object, &$slug, &$needToChangeSlug)
    {
        $this->om = $ea->getObjectManager();
        $this->isInsert = $this->om->getUnitOfWork()->isScheduledForInsert($object);
        $options = $config['handlers'][get_called_class()];

        $this->usedPathSeparator = isset($options['separator']) ? $options['separator'] : self::SEPARATOR;
        $this->prefix = isset($options['prefix']) ? $options['prefix'] : '';
        $this->suffix = isset($options['suffix']) ? $options['suffix'] : '';

        if (!$this->isInsert && !$needToChangeSlug) {
            $changeSet = $ea->getObjectChangeSet($this->om->getUnitOfWork(), $object);
            if (isset($changeSet[$options['parentRelationField']])) {
                $needToChangeSlug = true;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function postSlugBuild(SluggableAdapter $ea, array &$config, $object, &$slug)
    {
        $options = $config['handlers'][get_called_class()];
        $this->parentSlug = '';

        $wrapped = AbstractWrapper::wrap($object, $this->om);
        if ($parent = $wrapped->getPropertyValue($options['parentRelationField'])) {
            $parent = AbstractWrapper::wrap($parent, $this->om);
            $this->parentSlug = $parent->getPropertyValue($config['slug']);

            // if needed, remove suffix from parentSlug, so we can use it to prepend it to our slug
            if (isset($options['suffix'])) {
                $suffix = $options['suffix'];

                if (substr($this->parentSlug, -strlen($suffix)) === $suffix) { //endsWith
                    $this->parentSlug = substr_replace($this->parentSlug, '', -1 * strlen($suffix));
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function validate(array $options, ClassMetadata $meta)
    {
        if (!$meta->isSingleValuedAssociation($options['parentRelationField'])) {
            throw new InvalidMappingException("Unable to find tree parent slug relation through field - [{$options['parentRelationField']}] in class - {$meta->name}");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function beforeMakingUnique(SluggableAdapter $ea, array &$config, $object, &$slug)
    {
        $slug = $this->transliterate($slug, $config['separator'], $object);
    }

    /**
     * {@inheritdoc}
     */
    public function onSlugCompletion(SluggableAdapter $ea, array &$config, $object, &$slug)
    {
        if (!$this->isInsert) {
            $wrapped = AbstractWrapper::wrap($object, $this->om);
            $meta = $wrapped->getMetadata();
            $target = $wrapped->getPropertyValue($config['slug']);
            $config['pathSeparator'] = $this->usedPathSeparator;
            $ea->replaceRelative($object, $config, $target.$config['pathSeparator'], $slug);
            $uow = $this->om->getUnitOfWork();
            // update in memory objects
            foreach ($uow->getIdentityMap() as $className => $objects) {
                // for inheritance mapped classes, only root is always in the identity map
                if ($className !== $wrapped->getRootObjectName()) {
                    continue;
                }
                foreach ($objects as $object) {
                    if (property_exists($object, '__isInitialized__') && !$object->__isInitialized__) {
                        continue;
                    }
                    $oid = spl_object_hash($object);
                    $objectSlug = $meta->getReflectionProperty($config['slug'])->getValue($object);
                    if (preg_match("@^{$target}{$config['pathSeparator']}@smi", $objectSlug)) {
                        $objectSlug = str_replace($target, $slug, $objectSlug);
                        $meta->getReflectionProperty($config['slug'])->setValue($object, $objectSlug);
                        $ea->setOriginalObjectProperty($uow, $oid, $config['slug'], $objectSlug);
                    }
                }
            }
        }
    }

    /**
     * Transliterates the slug and prefixes the slug
     * by collection of parent slugs
     *
     * @param string $text
     * @param string $separator
     * @param object $object
     *
     * @return string
     */
    public function transliterate($text, $separator, $object)
    {
        $slug = $text.$this->suffix;

        if (strlen($this->parentSlug)) {
            $slug = $this->parentSlug.$this->usedPathSeparator.$slug;
        } else {
            // if no parentSlug, apply our prefix
            $slug = $this->prefix.$slug;
        }

        return $slug;
    }

    /**
     * {@inheritdoc}
     */
    public function handlesUrlization()
    {
        return false;
    }
}

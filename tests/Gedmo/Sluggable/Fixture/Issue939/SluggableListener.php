<?php

namespace Sluggable\Fixture\Issue939;

use Gedmo\Sluggable\SluggableListener as BaseSluggableListener;

class SluggableListener extends BaseSluggableListener
{
    protected $originalTransliterator;
    protected $originalUrlizer;

    public function __construct()
    {
        $this->originalTransliterator = $this->getTransliterator();
        $this->originalUrlizer = $this->getUrlizer();

        $this->setTransliterator(function ($slug, $separator, $object) {
            return $this->transliterator($slug, $separator, $object);
        });
        $this->setUrlizer(function ($slug, $separator, $object) {
            return $this->urlizer($slug, $separator, $object);
        });
    }

    public function transliterator($slug, $separator = '-', $object = null)
    {
        if ($object instanceof Article) {
            // custom transliteration here
            return $slug;
        }

        return call_user_func_array(
            $this->originalTransliterator,
            [$slug, $separator, $object]
        );
    }

    public function urlizer($slug, $separator = '-', $object = null)
    {
        if ($object instanceof Article) {
            // custom urlization here
            return $slug;
        }

        return call_user_func_array(
            $this->originalUrlizer,
            [$slug, $separator, $object]
        );
    }
}

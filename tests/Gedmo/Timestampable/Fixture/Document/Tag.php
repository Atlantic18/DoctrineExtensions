<?php

namespace Timestampable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\EmbeddedDocument()
 */
class Tag
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    protected $name;

    /**
     * @ODM\Field(type="date")
     * @Gedmo\Timestampable(on="create")
     *
     * @var \DateTime
     */
    protected $created;

    /**
     * @ODM\Field(type="date")
     * @Gedmo\Timestampable
     *
     * @var \DateTime
     */
    protected $updated;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \DateTime|\DateTimeImmutable $created
     */
    public function setCreated(\DateTimeInterface $created)
    {
        $this->created = $created;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param \DateTime|\DateTimeImmutable $updated
     */
    public function setUpdated(\DateTimeInterface $updated)
    {
        $this->updated = $updated;
    }
}

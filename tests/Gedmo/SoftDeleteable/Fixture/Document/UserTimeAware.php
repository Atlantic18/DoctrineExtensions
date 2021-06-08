<?php

namespace SoftDeleteable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document(collection="users")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=true)
 */
class UserTimeAware
{
    /** @ODM\Id */
    private $id;

    /** @ODM\Field(type="string") */
    private $username;

    /** @ODM\Field(type="date") */
    protected $deletedAt;

    /**
     * Sets deletedAt.
     *
     * @param Datetime $deletedAt
     *
     * @return $this
     */
    public function setDeletedAt(\DateTimeInterface $deletedAt)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * Returns deletedAt.
     *
     * @return DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getUsername()
    {
        return $this->username;
    }
}

<?php

namespace Mapping\Fixture\Yaml;

/**
 * @MappedSupperClass
 */
class BaseCategory
{
    /**
     * @Column(type="integer")
     */
    private $left;

    /**
     * @Column(type="integer")
     */
    private $right;

    /**
     * @Column(type="integer")
     */
    private $level;

    /**
     * @Column(type="integer")
     */
    private $rooted;

    /**
     * @var datetime
     *
     * @Column(name="created", type="datetime")
     */
    private $created;

    /**
     * @var date
     *
     * @Column(name="updated", type="date")
     */
    private $updated;

    /**
     * Set created
     *
     * @param dateTime $created
     */
    public function setCreated(\DateTimeInterface $created)
    {
        $this->created = $created;
    }

    /**
     * Get created
     *
     * @return dateTime $created
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param date $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    /**
     * Get updated
     *
     * @return date $updated
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    public function setLeft($left)
    {
        $this->left = $left;

        return $this;
    }

    public function getLeft()
    {
        return $this->left;
    }

    public function setRight($right)
    {
        $this->right = $right;

        return $this;
    }

    public function getRight()
    {
        return $this->right;
    }

    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function setRooted($rooted)
    {
        $this->rooted = $rooted;

        return $this;
    }

    public function getRooted()
    {
        return $this->rooted;
    }
}

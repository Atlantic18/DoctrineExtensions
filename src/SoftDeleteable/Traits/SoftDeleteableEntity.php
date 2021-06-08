<?php

namespace Gedmo\SoftDeleteable\Traits;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * A soft deletable trait you can apply to your Doctrine ORM entities.
 * Includes default annotation mapping.
 *
 * @author Wesley van Opdorp <wesley.van.opdorp@freshheads.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
trait SoftDeleteableEntity
{
    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @var DateTime|null
     */
    protected $deletedAt;

    /**
     * Set or clear the deleted at timestamp.
     *
     * @return self
     * @param \DateTime|\DateTimeImmutable $deletedAt
     */
    public function setDeletedAt(\DateTimeInterface $deletedAt = null)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * Get the deleted at timestamp value. Will return null if
     * the entity has not been soft deleted.
     *
     * @return DateTime|null
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * Check if the entity has been soft deleted.
     *
     * @return bool
     */
    public function isDeleted()
    {
        return null !== $this->deletedAt;
    }
}

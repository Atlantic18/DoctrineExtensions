<?php

namespace Gedmo\Enable\Traits;

use Doctrine\ORM\Mapping as ORM;

/**
 * Enable trait for Doctrine entities, usable with PHP >= 5.4
 *
 * @author Bocharsky Victor <bocharsky.bw@gmail.com>
 * @license http://opensource.org/licenses/mit-license.php The MIT License
 */
trait EnableEntity
{
    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $enabled;

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param boolean $enabled
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }
}

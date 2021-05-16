<?php

namespace Gedmo\ReferenceIntegrity\Mapping;

/**
 * This class is used to validate mapping information
 *
 * @author Evert Harmeling <evert.harmeling@freshheads.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Validator
{
    public const NULLIFY = 'nullify';
    public const PULL = 'pull';
    public const RESTRICT = 'restrict';

    /**
     * List of actions which are valid as integrity check
     *
     * @var array
     */
    private $integrityActions = [
        self::NULLIFY,
        self::PULL,
        self::RESTRICT,
    ];

    /**
     * Returns a list of available integrity actions
     *
     * @return array
     */
    public function getIntegrityActions()
    {
        return $this->integrityActions;
    }
}

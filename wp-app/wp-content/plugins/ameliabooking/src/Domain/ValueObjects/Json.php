<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\ValueObjects;

/**
 * Class Json
 *
 * @package AmeliaBooking\Domain\ValueObjects
 */
final class Json
{
    /**
     * @var string
     */
    private $value;

    /**
     * @param array $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @return array|string
     */
    public function getValue()
    {
        return $this->value;
    }
}

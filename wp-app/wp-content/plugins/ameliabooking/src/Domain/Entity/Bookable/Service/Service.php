<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Bookable\Service;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\IntegerValue;
use AmeliaBooking\Domain\ValueObjects\String\Status;
use AmeliaBooking\Domain\ValueObjects\Priority;
use AmeliaBooking\Domain\ValueObjects\String\Color;
use AmeliaBooking\Domain\ValueObjects\String\Description;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Domain\Entity\Bookable\AbstractBookable;
use AmeliaBooking\Domain\ValueObjects\Duration;
use AmeliaBooking\Domain\ValueObjects\PositiveDuration;

/**
 * Class Service
 *
 * @package AmeliaBooking\Domain\Entity\Bookable\Service
 */
class Service extends AbstractBookable
{
    /** @var  IntegerValue */
    private $minCapacity;

    /** @var  IntegerValue */
    private $maxCapacity;

    /** @var  PositiveDuration */
    private $duration;

    /** @var  Duration */
    private $timeBefore;

    /** @var  Duration */
    private $timeAfter;

    /** @var BooleanValueObject */
    private $bringingAnyone;

    /** @var Priority */
    private $priority;

    /** @var Collection */
    private $extras;

    /** @var Collection */
    private $gallery;

    /** @var Collection */
    private $coupons;

    /**
     * Service constructor.
     *
     * @param Name             $name
     * @param Description      $description
     * @param Color            $color
     * @param Price            $price
     * @param Status           $status
     * @param Id               $categoryId
     * @param IntegerValue     $minCapacity
     * @param IntegerValue     $maxCapacity
     * @param PositiveDuration $duration
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        Name $name,
        Description $description,
        Color $color,
        Price $price,
        Status $status,
        Id $categoryId,
        IntegerValue $minCapacity,
        IntegerValue $maxCapacity,
        PositiveDuration $duration
    ) {

        if (!$duration->getValue()) {
            throw new InvalidArgumentException('Duration cannot be zero.');
        }

        parent::__construct($name, $description, $color, $price, $status, $categoryId);
        $this->minCapacity = $minCapacity;
        $this->maxCapacity = $maxCapacity;
        $this->duration = $duration;
    }

    /**
     * @return IntegerValue
     */
    public function getMinCapacity()
    {
        return $this->minCapacity;
    }

    /**
     * @param IntegerValue $minCapacity
     */
    public function setMinCapacity(IntegerValue $minCapacity)
    {
        $this->minCapacity = $minCapacity;
    }

    /**
     * @return IntegerValue
     */
    public function getMaxCapacity()
    {
        return $this->maxCapacity;
    }

    /**
     * @param IntegerValue $maxCapacity
     */
    public function setMaxCapacity(IntegerValue $maxCapacity)
    {
        $this->maxCapacity = $maxCapacity;
    }

    /**
     * @return PositiveDuration
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param PositiveDuration $duration
     */
    public function setDuration(PositiveDuration $duration)
    {
        $this->duration = $duration;
    }

    /**
     * @return Duration
     */
    public function getTimeBefore()
    {
        return $this->timeBefore;
    }

    /**
     * @param Duration $timeBefore
     */
    public function setTimeBefore(Duration $timeBefore)
    {
        $this->timeBefore = $timeBefore;
    }

    /**
     * @return Duration
     */
    public function getTimeAfter()
    {
        return $this->timeAfter;
    }

    /**
     * @param Duration $timeAfter
     */
    public function setTimeAfter(Duration $timeAfter)
    {
        $this->timeAfter = $timeAfter;
    }

    /**
     * @return BooleanValueObject
     */
    public function getBringingAnyone()
    {
        return $this->bringingAnyone;
    }

    /**
     * @param BooleanValueObject $bringingAnyone
     */
    public function setBringingAnyone(BooleanValueObject $bringingAnyone)
    {
        $this->bringingAnyone = $bringingAnyone;
    }

    /**
     * @return Priority
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param Priority $priority
     */
    public function setPriority(Priority $priority)
    {
        $this->priority = $priority;
    }

    /**
     * @return Collection
     */
    public function getExtras()
    {
        return $this->extras;
    }

    /**
     * @param Collection $extras
     */
    public function setExtras(Collection $extras)
    {
        $this->extras = $extras;
    }

    /**
     * @return Collection
     */
    public function getGallery()
    {
        return $this->gallery;
    }

    /**
     * @param Collection $gallery
     */
    public function setGallery(Collection $gallery)
    {
        $this->gallery = $gallery;
    }

    /**
     * @return Collection
     */
    public function getCoupons()
    {
        return $this->coupons;
    }

    /**
     * @param Collection $coupons
     */
    public function setCoupons(Collection $coupons)
    {
        $this->coupons = $coupons;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            [
                'minCapacity'    => $this->getMinCapacity() ? $this->getMinCapacity()->getValue() : null,
                'maxCapacity'    => $this->getMaxCapacity() ? $this->getMaxCapacity()->getValue() : null,
                'duration'       => $this->getDuration() ? $this->getDuration()->getValue() : null,
                'timeBefore'     => $this->getTimeBefore() ? $this->getTimeBefore()->getValue() : null,
                'timeAfter'      => $this->getTimeAfter() ? $this->getTimeAfter()->getValue() : null,
                'bringingAnyone' => $this->getBringingAnyone() ? $this->getBringingAnyone()->getValue() : null,
                'priority'       => $this->getPriority() ? $this->getPriority()->getValue() : [],
                'extras'         => $this->getExtras() ? $this->getExtras()->toArray() : [],
                'coupons'        => $this->getCoupons() ? $this->getCoupons()->toArray() : [],
                'gallery'        => $this->getGallery() ? $this->getGallery()->toArray() : []
            ]
        );
    }
}

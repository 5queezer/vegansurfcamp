<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Schedule;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;

/**
 * Class SpecialDayPeriod
 *
 * @package AmeliaBooking\Domain\Entity\Schedule
 */
class SpecialDayPeriod
{
    /** @var Id */
    private $id;

    /** @var DateTimeValue */
    private $startTime;

    /** @var DateTimeValue */
    private $endTime;

    /** @var Collection */
    private $periodServiceList;

    /**
     * TimeOut constructor.
     *
     * @param DateTimeValue $startTime
     * @param DateTimeValue $endTime
     * @param Collection    $periodServiceList
     */
    public function __construct(
        DateTimeValue $startTime,
        DateTimeValue $endTime,
        Collection $periodServiceList
    ) {
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->periodServiceList = $periodServiceList;
    }

    /**
     * @return Id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Id $id
     */
    public function setId(Id $id)
    {
        $this->id = $id;
    }

    /**
     * @return DateTimeValue
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @param DateTimeValue $startTime
     */
    public function setStartTime(DateTimeValue $startTime)
    {
        $this->startTime = $startTime;
    }

    /**
     * @return DateTimeValue
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * @param DateTimeValue $endTime
     */
    public function setEndTime(DateTimeValue $endTime)
    {
        $this->endTime = $endTime;
    }

    /**
     * @return Collection
     */
    public function getPeriodServiceList()
    {
        return $this->periodServiceList;
    }

    /**
     * @param Collection $periodServiceList
     */
    public function setPeriodServiceList(Collection $periodServiceList)
    {
        $this->periodServiceList = $periodServiceList;
    }

    public function toArray()
    {
        return [
            'id'                => null !== $this->getId() ? $this->getId()->getValue() : null,
            'startTime'         => $this->startTime->getValue()->format('H:i:s'),
            'endTime'           => $this->endTime->getValue()->format('H:i:s') === '00:00:00' ?
                '24:00:00' : $this->endTime->getValue()->format('H:i:s'),
            'periodServiceList' => $this->periodServiceList->toArray(),
        ];
    }
}

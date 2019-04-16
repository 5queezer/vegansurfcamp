<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Booking;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\Description;

/**
 * Class Booking
 *
 * @package AmeliaBooking\Domain\Entity\Booking
 */
abstract class AbstractBooking
{
    /** @var Id */
    private $id;

    /** @var  Collection */
    protected $bookings;

    /** @var DateTimeValue */
    protected $bookingStart;

    /** @var DateTimeValue */
    protected $bookingEnd;

    /** @var bool */
    protected $notifyParticipants;

    /** @var Description */
    protected $internalNotes;

    /** @var BookingStatus */
    protected $status;

    /**
     * AbstractBooking constructor.
     *
     * @param DateTimeValue $bookingStart
     * @param DateTimeValue $bookingEnd
     * @param boolean       $notifyParticipants
     */
    public function __construct(
        DateTimeValue $bookingStart,
        DateTimeValue $bookingEnd,
        $notifyParticipants
    ) {
        $this->bookingStart = $bookingStart;
        $this->bookingEnd = $bookingEnd;
        $this->notifyParticipants = $notifyParticipants;
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
     * @return Collection
     */
    public function getBookings()
    {
        return $this->bookings;
    }

    /**
     * @param Collection $bookings
     */
    public function setBookings(Collection $bookings)
    {
        $this->bookings = $bookings;
    }

    /**
     * @return DateTimeValue
     */
    public function getBookingStart()
    {
        return $this->bookingStart;
    }

    /**
     * @param DateTimeValue $bookingStart
     */
    public function setBookingStart(DateTimeValue $bookingStart)
    {
        $this->bookingStart = $bookingStart;
    }

    /**
     * @return DateTimeValue
     */
    public function getBookingEnd()
    {
        return $this->bookingEnd;
    }

    /**
     * @param DateTimeValue $bookingEnd
     */
    public function setBookingEnd(DateTimeValue $bookingEnd)
    {
        $this->bookingEnd = $bookingEnd;
    }

    /**
     * @return bool
     */
    public function isNotifyParticipants()
    {
        return $this->notifyParticipants;
    }

    /**
     * @param bool $notifyParticipants
     */
    public function setNotifyParticipants($notifyParticipants)
    {
        $this->notifyParticipants = $notifyParticipants;
    }

    /**
     * @return Description
     */
    public function getInternalNotes()
    {
        return $this->internalNotes;
    }

    /**
     * @param Description $internalNotes
     */
    public function setInternalNotes(Description $internalNotes)
    {
        $this->internalNotes = $internalNotes;
    }

    /**
     * @return BookingStatus
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param BookingStatus $status
     */
    public function setStatus(BookingStatus $status)
    {
        $this->status = $status;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'                 => null !== $this->getId() ? $this->getId()->getValue() : null,
            'bookings'           => null !== $this->getBookings() ? $this->getBookings()->toArray() : null,
            'bookingStart'       => $this->getBookingStart()->getValue()->format('Y-m-d H:i:s'),
            'bookingEnd'         => $this->getBookingEnd()->getValue()->format('Y-m-d H:i:s'),
            'notifyParticipants' => $this->isNotifyParticipants(),
            'internalNotes'      => null !== $this->getInternalNotes() ? $this->getInternalNotes()->getValue() : null,
            'status'             => $this->getStatus()->getValue(),
        ];
    }
}

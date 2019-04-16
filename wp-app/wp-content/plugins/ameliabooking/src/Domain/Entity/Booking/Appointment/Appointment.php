<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Booking\Appointment;

use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\AbstractBooking;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\Token;

/**
 * Class Appointment
 *
 * @package AmeliaBooking\Domain\Entity\Booking
 */
class Appointment extends AbstractBooking
{
    /** @var Id */
    private $serviceId;

    /** @var Service */
    private $service;

    /** @var Id */
    private $providerId;

    /** @var Provider */
    private $provider;

    /** @var Token */
    private $googleCalendarEventId;

    /**
     * Appointment constructor.
     *
     * @param DateTimeValue $bookingStart
     * @param DateTimeValue $bookingEnd
     * @param bool          $notifyParticipants
     * @param Id            $serviceId
     * @param Id            $providerId
     */
    public function __construct(
        DateTimeValue $bookingStart,
        DateTimeValue $bookingEnd,
        $notifyParticipants,
        Id $serviceId,
        Id $providerId
    ) {
        parent::__construct($bookingStart, $bookingEnd, $notifyParticipants);
        $this->serviceId = $serviceId;
        $this->providerId = $providerId;
    }

    /**
     * @return Id
     */
    public function getServiceId()
    {
        return $this->serviceId;
    }

    /**
     * @param Id $serviceId
     */
    public function setServiceId(Id $serviceId)
    {
        $this->serviceId = $serviceId;
    }

    /**
     * @return Service
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @param Service $service
     */
    public function setService(Service $service)
    {
        $this->service = $service;
    }

    /**
     * @return Id
     */
    public function getProviderId()
    {
        return $this->providerId;
    }

    /**
     * @param Id $providerId
     */
    public function setProviderId(Id $providerId)
    {
        $this->providerId = $providerId;
    }

    /**
     * @return Provider
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @param Provider $provider
     */
    public function setProvider(Provider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @return Token
     */
    public function getGoogleCalendarEventId()
    {
        return $this->googleCalendarEventId;
    }

    /**
     * @param Token $googleCalendarEventId
     */
    public function setGoogleCalendarEventId($googleCalendarEventId)
    {
        $this->googleCalendarEventId = $googleCalendarEventId;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            [
                'serviceId'             => $this->getServiceId()->getValue(),
                'providerId'            => $this->getProviderId()->getValue(),
                'provider'              => null !== $this->getProvider() ? $this->getProvider()->toArray() : null,
                'service'               => null !== $this->getService() ? $this->getService()->toArray() : null,
                'googleCalendarEventId' => null !== $this->getGoogleCalendarEventId() ?
                    $this->getGoogleCalendarEventId()->getValue() : null,
            ]
        );
    }
}

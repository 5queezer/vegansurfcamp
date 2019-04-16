<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Repository\Booking\Appointment;

use AmeliaBooking\Domain\Repository\BaseRepositoryInterface;

/**
 * Interface AppointmentRepositoryInterface
 *
 * @package AmeliaBooking\Domain\Repository\Booking\Appointment
 */
interface AppointmentRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @param int $id
     * @param int $status
     *
     * @return mixed
     */
    public function updateStatusById($id, $status);

    /**
     * @return array
     */
    public function getCurrentAppointments();

    /**
     * @param int $providerId
     * @param int $appointmentId
     *
     * @return array
     */
    public function getFutureAppointments($providerId, $appointmentId);

    /**
     * @param array $criteria
     *
     * @return mixed
     */
    public function getFiltered($criteria);
}

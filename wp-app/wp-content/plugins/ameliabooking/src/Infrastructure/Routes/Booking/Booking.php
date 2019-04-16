<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Booking;

use AmeliaBooking\Application\Controller\Booking\Appointment\AddAppointmentController;
use AmeliaBooking\Application\Controller\Booking\Appointment\CancelBookingController;
use AmeliaBooking\Application\Controller\Booking\Appointment\CancelBookingRemotelyController;
use AmeliaBooking\Application\Controller\Booking\Appointment\DeleteAppointmentController;
use AmeliaBooking\Application\Controller\Booking\Appointment\GetAppointmentController;
use AmeliaBooking\Application\Controller\Booking\Appointment\GetAppointmentsController;
use AmeliaBooking\Application\Controller\Booking\Appointment\GetIcsController;
use AmeliaBooking\Application\Controller\Booking\Appointment\SuccessfulAppointmentController;
use AmeliaBooking\Application\Controller\Booking\Appointment\SuccessfulBookingController;
use AmeliaBooking\Application\Controller\Booking\Appointment\UpdateAppointmentController;
use AmeliaBooking\Application\Controller\Booking\Appointment\UpdateAppointmentStatusController;
use AmeliaBooking\Application\Controller\Booking\Appointment\AddBookingController;
use AmeliaBooking\Application\Controller\Booking\Appointment\UpdateAppointmentTimeController;
use Slim\App;

/**
 * Class Booking
 *
 * @package AmeliaBooking\Infrastructure\Routes\Booking
 */
class Booking
{
    /**
     * @param App $app
     *
     * @throws \InvalidArgumentException
     */
    public static function routes(App $app)
    {
        $app->get('/appointments', GetAppointmentsController::class);

        $app->get('/appointments/{id:[0-9]+}', GetAppointmentController::class);

        $app->post('/appointments', AddAppointmentController::class);

        $app->post('/appointments/delete/{id:[0-9]+}', DeleteAppointmentController::class);

        $app->post('/appointments/{id:[0-9]+}', UpdateAppointmentController::class);

        $app->post('/appointments/status/{id:[0-9]+}', UpdateAppointmentStatusController::class);

        $app->post('/appointments/time/{id:[0-9]+}', UpdateAppointmentTimeController::class);

        $app->post('/bookings/cancel/{id:[0-9]+}', CancelBookingController::class);

        $app->get('/bookings/cancel/{id:[0-9]+}', CancelBookingRemotelyController::class);

        $app->post('/bookings', AddBookingController::class);

        $app->get('/bookings/ics/{id:[0-9]+}', GetIcsController::class)->setOutputBuffering(false);

        $app->post('/bookings/success/{id:[0-9]+}', SuccessfulBookingController::class);
    }
}

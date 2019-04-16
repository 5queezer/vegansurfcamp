<?php

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Booking\AppointmentApplicationService;
use AmeliaBooking\Application\Services\Booking\BookingApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Services\Payment\PayPalService;
use AmeliaBooking\Infrastructure\Services\Payment\StripeService;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;

/**
 * Class AddBookingCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class AddBookingCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
        'bookings',
        'bookingStart',
        'notifyParticipants',
        'serviceId',
        'providerId',
        'couponCode',
        'payment'
    ];

    /**
     * @param AddBookingCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \Exception
     */
    public function handle(AddBookingCommand $command)
    {
        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var BookingApplicationService $bookingAS */
        $bookingAS = $this->container->get('application.booking.booking.service');

        /** @var AppointmentRepository $appointmentRepo */
        $appointmentRepo = $this->container->get('domain.booking.appointment.repository');

        /** @var AppointmentApplicationService $appointmentAS */
        $appointmentAS = $this->container->get('application.booking.appointment.service');

        $appointmentRepo->beginTransaction();

        $appointmentData = $bookingAS->getAppointmentData($command->getFields());

        try {
            $bookingData =
                $bookingAS->processBooking($result, $appointmentRepo, $appointmentData, true, true, true);
        } catch (QueryExecutionException $e) {
            $appointmentRepo->rollback();
            throw $e;
        }

        if ($result->getResult() === CommandResult::RESULT_ERROR) {
            $appointmentRepo->rollback();
            return $result;
        }

        /** @var Appointment $appointment */
        $appointment = $bookingData['appointment'];

        /** @var CustomerBooking $booking */
        $booking = $bookingData['booking'];

        /** @var Service $service */
        $service = $bookingData['service'];

        $paymentData = $command->getField('payment');

        $paymentAmount = $appointmentAS->getPaymentAmount($booking, $service);

        switch ($paymentData['gateway']) {
            case ('payPal'):
                /** @var PayPalService $paymentService */
                $paymentService = $this->container->get('infrastructure.payment.payPal.service');

                $response = $paymentService->complete([
                    'transactionReference' => $paymentData['data']['transactionReference'],
                    'PayerID'              => $paymentData['data']['PayerId'],
                    'amount'               => $paymentAmount,
                ]);

                if (!$response->isSuccessful()) {
                    $result->setResult(CommandResult::RESULT_ERROR);
                    $result->setMessage(FrontendStrings::getCommonStrings()['payment_error']);
                    $result->setData([
                        'paymentSuccessful' => false
                    ]);

                    $appointmentRepo->rollback();

                    return $result;
                }

                break;

            case ('stripe'):
                /** @var StripeService $paymentService */
                $paymentService = $this->container->get('infrastructure.payment.stripe.service');

                try {
                    $response = $paymentService->execute([
                        'amount' => $paymentAmount,
                        'token'  => $paymentData['data']['token']
                    ]);

                    if (!$response->isSuccessful()) {
                        $result->setResult(CommandResult::RESULT_ERROR);
                        $result->setMessage($response->getData()['error']['message']);
                        $result->setData([
                            'paymentSuccessful' => false
                        ]);

                        $appointmentRepo->rollback();

                        return $result;
                    }
                } catch (\Exception $e) {
                    $result->setResult(CommandResult::RESULT_ERROR);
                    $result->setMessage($e->getMessage());
                    $result->setData([
                        'paymentSuccessful' => false
                    ]);

                    $appointmentRepo->rollback();

                    return $result;
                }

                break;
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully added booking');
        $result->setData([
            Entities::APPOINTMENT      => $appointment->toArray(),
            Entities::BOOKING          => $booking->toArray(),
            'utcTime'                  => [
                'start' => DateTimeService::getCustomDateTimeInUtc(
                    $appointment->getBookingStart()->getValue()->format('Y-m-d H:i:s')
                ),
                'end'   => DateTimeService::getCustomDateTimeInUtc(
                    $appointment->getBookingEnd()->getValue()->format('Y-m-d H:i:s')
                )
            ],
            'appointmentStatusChanged' => $bookingData['appointmentStatusChanged']
        ]);

        $appointmentRepo->commit();

        return $result;
    }
}

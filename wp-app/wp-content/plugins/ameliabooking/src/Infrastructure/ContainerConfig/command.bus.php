<?php

defined('ABSPATH') or die('No script kiddies please!');

use AmeliaBooking\Application\Commands\Bookable;
use AmeliaBooking\Application\Commands\Stats;
use AmeliaBooking\Application\Commands\User;
use AmeliaBooking\Application\Commands\Location;
use AmeliaBooking\Application\Commands\Coupon;
use AmeliaBooking\Application\Commands\Payment;
use AmeliaBooking\Application\Commands\PaymentGateway;
use AmeliaBooking\Application\Commands\Notification;
use AmeliaBooking\Application\Commands\Booking;
use AmeliaBooking\Application\Commands\Entities;
use AmeliaBooking\Application\Commands\Settings;
use AmeliaBooking\Application\Commands\Report;
use AmeliaBooking\Application\Commands\Search;
use AmeliaBooking\Application\Commands\Google;
use AmeliaBooking\Application\Commands\CustomField;

// @codingStandardsIgnoreStart
$entries['command.bus'] = function ($c) {
    $commands = [
        // Bookable/Category
        Bookable\Category\AddCategoryCommand::class               => new Bookable\Category\AddCategoryCommandHandler($c),
        Bookable\Category\DeleteCategoryCommand::class            => new Bookable\Category\DeleteCategoryCommandHandler($c),
        Bookable\Category\GetCategoriesCommand::class             => new Bookable\Category\GetCategoriesCommandHandler($c),
        Bookable\Category\GetCategoryCommand::class               => new Bookable\Category\GetCategoryCommandHandler($c),
        Bookable\Category\UpdateCategoriesPositionsCommand::class => new Bookable\Category\UpdateCategoriesPositionsCommandHandler($c),
        Bookable\Category\UpdateCategoryCommand::class            => new Bookable\Category\UpdateCategoryCommandHandler($c),

        // Bookable/Service
        Bookable\Service\AddServiceCommand::class                 => new Bookable\Service\AddServiceCommandHandler($c),
        Bookable\Service\DeleteServiceCommand::class              => new Bookable\Service\DeleteServiceCommandHandler($c),
        Bookable\Service\GetServiceCommand::class                 => new Bookable\Service\GetServiceCommandHandler($c),
        Bookable\Service\GetServiceDeleteEffectCommand::class     => new Bookable\Service\GetServiceDeleteEffectCommandHandler($c),
        Bookable\Service\GetServicesCommand::class                => new Bookable\Service\GetServicesCommandHandler($c),
        Bookable\Service\UpdateServiceCommand::class              => new Bookable\Service\UpdateServiceCommandHandler($c),
        Bookable\Service\UpdateServiceStatusCommand::class        => new Bookable\Service\UpdateServiceStatusCommandHandler($c),

        // Booking/Appointment
        Booking\Appointment\AddAppointmentCommand::class          => new Booking\Appointment\AddAppointmentCommandHandler($c),
        Booking\Appointment\AddBookingCommand::class              => new Booking\Appointment\AddBookingCommandHandler($c),
        Booking\Appointment\CancelBookingCommand::class           => new Booking\Appointment\CancelBookingCommandHandler($c),
        Booking\Appointment\CancelBookingRemotelyCommand::class   => new Booking\Appointment\CancelBookingRemotelyCommandHandler($c),
        Booking\Appointment\DeleteAppointmentCommand::class       => new Booking\Appointment\DeleteAppointmentCommandHandler($c),
        Booking\Appointment\GetAppointmentCommand::class          => new Booking\Appointment\GetAppointmentCommandHandler($c),
        Booking\Appointment\GetAppointmentsCommand::class         => new Booking\Appointment\GetAppointmentsCommandHandler($c),
        Booking\Appointment\GetTimeSlotsCommand::class            => new Booking\Appointment\GetTimeSlotsCommandHandler($c),
        Booking\Appointment\UpdateAppointmentCommand::class       => new Booking\Appointment\UpdateAppointmentCommandHandler($c),
        Booking\Appointment\UpdateAppointmentStatusCommand::class => new Booking\Appointment\UpdateAppointmentStatusCommandHandler($c),
        Booking\Appointment\UpdateAppointmentTimeCommand::class   => new Booking\Appointment\UpdateAppointmentTimeCommandHandler($c),
        Booking\Appointment\SuccessfulBookingCommand::class       => new Booking\Appointment\SuccessfulBookingCommandHandler($c),

        // Entities
        Entities\GetEntitiesCommand::class                        => new Entities\GetEntitiesCommandHandler($c),

        // Notification
        Notification\GetNotificationsCommand::class               => new Notification\GetNotificationsCommandHandler($c),
        Notification\SendTestEmailCommand::class                  => new Notification\SendTestEmailCommandHandler($c),
        Notification\UpdateNotificationCommand::class             => new Notification\UpdateNotificationCommandHandler($c),
        Notification\UpdateNotificationStatusCommand::class       => new Notification\UpdateNotificationStatusCommandHandler($c),
        Notification\SendAmeliaSmsApiRequestCommand::class        => new Notification\SendAmeliaSmsApiRequestCommandHandler($c),
        Notification\UpdateSMSNotificationHistoryCommand::class   => new Notification\UpdateSMSNotificationHistoryCommandHandler($c),
        Notification\GetSMSNotificationsHistoryCommand::class     => new Notification\GetSMSNotificationsHistoryCommandHandler($c),

        // Payment
        Payment\AddPaymentCommand::class                          => new Payment\AddPaymentCommandHandler($c),
        Payment\DeletePaymentCommand::class                       => new Payment\DeletePaymentCommandHandler($c),
        Payment\GetPaymentCommand::class                          => new Payment\GetPaymentCommandHandler($c),
        Payment\GetPaymentsCommand::class                         => new Payment\GetPaymentsCommandHandler($c),
        Payment\UpdatePaymentCommand::class                       => new Payment\UpdatePaymentCommandHandler($c),

        // Settings
        Settings\GetSettingsCommand::class                        => new Settings\GetSettingsCommandHandler($c),
        Settings\UpdateSettingsCommand::class                     => new Settings\UpdateSettingsCommandHandler($c),

        // User/Customer
        User\Customer\AddCustomerCommand::class                   => new User\Customer\AddCustomerCommandHandler($c),
        User\Customer\GetCustomerCommand::class                   => new User\Customer\GetCustomerCommandHandler($c),
        User\Customer\GetCustomerCommand::class                   => new User\Customer\GetCustomerCommandHandler($c),
        User\Customer\GetCustomersCommand::class                  => new User\Customer\GetCustomersCommandHandler($c),
        User\Customer\UpdateCustomerCommand::class                => new User\Customer\UpdateCustomerCommandHandler($c),

        // User
        User\DeleteUserCommand::class                             => new User\DeleteUserCommandHandler($c),
        User\GetCurrentUserCommand::class                         => new User\GetCurrentUserCommandHandler($c),
        User\GetUserDeleteEffectCommand::class                    => new User\GetUserDeleteEffectCommandHandler($c),
        User\GetWPUsersCommand::class                             => new User\GetWPUsersCommandHandler($c),

        // User/Provider
        User\Provider\AddProviderCommand::class                   => new User\Provider\AddProviderCommandHandler($c),
        User\Provider\GetProviderCommand::class                   => new User\Provider\GetProviderCommandHandler($c),
        User\Provider\GetProvidersCommand::class                  => new User\Provider\GetProvidersCommandHandler($c),
        User\Provider\UpdateProviderCommand::class                => new User\Provider\UpdateProviderCommandHandler($c),
        User\Provider\UpdateProviderStatusCommand::class          => new User\Provider\UpdateProviderStatusCommandHandler($c),

        // Status
        Stats\GetStatsCommand::class                              => new AmeliaBooking\Application\Commands\Stats\GetStatsCommandHandler($c),
    ];

    if (!AMELIA_LITE_VERSION) {
        $commands = array_merge($commands, [
            // Bookable/Extra
            Bookable\Extra\AddExtraCommand::class                     => new Bookable\Extra\AddExtraCommandHandler($c),
            Bookable\Extra\DeleteExtraCommand::class                  => new Bookable\Extra\DeleteExtraCommandHandler($c),
            Bookable\Extra\GetExtraCommand::class                     => new Bookable\Extra\GetExtraCommandHandler($c),
            Bookable\Extra\GetExtrasCommand::class                    => new Bookable\Extra\GetExtrasCommandHandler($c),
            Bookable\Extra\UpdateExtraCommand::class                  => new Bookable\Extra\UpdateExtraCommandHandler($c),

            Booking\Appointment\GetIcsCommand::class                  => new Booking\Appointment\GetIcsCommandHandler($c),

            // Coupon
            Coupon\AddCouponCommand::class                            => new Coupon\AddCouponCommandHandler($c),
            Coupon\DeleteCouponCommand::class                         => new Coupon\DeleteCouponCommandHandler($c),
            Coupon\GetCouponCommand::class                            => new Coupon\GetCouponCommandHandler($c),
            Coupon\GetCouponsCommand::class                           => new Coupon\GetCouponsCommandHandler($c),
            Coupon\GetValidCouponCommand::class                       => new Coupon\GetValidCouponCommandHandler($c),
            Coupon\UpdateCouponCommand::class                         => new Coupon\UpdateCouponCommandHandler($c),
            Coupon\UpdateCouponStatusCommand::class                   => new Coupon\UpdateCouponStatusCommandHandler($c),

            // CustomField
            CustomField\GetCustomFieldsCommand::class                 => new CustomField\GetCustomFieldsCommandHandler($c),
            CustomField\AddCustomFieldCommand::class                  => new CustomField\AddCustomFieldCommandHandler($c),
            CustomField\DeleteCustomFieldCommand::class               => new CustomField\DeleteCustomFieldCommandHandler($c),
            CustomField\UpdateCustomFieldCommand::class               => new CustomField\UpdateCustomFieldCommandHandler($c),
            CustomField\UpdateCustomFieldsPositionsCommand::class     => new CustomField\UpdateCustomFieldsPositionsCommandHandler($c),

            // Google
            Google\DisconnectFromGoogleAccountCommand::class          => new Google\DisconnectFromGoogleAccountCommandHandler($c),
            Google\FetchAccessTokenWithAuthCodeCommand::class         => new Google\FetchAccessTokenWithAuthCodeCommandHandler($c),
            Google\GetGoogleAuthURLCommand::class                     => new Google\GetGoogleAuthURLCommandHandler($c),

            // Location
            Location\AddLocationCommand::class                        => new Location\AddLocationCommandHandler($c),
            Location\DeleteLocationCommand::class                     => new Location\DeleteLocationCommandHandler($c),
            Location\GetLocationCommand::class                        => new Location\GetLocationCommandHandler($c),
            Location\GetLocationDeleteEffectCommand::class            => new Location\GetLocationDeleteEffectCommandHandler($c),
            Location\GetLocationsCommand::class                       => new Location\GetLocationsCommandHandler($c),
            Location\UpdateLocationCommand::class                     => new Location\UpdateLocationCommandHandler($c),
            Location\UpdateLocationStatusCommand::class               => new Location\UpdateLocationStatusCommandHandler($c),

            // Notification
            Notification\SendScheduledNotificationsCommand::class     => new Notification\SendScheduledNotificationsCommandHandler($c),

            // Payment
            PaymentGateway\PayPalPaymentCallbackCommand::class        => new PaymentGateway\PayPalPaymentCallbackCommandHandler($c),
            PaymentGateway\PayPalPaymentCommand::class                => new PaymentGateway\PayPalPaymentCommandHandler($c),
            PaymentGateway\WooCommercePaymentCommand::class           => new PaymentGateway\WooCommercePaymentCommandHandler($c),

            // Report
            Report\GetAppointmentsCommand::class                      => new Report\GetAppointmentsCommandHandler($c),
            Report\GetCouponsCommand::class                           => new Report\GetCouponsCommandHandler($c),
            Report\GetCustomersCommand::class                         => new Report\GetCustomersCommandHandler($c),
            Report\GetPaymentsCommand::class                          => new Report\GetPaymentsCommandHandler($c),

            // Search
            Search\GetSearchCommand::class                            => new Search\GetSearchCommandHandler($c),

            // Status
            Stats\AddStatsCommand::class                              => new AmeliaBooking\Application\Commands\Stats\AddStatsCommandHandler($c),
        ]);
    }

    return League\Tactician\Setup\QuickStart::create($commands);
};
// @codingStandardsIgnoreEnd

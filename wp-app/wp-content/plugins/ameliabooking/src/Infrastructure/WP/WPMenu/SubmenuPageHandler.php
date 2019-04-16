<?php

namespace AmeliaBooking\Infrastructure\WP\WPMenu;

use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;

/**
 * Renders menu pages
 */
class SubmenuPageHandler
{
    /** @var SettingsService $settingsService */
    private $settingsService;

    /**
     * SubmenuPageHandler constructor.
     *
     * @param SettingsService $settingsService
     */
    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Submenu page render function
     *
     * @param $page
     */
    public function render($page)
    {
        // Enqueue Scripts
        wp_enqueue_script(
            'amelia_booking_scripts',
            AMELIA_URL . 'public/js/backend/amelia-booking.js',
            [],
            AMELIA_VERSION
        );

        if ($page === 'wpamelia-locations' || $page === 'wpamelia-settings') {
            wp_enqueue_script(
                'google_maps_api',
                'https://maps.googleapis.com/maps/api/js?key=AIzaSyBbdMvZlipNEI2JgauxTv37tgWoAMUGkZQ&libraries=places'
            );
        }

        // Enqueue Styles
        wp_enqueue_style(
            'amelia_booking_styles',
            AMELIA_URL . 'public/css/backend/amelia-booking.css',
            [],
            AMELIA_VERSION
        );

        // WordPress enqueue
        wp_enqueue_media();

        // Strings Localization
        switch ($page) {
            case ('wpamelia-locations'):
                wp_localize_script(
                    'amelia_booking_scripts',
                    'wpAmeliaLabels',
                    array_merge(
                        BackendStrings::getEntityFormStrings(),
                        BackendStrings::getLocationStrings(),
                        BackendStrings::getCommonStrings()
                    )
                );

                break;
            case ('wpamelia-services'):
                wp_localize_script(
                    'amelia_booking_scripts',
                    'wpAmeliaLabels',
                    array_merge(
                        BackendStrings::getEntityFormStrings(),
                        BackendStrings::getServiceStrings(),
                        BackendStrings::getCommonStrings()
                    )
                );

                break;
            case ('wpamelia-employees'):
                wp_localize_script(
                    'amelia_booking_scripts',
                    'wpAmeliaLabels',
                    array_merge(
                        BackendStrings::getEntityFormStrings(),
                        BackendStrings::getUserStrings(),
                        BackendStrings::getEmployeeStrings(),
                        BackendStrings::getCommonStrings(),
                        BackendStrings::getScheduleStrings()
                    )
                );

                break;
            case ('wpamelia-customers'):
                wp_localize_script(
                    'amelia_booking_scripts',
                    'wpAmeliaLabels',
                    array_merge(
                        BackendStrings::getEntityFormStrings(),
                        BackendStrings::getUserStrings(),
                        BackendStrings::getCustomerStrings(),
                        BackendStrings::getCommonStrings(),
                        BackendStrings::getScheduleStrings()
                    )
                );

                break;
            case ('wpamelia-finance'):
                wp_localize_script(
                    'amelia_booking_scripts',
                    'wpAmeliaLabels',
                    array_merge(
                        BackendStrings::getEntityFormStrings(),
                        BackendStrings::getCommonStrings(),
                        BackendStrings::getFinanceStrings(),
                        BackendStrings::getPaymentStrings()
                    )
                );

                break;
            case ('wpamelia-appointments'):
                wp_localize_script(
                    'amelia_booking_scripts',
                    'wpAmeliaLabels',
                    array_merge(
                        BackendStrings::getEntityFormStrings(),
                        BackendStrings::getCommonStrings(),
                        BackendStrings::getUserStrings(),
                        BackendStrings::getCustomerStrings(),
                        BackendStrings::getAppointmentStrings(),
                        BackendStrings::getPaymentStrings()
                    )
                );

                break;
            case ('wpamelia-dashboard'):
                wp_localize_script(
                    'amelia_booking_scripts',
                    'wpAmeliaLabels',
                    array_merge(
                        BackendStrings::getEntityFormStrings(),
                        BackendStrings::getCommonStrings(),
                        BackendStrings::getAppointmentStrings(),
                        BackendStrings::getUserStrings(),
                        BackendStrings::getCustomerStrings(),
                        BackendStrings::getDashboardStrings(),
                        BackendStrings::getPaymentStrings()
                    )
                );

                break;
            case ('wpamelia-calendar'):
                wp_localize_script(
                    'amelia_booking_scripts',
                    'wpAmeliaLabels',
                    array_merge(
                        BackendStrings::getEntityFormStrings(),
                        BackendStrings::getCommonStrings(),
                        BackendStrings::getAppointmentStrings(),
                        BackendStrings::getUserStrings(),
                        BackendStrings::getCustomerStrings(),
                        BackendStrings::getCalendarStrings(),
                        BackendStrings::getPaymentStrings()
                    )
                );

                break;
            case ('wpamelia-notifications'):
                wp_localize_script(
                    'amelia_booking_scripts',
                    'wpAmeliaLabels',
                    array_merge(
                        BackendStrings::getCommonStrings(),
                        BackendStrings::getNotificationsStrings()
                    )
                );

                break;

            case ('wpamelia-smsnotifications'):
                wp_localize_script(
                    'amelia_booking_scripts',
                    'wpAmeliaLabels',
                    array_merge(
                        BackendStrings::getCommonStrings(),
                        BackendStrings::getNotificationsStrings()
                    )
                );

                break;
            case ('wpamelia-settings'):
                wp_localize_script(
                    'amelia_booking_scripts',
                    'wpAmeliaLabels',
                    array_merge(
                        BackendStrings::getCommonStrings(),
                        BackendStrings::getScheduleStrings(),
                        BackendStrings::getUserStrings(),
                        BackendStrings::getEmployeeStrings(),
                        BackendStrings::getSettingsStrings()
                    )
                );

                break;
            case ('wpamelia-customize'):
                wp_localize_script(
                    'amelia_booking_scripts',
                    'wpAmeliaLabels',
                    array_merge(
                        BackendStrings::getCustomizeStrings(),
                        BackendStrings::getCommonStrings()
                    )
                );

                break;
        }

        // Settings Localization
        wp_localize_script(
            'amelia_booking_scripts',
            'wpAmeliaSettings',
            $this->settingsService->getFrontendSettings()
        );

        wp_localize_script(
            'amelia_booking_scripts',
            'localeLanguage',
            AMELIA_LOCALE
        );

        include AMELIA_PATH . '/view/backend/view.php';
    }
}

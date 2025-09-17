<?php

namespace WhydotCo\TutorAutomation\Infrastructure\Cron\Jobs;

use WhydotCo\TutorAutomation\Common\Contracts\LoggerInterface;
use WhydotCo\TutorAutomation\Infrastructure\Logging\Logger;
use WhydotCo\TutorAutomation\Integrations\TutorLMS;
use WhydotCo\TutorAutomation\Integrations\WooCommerce;
use WhydotCo\TutorAutomation\Modules\SubscriptionManager\Services\EnrollmentService;

/**
 * Tarea programada (Cron Job) para sincronizar el estado de las inscripciones.
 *
 * Esta clase se ejecuta diariamente para auditar y corregir cualquier inconsistencia
 * entre las suscripciones de los usuarios y su acceso a los cursos.
 */
class SubscriptionCheckJob {

    private LoggerInterface $logger;

    public function __construct() {
        // Como esta clase es instanciada por el CronManager, creamos nuestro propio logger.
        $this->logger = new Logger( 'CronJob' );
    }

    /**
     * El método principal que ejecuta la tarea.
     */
    public function handle(): void {
        $this->logger->info( 'Iniciando la tarea de sincronización diaria de suscripciones.' );

        // Verificamos que la función de WooCommerce Subscriptions exista.
        if ( ! function_exists( 'wcs_get_subscriptions' ) ) {
            $this->logger->error( 'La función wcs_get_subscriptions() no existe. Abortando la tarea.' );
            return;
        }

        try {
            // Instanciamos los servicios que necesitamos.
            $tutorlms_api = new TutorLMS();
            $woocommerce_api = new WooCommerce();
            $enrollment_service = new EnrollmentService( $this->logger, $tutorlms_api, $woocommerce_api );

            // Obtenemos todas las suscripciones para encontrar los usuarios a procesar.
            $subscriptions = wcs_get_subscriptions( [ 'subscriptions_per_page' => -1 ] );
            
            if ( empty( $subscriptions ) ) {
                $this->logger->info( 'No se encontraron suscripciones para procesar. Tarea finalizada.' );
                return;
            }

            // Creamos una lista de IDs de usuario únicos para no procesar al mismo usuario varias veces.
            $user_ids_to_process = [];
            foreach ( $subscriptions as $subscription ) {
                $user_id = $subscription->get_user_id();
                if ( $user_id > 0 ) {
                    $user_ids_to_process[ $user_id ] = true;
                }
            }
            $unique_user_ids = array_keys( $user_ids_to_process );
            
            $this->logger->info( 'Se procesarán ' . count( $unique_user_ids ) . ' usuarios.' );

            foreach ( $unique_user_ids as $user_id ) {
                $this->logger->info( "Sincronizando permisos para el usuario #{$user_id}." );
                // Ejecutamos la lógica de cancelación/revocación primero.
                $enrollment_service->handle_subscription_cancelled( $user_id );
                // Luego, la lógica de activación/concesión.
                $enrollment_service->handle_subscription_activated( $user_id );
            }

        } catch ( \Exception $e ) {
            $this->logger->error( 'Ocurrió un error inesperado durante la tarea de sincronización: ' . $e->getMessage() );
        }

        $this->logger->info( 'Tarea de sincronización diaria de suscripciones finalizada.' );
    }
}
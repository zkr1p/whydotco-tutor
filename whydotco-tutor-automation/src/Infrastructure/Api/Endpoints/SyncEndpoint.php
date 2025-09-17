<?php

namespace WhydotCo\TutorAutomation\Infrastructure\Api\Endpoints;

use WhydotCo\TutorAutomation\Common\Contracts\LoggerInterface;
use WhydotCo\TutorAutomation\Infrastructure\Logging\Logger;
use WhydotCo\TutorAutomation\Integrations\TutorLMS;
use WhydotCo\TutorAutomation\Integrations\WooCommerce;
use WhydotCo\TutorAutomation\Modules\SubscriptionManager\Services\EnrollmentService;

/**
 * Contiene los métodos callback para los endpoints de la API relacionados con la sincronización.
 */
class SyncEndpoint {

    private LoggerInterface $logger;

    public function __construct() {
        // Cada endpoint crea su propio logger para registrar la actividad de la API.
        $this->logger = new Logger( 'API' );
    }

    /**
     * Maneja la petición para sincronizar manualmente los permisos de un usuario.
     *
     * @param \WP_REST_Request $request El objeto de la petición.
     * @return \WP_REST_Response La respuesta que se enviará como JSON.
     */
    public function handle_user_sync_request( \WP_REST_Request $request ): \WP_REST_Response {
        $user_id = (int) $request->get_param( 'user_id' );

        $this->logger->info( "Petición de sincronización recibida para el usuario #{$user_id}." );

        // Verificación adicional, aunque ya está validado en el ApiManager.
        $user = get_user_by( 'id', $user_id );
        if ( ! $user ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => 'Error: El usuario especificado no existe.',
            ], 404 ); // 404 Not Found
        }

        try {
            // Instanciamos los servicios necesarios para realizar la tarea.
            $tutorlms_api = new TutorLMS();
            $woocommerce_api = new WooCommerce();
            $enrollment_service = new EnrollmentService( $this->logger, $tutorlms_api, $woocommerce_api );

            // Reutilizamos la misma lógica que el Cron Job para una sincronización completa.
            $enrollment_service->handle_subscription_cancelled( $user_id );
            $enrollment_service->handle_subscription_activated( $user_id );

            $response_data = [
                'success' => true,
                'message' => "Sincronización completada para el usuario #{$user_id}.",
            ];

            return new \WP_REST_Response( $response_data, 200 ); // 200 OK

        } catch ( \Exception $e ) {
            $this->logger->error( "Error en la sincronización del usuario #{$user_id}: " . $e->getMessage() );

            return new \WP_REST_Response( [
                'success' => false,
                'message' => 'Ocurrió un error interno en el servidor durante la sincronización.',
            ], 500 ); // 500 Internal Server Error
        }
    }
}
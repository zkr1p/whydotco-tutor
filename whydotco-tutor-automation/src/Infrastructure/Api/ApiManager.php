<?php

namespace WhydotCo\TutorAutomation\Infrastructure\Api;

use WhydotCo\TutorAutomation\Infrastructure\Api\Endpoints\SyncEndpoint;

/**
 * Gestiona el registro y la configuración de los endpoints de la API REST del plugin.
 */
class ApiManager {
    /**
     * El namespace para la API del plugin.
     * La URL se verá así: /wp-json/whydotco/v1/
     * @var string
     */
    private string $namespace = 'whydotco/v1';

    /**
     * Constructor. Registra el hook para inicializar las rutas.
     */
    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    /**
     * Registra todas las rutas de la API del plugin.
     */
    public function register_routes(): void {
        $sync_endpoint = new SyncEndpoint();

        // Ejemplo de registro de una ruta:
        // POST /wp-json/whydotco/v1/sync/user
        register_rest_route(
            $this->namespace,
            '/sync/user',
            [
                'methods'             => \WP_REST_Server::EDITABLE, // EDITABLE es un alias para POST, PUT, PATCH
                'callback'            => [ $sync_endpoint, 'handle_user_sync_request' ],
                'permission_callback' => [ $this, 'can_manage_options' ],
                'args'                => [
                    'user_id' => [
                        'required'          => true,
                        'validate_callback' => function ( $param ) {
                            return is_numeric( $param ) && $param > 0;
                        },
                        'description'       => 'El ID del usuario a sincronizar.',
                    ],
                ],
            ]
        );
    }

    /**
     * Callback de permisos.
     *
     * Solo permite el acceso a usuarios que pueden gestionar las opciones del sitio (administradores).
     *
     * @return bool
     */
    public function can_manage_options(): bool {
        return current_user_can( 'manage_options' );
    }
}
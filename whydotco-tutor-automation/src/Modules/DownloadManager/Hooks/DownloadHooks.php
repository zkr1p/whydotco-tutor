<?php

namespace WhydotCo\TutorAutomation\Modules\DownloadManager\Hooks;

use WhydotCo\TutorAutomation\Common\Contracts\LoggerInterface;
use WhydotCo\TutorAutomation\Modules\DownloadManager\Services\PermissionService;

/**
 * Registra los hooks de WordPress para el módulo de descargas.
 */
class DownloadHooks {

    private PermissionService $permission_service;
    private LoggerInterface $logger;

    /**
     * Constructor.
     *
     * @param PermissionService $permission_service
     * @param LoggerInterface $logger
     */
    public function __construct( PermissionService $permission_service, LoggerInterface $logger ) {
        $this->permission_service = $permission_service;
        $this->logger = $logger;
    }

    /**
     * Añade todas las acciones y filtros necesarios a WordPress.
     */
    public function register_hooks(): void {
        // 1. Otorga permisos cuando una orden se marca como 'completada'.
        add_action( 'woocommerce_order_status_completed', [ $this, 'on_order_completed' ], 10, 1 );

        // 2. Controla la lógica de decremento del contador de descargas.
        // Este hook se dispara justo antes de que WooCommerce sirva el archivo.
        add_action( 'woocommerce_download_product', [ $this, 'on_process_download' ], 10, 6 );
    }

    /**
     * Método que se ejecuta cuando una orden se completa.
     *
     * @param int $order_id
     */
    public function on_order_completed( int $order_id ): void {
        $this->logger->info( "Orden #{$order_id} completada. Otorgando permisos de descarga." );
        $this->permission_service->grant_permissions_on_order_complete( $order_id );
    }

    /**
     * Se ejecuta cuando un cliente intenta descargar un archivo.
     * Decrementa el contador en nuestra tabla personalizada.
     *
     * @param string $email
     * @param string $order_key
     * @param int $product_id
     * @param int $user_id
     * @param string $download_id (Este es el ID de la fila de descarga de WC, no el nuestro)
     * @param int $order_id
     */
    public function on_process_download( string $email, string $order_key, int $product_id, int $user_id, string $download_id, int $order_id ): void {
        global $wpdb;
        $table_name = $wpdb->prefix . 'downloadable_product_permissions_new';

        // Obtenemos el permiso actual de nuestra tabla.
        $sql = $wpdb->prepare(
            "SELECT permission_id, download_count FROM {$table_name} WHERE product_id = %d AND user_id = %d LIMIT 1",
            $product_id,
            $user_id
        );
        $permission = $wpdb->get_row( $sql );

        // Si existe un permiso y el contador es mayor que 0, lo decrementamos.
        if ( $permission && $permission->download_count > 0 ) {
            $new_count = $permission->download_count - 1;

            $wpdb->update(
                $table_name,
                [
                    'download_count' => $new_count,
                    'updated_at'     => current_time( 'mysql' ),
                ],
                [ 'permission_id' => $permission->permission_id ],
                [ '%d', '%s' ],
                [ '%d' ]
            );

            $this->logger->info( "Descarga procesada para usuario #{$user_id}, producto #{$product_id}. Nuevo contador: {$new_count}." );
        }
    }
}
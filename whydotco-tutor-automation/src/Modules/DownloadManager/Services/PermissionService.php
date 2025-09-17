<?php

namespace WhydotCo\TutorAutomation\Modules\DownloadManager\Services;

use WhydotCo\TutorAutomation\Common\Contracts\LoggerInterface;
use WhydotCo\TutorAutomation\Integrations\WooCommerce;

/**
 * Servicio para gestionar la lógica de permisos de productos descargables.
 *
 * Interactúa directamente con la tabla personalizada de la base de datos para
 * otorgar y verificar los permisos de descarga.
 */
class PermissionService {

    private LoggerInterface $logger;
    private WooCommerce $woocommerce_api;
    private \wpdb $wpdb;
    private string $table_name;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger
     * @param WooCommerce $woocommerce_api
     */
    public function __construct( LoggerInterface $logger, WooCommerce $woocommerce_api ) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'downloadable_product_permissions_new';

        $this->logger = $logger;
        $this->woocommerce_api = $woocommerce_api;
    }

    /**
     * Otorga permisos de descarga para los productos de una orden completada.
     *
     * @param int $order_id El ID de la orden de WooCommerce.
     */
    public function grant_permissions_on_order_complete( int $order_id ): void {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        $user_id = $order->get_user_id();
        if ( ! $user_id ) {
            return;
        }

        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();

            if ( $product && $product->is_downloadable() ) {
                // Evita duplicados: solo otorga permiso si no existe previamente.
                if ( ! $this->permission_exists( $product->get_id(), $user_id ) ) {
                    $this->insert_permission( $product, $user_id );
                }
            }
        }
    }
    
    /**
     * Inserta un nuevo registro de permiso en la tabla personalizada.
     *
     * @param \WC_Product $product
     * @param int $user_id
     * @return bool
     */
    private function insert_permission( \WC_Product $product, int $user_id ): bool {
        $download_limit = $product->get_download_limit();
        // Si el límite es -1 (ilimitado en WC), lo guardamos como un número alto.
        $download_count = ( $download_limit === -1 ) ? 999 : $download_limit;

        // Asumimos que hay un solo archivo descargable por producto/variación.
        $downloadable_files = $product->get_downloads();
        $first_file = reset( $downloadable_files );

        if ( ! $first_file ) {
            $this->logger->warning( "El producto descargable #{$product->get_id()} no tiene archivos adjuntos." );
            return false;
        }
        $file_url = $first_file->get_file();
        $download_id = base64_encode( $file_url );

        $result = $this->wpdb->insert(
            $this->table_name,
            [
                'download_id'    => $download_id,
                'product_id'     => $product->get_id(),
                'user_id'        => $user_id,
                'download_count' => $download_count,
                'access_granted' => current_time( 'mysql' ),
                'created_at'     => current_time( 'mysql' ),
                'updated_at'     => current_time( 'mysql' ),
            ],
            [ '%s', '%d', '%d', '%d', '%s', '%s', '%s' ]
        );

        if ($result) {
            $this->logger->info( "Permiso de descarga otorgado para producto #{$product->get_id()} al usuario #{$user_id}." );
        } else {
            $this->logger->error( "Falló al insertar permiso de descarga para producto #{$product->get_id()} al usuario #{$user_id}." );
        }

        return (bool) $result;
    }

    /**
     * Verifica si ya existe un permiso para un producto y usuario.
     *
     * @param int $product_id
     * @param int $user_id
     * @return bool
     */
    private function permission_exists( int $product_id, int $user_id ): bool {
        $sql = $this->wpdb->prepare(
            "SELECT 1 FROM {$this->table_name} WHERE product_id = %d AND user_id = %d LIMIT 1",
            $product_id,
            $user_id
        );
        return (bool) $this->wpdb->get_var( $sql );
    }
}
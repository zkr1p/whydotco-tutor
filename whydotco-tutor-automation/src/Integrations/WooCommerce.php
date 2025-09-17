<?php

namespace WhydotCo\TutorAutomation\Integrations;

/**
 * Clase Wrapper para la interacción con WooCommerce y WooCommerce Subscriptions.
 *
 * Centraliza todas las llamadas a funciones de WC para mejorar la mantenibilidad
 * y proteger el plugin de futuros cambios en las APIs de WooCommerce.
 */
class WooCommerce {
    /**
     * Verifica si un usuario tiene al menos una suscripción activa.
     *
     * Utiliza la función de WooCommerce Subscriptions para obtener las suscripciones
     * del usuario y comprueba si alguna de ellas tiene el estado 'active'.
     *
     * @param int $user_id ID del usuario de WordPress.
     * @return bool True si tiene una suscripción activa, false en caso contrario.
     */
    public function has_active_subscription( int $user_id ): bool {
        // Primero, nos aseguramos de que la función de Subscriptions exista.
        if ( ! function_exists( 'wcs_get_users_subscriptions' ) ) {
            return false;
        }

        $subscriptions = wcs_get_users_subscriptions( $user_id );

        if ( empty( $subscriptions ) ) {
            return false;
        }

        foreach ( $subscriptions as $subscription ) {
            if ( $subscription->has_status( 'active' ) ) {
                return true; // Encontramos una activa, no necesitamos seguir buscando.
            }
        }

        return false;
    }

    /**
     * Verifica si un usuario ha comprado un producto específico.
     *
     * Comprueba el historial de órdenes del cliente para ver si alguna orden
     * con estado 'completed' o 'processing' contiene el producto.
     *
     * @param int $user_id    ID del usuario.
     * @param int $product_id ID del producto de WooCommerce.
     * @return bool True si ha comprado el producto, false en caso contrario.
     */
    public function has_purchased_product( int $user_id, int $product_id ): bool {
        if ( ! function_exists( 'wc_get_orders' ) ) {
            return false;
        }
        
        // wc_customer_bought_product() es la función nativa y optimizada de WooCommerce
        // para esta tarea exacta. Es más eficiente que iterar manualmente las órdenes.
        return wc_customer_bought_product( '', $user_id, $product_id );
    }

    /**
     * Obtiene el objeto de un producto de WooCommerce por su ID.
     *
     * @param int $product_id
     * @return \WC_Product|null
     */
    public function get_product( int $product_id ): ?\WC_Product {
        if ( function_exists( 'wc_get_product' ) ) {
            $product = wc_get_product( $product_id );
            return $product ? $product : null;
        }
        return null;
    }
}
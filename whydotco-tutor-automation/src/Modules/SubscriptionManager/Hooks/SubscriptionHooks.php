<?php

namespace WhydotCo\TutorAutomation\Modules\SubscriptionManager\Hooks;

use WhydotCo\TutorAutomation\Modules\SubscriptionManager\Services\EnrollmentService;

/**
 * Registra y gestiona los hooks de WordPress para el módulo de suscripciones.
 *
 * Esta clase actúa como el puente entre los eventos de WooCommerce Subscriptions
 * y nuestra lógica de negocio encapsulada en el EnrollmentService.
 */
class SubscriptionHooks {
    /**
     * El servicio que contiene la lógica de negocio.
     * @var EnrollmentService
     */
    private EnrollmentService $enrollment_service;

    /**
     * Constructor.
     *
     * @param EnrollmentService $enrollment_service El servicio de inscripciones.
     */
    public function __construct(EnrollmentService $enrollment_service) {
        $this->enrollment_service = $enrollment_service;
    }

    /**
     * Añade todas las acciones y filtros necesarios a WordPress.
     */
    public function register_hooks(): void {
        // Este es el hook principal de WooCommerce Subscriptions. Se dispara CADA VEZ
        // que el estado de una suscripción cambia.
        // Acepta 3 argumentos y tiene una prioridad de 10.
        add_action('woocommerce_subscription_status_changed', [$this, 'on_subscription_status_changed'], 10, 3);

        // Añadimos un hook de 'wp_login' como una capa extra de seguridad y sincronización.
        // Si por alguna razón un webhook falla, esto ayuda a corregir el estado del usuario
        // la próxima vez que inicie sesión.
        add_action('wp_login', [$this, 'on_user_login'], 10, 2);
    }

    /**
     * Se ejecuta cuando el estado de una suscripción cambia.
     *
     * @param int|\WC_Subscription $subscription El ID de la suscripción (o a veces el objeto).
     * @param string               $new_status   El nuevo estado (ej. 'active', 'cancelled').
     * @param string               $old_status   El estado anterior.
     */
    public function on_subscription_status_changed($subscription, string $new_status, string $old_status): void {

        // --- INICIO DE LA SOLUCIÓN ---
        // Se elimina el type-hint `\WC_Subscription` de la firma de la función para
        // aceptar el ID (entero) que pasa el hook sin causar un error fatal.

        // 1. Verificamos si el parámetro $subscription es numérico (un ID).
        if (is_numeric($subscription)) {
            // Si es un ID, usamos la función `wcs_get_subscription` para obtener el objeto completo.
            $subscription_obj = wcs_get_subscription($subscription);
        } else {
            // Si ya es un objeto, simplemente lo usamos.
            $subscription_obj = $subscription;
        }

        // 2. Nos aseguramos de que ahora tengamos un objeto de suscripción válido.
        //    Si por alguna razón no se pudo obtener, detenemos la ejecución para evitar más errores.
        if (!$subscription_obj instanceof \WC_Subscription) {
            return;
        }
        // --- FIN DE LA SOLUCIÓN ---

        $user_id = $subscription_obj->get_user_id();

        if (!$user_id) {
            return;
        }

        // Si el nuevo estado es 'activo', activamos la lógica de inscripción.
        if ('active' === $new_status) {
            $this->enrollment_service->handle_subscription_activated($user_id);
        }

        // Si el nuevo estado es uno de inactividad, activamos la lógica de cancelación.
        $inactive_statuses = ['on-hold', 'cancelled', 'expired'];
        if (in_array($new_status, $inactive_statuses, true)) {
            $this->enrollment_service->handle_subscription_cancelled($user_id);
        }
    }

    /**
     * Se ejecuta cuando un usuario inicia sesión.
     * Realiza una sincronización completa para asegurar que los permisos del usuario son correctos.
     *
     * @param string   $user_login El nombre de usuario.
     * @param \WP_User $user       El objeto del usuario.
     */
    public function on_user_login(string $user_login, \WP_User $user): void {
        $user_id = $user->ID;

        if (!$user_id) {
            return;
        }

        // Al iniciar sesión, forzamos una resincronización completa para este usuario.
        // Esto soluciona cualquier posible desajuste que haya ocurrido si algún webhook falló.
        $this->enrollment_service->handle_subscription_activated($user_id);
        $this->enrollment_service->handle_subscription_cancelled($user_id);
    }
}
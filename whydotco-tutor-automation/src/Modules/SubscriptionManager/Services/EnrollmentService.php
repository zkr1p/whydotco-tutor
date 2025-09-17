<?php

namespace WhydotCo\TutorAutomation\Modules\SubscriptionManager\Services;

use WhydotCo\TutorAutomation\Common\Contracts\LoggerInterface;
use WhydotCo\TutorAutomation\Integrations\TutorLMS;
use WhydotCo\TutorAutomation\Integrations\WooCommerce;

/**
 * Servicio encargado de toda la lógica de negocio para inscribir y
 * desinscribir usuarios de los cursos de Tutor LMS.
 */
class EnrollmentService {

    private LoggerInterface $logger;
    private TutorLMS $tutorlms_api;
    private WooCommerce $woocommerce_api;

    /**
     * Constructor del servicio.
     *
     * @param LoggerInterface $logger
     * @param TutorLMS $tutorlms_api
     * @param WooCommerce $woocommerce_api
     */
    public function __construct( LoggerInterface $logger, TutorLMS $tutorlms_api, WooCommerce $woocommerce_api ) {
        $this->logger = $logger;
        $this->tutorlms_api = $tutorlms_api;
        $this->woocommerce_api = $woocommerce_api;
    }

    /**
     * Gestiona el proceso cuando una suscripción de un usuario se activa.
     * Inscribe al usuario en todos los cursos a los que ahora tiene acceso.
     *
     * @param int $user_id El ID del usuario.
     */
    public function handle_subscription_activated( int $user_id ): void {
        $this->logger->info( "Suscripción activada para el usuario #{$user_id}. Iniciando proceso de inscripción masiva." );

        $all_courses = $this->tutorlms_api->get_all_courses();

        if ( empty( $all_courses ) ) {
            $this->logger->warning( 'No se encontraron cursos de Tutor LMS para procesar.' );
            return;
        }

        foreach ( $all_courses as $course ) {
            // Se inscribe al usuario si tiene una suscripción activa y no está ya inscrito.
            // La lógica de si debe tener acceso se centraliza en user_should_have_access.
            if ( ! $this->tutorlms_api->is_user_enrolled( $user_id, $course->ID ) && $this->user_should_have_access($user_id, $course->ID) ) {
                $this->tutorlms_api->enroll_user( $user_id, $course->ID );
                $this->logger->info( "Usuario #{$user_id} inscrito en el curso '{$course->post_title}' (#{$course->ID}) debido a suscripción activa." );
            }
        }
    }

    /**
     * Gestiona el proceso cuando una suscripción de un usuario se cancela o expira.
     * Revoca el acceso a los cursos, a menos que el usuario tenga otra forma de acceso válida.
     *
     * @param int $user_id El ID del usuario.
     */
    public function handle_subscription_cancelled( int $user_id ): void {
        $this->logger->info( "Suscripción cancelada/expirada para el usuario #{$user_id}. Evaluando revocación de acceso." );

        $all_courses = $this->tutorlms_api->get_all_courses();

        if ( empty( $all_courses ) ) {
            $this->logger->warning( 'No se encontraron cursos de Tutor LMS para procesar.' );
            return;
        }

        foreach ( $all_courses as $course ) {
            // Solo nos interesan los cursos en los que el usuario está actualmente inscrito.
            if ( ! $this->tutorlms_api->is_user_enrolled( $user_id, $course->ID ) ) {
                continue;
            }

            if ( ! $this->user_should_have_access( $user_id, $course->ID ) ) {
                $this->tutorlms_api->cancel_enrollment( $user_id, $course->ID );
                $this->logger->info( "Acceso revocado para el usuario #{$user_id} al curso '{$course->post_title}' (#{$course->ID})." );
            } else {
                $this->logger->info( "Se mantiene el acceso del usuario #{$user_id} al curso '{$course->post_title}' (#{$course->ID}) debido a compra individual o acceso VIP." );
            }
        }
    }

    /**
     * El núcleo de la lógica de negocio. Verifica si un usuario debe tener acceso a un curso.
     *
     * @param int $user_id El ID del usuario.
     * @param int $course_id El ID del curso.
     * @return bool True si debe tener acceso, false en caso contrario.
     */
    private function user_should_have_access( int $user_id, int $course_id ): bool {
        // Condición 1: ¿Tiene una suscripción activa?
        if ( $this->woocommerce_api->has_active_subscription( $user_id ) ) {
            return true;
        }

        // Condición 2: ¿Compró el curso individualmente?
        $product_id = $this->tutorlms_api->get_attached_product_id( $course_id );
        if ( $product_id && $this->woocommerce_api->has_purchased_product( $user_id, $product_id ) ) {
            return true;
        }

        // ¡AHORA ACTIVADO!
        // Condición 3: ¿Está en la lista VIP del curso?
        if ( $this->tutorlms_api->is_user_in_vip_list( $user_id, $course_id ) ) {
            return true;
        }
        
        // Si no cumple ninguna condición, no debe tener acceso.
        return false;
    }
}
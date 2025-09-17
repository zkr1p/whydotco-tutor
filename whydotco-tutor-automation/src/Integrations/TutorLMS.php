<?php

namespace WhydotCo\TutorAutomation\Integrations;

/**
 * Clase Wrapper para la interacción con el plugin Tutor LMS.
 *
 * Centraliza todas las llamadas a funciones y métodos de Tutor LMS, actuando como
 * una capa de abstracción. Esto protege nuestro plugin de cambios en futuras
 * actualizaciones de Tutor LMS.
 */
class TutorLMS {
    /**
     * Verifica si un usuario ya está inscrito en un curso específico.
     *
     * @param int $user_id   ID del usuario de WordPress.
     * @param int $course_id ID del post del curso de Tutor LMS.
     * @return bool True si está inscrito, false en caso contrario.
     */
    public function is_user_enrolled( int $user_id, int $course_id ): bool {
        if ( function_exists( 'tutor_utils' ) ) {
            return tutor_utils()->is_enrolled( $course_id, $user_id );
        }
        return false;
    }

    /**
     * Inscribe a un usuario en un curso.
     *
     * @param int $user_id   ID del usuario a inscribir.
     * @param int $course_id ID del curso donde se inscribirá.
     * @return bool True si la inscripción fue exitosa, false si falló.
     */
    public function enroll_user( int $user_id, int $course_id ): bool {
        if ( function_exists( 'tutor_utils' ) ) {
            // 'do_enroll' se encarga de todo: crea el post de inscripción,
            // actualiza los metadatos y ejecuta los hooks necesarios.
            $enrollment = tutor_utils()->do_enroll( $course_id, 0, $user_id );
            return (bool) $enrollment;
        }
        return false;
    }

    /**
     * Cancela la inscripción de un usuario en un curso.
     *
     * @param int $user_id   ID del usuario a desinscribir.
     * @param int $course_id ID del curso.
     * @return bool True si la cancelación fue exitosa, false si falló.
     */
    public function cancel_enrollment( int $user_id, int $course_id ): bool {
        if ( function_exists( 'tutor_utils' ) ) {
            // 'cancel_enrolment' busca la inscripción y cambia su estado a 'cancel'.
            return tutor_utils()->cancel_enrolment( $course_id, $user_id );
        }
        return false;
    }

    /**
     * Obtiene todos los cursos publicados de Tutor LMS.
     *
     * @return array<int, \WP_Post> Un array de objetos WP_Post.
     */
    public function get_all_courses(): array {
        return get_posts( [
            'post_type'      => tutor()->course_post_type,
            'post_status'    => 'publish',
            'posts_per_page' => -1, // -1 para obtener todos los cursos.
        ] );
    }

    /**
     * Obtiene el ID del producto de WooCommerce asociado a un curso de Tutor LMS.
     *
     * @param int $course_id El ID del curso.
     * @return int|null El ID del producto, o null si no está asociado.
     */
    public function get_attached_product_id( int $course_id ): ?int {
        $product_id = get_post_meta( $course_id, '_tutor_course_product_id', true );
        return ! empty( $product_id ) ? (int) $product_id : null;
    }

    /**
     * ¡NUEVO! Verifica si un usuario está en la lista de acceso VIP de un curso.
     *
     * @param int $user_id   ID del usuario.
     * @param int $course_id ID del curso.
     * @return bool True si el email del usuario está en la lista, false en caso contrario.
     */
    public function is_user_in_vip_list( int $user_id, int $course_id ): bool {
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return false;
        }
        $user_email = $user->user_email;

        // Obtenemos la lista de correos guardada en el metabox.
        $vip_list_raw = get_post_meta( $course_id, '_vip_student_list', true );
        if ( empty( $vip_list_raw ) ) {
            return false;
        }

        // Procesamos la lista para convertirla en un array de correos limpios.
        // \s*,\s* o saltos de línea.
        $emails = preg_split( '/\r\n|\r|\n|,/', $vip_list_raw );
        
        $vip_emails = [];
        foreach ($emails as $email) {
            $trimmed_email = trim($email);
            if (is_email($trimmed_email)) {
                $vip_emails[] = strtolower($trimmed_email);
            }
        }
        
        // Comprobamos si el email del usuario (en minúsculas) está en la lista.
        return in_array( strtolower( $user_email ), $vip_emails, true );
    }
}
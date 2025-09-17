<?php

namespace WhydotCo\TutorAutomation\Modules\Admin;

use WhydotCo\TutorAutomation\Common\Contracts\LoggerInterface;
use WhydotCo\TutorAutomation\Common\Contracts\ModuleInterface;
use WhydotCo\TutorAutomation\Modules\Admin\Pages\LogsPage;
use WhydotCo\TutorAutomation\Modules\Admin\Pages\SettingsPage;

/**
 * Módulo para gestionar toda la interfaz de usuario en el panel de administración de WordPress.
 */
class Module implements ModuleInterface {

    private LoggerInterface $logger;
    private SettingsPage $settings_page;
    private LogsPage $logs_page;

    public function __construct( LoggerInterface $logger ) {
        $this->logger = $logger;
        $this->settings_page = new SettingsPage( $this->logger );
        $this->logs_page = new LogsPage( $this->logger );
    }

    public function init(): void {
        $this->logger->info( 'Inicializando el módulo Admin.' );
        add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
        
        // ¡NUEVO! Enganchamos la configuración del metabox.
        $this->setup_course_metabox();
        $this->setup_my_account_tabs();
    }

    public function register_admin_menu(): void {
        // ... (el código del menú que ya teníamos permanece igual) ...
        add_menu_page(
            __( 'Tutor Automation', 'whydotco-tutor-automation' ),
            __( 'Tutor Automation', 'whydotco-tutor-automation' ),
            'manage_options',
            'whydotco-tutor-automation',
            [ $this->settings_page, 'render' ],
            'dashicons-admin-generic',
            30
        );
        add_submenu_page(
            'whydotco-tutor-automation',
            __( 'Ajustes', 'whydotco-tutor-automation' ),
            __( 'Ajustes', 'whydotco-tutor-automation' ),
            'manage_options',
            'whydotco-tutor-automation',
            [ $this->settings_page, 'render' ]
        );
        add_submenu_page(
            'whydotco-tutor-automation',
            __( 'Logs de Actividad', 'whydotco-tutor-automation' ),
            __( 'Logs', 'whydotco-tutor-automation' ),
            'manage_options',
            'whydotco-tutor-logs',
            [ $this->logs_page, 'render' ]
        );
    }
    

    /**
     * ¡NUEVO! Registra los hooks para las pestañas de "Mi Cuenta".
     */
    public function setup_my_account_tabs(): void {
        // Añade el nuevo endpoint para la URL (ej. /mi-cuenta/mis-reservas/)
        add_action( 'init', function() {
            add_rewrite_endpoint( 'mis-reservas', EP_ROOT | EP_PAGES );
        });

        // Añade la nueva pestaña a la lista
        add_filter( 'woocommerce_account_menu_items', [ $this, 'add_my_bookings_tab' ] );

        // Renderiza el contenido de la pestaña
        add_action( 'woocommerce_account_mis-reservas_endpoint', [ $this, 'render_my_bookings_content' ] );
    }

    /**
     * ¡NUEVO! Añade el enlace "Mis Reservas" al menú de Mi Cuenta.
     *
     * @param array $items
     * @return array
     */
    public function add_my_bookings_tab( array $items ): array {
        // Insertamos la nueva pestaña después de 'orders'
        $new_items = [];
        foreach ($items as $key => $value) {
            $new_items[$key] = $value;
            if ($key === 'orders') {
                $new_items['mis-reservas'] = __( 'Mis Reservas', 'whydotco-tutor-automation' );
            }
        }
        return $new_items;
    }

    /**
     * ¡NUEVO! Muestra el contenido de la pestaña "Mis Reservas".
     */
    public function render_my_bookings_content(): void {
        echo '<h3>' . esc_html__( 'Mis Próximas Reservas', 'whydotco-tutor-automation' ) . '</h3>';
        // Mostramos el shortcode de Amelia para las citas del cliente.
        echo do_shortcode( '[ameliafrontenceployeepanel]' );
    }

    /**
     * ¡NUEVO! Registra los hooks para el metabox de cursos.
     */
    public function setup_course_metabox(): void {
        add_action( 'add_meta_boxes', [ $this, 'add_vip_metabox' ] );
        add_action( 'save_post', [ $this, 'save_vip_metabox' ] );
    }

    /**
     * ¡NUEVO! Añade el metabox a la pantalla de edición de cursos de Tutor LMS.
     */
    public function add_vip_metabox(): void {
        add_meta_box(
            'whydotco_vip_students_metabox',
            __( 'Acceso VIP de Estudiantes', 'whydotco-tutor-automation' ),
            [ $this, 'render_vip_metabox' ],
            'courses', // El tipo de post de los cursos de Tutor LMS
            'side',
            'default'
        );
    }

    /**
     * ¡NUEVO! Renderiza el HTML del metabox.
     *
     * @param \WP_Post $post El objeto del post actual.
     */
    public function render_vip_metabox( \WP_Post $post ): void {
        // Añade un campo de seguridad (nonce).
        wp_nonce_field( 'whydotco_save_vip_list', 'whydotco_vip_nonce' );

        $vip_list = get_post_meta( $post->ID, '_vip_student_list', true );
        ?>
        <p><?php esc_html_e( 'Introduce una lista de correos electrónicos, uno por línea. Los usuarios con estos correos tendrán acceso permanente a este curso.', 'whydotco-tutor-automation' ); ?></p>
        <textarea name="vip_student_list" id="vip_student_list" rows="10" style="width:100%;"><?php echo esc_textarea( $vip_list ); ?></textarea>
        <?php
    }

    /**
     * ¡NUEVO! Guarda los datos del metabox cuando se actualiza el curso.
     *
     * @param int $post_id El ID del post que se está guardando.
     */
    public function save_vip_metabox( int $post_id ): void {
        // 1. Verificación de seguridad (nonce).
        if ( ! isset( $_POST['whydotco_vip_nonce'] ) || ! wp_verify_nonce( $_POST['whydotco_vip_nonce'], 'whydotco_save_vip_list' ) ) {
            return;
        }

        // 2. No guardar en autoguardados.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // 3. Verificar permisos del usuario.
        if ( isset( $_POST['post_type'] ) && 'courses' === $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }
        }

        // 4. Guardar los datos.
        if ( isset( $_POST['vip_student_list'] ) ) {
            // Sanitizamos el contenido para asegurar que solo guardamos texto plano.
            $sanitized_list = sanitize_textarea_field( $_POST['vip_student_list'] );
            update_post_meta( $post_id, '_vip_student_list', $sanitized_list );
        }
    }
}
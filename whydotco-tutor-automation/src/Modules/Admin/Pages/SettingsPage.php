<?php

namespace WhydotCo\TutorAutomation\Modules\Admin\Pages;

use WhydotCo\TutorAutomation\Common\Contracts\LoggerInterface;

/**
 * Gestiona la página de ajustes del plugin en el panel de administración.
 */
class SettingsPage {

    private LoggerInterface $logger;
    private const OPTION_GROUP = 'whydotco_tutor_automation_settings';
    private const OPTION_NAME = 'whydotco_tutor_automation_options';

    public function __construct( LoggerInterface $logger ) {
        $this->logger = $logger;
        // Engancha los métodos a los hooks de administración.
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    /**
     * Renderiza el contenido HTML de la página de ajustes.
     */
    public function render(): void {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Ajustes de Tutor Automation', 'whydotco-tutor-automation' ); ?></h1>
            <p><?php esc_html_e( 'Configura las opciones principales para la automatización de Tutor LMS y WooCommerce.', 'whydotco-tutor-automation' ); ?></p>
            
            <form method="post" action="options.php">
                <?php
                // Funciones de WordPress que renderizan los campos y la seguridad del formulario.
                settings_fields( self::OPTION_GROUP );
                do_settings_sections( 'whydotco-tutor-automation-settings-page' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Registra las secciones y campos de ajustes usando la Settings API.
     */
    public function register_settings(): void {
        // Registra el grupo de opciones. WordPress guardará todas las opciones
        // en un único array en la base de datos, lo cual es muy eficiente.
        register_setting( self::OPTION_GROUP, self::OPTION_NAME, [ $this, 'sanitize_options' ] );

        // --- Sección de Disparadores de Email ---
        add_settings_section(
            'email_triggers_section',
            __( 'Disparadores de Email', 'whydotco-tutor-automation' ),
            [ $this, 'render_email_triggers_section_text' ],
            'whydotco-tutor-automation-settings-page'
        );

        add_settings_field(
            'product_id_preorder',
            __( 'ID del Producto de Pre-Venta', 'whydotco-tutor-automation' ),
            [ $this, 'render_text_input' ],
            'whydotco-tutor-automation-settings-page',
            'email_triggers_section',
            [ 'id' => 'product_id_preorder', 'description' => __( 'El ID del producto de WooCommerce que dispara el email de pre-venta.', 'whydotco-tutor-automation' ) ]
        );
        add_settings_field(
            'product_id_sub_annual',
            __( 'ID de Suscripción Anual', 'whydotco-tutor-automation' ),
            [ $this, 'render_text_input' ],
            'whydotco-tutor-automation-settings-page',
            'email_triggers_section',
            [ 'id' => 'product_id_sub_annual', 'description' => __( 'ID del producto para la bienvenida a la suscripción anual.', 'whydotco-tutor-automation' ) ]
        );
        add_settings_field(
            'product_id_sub_monthly',
            __( 'ID de Suscripción Mensual', 'whydotco-tutor-automation' ),
            [ $this, 'render_text_input' ],
            'whydotco-tutor-automation-settings-page',
            'email_triggers_section',
            [ 'id' => 'product_id_sub_monthly', 'description' => __( 'ID del producto para la bienvenida a la suscripción mensual.', 'whydotco-tutor-automation' ) ]
        );
    }

    /**
     * Renderiza el texto de introducción para la sección de emails.
     */
    public function render_email_triggers_section_text(): void {
        echo '<p>' . esc_html__( 'Introduce los IDs de los productos de WooCommerce que activarán los correos electrónicos personalizados.', 'whydotco-tutor-automation' ) . '</p>';
    }

    /**
     * Renderiza un campo de texto genérico.
     *
     * @param array $args Argumentos pasados desde add_settings_field.
     */
    public function render_text_input( array $args ): void {
        $options = get_option( self::OPTION_NAME, [] );
        $id = $args['id'];
        $value = $options[ $id ] ?? '';
        
        echo "<input type='number' id='{$id}' name='" . self::OPTION_NAME . "[{$id}]' value='" . esc_attr( $value ) . "' class='regular-text' />";
        if ( ! empty( $args['description'] ) ) {
            echo "<p class='description'>" . esc_html( $args['description'] ) . "</p>";
        }
    }

    /**
     * Sanitiza los datos de las opciones antes de guardarlos en la base de datos.
     *
     * @param array $input Los datos enviados por el formulario.
     * @return array Los datos sanitizados.
     */
    public function sanitize_options( array $input ): array {
        $sanitized_input = [];
        foreach ( $input as $key => $value ) {
            // Se asegura de que todos los valores sean números enteros positivos.
            $sanitized_input[ $key ] = absint( $value );
        }
        return $sanitized_input;
    }
}
<?php
/**
 * Plugin Name:       WhydotCo Tutor Automation Pro
 * Plugin URI:        https://whydotco.co/
 * Description:       Gestiona la automatización avanzada entre Tutor LMS y WooCommerce Subscriptions, incluyendo el acceso a cursos y productos descargables.
 * Version:           1.0.5
 * Author:            WhydotCo
 * Author URI:        https://whydotco.co/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       whydotco-tutor-automation
 * Domain Path:       /languages
 * Requires PHP:      8.0
 * Requires at least: 6.0
 * Tested up to:      6.8
 * WC requires at least: 8.0
 * WC tested up to: 9.1
 */

// Si este archivo es llamado directamente, abortar.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// =========================================================================
// 1. Definición de Constantes del Plugin
// =========================================================================
define( 'WHYDOTCO_TUTOR_AUTOMATION_VERSION', '1.0.2' ); // Actualizado para reflejar cambios
define( 'WHYDOTCO_TUTOR_AUTOMATION_PLUGIN_FILE', __FILE__ );
define( 'WHYDOTCO_TUTOR_AUTOMATION_PATH', plugin_dir_path( WHYDOTCO_TUTOR_AUTOMATION_PLUGIN_FILE ) );
define( 'WHYDOTCO_TUTOR_AUTOMATION_URL', plugin_dir_url( WHYDOTCO_TUTOR_AUTOMATION_PLUGIN_FILE ) );
define( 'WHYDOTCO_TUTOR_AUTOMATION_BASENAME', plugin_basename( WHYDOTCO_TUTOR_AUTOMATION_PLUGIN_FILE ) );


// =========================================================================
// 2. Cargar el Autoloader de Composer
// =========================================================================
// Esencial para cargar automáticamente nuestras clases PHP sin 'require' manuales.
if ( ! file_exists( WHYDOTCO_TUTOR_AUTOMATION_PATH . 'vendor/autoload.php' ) ) {
    // Muestra un aviso en el admin si Composer no ha sido ejecutado.
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__( 'WhydotCo Tutor Automation Pro: Las dependencias de Composer no están instaladas. Por favor, ejecute "composer install" en la carpeta del plugin.', 'whydotco-tutor-automation' );
        echo '</p></div>';
    });
    return;
}
require_once WHYDOTCO_TUTOR_AUTOMATION_PATH . 'vendor/autoload.php';


// =========================================================================
// 3. Funciones de Ciclo de Vida del Plugin (Activación/Desactivación)
// =========================================================================
/**
 * MODIFICACIÓN: Creamos funciones wrapper para la activación y desactivación.
 * Esto asegura que las clases Activator y Deactivator se carguen manualmente,
 * evitando errores si el autoloader no está disponible durante la activación.
 */
function whydotco_tutor_automation_activate_plugin() {
    // Cargamos manualmente el archivo necesario.
    require_once WHYDOTCO_TUTOR_AUTOMATION_PATH . 'src/Core/Activator.php';
    \WhydotCo\TutorAutomation\Core\Activator::activate();
}

function whydotco_tutor_automation_deactivate_plugin() {
    // Cargamos manualmente el archivo necesario.
    require_once WHYDOTCO_TUTOR_AUTOMATION_PATH . 'src/Core/Deactivator.php';
    \WhydotCo\TutorAutomation\Core\Deactivator::deactivate();
}

// Estas funciones se ejecutan UNA SOLA VEZ al activar o desactivar el plugin.
register_activation_hook( __FILE__, 'whydotco_tutor_automation_activate_plugin' );
register_deactivation_hook( __FILE__, 'whydotco_tutor_automation_deactivate_plugin' );


// =========================================================================
// 4. Función Principal de Arranque del Plugin
// =========================================================================
/**
 * Inicia el plugin.
 *
 * Esta función se engancha a 'plugins_loaded', asegurando que todos los
 * plugins necesarios (como WooCommerce y Tutor LMS) ya han sido cargados
 * antes de que nuestro código se ejecute.
 */
function whydotco_tutor_automation_run() {

    // Comprobación de dependencias críticas.
    if ( ! function_exists( 'tutor' ) || ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function() {
            $message = sprintf(
                /* translators: %s: Nombre del plugin */
                esc_html__( '%s requiere Tutor LMS y WooCommerce para funcionar. Por favor, asegúrese de que ambos plugins estén activos.', 'whydotco-tutor-automation' ),
                '<strong>' . esc_html__( 'WhydotCo Tutor Automation Pro', 'whydotco-tutor-automation' ) . '</strong>'
            );
            echo '<div class="notice notice-error is-dismissible"><p>' . wp_kses_post( $message ) . '</p></div>';
        });
        return; // Detiene la ejecución si las dependencias no están.
    }

    // Si todo está correcto, obtenemos la instancia principal del plugin.
    try {
        \WhydotCo\TutorAutomation\Core\Plugin::get_instance();
    } catch ( \Exception $e ) {
        // Capturamos cualquier excepción durante la inicialización.
        $error_message = $e->getMessage();
        add_action( 'admin_notices', function() use ( $error_message ) {
            echo '<div class="notice notice-error"><p>';
            /* translators: %s: Mensaje de error */
            echo sprintf( esc_html__( 'Error al inicializar WhydotCo Tutor Automation Pro: %s', 'whydotco-tutor-automation' ), esc_html( $error_message ) );
            echo '</p></div>';
        });
    }
}
// Enganchamos nuestra función de arranque al hook 'plugins_loaded' con una prioridad de 10.
add_action( 'plugins_loaded', 'whydotco_tutor_automation_run' );
<?php
/**
 * Lógica que se ejecuta cuando el usuario elimina el plugin.
 *
 * @package WhydotCo\TutorAutomation
 */

// Si no se está desinstalando, salir.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// 1. Eliminar las opciones guardadas de la base de datos.
delete_option( 'whydotco_tutor_automation_migrations' );
delete_option( 'whydotco_tutor_automation_options' );
delete_option( 'whydotco_tutor_automation_version' );

// 2. Eliminar la tabla personalizada de permisos de descarga.
$table_name = $wpdb->prefix . 'downloadable_product_permissions_new';
// Usamos una consulta directa SQL para eliminar la tabla.
// La comprobación 'IF EXISTS' previene errores si la tabla ya fue eliminada.
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

// 3. Limpiar cualquier tarea programada (cron) que pueda haber quedado.
wp_clear_scheduled_hook( 'whydotco_daily_sync_hook' );
<?php

namespace WhydotCo\TutorAutomation\Database\Migrations;

/**
 * Migración para crear la tabla de logs.
 *
 * Esta tabla se usará en el futuro para registrar eventos importantes del plugin
 * directamente en la base de datos para un análisis más avanzado.
 */
class CreateLogsTable {
    /**
     * Ejecuta la migración.
     *
     * @return void
     */
    public function up(): void {
        global $wpdb;
        $table_name = $wpdb->prefix . 'whydotco_tutor_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            log_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            timestamp DATETIME NOT NULL,
            level VARCHAR(20) NOT NULL,
            channel VARCHAR(255) NOT NULL,
            message LONGTEXT NOT NULL,
            context LONGTEXT NULL,
            PRIMARY KEY (log_id),
            KEY level (level)
        ) {$charset_collate};";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
}
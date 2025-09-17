<?php

namespace WhydotCo\TutorAutomation\Database\Migrations;

/**
 * Migración para crear la tabla 'downloadable_product_permissions_new'.
 *
 * Esta tabla es una réplica exacta de la tabla utilizada en el plugin original
 * para garantizar la compatibilidad y no perder los datos de los clientes existentes.
 */
class CreateDownloadableProductPermissionsNewTable {
    /**
     * Ejecuta la migración.
     *
     * @return void
     */
    public function up(): void {
        global $wpdb;
        $table_name = $wpdb->prefix . 'downloadable_product_permissions_new';
        $charset_collate = $wpdb->get_charset_collate();

        // Se utiliza 'CREATE TABLE IF NOT EXISTS' como medida de seguridad fundamental.
        // Si la tabla ya existe en el sitio en producción, esta consulta no hará nada
        // y no producirá ningún error, garantizando una transición segura.
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            permission_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            download_id VARCHAR(255) NOT NULL,
            product_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            download_count INT(9) NOT NULL DEFAULT 0,
            access_granted DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            created_at TIMESTAMP NULL DEFAULT NULL,
            updated_at TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (permission_id),
            KEY product_id (product_id),
            KEY user_id (user_id),
            KEY download_id_product_user (download_id, product_id, user_id)
        ) {$charset_collate};";

        // dbDelta es la función recomendada por WordPress para ejecutar SQL
        // que define o modifica la estructura de la base de datos.
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
}
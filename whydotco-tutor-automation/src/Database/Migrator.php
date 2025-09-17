<?php

namespace WhydotCo\TutorAutomation\Database;

/**
 * Gestiona las migraciones de la base de datos.
 *
 * Esta clase se encarga de encontrar, registrar y ejecutar los archivos de migración
 * necesarios para crear o actualizar las tablas personalizadas del plugin.
 */
class Migrator {
    /**
     * El nombre de la opción en la tabla wp_options donde se guardan las migraciones ejecutadas.
     * @var string
     */
    private const MIGRATION_OPTION_KEY = 'whydotco_tutor_automation_migrations';

    /**
     * Ejecuta todas las migraciones pendientes.
     *
     * Compara los archivos de migración en el directorio con los que ya han sido
     * ejecutados y corre solo los nuevos.
     *
     * @return void
     * @throws \Exception Si un archivo de migración o su clase no se encuentra.
     */
    public static function run(): void {
        $all_migration_files = self::get_migration_files();
        $ran_migrations = get_option( self::MIGRATION_OPTION_KEY, [] );

        $pending_migrations = array_diff( $all_migration_files, $ran_migrations );

        if ( empty( $pending_migrations ) ) {
            // No hay nada que hacer.
            return;
        }

        foreach ( $pending_migrations as $migration_file ) {
            $path = WHYDOTCO_TUTOR_AUTOMATION_PATH . 'src/Database/Migrations/' . $migration_file;

            if ( ! file_exists( $path ) ) {
                throw new \Exception( "Archivo de migración no encontrado: {$migration_file}" );
            }
            require_once $path;

            // Convierte el nombre del archivo (ej. 2025_09_12_100000_create_logs_table.php)
            // en un nombre de clase (ej. CreateLogsTable)
            $class_name_parts = array_slice( explode( '_', pathinfo( $migration_file, PATHINFO_FILENAME ) ), 4 );
            $class_name = '\\WhydotCo\\TutorAutomation\\Database\\Migrations\\' . implode( '', array_map( 'ucfirst', $class_name_parts ) );


            if ( ! class_exists( $class_name ) ) {
                throw new \Exception( "Clase de migración no encontrada: {$class_name}" );
            }

            $migration_instance = new $class_name();

            // Ejecuta el método 'up' que contiene la lógica SQL.
            if ( method_exists( $migration_instance, 'up' ) ) {
                $migration_instance->up();
            }

            // Registra esta migración como ejecutada.
            $ran_migrations[] = $migration_file;
        }

        // Guarda la lista actualizada de migraciones ejecutadas.
        update_option( self::MIGRATION_OPTION_KEY, $ran_migrations );
    }

    /**
     * Obtiene una lista de todos los archivos de migración del directorio de migraciones.
     *
     * @return array<int, string> Lista de nombres de archivo.
     */
    private static function get_migration_files(): array {
        $migrations_path = WHYDOTCO_TUTOR_AUTOMATION_PATH . 'src/Database/Migrations/';
        
        if ( ! is_dir( $migrations_path ) ) {
            return [];
        }

        $files = scandir( $migrations_path );
        
        // Filtra para devolver solo archivos .php, excluyendo '.' y '..'.
        return array_filter( $files, function( $file ) {
            return ! in_array( $file, [ '.', '..' ] ) && is_file( WHYDOTCO_TUTOR_AUTOMATION_PATH . 'src/Database/Migrations/' . $file ) && pathinfo( $file, PATHINFO_EXTENSION ) === 'php';
        });
    }
}
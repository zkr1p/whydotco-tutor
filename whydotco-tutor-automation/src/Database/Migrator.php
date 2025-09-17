<?php

namespace WhydotCo\TutorAutomation\Database;

// Importa las clases de migración para asegurar que existen y facilitar su uso.
// Esto funcionará una vez que los archivos sean renombrados.
use WhydotCo\TutorAutomation\Database\Migrations\CreateDownloadableProductPermissionsNewTable;
use WhydotCo\TutorAutomation\Database\Migrations\CreateLogsTable;

/**
 * Gestiona las migraciones de la base de datos.
 *
 * Esta clase se encarga de encontrar, registrar y ejecutar las migraciones
 * necesarias para crear o actualizar las tablas personalizadas del plugin,
 * basándose en una lista predefinida de clases para ser compatible con PSR-4.
 */
class Migrator {

    /**
     * El nombre de la opción en la tabla wp_options donde se guardan las migraciones ejecutadas.
     * @var string
     */
    private const MIGRATION_OPTION_KEY = 'whydotco_tutor_automation_migrations';

    /**
     * Lista ordenada de las clases de migración a ejecutar.
     *
     * El orden es crucial. Las nuevas migraciones deben añadirse al final de este array.
     * Se utilizan los nombres de clase completos (::class) para asegurar la compatibilidad
     * con el autoloader de Composer y evitar errores de "Clase no encontrada".
     *
     * @var array<int, class-string>
     */
    private static $migrations = [
        CreateDownloadableProductPermissionsNewTable::class,
        CreateLogsTable::class,
        // ¡Importante! Añade aquí las nuevas clases de migración en el futuro.
    ];

    /**
     * Ejecuta todas las migraciones pendientes.
     *
     * Compara la lista de clases de migración definidas en la propiedad `$migrations`
     * con las que ya han sido ejecutadas (guardadas en la base de datos) y corre solo las nuevas.
     *
     * @return void
     * @throws \Exception Si una clase de migración no se encuentra o no tiene el método `up()`.
     */
    public static function run(): void {
        // Obtiene las migraciones ya ejecutadas. Ahora se guarda el nombre de la clase, no el del archivo.
        $ran_migrations = get_option(self::MIGRATION_OPTION_KEY, []);

        // Asegura que $ran_migrations sea siempre un array para evitar errores.
        if (!is_array($ran_migrations)) {
            $ran_migrations = [];
        }

        // Compara el array predefinido de migraciones con las ya ejecutadas para encontrar las pendientes.
        $pending_migrations = array_diff(self::$migrations, $ran_migrations);

        if (empty($pending_migrations)) {
            // No hay nada que hacer.
            return;
        }

        foreach ($pending_migrations as $migration_class) {
            // Verifica que la clase exista gracias al autoloader de Composer.
            if (!class_exists($migration_class)) {
                throw new \Exception(
                    "Clase de migración no encontrada: {$migration_class}. Asegúrate de que el archivo ha sido renombrado correctamente y `composer dump-autoload` se ha ejecutado."
                );
            }

            // Crea una instancia de la clase de migración.
            $migration_instance = new $migration_class();

            // Verifica y ejecuta el método 'up' que contiene la lógica SQL.
            if (!method_exists($migration_instance, 'up')) {
                throw new \Exception("El método `up()` no existe en la clase de migración: {$migration_class}");
            }

            $migration_instance->up();

            // Registra esta migración como ejecutada, usando el nombre de la clase para consistencia.
            $ran_migrations[] = $migration_class;
        }

        // Guarda la lista actualizada de migraciones ejecutadas en la base de datos.
        update_option(self::MIGRATION_OPTION_KEY, $ran_migrations);
    }
}
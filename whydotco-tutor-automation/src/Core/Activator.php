<?php

namespace WhydotCo\TutorAutomation\Core;

// MODIFICACIÓN: Se eliminan los 'use' de aquí para usar 'require_once' dentro del método.
// use WhydotCo\TutorAutomation\Database\Migrator;
// use WhydotCo\TutorAutomation\Infrastructure\Cron\CronManager;

/**
 * Lógica a ejecutar durante la activación del plugin.
 */
class Activator {
    /**
     * El método principal que se ejecuta al activar el plugin.
     *
     * @return void
     */
    public static function activate(): void {
        try {
            // Cargamos manualmente las clases que necesitamos
            // justo antes de usarlas para asegurar que estén disponibles durante la activación.
            require_once WHYDOTCO_TUTOR_AUTOMATION_PATH . 'src/Database/Migrator.php';
            require_once WHYDOTCO_TUTOR_AUTOMATION_PATH . 'src/Infrastructure/Cron/CronManager.php';

            // 1. Ejecuta el sistema de migraciones para asegurar que las tablas de la BD existen.
            // Se usa el namespace completo para llamar a la clase.
            \WhydotCo\TutorAutomation\Database\Migrator::run();

            // 2. Programa las tareas Cron personalizadas del plugin.
            // Se usa el namespace completo para instanciar la clase.
            $cron_manager = new \WhydotCo\TutorAutomation\Infrastructure\Cron\CronManager();
            $cron_manager->schedule_events();

            // 3. Limpia las reglas de reescritura de permalinks.
            // Es una buena práctica hacerlo en la activación para que cualquier
            // endpoint de la API REST que registremos funcione inmediatamente.
            flush_rewrite_rules();

            // (Opcional) Guardar una opción con la versión para futuras actualizaciones.
            update_option( 'whydotco_tutor_automation_version', WHYDOTCO_TUTOR_AUTOMATION_VERSION );

        } catch ( \Exception $e ) {
            // Si algo sale mal durante la activación, lo registramos en el log de errores de PHP
            // y prevenimos que el plugin se active en un estado corrupto.
            error_log( 'Error durante la activación de WhydotCo Tutor Automation Pro: ' . $e->getMessage() );
            // Opcionalmente, se podría desactivar el plugin si el error es crítico.
            // deactivate_plugins( plugin_basename( WHYDOTCO_TUTOR_AUTOMATION_PLUGIN_FILE ) );
            // wp_die( 'Hubo un error crítico al activar el plugin. Por favor, revise los logs.' );
        }
    }
}
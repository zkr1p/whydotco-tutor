<?php

namespace WhydotCo\TutorAutomation\Core;

use WhydotCo\TutorAutomation\Infrastructure\Cron\CronManager;

/**
 * Lógica a ejecutar durante la desactivación del plugin.
 */
class Deactivator {
    /**
     * El método principal que se ejecuta al desactivar el plugin.
     *
     * @return void
     */
    public static function deactivate(): void {
        // 1. Desprograma todas las tareas Cron personalizadas del plugin.
        $cron_manager = new CronManager();
        $cron_manager->unschedule_events();

        // 2. Limpia las reglas de reescritura de permalinks.
        flush_rewrite_rules();

        // NOTA: No eliminamos ninguna opción ni tabla de la base de datos aquí.
        // La desactivación debe ser una acción no destructiva. El borrado de datos
        // solo se realiza en el archivo uninstall.php.
    }
}
<?php

namespace WhydotCo\TutorAutomation\Infrastructure\Cron;

use WhydotCo\TutorAutomation\Infrastructure\Cron\Jobs\SubscriptionCheckJob;

/**
 * Gestiona la programación y ejecución de las tareas Cron de WordPress.
 */
class CronManager {
    /**
     * Un array que mapea los hooks de cron a las clases que los manejan.
     * @var array<string, string>
     */
    private array $jobs = [
        'whydotco_daily_sync_hook' => SubscriptionCheckJob::class,
    ];

    /**
     * Constructor. Registra los hooks para ejecutar los trabajos.
     */
    public function __construct() {
        foreach ( $this->jobs as $hook => $class ) {
            // Engancha el método 'handle' de cada clase de trabajo al hook de cron correspondiente.
            add_action( $hook, [ $this, 'execute_job' ] );
        }
    }

    /**
     * Ejecuta una tarea programada.
     *
     * Este método se llama cuando WordPress dispara un hook de cron.
     * La clase del trabajo a ejecutar se pasa a través del filtro actual.
     */
    public function execute_job(): void {
        $current_hook = current_filter();
        
        if ( ! isset( $this->jobs[ $current_hook ] ) ) {
            return;
        }

        $job_class = $this->jobs[ $current_hook ];

        if ( class_exists( $job_class ) && method_exists( $job_class, 'handle' ) ) {
            $job = new $job_class();
            $job->handle();
        }
    }

    /**
     * Programa todos los eventos de cron.
     * Llamado por el Activator.
     */
    public function schedule_events(): void {
        // Programa nuestro evento de sincronización diaria si no está ya programado.
        if ( ! wp_next_scheduled( 'whydotco_daily_sync_hook' ) ) {
            // Programa el evento para que se ejecute diariamente, a partir de ahora.
            wp_schedule_event( time(), 'daily', 'whydotco_daily_sync_hook' );
        }
    }

    /**
     * Desprograma todos los eventos de cron.
     * Llamado por el Deactivator.
     */
    public function unschedule_events(): void {
        // Desprograma nuestro evento de sincronización diaria.
        wp_clear_scheduled_hook( 'whydotco_daily_sync_hook' );
    }
}
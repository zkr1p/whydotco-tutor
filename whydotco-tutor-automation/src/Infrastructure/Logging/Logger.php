<?php

namespace WhydotCo\TutorAutomation\Infrastructure\Logging;

use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;
use Monolog\Level;
use WhydotCo\TutorAutomation\Common\Contracts\LoggerInterface;

/**
 * Implementación del Logger usando la librería Monolog.
 *
 * Esta clase se encarga de registrar todos los eventos del plugin en un
 * archivo de log dedicado para facilitar la depuración y el monitoreo.
 */
class Logger implements LoggerInterface {
    /**
     * La instancia de Monolog.
     * @var MonologLogger
     */
    private MonologLogger $logger;

    /**
     * Constructor del Logger.
     *
     * @param string $channel El nombre del canal de log (usualmente el nombre del plugin).
     * @throws \Exception Si el directorio de logs no se puede crear.
     */
    public function __construct( string $channel = 'plugin' ) {
        $this->logger = new MonologLogger( $channel );
        $this->setup_handler();
    }

    /**
     * Configura el 'handler' que escribirá los logs en un archivo.
     *
     * @throws \Exception Si el directorio de logs no se puede crear.
     */
    private function setup_handler(): void {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/whydotco-logs/';
        $log_file = $log_dir . 'tutor-automation.log';

        // Crea el directorio de logs si no existe.
        if ( ! is_dir( $log_dir ) ) {
            // El modo 0755 es el estándar para directorios.
            if ( ! wp_mkdir_p( $log_dir ) ) {
                throw new \Exception( "No se pudo crear el directorio de logs: {$log_dir}" );
            }
        }
        
        // Añade un archivo .htaccess para prevenir el acceso directo a los logs.
        if ( ! file_exists( $log_dir . '.htaccess' ) ) {
            $file_handle = @fopen( $log_dir . '.htaccess', 'w' );
            if ( $file_handle ) {
                fwrite( $file_handle, 'deny from all' );
                fclose( $file_handle );
            }
        }

        // Añade el handler que escribe en el archivo con un nivel mínimo de DEBUG.
        // Esto significa que registrará todos los niveles de severidad.
        $this->logger->pushHandler( new StreamHandler( $log_file, Level::Debug ) );
    }

    // =========================================================================
    // Implementación de los métodos de LoggerInterface
    // =========================================================================

    public function emergency( string $message, array $context = [] ): void {
        $this->logger->emergency( $message, $context );
    }

    public function alert( string $message, array $context = [] ): void {
        $this->logger->alert( $message, $context );
    }

    public function critical( string $message, array $context = [] ): void {
        $this->logger->critical( $message, $context );
    }

    public function error( string $message, array $context = [] ): void {
        $this->logger->error( $message, $context );
    }

    public function warning( string $message, array $context = [] ): void {
        $this->logger->warning( $message, $context );
    }

    public function notice( string $message, array $context = [] ): void {
        $this->logger->notice( $message, $context );
    }

    public function info( string $message, array $context = [] ): void {
        $this->logger->info( $message, $context );
    }

    public function debug( string $message, array $context = [] ): void {
        $this->logger->debug( $message, $context );
    }

    public function log( $level, string $message, array $context = [] ): void {
        $this->logger->log( $level, $message, $context );
    }
}
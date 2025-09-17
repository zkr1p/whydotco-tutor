<?php

namespace WhydotCo\TutorAutomation\Common\Contracts;

/**
 * Interface LoggerInterface
 *
 * Define el contrato que debe seguir cualquier clase de logging dentro del plugin.
 *
 * Este contrato se basa en el estándar PSR-3, lo que garantiza la interoperabilidad
 * y el uso de buenas prácticas en el registro de eventos.
 */
interface LoggerInterface {
    /**
     * Registra un evento de emergencia crítico para el sistema.
     *
     * @param string $message El mensaje a registrar.
     * @param array<string, mixed> $context Datos contextuales adicionales.
     * @return void
     */
    public function emergency( string $message, array $context = [] ): void;

    /**
     * Registra una acción que debe ser atendida inmediatamente.
     *
     * @param string $message El mensaje a registrar.
     * @param array<string, mixed> $context Datos contextuales adicionales.
     * @return void
     */
    public function alert( string $message, array $context = [] ): void;

    /**
     * Registra condiciones críticas.
     *
     * @param string $message El mensaje a registrar.
     * @param array<string, mixed> $context Datos contextuales adicionales.
     * @return void
     */
    public function critical( string $message, array $context = [] ): void;

    /**
     * Registra errores en tiempo de ejecución que no requieren acción inmediata
     * pero que típicamente deberían ser registrados y monitorizados.
     *
     * @param string $message El mensaje a registrar.
     * @param array<string, mixed> $context Datos contextuales adicionales.
     * @return void
     */
    public function error( string $message, array $context = [] ): void;

    /**
     * Registra eventos excepcionales que no son errores.
     *
     * @param string $message El mensaje a registrar.
     * @param array<string, mixed> $context Datos contextuales adicionales.
     * @return void
     */
    public function warning( string $message, array $context = [] ): void;

    /**
     * Registra eventos normales pero significativos.
     *
     * @param string $message El mensaje a registrar.
     * @param array<string, mixed> $context Datos contextuales adicionales.
     * @return void
     */
    public function notice( string $message, array $context = [] ): void;

    /**
     * Registra eventos interesantes.
     *
     * @param string $message El mensaje a registrar.
     * @param array<string, mixed> $context Datos contextuales adicionales.
     * @return void
     */
    public function info( string $message, array $context = [] ): void;

    /**
     * Registra mensajes detallados para depuración.
     *
     * @param string $message El mensaje a registrar.
     * @param array<string, mixed> $context Datos contextuales adicionales.
     * @return void
     */
    public function debug( string $message, array $context = [] ): void;

    /**
     * Registra un mensaje con un nivel de severidad arbitrario.
     *
     * @param mixed $level El nivel de severidad.
     * @param string $message El mensaje a registrar.
     * @param array<string, mixed> $context Datos contextuales adicionales.
     * @return void
     */
    public function log( $level, string $message, array $context = [] ): void;
}
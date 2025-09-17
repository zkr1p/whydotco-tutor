<?php

namespace WhydotCo\TutorAutomation\Common\Contracts;

/**
 * Interface ModuleInterface
 *
 * Define el contrato que debe seguir cada módulo de lógica de negocio del plugin.
 *
 * Un módulo es una unidad autocontenida de funcionalidad (ej. SubscriptionManager, Admin).
 * Al implementar esta interfaz, cada módulo garantiza que tiene un punto de entrada
 * estandarizado para su inicialización.
 */
interface ModuleInterface {
    /**
     * Inicializa el módulo.
     *
     * Este método es el lugar designado para que cada módulo registre
     * todos sus hooks (acciones y filtros) en WordPress.
     *
     * @return void
     */
    public function init(): void;
}
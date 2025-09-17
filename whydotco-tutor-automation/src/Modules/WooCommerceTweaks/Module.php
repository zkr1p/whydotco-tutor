<?php

namespace WhydotCo\TutorAutomation\Modules\WooCommerceTweaks;

use WhydotCo\TutorAutomation\Common\Contracts\LoggerInterface;
use WhydotCo\TutorAutomation\Common\Contracts\ModuleInterface;
use WhydotCo\TutorAutomation\Modules\WooCommerceTweaks\Hooks;

/**
 * Módulo para centralizar pequeños ajustes y modificaciones de WooCommerce.
 */
class Module implements ModuleInterface {

    private LoggerInterface $logger;
    private Hooks $hooks;

    public function __construct( LoggerInterface $logger ) {
        $this->logger = $logger;
        $this->hooks = new Hooks();
    }

    /**
     * Inicializa el módulo registrando sus hooks.
     */
    public function init(): void {
        $this->logger->info( 'Inicializando el módulo WooCommerceTweaks.' );
        $this->hooks->register_hooks();
    }
}
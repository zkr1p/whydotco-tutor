<?php

namespace WhydotCo\TutorAutomation\Modules\DownloadManager;

use WhydotCo\TutorAutomation\Common\Contracts\LoggerInterface;
use WhydotCo\TutorAutomation\Common\Contracts\ModuleInterface;
use WhydotCo\TutorAutomation\Integrations\WooCommerce;
use WhydotCo\TutorAutomation\Modules\DownloadManager\Hooks\DownloadHooks;
use WhydotCo\TutorAutomation\Modules\DownloadManager\Services\PermissionService;

/**
 * Módulo principal para gestionar la lógica de productos descargables (Ebooks).
 */
class Module implements ModuleInterface {

    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /** @var DownloadHooks */
    private DownloadHooks $hooks;

    /**
     * Constructor del módulo.
     *
     * @param LoggerInterface $logger
     * @param WooCommerce $woocommerce_api
     */
    public function __construct( LoggerInterface $logger, WooCommerce $woocommerce_api ) {
        $this->logger = $logger;

        // 1. Se crea el servicio de permisos (el cerebro de las descargas).
        $permission_service = new PermissionService( $logger, $woocommerce_api );

        // 2. Se crea el gestor de hooks (los oídos).
        // Se le pasa el servicio de permisos para que lo utilice cuando se disparen
        // los eventos de descarga.
        $this->hooks = new DownloadHooks( $permission_service, $logger );
    }

    /**
     * Inicializa el módulo registrando sus hooks.
     *
     * @return void
     */
    public function init(): void {
        $this->logger->info( 'Inicializando el módulo DownloadManager.' );
        $this->hooks->register_hooks();
    }
}
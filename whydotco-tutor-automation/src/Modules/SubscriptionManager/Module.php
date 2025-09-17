<?php

namespace WhydotCo\TutorAutomation\Modules\SubscriptionManager;

use WhydotCo\TutorAutomation\Common\Contracts\LoggerInterface;
use WhydotCo\TutorAutomation\Common\Contracts\ModuleInterface;
use WhydotCo\TutorAutomation\Integrations\TutorLMS;
use WhydotCo\TutorAutomation\Integrations\WooCommerce;
use WhydotCo\TutorAutomation\Modules\SubscriptionManager\Hooks\SubscriptionHooks;
use WhydotCo\TutorAutomation\Modules\SubscriptionManager\Services\EnrollmentService;

/**
 * Módulo principal para gestionar la lógica de suscripciones y cursos.
 */
class Module implements ModuleInterface {

    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /** @var SubscriptionHooks */
    private SubscriptionHooks $hooks;

    /**
     * Constructor del módulo.
     *
     * Recibe todas las dependencias necesarias e instancia las clases
     * que componen la funcionalidad de este módulo.
     *
     * @param LoggerInterface $logger
     * @param TutorLMS $tutorlms_api
     * @param WooCommerce $woocommerce_api
     */
    public function __construct( LoggerInterface $logger, TutorLMS $tutorlms_api, WooCommerce $woocommerce_api ) {
        $this->logger = $logger;

        // 1. Se crea el servicio de inscripciones (el cerebro).
        // Le pasamos las herramientas que necesita para trabajar.
        $enrollment_service = new EnrollmentService( $logger, $tutorlms_api, $woocommerce_api );

        // 2. Se crea el gestor de hooks (los oídos).
        // Le pasamos el servicio de inscripciones para que sepa a quién avisar
        // cuando escuche un evento importante.
        $this->hooks = new SubscriptionHooks( $enrollment_service );
    }

    /**
     * Inicializa el módulo registrando sus hooks.
     *
     * @return void
     */
    public function init(): void {
        $this->logger->info( 'Inicializando el módulo SubscriptionManager.' );
        $this->hooks->register_hooks();
    }
}
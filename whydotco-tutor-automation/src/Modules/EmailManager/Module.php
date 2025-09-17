<?php

namespace WhydotCo\TutorAutomation\Modules\EmailManager;

use WhydotCo\TutorAutomation\Common\Contracts\LoggerInterface;
use WhydotCo\TutorAutomation\Common\Contracts\ModuleInterface;
use WhydotCo\TutorAutomation\Modules\EmailManager\Services\EmailService;

/**
 * Módulo principal para gestionar los correos electrónicos transaccionales personalizados.
 *
 * Se encarga de centralizar la lógica de notificaciones que antes estaba en el tema hijo.
 */
class Module implements ModuleInterface {

    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /** @var EmailService */
    private EmailService $email_service;

    /**
     * Constructor del módulo.
     *
     * @param LoggerInterface $logger
     */
    public function __construct( LoggerInterface $logger ) { 
        $this->logger = $logger;
        $this->email_service = new EmailService( $this->logger );
    }

    /**
     * Inicializa el módulo registrando sus hooks y filtros.
     *
     * @return void
     */
    public function init(): void {
        // CORRECCIÓN: Usar -> en lugar de .
        $this->logger->info( 'Inicializando el módulo EmailManager.' );

        // Ahora, simplemente le decimos al servicio que registre sus propios hooks.
        $this->email_service->register_hooks();
    }
}
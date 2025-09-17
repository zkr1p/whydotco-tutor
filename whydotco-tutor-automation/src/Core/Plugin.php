<?php

namespace WhydotCo\TutorAutomation\Core;

// Importamos todas las clases que vamos a necesitar para que el autoloader las encuentre.
// Usamos "alias" (ej. 'as AdminModule') para evitar conflictos de nombres,
// ya que muchos módulos tendrán una clase principal llamada 'Module'.
use WhydotCo\TutorAutomation\Admin\Module as AdminModule;
use WhydotCo\TutorAutomation\Assets;
use WhydotCo\TutorAutomation\Common\Contracts\LoggerInterface;
use WhydotCo\TutorAutomation\Database\Migrator;
use WhydotCo\TutorAutomation\DownloadManager\Module as DownloadManagerModule;
use WhydotCo\TutorAutomation\EmailManager\Module as EmailManagerModule;
use WhydotCo\TutorAutomation\Infrastructure\Api\ApiManager;
use WhydotCo\TutorAutomation\Infrastructure\Cron\CronManager;
use WhydotCo\TutorAutomation\Infrastructure\Logging\Logger;
use WhydotCo\TutorAutomation\Integrations\TutorLMS;
use WhydotCo\TutorAutomation\Integrations\WooCommerce;
use WhydotCo\TutorAutomation\SubscriptionManager\Module as SubscriptionManagerModule;
use WhydotCo\TutorAutomation\Modules\WooCommerceTweaks\Module as WooCommerceTweaksModule;


/**
 * La clase principal del plugin.
 *
 * Orquesta la carga de todas las dependencias, módulos y hooks del plugin.
 * Sigue el patrón Singleton para asegurar una única instancia.
 */
final class Plugin {
    /**
     * La única instancia de la clase Plugin.
     * @var Plugin|null
     */
    private static ?Plugin $instance = null;

    /**
     * Contenedor para los servicios de infraestructura.
     * @var array<string, object>
     */
    private array $infrastructure = [];

    /**
     * Contenedor para los wrappers de integración.
     * @var array<string, object>
     */
    private array $integrations = [];

    /**
     * Contenedor para los módulos de lógica de negocio.
     * @var array<string, object>
     */
    private array $modules = [];

    /**
     * Constructor privado para forzar el uso de get_instance().
     */
    private function __construct() {
        $this->load_infrastructure();
        $this->load_integrations();
        $this->load_modules();
        $this->register_hooks();
    }

    /**
     * Obtiene la instancia única del plugin.
     *
     * @return Plugin La instancia única del plugin.
     * @throws \Exception Si algo sale mal durante la instanciación.
     */
    public static function get_instance(): Plugin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Carga los servicios de bajo nivel como el Logger y el gestor de Cron.
     */
    private function load_infrastructure(): void {
        // El Logger es el primero, ya que otros servicios pueden necesitarlo.
        $this->infrastructure['logger'] = new Logger( 'WhydotCo Tutor Automation' );
        
        $this->infrastructure['cron_manager'] = new CronManager();
        $this->infrastructure['api_manager'] = new ApiManager();
    }

    /**
     * Carga los wrappers que interactúan con plugins externos (Tutor LMS, WooCommerce).
     */
    private function load_integrations(): void {
        $this->integrations['tutorlms'] = new TutorLMS();
        $this->integrations['woocommerce'] = new WooCommerce();
    }

    /**
     * Carga los módulos de lógica de negocio del plugin.
     * Aquí se aplica la Inyección de Dependencias, pasando los servicios
     * necesarios a cada módulo a través de su constructor.
     */
    private function load_modules(): void {
        /** @var LoggerInterface $logger */
        $logger = $this->infrastructure['logger'];

        // Módulo de Suscripciones
        $this->modules['subscription_manager'] = new SubscriptionManagerModule(
            $logger,
            $this->integrations['tutorlms'],
            $this->integrations['woocommerce']
        );

        // Módulo de Descargas
        $this->modules['download_manager'] = new DownloadManagerModule(
            $logger,
            $this->integrations['woocommerce']
        );

        // Módulo de Emails
        $this->modules['email_manager'] = new EmailManagerModule($logger);

        // Módulo de Administración (Backend)
        $this->modules['admin'] = new AdminModule($logger);
        $this->modules['woocommerce_tweaks'] = new WooCommerceTweaksModule($logger);
    }

    /**
     * Registra los hooks principales del plugin.
     */
    private function register_hooks(): void {
        // El hook 'init' es un buen punto para inicializar los módulos,
        // ya que WordPress está completamente cargado en este punto.
        add_action( 'init', [ $this, 'init_modules' ] );
    }

    /**
     * Inicializa todos los módulos cargados.
     * Este método es llamado por el hook 'init'.
     */
    public function init_modules(): void {
        // Carga el dominio de texto para la traducción.
        load_plugin_textdomain(
            'whydotco-tutor-automation',
            false,
            dirname( WHYDOTCO_TUTOR_AUTOMATION_BASENAME ) . '/languages'
        );
        
        // Llama al método 'init()' de cada módulo para que registren sus propios hooks.
        foreach ( $this->modules as $module ) {
            if ( method_exists( $module, 'init' ) ) {
                $module->init();
            }
        }
    }

    /**
     * Previene la clonación de la instancia.
     */
    private function __clone() {}

    /**
     * Previene la deserialización de la instancia.
     */
    public function __wakeup() {
        throw new \Exception( "Cannot unserialize a singleton." );
    }
}
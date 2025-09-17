<?php

namespace WhydotCo\TutorAutomation\Modules\EmailManager\Services;

use WhydotCo\TutorAutomation\Common\Contracts\LoggerInterface;

/**
 * Servicio para gestionar la lógica y el envío de correos transaccionales personalizados.
 */
class EmailService {

    private LoggerInterface $logger;

    /**
     * IDs de productos que disparan correos específicos.
     * Deberían ser configurables en la página de Ajustes en el futuro.
     * @var array<string, int>
     */
    private array $product_triggers = [
        'preorder' => 12345, // EJEMPLO: Reemplazar con el ID real del producto de pre-venta
        'sub_annual' => 54321, // EJEMPLO: Reemplazar con el ID real de la suscripción anual
        'sub_monthly' => 54322, // EJEMPLO: Reemplazar con el ID real de la suscripción mensual
    ];

    public function __construct( LoggerInterface $logger ) {
        $this->logger = $logger;
    }

    /**
     * Registra los hooks necesarios para disparar los correos.
     * Este método será llamado por el Module.php
     */
    public function register_hooks(): void {
        add_action( 'woocommerce_order_status_completed', [ $this, 'dispatch_email_on_order_complete' ], 20, 1 );
    }

    /**
     * Método principal que se ejecuta cuando una orden se completa.
     * Analiza la orden y envía el correo personalizado si corresponde.
     *
     * @param int $order_id
     */
    public function dispatch_email_on_order_complete( int $order_id ): void {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        $email_type = null;
        $email_subject = '';
        $recipient = $order->get_billing_email();
        $lang = str_starts_with( get_locale(), 'es' ) ? 'es' : 'en';

        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();

            if ( $product_id === $this->product_triggers['preorder'] ) {
                $email_type = 'preorder';
                $email_subject = ( 'es' === $lang ) ? 'Confirmación de tu Pre-venta' : 'Your Pre-order Confirmation';
                break;
            }
            if ( $product_id === $this->product_triggers['sub_annual'] ) {
                $email_type = 'subscription-annual';
                $email_subject = ( 'es' === $lang ) ? 'Bienvenido/a a tu Suscripción Anual' : 'Welcome to Your Annual Subscription';
                break;
            }
            if ( $product_id === $this->product_triggers['sub_monthly'] ) {
                $email_type = 'subscription-monthly';
                $email_subject = ( 'es' === $lang ) ? 'Bienvenido/a a tu Suscripción Mensual' : 'Welcome to Your Monthly Subscription';
                break;
            }
        }

        if ( ! $email_type ) {
            $this->logger->info( "No se encontró un disparador de email personalizado para la orden #{$order_id}." );
            return;
        }

        $template_name = "{$email_type}-{$lang}.html";
        $email_body = $this->get_template_content( $template_name, [
            '{customer_name}' => $order->get_billing_first_name(),
            // Añadir más placeholders aquí si es necesario
        ] );

        if ( $email_body ) {
            $mailer = WC()->mailer();
            $headers = [ 'Content-Type: text/html; charset=UTF-8' ];

            $mailer->send( $recipient, $email_subject, $email_body, $headers, [] );
            $this->logger->info( "Email '{$email_type}' enviado a {$recipient} para la orden #{$order_id}." );
        }
    }

    /**
     * Carga el contenido de una plantilla de correo.
     *
     * Busca primero en el tema hijo (para permitir overrides) y si no lo encuentra,
     * carga la plantilla por defecto del plugin.
     *
     * @param string $template_name Nombre del archivo de la plantilla (ej. 'preorder-es.html').
     * @param array $placeholders Array asociativo de placeholders a reemplazar.
     * @return string|false El contenido del correo o false si no se encuentra la plantilla.
     */
    private function get_template_content( string $template_name, array $placeholders = [] ): string|false {
        // 1. Buscar en el tema hijo: wp-content/themes/tu-tema-hijo/whydotco-tutor-automation/emails/template.html
        $override_path = get_stylesheet_directory() . '/whydotco-tutor-automation/emails/' . $template_name;

        // 2. Si no existe, usar la plantilla del plugin: wp-content/plugins/mi-plugin/templates/emails/template.html
        $default_path = WHYDOTCO_TUTOR_AUTOMATION_PATH . 'templates/emails/' . $template_name;

        $path_to_use = file_exists( $override_path ) ? $override_path : $default_path;

        if ( ! file_exists( $path_to_use ) ) {
            $this->logger->error( "No se encontró la plantilla de email: {$template_name}" );
            return false;
        }

        ob_start();
        include $path_to_use;
        $content = ob_get_clean();

        // Reemplazar placeholders
        if ( ! empty( $placeholders ) ) {
            $content = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $content );
        }

        // Envuelve el contenido en la plantilla estándar de WooCommerce para consistencia.
        return wc_get_template_html('emails/email-header.php', ['email_heading' => '']) . $content . wc_get_template_html('emails/email-footer.php');
    }
}
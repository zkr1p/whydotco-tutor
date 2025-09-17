<?php

namespace WhydotCo\TutorAutomation\Modules\Admin\Pages;

use WhydotCo\TutorAutomation\Common\Contracts\LoggerInterface;
use WhydotCo\TutorAutomation\Modules\Admin\ListTables\LogsListTable;

/**
 * Gestiona la página de visualización de logs en el panel de administración.
 */
class LogsPage {

    private LoggerInterface $logger;
    private ?LogsListTable $logs_list_table = null;

    public function __construct( LoggerInterface $logger ) {
        $this->logger = $logger;
    }

    /**
     * Renderiza el contenido HTML de la página de logs.
     */
    public function render(): void {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Logs de Actividad', 'whydotco-tutor-automation' ); ?></h1>
            <p><?php esc_html_e( 'Aquí puedes ver un registro de todas las acciones importantes realizadas por el plugin.', 'whydotco-tutor-automation' ); ?></p>
            
            <?php
            // Prepara y muestra la tabla de logs.
            if ( $this->prepare_logs_table() ) {
                $this->logs_list_table->display();
            } else {
                echo '<div class="notice notice-warning"><p>' . esc_html__( 'El archivo de log no existe o está vacío. Aún no se han registrado eventos.', 'whydotco-tutor-automation' ) . '</p></div>';
            }
            ?>
        </div>
        <?php
    }

    /**
     * Prepara la instancia de WP_List_Table con los datos del log.
     *
     * @return bool True si la tabla se pudo preparar, false si no hay datos.
     */
    private function prepare_logs_table(): bool {
        $log_file_path = $this->get_log_file_path();

        if ( ! file_exists( $log_file_path ) || filesize( $log_file_path ) === 0 ) {
            return false;
        }

        $log_content = file( $log_file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
        if ( empty( $log_content ) ) {
            return false;
        }

        $log_data = [];
        foreach ( $log_content as $line_num => $line ) {
            // Monolog formatea las líneas como: [YYYY-MM-DD HH:MM:SS] CHANNEL.LEVEL: Message {context} [extra]
            if ( preg_match( '/^\[(.*?)\]\s(.*?)\.(.*?):\s(.*?)\s(\{.*\})\s(\[.*\])$/', $line, $matches ) ) {
                $log_data[] = [
                    'id'      => $line_num + 1,
                    'date'    => $matches[1],
                    'channel' => $matches[2],
                    'level'   => $matches[3],
                    'message' => esc_html( $matches[4] ),
                    'context' => esc_html( $matches[5] ),
                ];
            }
        }
        
        // Invertimos el array para mostrar los logs más recientes primero.
        $log_data = array_reverse($log_data);

        $this->logs_list_table = new LogsListTable();
        $this->logs_list_table->prepare_items( $log_data );

        return true;
    }

    /**
     * Obtiene la ruta completa al archivo de log.
     *
     * @return string
     */
    private function get_log_file_path(): string {
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'] . '/whydotco-logs/tutor-automation.log';
    }
}
<?php

namespace WhydotCo\TutorAutomation\Modules\Admin\ListTables;

// Asegurarnos de que la clase base de WordPress esté disponible.
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Crea una tabla de logs personalizada al estilo de WordPress.
 */
class LogsListTable extends \WP_List_Table {

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct( [
            'singular' => __( 'Log', 'whydotco-tutor-automation' ),
            'plural'   => __( 'Logs', 'whydotco-tutor-automation' ),
            'ajax'     => false,
        ] );
    }

    /**
     * Define las columnas de la tabla.
     *
     * @return array
     */
    public function get_columns(): array {
        return [
            'date'    => __( 'Fecha', 'whydotco-tutor-automation' ),
            'level'   => __( 'Nivel', 'whydotco-tutor-automation' ),
            'message' => __( 'Mensaje', 'whydotco-tutor-automation' ),
        ];
    }

    /**
     * Prepara los datos para ser mostrados.
     *
     * @param array $data Los datos del log.
     */
    public function prepare_items( array $data = [] ): void {
        $this->_column_headers = [ $this->get_columns(), [], [] ];
        $per_page     = 20;
        $current_page = $this->get_pagenum();
        $total_items  = count( $data );

        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page,
        ] );

        $this->items = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
    }

    /**
     * Define cómo renderizar cada celda por defecto.
     *
     * @param array $item
     * @param string $column_name
     * @return string
     */
    protected function column_default( $item, $column_name ): string {
        return $item[ $column_name ] ?? '';
    }
    
    /**
     * Renderizado personalizado para la columna 'level'.
     * Añade colores para distinguir los niveles de log.
     *
     * @param array $item
     * @return string
     */
    protected function column_level( $item ): string {
        $level = strtoupper( $item['level'] );
        $color = 'inherit';

        switch ( $level ) {
            case 'EMERGENCY':
            case 'CRITICAL':
            case 'ALERT':
            case 'ERROR':
                $color = '#d63638';
                break;
            case 'WARNING':
                $color = '#f0a800';
                break;
            case 'NOTICE':
                $color = '#00a0d2';
                break;
            case 'INFO':
                $color = '#2271b1';
                break;
            case 'DEBUG':
                $color = '#7f7f7f';
                break;
        }

        return sprintf( '<strong style="color: %s;">%s</strong>', esc_attr( $color ), esc_html( $level ) );
    }
}
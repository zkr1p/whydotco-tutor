<?php

namespace WhydotCo\TutorAutomation\Modules\WooCommerceTweaks;

/**
 * Registra todos los hooks, filtros y shortcodes para personalizar WooCommerce.
 */
class Hooks {
    /**
     * Añade todas las acciones y filtros necesarios.
     */
    public function register_hooks(): void {
        // =========================================================================
        // Lógica Migrada de 'woocommerce-shop.php'
        // =========================================================================

        // 1. REGISTRO DE SHORTCODES
        add_action('init', [ $this, 'register_shop_shortcodes' ]);

        // 2. FILTRO DE CATEGORÍA
        add_action('pre_get_posts', [ $this, 'apply_category_filter_query' ], 20);

        // 3. VALIDACIÓN DE "VENDIDO INDIVIDUALMENTE"
        add_filter('woocommerce_add_to_cart_validation', [ $this, 'handle_sold_individually_products' ], 20, 2);


        // =========================================================================
        // Lógica Migrada de 'woocommerce-checkout.php'
        // =========================================================================
        
        // 4. CAMBIAR ETIQUETAS DE CAMPOS
        add_filter('woocommerce_get_country_locale', [ $this, 'change_us_locale_labels' ]);

        // 5. MODIFICAR CAMPOS DEL CHECKOUT
        add_filter('woocommerce_checkout_fields', [ $this, 'customize_checkout_fields' ], 20);

        // 6. LIMITAR PAÍSES A EE.UU.
        add_filter('woocommerce_countries_allowed_countries', [ $this, 'allow_only_us_country' ]);
        add_filter('woocommerce_countries_shipping_countries', [ $this, 'allow_only_us_country' ]);

        // 7. PRESELECCIONAR EE.UU.
        add_filter('woocommerce_default_address_fields', [ $this, 'default_country_to_us' ]);

        // 8. OCULTAR OPCIÓN DE ENVÍO A OTRA DIRECCIÓN
        add_filter('woocommerce_cart_needs_shipping_address', '__return_false');
    }

    // =========================================================================
    // MÉTODOS DE LÓGICA (SHOP)
    // =========================================================================

    public function register_shop_shortcodes(): void {
        add_shortcode('custom_woocommerce_ordering', [ $this, 'render_ordering_shortcode' ]);
        add_shortcode('custom_woocommerce_category_filter', [ $this, 'render_category_filter_shortcode' ]);
        add_shortcode('custom_woocommerce_result_count', [ $this, 'render_result_count_shortcode' ]);
    }

    public function render_ordering_shortcode(): string {
        ob_start();
        woocommerce_catalog_ordering();
        return ob_get_clean();
    }

    public function render_result_count_shortcode(): string {
        ob_start();
        woocommerce_result_count();
        return ob_get_clean();
    }

    public function render_category_filter_shortcode( $atts ): string {
        $atts = shortcode_atts(['form_class' => 'custom-category-filter', 'select_class' => 'custom-category-select'], $atts, 'custom_woocommerce_category_filter');
        $exclude_ids = [34, 15, 80]; // Excluir 'Uncategorized', 'Cursos', etc.
        $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true, 'exclude' => $exclude_ids, 'orderby' => 'name', 'order' => 'ASC']);
        if (is_wp_error($terms) || empty($terms)) return '';
        $current = isset($_GET['product_cat']) ? sanitize_text_field($_GET['product_cat']) : '';
        ob_start();
        ?>
        <form class="<?php echo esc_attr( $atts['form_class'] ); ?>" method="get">
            <?php foreach ( $_GET as $key => $value ) {
                if ( in_array( $key, [ 'product_cat', 'submit', 'paged' ], true ) ) continue;
                if ( is_array( $value ) ) {
                    foreach ( $value as $v ) { printf('<input type="hidden" name="%1$s[]" value="%2$s">', esc_attr($key), esc_attr($v)); }
                } else {
                    printf('<input type="hidden" name="%1$s" value="%2$s">', esc_attr($key), esc_attr($value));
                }
            } ?>
            <select name="product_cat" class="<?php echo esc_attr( $atts['select_class'] ); ?>" onchange="this.form.submit()">
                <option value=""><?php esc_html_e( 'Todas las categorías', 'woocommerce' ); ?></option>
                <?php foreach ( $terms as $term ) : ?>
                    <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $current, $term->slug ); ?>>
                        <?php echo esc_html( $term->name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php
        return ob_get_clean();
    }

    public function apply_category_filter_query( $query ): void {
        if (is_admin() || !$query->is_main_query()) return;
        if (is_shop() || is_post_type_archive('product') || is_tax('product_cat')) {
            if (!empty($_GET['product_cat'])) {
                $query->set('tax_query', [[ 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => sanitize_text_field($_GET['product_cat']) ]]);
            }
        }
    }

    public function handle_sold_individually_products( $passed, $product_id ) {
        $product_to_add = wc_get_product($product_id);
        if ($product_to_add && $product_to_add->is_sold_individually() && !WC()->cart->is_empty()) {
            $cart_items = WC()->cart->get_cart();
            $first_item = reset($cart_items);
            $removed_product_name = $first_item['data']->get_name();
            WC()->cart->empty_cart();
            $notice_text = sprintf('Solo se permite un producto por pedido. Hemos reemplazado "%s" con "%s" en tu carrito.', esc_html($removed_product_name), esc_html($product_to_add->get_name()));
            wc_add_notice($notice_text, 'notice');
        }
        return $passed;
    }

    // =========================================================================
    // MÉTODOS DE LÓGICA (CHECKOUT)
    // =========================================================================

    public function change_us_locale_labels( $locale ) {
        $locale['US']['state']['label'] = __('Estado', 'woocommerce');
        $locale['US']['city']['label'] = __('Ciudad', 'woocommerce');
        return $locale;
    }

    public function customize_checkout_fields( $fields ) {
        $needs_shipping = WC()->cart && WC()->cart->needs_shipping();
        $billing_fields_to_keep = ['billing_first_name', 'billing_last_name', 'billing_email', 'billing_phone'];
        if ($needs_shipping) {
            $shipping_fields = ['billing_country', 'billing_state', 'billing_city', 'billing_address_1', 'billing_postcode'];
            $billing_fields_to_keep = array_merge($billing_fields_to_keep, $shipping_fields);
        }
        $fields['billing'] = array_intersect_key($fields['billing'], array_flip($billing_fields_to_keep));
        unset($fields['order']['order_comments']);
        return $fields;
    }

    public function allow_only_us_country( $countries ) {
        return ['US' => __('United States', 'woocommerce')];
    }

    public function default_country_to_us( $address_fields ) {
        $address_fields['country']['default'] = 'US';
        return $address_fields;
    }
}
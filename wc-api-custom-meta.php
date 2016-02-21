<?php
/*
Plugin Name: WC API Custom Meta
Plugin URI:  hhttps://github.com/judgej/wc-api-custom-meta
Description: Allows access to custom meta fields on products through the API.
Version:     0.7.0
Author:      Jason Judge
Author URI:  http://academe.co.uk https://github.com/buxit
*/

/**
 * Everything is static at present.
 * We may go singletone route in the future if there is some state
 * to handle, for example if the list of protected fields can be
 * modified - perhaps through a filter hook - at the start.
 */

class Academe_Wc_Api_Custom_Meta
{
    // Meta fields we want to protect, due to them being already handled
    // by the WC API.
    // To view or change these fields, go through the appropriate API.

    protected static $protected_fields = array(
        // A few WP internal fields should not be exposed.
        '_edit_lock',
        '_edit_last',
        // All these meta fields are already present in the
        // product_data in some form.
        '_visibility',
        '_stock_status',
        'total_sales',
        '_downloadable',
        '_virtual',
        '_regular_price',
        '_sale_price',
        '_purchase_note',
        '_featured',
        '_weight',
        '_length',
        '_width',
        '_height',
        '_sku',
        '_product_attributes',
        '_price',
        '_sold_individually',
        '_manage_stock',
        '_backorders',
        '_stock',
        '_upsell_ids',
        '_crosssell_ids',
        '_product_image_gallery',
        '_sale_price_dates_from',
        '_sale_price_dates_to',
    );

    protected static $product_type = array(
        'meta_simple',
        'meta_variable',
        'grouped',
        'external',
    );

    /**
     * Initialise all hooks at plugin initialisation.
     * It may be worth registering the hooks in two layers, so we
     * first check we have the capability and that WooCommerce is
     * installed, before registering the remaining hooks. Also can
     * check if we are being invoked by the WC API, as there is no
     * point registering these API hooks if we aren't.
     */
    public static function initialize()
    {
        // GET product: add in meta field to results.
        add_filter(
            'woocommerce_api_product_response',
            array('Academe_Wc_Api_Custom_Meta', 'fetchCustomMeta'),
            10,
            4
        );

        // Want to hook into woocommerce_api_process_product_meta_{product_type} for all product types.
        foreach(static::$product_type as $product_type) {
            add_action(
                'woocommerce_api_process_product_' . $product_type,
                array('Academe_Wc_Api_Custom_Meta', 'updateCustomMeta'),
                10,
                2
            );
        }
        // Add a hook to update product variations
        add_action(
            'woocommerce_api_save_product_variation',
            array('Academe_Wc_Api_Custom_Meta', 'updateVariationCustomMeta'),
            10,
            3
        );
    }

    /**
     * Fetching a product detail.
     * Add in the custom meta fields if we have the capability.
     */
    public static function fetchCustomMeta($product_data, $product, $fields, $server) {
        // The admin and shop manager will have the capability "manage_woocommerce".
        // We only want users with this capability to see additional product meta fields.

        if (current_user_can('manage_woocommerce')) {
            $product_id = $product->id;

            $all_meta = get_post_meta($product_id);

            // Filter out meta we don't want.
            $all_meta = array_diff_key($all_meta, array_flip(static::$protected_fields));

            // Unserialize the meta field data where necessary.
            foreach($all_meta as $key => &$value) {
                $value = maybe_unserialize(reset($value));
            }
            unset($value);

            $meta = $all_meta;

            $product_data['meta'] = $meta;

            if(isset($product_data['variations'])) {
                foreach($product_data['variations'] as $k => &$variation) {
                    $variation_id = $variation['id'];

                    $all_meta = get_post_meta($variation_id);

                    // Filter out meta we don't want.
                    $all_meta = array_diff_key($all_meta, array_flip(static::$protected_fields));

                    // Unserialize the meta field data where necessary.
                    foreach($all_meta as $key => &$value) {
                        $value = maybe_unserialize(reset($value));
                    }
                    unset($value);

                    $meta = $all_meta;

                    $variation['meta'] = $meta;
                }
            }
        }

        return $product_data;
    }

    /**
     * Update or create a product.
     */
    public static function updateCustomMeta($id, $data) {
        // Create or update fields.
        if (!empty($data['custom_meta']) && is_array($data['custom_meta'])) {
            // Filter out protected fields.
            $custom_meta = array_diff_key(
                $data['custom_meta'],
                array_flip(static::$protected_fields)
            );

            foreach($custom_meta as $field_name => $field_value) {
                update_post_meta($id, $field_name, wc_clean($field_value));
            }
        }

        // Remove meta fields.
        if (!empty($data['remove_custom_meta']) && is_array($data['remove_custom_meta'])) {
            // Filter out protected fields.
            $remove_custom_meta = array_diff(
                $data['remove_custom_meta'],
                static::$protected_fields
            );

            foreach($remove_custom_meta as $key => $value) {
                // If the key is numeric, then assume $value is the field name
                // and all entries need to be deleted. Otherwise is is a specfic value
                // of a named meta field that should be removed.

                if (is_numeric($key)) {
                    delete_post_meta($id, $value);
                } else {
                    delete_post_meta($id, $key, $value);
                }
            }
        }
    }

    /**
     * Update or create a product variation using above function.
     */
    public static function updateVariationCustomMeta($id, $menu_order, $data) {
        Academe_Wc_Api_Custom_Meta::updateCustomMeta($id, $data);
    }
}

Academe_Wc_Api_Custom_Meta::initialize();

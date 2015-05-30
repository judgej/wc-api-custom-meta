<?php
/*
Plugin Name: WC API Custom Metafields
Plugin URI:  https://github.com/academe/TBC
Description: Allows custom meta fields to be added to products when creating or updating.
Version:     0.5.1
Author:      Jason Judge
Author URI:  http://academe.co.uk
*/

// Want to hook into woocommerce_api_process_product_meta_{product_type} for all product types.

add_action('woocommerce_api_process_product_meta_simple', 'academe_wc_api_custom_meta', 10, 2);
add_action('woocommerce_api_process_product_meta_variable', 'academe_wc_api_custom_meta', 10, 2);
add_action('woocommerce_api_process_product_grouped', 'academe_wc_api_custom_meta', 10, 2);
add_action('woocommerce_api_process_product_external', 'academe_wc_api_custom_meta', 10, 2);

function academe_wc_api_custom_meta($id, $data) {
    if (!empty($data['custom_meta']) && is_array($data['custom_meta'])) {
        foreach($data['custom_meta'] as $field_name => $field_value) {
            update_post_meta($id, $field_name, wc_clean($field_value));
        }
    }

    if (!empty($data['custom_meta']) && is_array($data['remove_custom_meta'])) {
        foreach($data['remove_custom_meta'] as $key => $value) {
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


// GET product through the API
add_filter('woocommerce_api_product_response', 'academe_wc_api_get_meta', 10, 4);

function academe_wc_api_get_meta($product_data, $product, $fields, $server) {
    // The admin and shop manager will have the capability "manage_woocommerce".
    // We only want users with this capability to see additional product meta fields.

    if (current_user_can('manage_woocommerce')) {
        $product_id = $product->id;

        // Meta fields we don't want, due to them being already in the data.
        $exclude = array(
            // A few WP internal fields should not be exposed.
            "_edit_lock",
            "_edit_last",
            // All these meta fields are already present in the product_data in some form.
            "_visibility",
            "_stock_status",
            "total_sales",
            "_downloadable",
            "_virtual",
            "_regular_price",
            "_sale_price",
            "_purchase_note",
            "_featured",
            "_weight",
            "_length",
            "_width",
            "_height",
            "_sku",
            "_product_attributes",
            "_price",
            "_sold_individually",
            "_manage_stock",
            "_backorders",
            "_stock",
            "_upsell_ids",
            "_crosssell_ids",
            "_product_image_g
            allery",
            // The sale dates are actually not directly in the normal data returned.
            "_sale_price_dates_from",
            "_sale_price_dates_to",
        );

        $all_meta = get_post_meta($product_id);

        // Filter out meta we don't want.
        $all_meta = array_diff_key($all_meta, array_flip($exclude));

        // Unserialize the meta field data where necessary.
        foreach($all_meta as $key => &$value) {
            $value = maybe_unserialize(reset($value));
        }
        unset($value);

        $meta = array($all_meta);

        $product_data['meta'] = $meta;
    }

    return $product_data;
}

<?php
/*
Plugin Name: WC API Custom Metafields
Plugin URI:  https://github.com/academe/TBC
Description: Allows custom meta fields to be added to products when creating or updating.
Version:     0.5
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


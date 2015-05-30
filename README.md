# wc-api-custom-meta
WordPress/WooCommerce plugin to support custom meta fields through the product API

A very simple plugin that allows you to create, update or remove custom meta fields when
managing prducts through the WooCommerce API.

It is necessary to install this plugin on the WC site providing the API.
I have raised a ticket asking whether this can ever be a core feature of WooCommerce,
but the ticket has been rejected:

https://github.com/woothemes/woocommerce/issues/7593

It may come back, but this plugin fills a gap in the meantime.

To use it, add elements to the product array passed to the `products` entrypoint. Each element is
an array, with examples that follow:

~~~php
$product = [
    'product' => [
        'title' => 'Foobar',
        ...
        'custom_meta' => [
            'my_custom_field_name' => 'my custom value',
        ],
        'remove_custom_meta' => [
            'remove_all_instances_of_this_field_name',
            'remove_just_one_value_of_this_field_name' => 'this is the value',
        ],
    ]
]
~~~

That's it. Make sure those elements are in your REST API request, and this plugin is installed at the other end,
and you can set any meta fields you like.

I have not tested this with anything other than strings, so be wary that the behaviour storing other data structures
in metafields are *undefined* at present.

## Returning Product Meta

When retrieving a product, the custom product meta fields will be put into the "meta" field as an array when
retrieving a product through the API. This only works for capability 'manage_woocommerce' to help prevent
leakage of secure data.

The field will look something like this:

    "meta": [

        {
            "test custom field": "Hi There!",
            "pv_commission_rate": "",
            "ISBN": "1234567890-1"
        }
    ]

Both visibla and hidden ("_" prefixed) fields will be included in the list. All raw WooCommerce fields that 
are already present in some form in the product data will be filtered out, leaving only fields added by
third-party plugins or manually by the shop manager.

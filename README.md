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

That's it. Make sure those elements are in yoru REST API request, and this plugin is installed at the other end,
and you can set any meta fields you like.

I have not tested this with anything other than strings, so be wary that the behaviour storing otehr data structures
in metafields are *undefined* at present.

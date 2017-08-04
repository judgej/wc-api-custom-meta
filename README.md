# PLEASE NOTE

This plugin was for the WC 2.x range that supported the V2 WC API. That was when
WordPress did not have an API of its own.

The WC APIs have now been replaced by a new REST API, with WC having v1 and v2
endpoints for this. The v2 REST API for WC supports reading, updating, creating
and deleting custom metadata on products out of the box, so there is no need for
this plugin. This plugin will not longer be maintained.

The examples below ignore any authentication you may need, which will be provided
using Basic HTTP auth or OAuth key/secret tokens, either in a header or as GET parameters.

To fetch a product metadata, just fetch the product. The metadata will be in a `meta_data`
array of objects:

GET https://example.co.uk/printtrail-shop/wp-json/wc/v2/products/{product_id}

```
...
"meta_data": [
    {
        "id": 3477,
        "key": "ISBN",
        "value": "123123123123123"
    },
    {
        "id": 3478,
        "key": "newcustom",
        "value": "newcustom value"
    },
    {
        "id": 3479,
        "key": "_ups_method_restriction",
        "value": {
            "new_version": true,
            "restrictions": [ ]
        }
    },
    {
        "id": 3484,
        "key": "pv_commission_rate",
        "value": "50%"
    }
],
...
```

Note that the meta keys returned will include all hidden and non-hidden meta fields, except:

* Fields listed as internal to WC, mostly hidden fields but including `total_sales`.
* Fields starting with `wp_*`.
* Fields starting with `attribute_*` for some product types.
* Fields set as protected by a plugin that owns them through the appropriate filters.

One or more meta fields can be updated using:

PUT https://example.co.uk/printtrail-shop/wp-json/wc/v2/products/{product_id}

You can update an existing value, and even the meta key, keeping the ID:

```
...
"meta_data": [
    {
        "id": 3477,
        "key": "ISBN",
        "value": "new ISBN number"
    }
]
...
```

You can replace a meta key with a new value, which will also give it a new ID
(the old record ID will be removed, which will have a bigger impact on cache
rebuilding, so try to use the ID as shown above).

```
...
"meta_data": [
    {
        "key": "ISBN",
        "value": "yet another ISBN number"
    }
]
...
```

The above will also create a new meta field if the "ISBN" meta key does not
already exist.

Non-string data types will (presumably) be serialised automatically, but I have not tried that.

The DELETE method can be used to delete a meta key. Just supply either the ID or the key.

The POST method is used to create a product. A full set of custom meta fields can be created
with the product by inluding the `meta_data` array.

These operations extend to hidden fields - those with meta keys starting with an underscore.

This PHP library will talk to the REST API and support all the features the REST API provides:

https://github.com/woocommerce/wc-api-php

That's just about it really. Use the new v2 REST API and have fun :-)

--------

# wc-api-custom-meta
WordPress/WooCommerce plugin to support custom meta fields through the product API

A very simple plugin that allows you to view, create, update or remove custom meta fields when
managing products through the WooCommerce API v2.

It is necessary to install this plugin on the WC site providing the API.

I have raised a ticket asking whether this can ever be a core feature of WooCommerce,
but the ticket has been rejected:

https://github.com/woothemes/woocommerce/issues/7593

It may come back, but this plugin fills a gap in the meantime.

## Modifying Product Meta

To use it, add elements to the product array passed to the `products` POST entrypoint.
Each element is an array, with examples below. Both `custom_meta` and `remove_custom_meta`
elements are optional.

~~~php
$product = [
    'product' => [
        'title' => 'Foobar',
        ...
        'custom_meta' => [
            'my_custom_field_name' => 'my custom value',
            'my_other_custom_field_name' => 'my other custom value',
        ],
        'remove_custom_meta' => [
            'remove_all_instances_of_this_field_name',
            'remove_just_one_value_of_this_field_name' => 'this is the value',
        ],
    ]
]
~~~

That's it. Make sure those elements are in your REST API request, and this plugin is installed at the other end,
and you can set any meta fields you like, except for the protected fields (see below).

From version 0.7.0 metadata on variations is also supported:

~~~php
$product = [
    'product' => [
        'title' => 'Foobar',
        ...
        'variations' => [
            [
                'regular_price' => '9.50',
                'attributes' => [
                    [
                        'name' => 'Pack Size',
                        'slug' => 'pack-size',
                        'option' => '4-pack etc',
                    ],
                    // These custom meta fields will be added to the variations.
                    // When fetching variable products, the metafields will be retrieved
                    // for the variations.
                    'custom_meta' => [
                        'my_custom_variation_field_name' => 'my custom variation value',
                        'my_other_variation_custom_field_name' => 'my other custom variation value',
                    ],
                ],
            ],
        ],
    ]
]
~~~

I have not tested this with anything other than strings, so be aware that the behaviour storing other data structures
in metafields are *undefined* at present.

## Returning Product Meta

When retrieving a product, the custom product meta fields will be put into the "meta" field as an array when
retrieving a product through the API. This only works for capability 'manage_woocommerce' to help prevent
leakage of secure data.

The returned `meta` field will look something like this in structure:

~~~json
"meta":
{
    "test custom field": "Hi There!",
    "pv_commission_rate": "20%",
    "ISBN": "978-1910223260"
}
~~~

Both visible and hidden (with "_" prefixes) fields will be included in the list. All raw WooCommerce fields that 
are already present in some form in the product data will be filtered out, leaving only fields added by
third-party plugins or manually by the shop manager.

# WP Emerge Blade

Enables the use of Blade views in [WP Emerge](https://github.com/htmlburger/wpemerge).

## Summary

- [Quickstart](#quickstart)
- [Options](#options)
- [Extending Blade](#extending-blade)
- [WooCommerce](#woocommerce)

## Quickstart

1. Run `composer require htmlburger/wpemerge-blade` in your theme directory
2. Add `\WPEmergeBlade\View\ServiceProvider` to your array of providers in WP Emerge's configuration:
    ```php
    \App::make()->bootstrap( [
        'providers' => [
            \WPEmergeBlade\View\ServiceProvider::class,
        ],
    ] );
    ```
3. If you are using the [WP Emerge Starter Theme](https://github.com/htmlburger/wpemerge-theme) you can **replace** your theme views with the ones inside `theme/views-alternative/blade/`.

## Options

Default options:
```php
[
    // Automatically replace the default view engine for WP Emerge.
    'replace_default_engine' => true,

    // Pass .php views to the default view engine.
    // replace_default_engine must be true for this to take effect.
    'proxy_php_views' => true,

    // Filter core theme templates to search for .blade.php files.
    // This is only necessary in themes.
    'filter_core_templates' => false,

    // Options passed directly to Blade.
    'options' => [
        // 'views' defaults to the main ['views'] key of the configuration.
        'views' => [get_stylesheet_directory(), get_template_directory()],
        // 'cache' defaults to the main ['cache']['path'] key of the configuration.
        'cache' => 'wp-content/uploads/wpemerge/cache/blade',
    ],
]
```

You can change these options by specifying a `blade` key in your WP Emerge config array:
```php
\App::make()->bootstrap( [
    // ... other WP Emerge options
    'blade' => [
        // ... other WP Emerge Blade options
        'options' => [
            // ... other Blade options
            'cache' => get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'blade-cache',
        ],
    ],
] );
```

## Extending Blade

You can use the following to extend blade with a custom directive, for example:
```php
// \App::resolve() used for brevity's sake - use a Service Provider instead.
$blade = \App::resolve( WPEMERGEBLADE_VIEW_BLADE_VIEW_ENGINE_KEY );
$blade->compiler()->directive( 'mydirective', function( $expression ) {
    return "<?php echo 'MyDirective: ' . $expression . '!'; ?>";
} );
```
With this, you now have your very own custom Blade directive:
```blade
@mydirective('foobar')
```

More information on how you can extend Blade is available on [https://laravel.com/docs/5.4/blade#extending-blade](https://laravel.com/docs/5.4/blade#extending-blade)

## WooCommerce

In order to render WooCommerce templates using Blade you must **NOT** use the `.blade.php` extension for WooCommerce templates as it will not detect them. Instead, use the usual `.php` extension for your files, for example:
- `my-theme/woocommerce.php`
- `my-theme/woocommerce/single-product.php`
- `my-theme/woocommerce/archive-product.php`

Even though these files are `.php`, this extension will render them using Blade.

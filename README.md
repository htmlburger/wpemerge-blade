# WP Emerge Blade

Enables the use of Blade views in [WP Emerge](https://github.com/htmlburger/wpemerge).

## Quickstart

1. Run `composer require htmlburger/wpemerge-blade` in your theme directory
1. Add `\WPEmergeBlade\View\ServiceProvider` to your array of providers when booting WP Emerge:
    ```php
    WPEmerge::boot( [
        'providers' => [
            \WPEmergeBlade\View\ServiceProvider::class,
        ],
    ] );
    ```

## Options

Default options:
```php
[
    'replace_default_engine' => true,
    'options' => [
        'views' => get_stylesheet_directory(),
        'cache' => get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'blade',
    ],
]
```

You can change these options by specifying a `blade` key in your WP Emerge config array:
```php
WPEmerge::boot( [
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
$blade = WPEmerge::resolve( 'wpemerge_blade.view.engine' );
$blade->compiler()->directive( 'mydirective', function( $expression ) {
    return "<?php echo 'MyDirective: ' . $expression . '!'; ?>";
} );
```
With this, you now have your very own custom Blade directive:
```blade
@mydirective('foobar')
```

More information on how you can extend Blade is available on https://laravel.com/docs/5.4/blade#extending-blade

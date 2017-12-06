# Оbsidian Blade

Enables the use of Blade templates in WP Emerge.

## Quickstart

1. Run `composer require htmlburger/wp-emerge-blade` in your theme directory
1. Add `\WPEmergeBlade\Templating\ServiceProvider` to your array of providers when booting WP Emerge:
    ```php
    WPEmerge::boot( [
        'providers' => [
            \WPEmergeBlade\Templating\ServiceProvider::class,
        ],
    ] );
    ```
1. Replace the current template engine by adding this immediately after `WPEmerge::boot()`:
    ```php
    $container = WPEmerge::getContainer();
    $container[ WP_EMERGE_TEMPLATING_ENGINE_KEY ] = $container->raw( 'wp_emerge_blade.templating.engine' );
    ```

## Options

Default options:
```php
[
    'views' => get_stylesheet_directory(),
    'cache' => get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'blade',
]
```

You can use this to change the default options:
```php
$container = WPEmerge::getContainer();
$container['wp_emerge_blade.templating.engine.options'] = [
    // example:
    'cache' => get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'blade-cache',
    // ... other options
];
```

## Extending Blade

You can use the following to extend blade with a custom directive, for example:
```php
$blade = WPEmerge::resolve( 'wp_emerge_blade.templating.engine' );
$blade->compiler()->directive( 'mydirective', function( $expression ) {
    return "<?php echo 'MyDirective: ' . $expression . '!'; ?>";
} );
```
With this, you now have your very own custom Blade directive:
```blade
@mydirective('foobar')
```

More information on how you can extend Blade is available on https://laravel.com/docs/5.4/blade#extending-blade

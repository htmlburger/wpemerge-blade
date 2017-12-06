# Оbsidian Blade

Enables the use of Blade templates in Obsidian.

## Quickstart

1. Run `composer require htmlburger/obsidian-blade` in your theme directory
1. Add `\ObsidianBlade\Templating\ServiceProvider` to your array of providers when booting Obsidian:
    ```php
    Obsidian::boot( [
        'providers' => [
            \ObsidianBlade\Templating\ServiceProvider::class,
        ],
    ] );
    ```
1. Replace the current template engine by adding this immediately after `Obsidian::boot()`:
    ```php
    $container = Obsidian::getContainer();
    $container[ OBSIDIAN_TEMPLATING_ENGINE_KEY ] = $container->raw( 'obsidian_blade.templating.engine' );
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
$container = Obsidian::getContainer();
$container['obsidian_blade.templating.engine.options'] = [
    // example:
    'cache' => get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'blade-cache',
    // ... other options
];
```

## Extending Blade

You can use the following to extend blade with a custom directive, for example:
```php
$blade = Obsidian::resolve( 'obsidian_blade.templating.engine' );
$blade->compiler()->directive( 'mydirective', function( $expression ) {
    return "<?php echo 'MyDirective: ' . $expression . '!'; ?>";
} );
```
With this, you now have your very own custom Blade directive:
```blade
@mydirective('foobar')
```

More information on how you can extend Blade is available on https://laravel.com/docs/5.4/blade#extending-blade

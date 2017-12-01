# Оbsidian Blade

Enables the use of Blade templates in Obsidian.

## Quickstart

1. Run `composer require htmlburger/obsidian-blade` in your theme directory
1. Add `\ObsidianBlade\Templating\ServiceProvider` to your array of providers when booting Obsidian:
    ```php
    \Obsidian\Framework::boot( [
        'providers' => [
            \ObsidianBlade\Templating\ServiceProvider::class,
        ],
    ] );
    ```
1. Replace the current template engine by adding this immediately after `\Obsidian\Framework::boot()`:
    ```php
    $container = \Obsidian\Framework::getContainer();
    $container['framework.templating.engine'] = $container->raw( 'obsidian_blade.templating.engine' );
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
$container = \Obsidian\Framework::getContainer();
$container['obsidian_blade.templating.engine.options'] = [
    // example:
    'cache' => get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'blade-cache',
    // ... other options
];
```

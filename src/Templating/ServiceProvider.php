<?php

namespace ObsidianBlade\Templating;

use Obsidian\Helpers\Mixed;
use Obsidian\ServiceProviders\ServiceProviderInterface;

class ServiceProvider implements ServiceProviderInterface {
	/**
	 * Register all dependencies in the IoC container
	 *
	 * @param  \Pimple\Container $container
	 * @return null
	 */
	public function register( $container ) {
		$container['obsidian_blade.templating.engine'] = function( $c ) {
			$key = 'obsidian_blade.templating.engine.options';
			$options = isset( $c[ $key ] ) ? $c[ $key ] : [];

			$options = array_merge( [
				'views' => get_stylesheet_directory(),
				'cache' => get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'blade',
			], $options );

			$blade = new Blade( Mixed::toArray( $options['views'] ), $options['cache'] );
			return new Engine( $blade, $options['views'], $options['cache'] );
		};
	}

	/**
	 * Bootstrap any services if needed
	 *
	 * @param  \Pimple\Container $container
	 * @return null
	 */
	public function boot( $container ) {
		// nothing to boot
	}
}

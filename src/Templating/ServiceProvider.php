<?php

namespace WPEmergeBlade\Templating;

use WPEmerge\Helpers\Mixed;
use WPEmerge\ServiceProviders\ServiceProviderInterface;

class ServiceProvider implements ServiceProviderInterface {
	/**
	 * {@inheritDoc}
	 */
	public function register( $container ) {
		$container['wp_emerge_blade.templating.engine'] = function( $c ) {
			$key = 'wp_emerge_blade.templating.engine.options';
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
	 * {@inheritDoc}
	 */
	public function boot( $container ) {
		// nothing to boot
	}
}

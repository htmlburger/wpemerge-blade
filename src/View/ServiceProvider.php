<?php

namespace WPEmergeBlade\View;

use WPEmerge\Helpers\Mixed;
use WPEmerge\ServiceProviders\ServiceProviderInterface;

class ServiceProvider implements ServiceProviderInterface {
	/**
	 * {@inheritDoc}
	 */
	public function register( $container ) {
		$container[ WPEMERGEBLADE_VIEW_BLADE_VIEW_ENGINE_KEY ] = function( $c ) {
			$key = WPEMERGEBLADE_VIEW_BLADE_VIEW_ENGINE_OPTIONS_KEY;
			$options = isset( $c[ $key ] ) ? $c[ $key ] : [];

			$options = array_merge( [
				'views' => get_stylesheet_directory(),
				'cache' => get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'blade',
			], $options );

			$blade = new Blade( Mixed::toArray( $options['views'] ), $options['cache'] );
			return new ViewEngine( $blade, $options['views'], $options['cache'] );
		};
	}

	/**
	 * {@inheritDoc}
	 */
	public function boot( $container ) {
		// nothing to boot
	}
}

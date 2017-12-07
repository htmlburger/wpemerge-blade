<?php

namespace WPEmergeBlade\Templating;

use WPEmerge\Templating\EngineInterface;

class Engine implements EngineInterface {
	/**
	 * Blade instance
	 *
	 * @var Blade
	 */
	protected $blade = null;

	/**
	 * Root directory for all views
	 *
	 * @var string
	 */
	protected $views = '';

	/**
	 * Constructor
	 *
	 * @param Blade  $blade
	 * @param array  $global_context
	 * @param string $views
	 * @param string $cache
	 */
	public function __construct( Blade $blade, $global_context, $views, $cache ) {
		$this->blade = $blade;
        $this->views = $views;

        $this->blade->get_view_factory()->share( 'global', $global_context );

        wp_mkdir_p( $cache ); // ensure cache directory exists
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( $file, $context ) {
		$file = substr( $file, strlen( $this->views ) );
		$file = preg_replace( '~^/~', '', $file );
		$file = preg_replace( '~\.blade\.php$~', '', $file );
		$file = str_replace( DIRECTORY_SEPARATOR, '.', $file );
		return $this->blade->render( $file, $context );
	}

	/**
	 * Get the compiler
	 *
	 * @return mixed
	 */
	public function compiler() {
		return $this->blade->get_compiler();
	}

	/**
	 * Pass any other methods to the view factory instance
	 *
	 * @param  string $method
	 * @param  array  $params
	 * @return mixed
	 */
	public function __call( $method, $parameters ) {
		$factory = $this->blade->get_view_factory();
		return call_user_func_array( [$factory, $method], $parameters );
	}
}

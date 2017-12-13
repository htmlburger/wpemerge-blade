<?php

namespace WPEmergeBlade\View;

use View;
use WPEmerge\Helpers\Handler;
use WPEmerge\View\EngineInterface;

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
	 * @param string $views
	 * @param string $cache
	 */
	public function __construct( Blade $blade, $views, $cache ) {
		$this->blade = $blade;
		$this->views = $views;

		$this->blade
			->get_view_factory()
			->share( 'global', View::getGlobals() );

		$this->blade
			->get_view_factory()
			->getDispatcher()
			->listen( 'composing: *', function( $event_name, $arguments ) {
				$view = $arguments[0];
				$context = View::compose( $view->getName() );
				$data = $view->getData();
				$view->with( array_merge(
					$context,
					$data
				) );
			} );

		wp_mkdir_p( $cache ); // ensure cache directory exists
	}

	/**
	 * {@inheritDoc}
	 */
	public function exists( $view ) {
		return $this->blade->get_view_factory()->exists( $view );
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( $views, $context ) {
		foreach ( $views as $view ) {
			if ( $this->exists( $view ) ) {
				return $this->blade->render( $view, $context );
			}
		}

		return '';
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

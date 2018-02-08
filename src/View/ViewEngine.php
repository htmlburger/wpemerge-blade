<?php

namespace WPEmergeBlade\View;

use WPEmerge\Facades\View;
use WPEmerge\View\ViewEngineInterface;

class ViewEngine implements ViewEngineInterface {
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
				$blade_view = $arguments[0];

				$view = (new BladeView())->setName( $blade_view->getName() );
				View::compose( $view );

				$blade_view->with( array_merge(
					$view->getContext(),
					$blade_view->getData()
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
	public function canonical( $view ) {
		$finder = $this->blade->get_view_factory()->getFinder();
		try {
			return realpath( $finder->find( $view ) );
		} catch (InvalidArgumentException $e) {
			return '';
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function make( $views, $context = [] ) {
		foreach ( $views as $view ) {
			if ( $this->exists( $view ) ) {
				return (new BladeView())
					->setName( $view )
					->setBladeEngine( $this->blade )
					->with( $context );
			}
		}

		throw new Exception( 'View not found for "' . implode( ', ', $views ) . '"' );
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

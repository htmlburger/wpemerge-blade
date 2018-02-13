<?php

namespace WPEmergeBlade\View;

use WPEmerge\Facades\View;
use WPEmerge\Helpers\Mixed;
use WPEmerge\View\ViewEngineInterface;

class ViewEngine implements ViewEngineInterface {
	/**
	 * Blade instance.
	 *
	 * @var Blade
	 */
	protected $blade = null;

	/**
	 * Root directory for all views.
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
		$this->views = Mixed::normalizePath( realpath( $views ) );

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
		$view = $this->bladeCanonical( $view );
		return $this->blade->get_view_factory()->exists( $view );
	}

	/**
	 * {@inheritDoc}
	 */
	public function canonical( $view ) {
		$view = $this->bladeCanonical( $view );
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
			$view = $this->bladeCanonical( $view );
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
	 * Return a canonical string representation of the view name in Blade's format.
	 *
	 * @param  string $view
	 * @return string
	 */
	public function bladeCanonical( $view ) {
		$views_root = $this->views . DIRECTORY_SEPARATOR;
		$normalized = realpath( $view );

		if ( $normalized && is_file( $normalized ) ) {
			$normalized = preg_replace( '~^' . preg_quote( $views_root, '~' ) . '~', '', $normalized );
			$normalized = str_replace( DIRECTORY_SEPARATOR, '.', $normalized );
			$view = preg_replace( '~\.blade\.php$~', '', $normalized );
		}

		return $view;
	}

	/**
	 * Get the compiler.
	 *
	 * @return mixed
	 */
	public function compiler() {
		return $this->blade->get_compiler();
	}

	/**
	 * Pass any other methods to the view factory instance.
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

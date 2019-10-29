<?php

namespace WPEmergeBlade\View;

use InvalidArgumentException;
use WPEmerge\Application\Application;
use WPEmerge\Facades\View;
use WPEmerge\Helpers\MixedType;
use WPEmerge\View\ViewEngineInterface;
use WPEmerge\View\ViewNotFoundException;

class ViewEngine implements ViewEngineInterface {
	/**
	 * Application.
	 *
	 * @var Application
	 */
	protected $app = null;

	/**
	 * Blade instance.
	 *
	 * @var Blade
	 */
	protected $blade = null;

	/**
	 * Directories for all views.
	 *
	 * @var array<string>
	 */
	protected $directories = [];

	/**
	 * Constructor.
	 *
	 * @param Blade         $blade
	 * @param array<string> $directories
	 * @param string        $cache
	 */
	public function __construct( Application $app, Blade $blade, $directories, $cache ) {
		// Ensure cache directory exists.
		wp_mkdir_p( $cache );

		$this->app = $app;
		$this->blade = $blade;
		$this->directories = $directories;

		$this->blade
			->get_view_factory()
			->getDispatcher()
			->listen( 'composing: *', function( $event_name, $arguments ) {
				$blade_view = $arguments[0];
				$blade_data = $blade_view->getData();

				// Remove Blade internals from context.
				unset( $blade_data['obLevel'] );
				unset( $blade_data['__env'] );
				unset( $blade_data['app'] );

				$view = (new BladeView())
					->setName( $blade_view->getName() )
					->with( $blade_data );

				$compose = $this->app->resolve( WPEMERGE_VIEW_COMPOSE_ACTION_KEY );
				$compose( $view );

				$blade_view->with( $view->getContext() );
			} );
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
		} catch ( InvalidArgumentException $e ) {
			return '';
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function make( $views ) {
		foreach ( $views as $view ) {
			$view = $this->bladeCanonical( $view );
			if ( $this->exists( $view ) ) {
				return (new BladeView())
					->setName( $view )
					->setBladeEngine( $this->blade );
			}
		}

		throw new ViewNotFoundException( 'View not found for "' . implode( ', ', $views ) . '"' );
	}

	/**
	 * Return a canonical string representation of the view name in Blade's format.
	 *
	 * @param  string $view
	 * @return string
	 */
	public function bladeCanonical( $view ) {
		$normalized = realpath( $view );

		if ( $normalized && is_file( $normalized ) ) {
			foreach ( $this->directories as $directory ) {
				$root = $directory . DIRECTORY_SEPARATOR;

				if ( substr( $normalized, 0, strlen( $root ) ) === $root ) {
					$normalized = substr( $normalized, strlen( $root ) );
					$normalized = str_replace( DIRECTORY_SEPARATOR, '.', $normalized );
					$view = preg_replace( '~(\.blade)?\.php$~', '', $normalized );
					break;
				}
			}
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
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function __call( $method, $parameters ) {
		$factory = $this->blade->get_view_factory();
		return call_user_func_array( [$factory, $method], $parameters );
	}

	/**
	 * Check whether a string has a certain suffix.
	 *
	 * @param  string  $haystack
	 * @param  string  $needle
	 * @return boolean
	 */
	private function hasSuffix( $haystack, $needle ) {
		return strtolower( substr( $haystack, -strlen( $needle ) ) ) === $needle;
	}

	/**
	 * Replace a string suffix with a different one.
	 *
	 * @param  string $haystack
	 * @param  string $needle
	 * @param  string $replace
	 * @return string
	 */
	protected function replaceSuffix( $haystack, $needle, $replace ) {
		if ( ! $this->hasSuffix( $haystack, $needle ) || $this->hasSuffix( $haystack, $replace ) ) {
			return $haystack;
		}
		return substr( $haystack, 0, -strlen( $needle ) ) . $replace;
	}

	/**
	 * Proxy a template that is going to be included imminently.
	 *
	 * @param  string $template
	 * @return string
	 */
	protected function proxy( $template ) {
		$container = $this->app->getContainer();

		$container[ WPEMERGEBLADE_VIEW_PROXY ] = $template;

		return WPEMERGEBLADE_DIR . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'proxy.php';
	}

	/**
	 * Get if a template is a WooCommerce one.
	 *
	 * @param  string $template
	 * @return boolean
	 */
	protected function is_woocommerce_template( $template ) {
		$normalized = MixedType::normalizePath( $template );
		$woocommerce = [
			MixedType::normalizePath( get_stylesheet_directory() . '/woocommerce.php' ),
			MixedType::normalizePath( get_stylesheet_directory() . '/woocommerce/' ),
			MixedType::normalizePath( get_template_directory() . '/woocommerce.php' ),
			MixedType::normalizePath( get_template_directory() . '/woocommerce/' ),
		];

		foreach ( $woocommerce as $path ) {
			if ( substr( $template, 0, strlen( $path ) ) === $path ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Filter core template hierarchy to prioritize files with the .blade.php extension.
	 *
	 * @param  array<string> $templates
	 * @return array<string>
	 */
	public function filter_core_template_hierarchy( $templates ) {
		$template_pairs = array_map( function( $template ) {
			$pair = [$template];
			if ( ! $this->hasSuffix( $template, '.blade.php') ) {
				array_unshift( $pair, $this->replaceSuffix( $template, '.php', '.blade.php' ) );
			}
			return $pair;
		}, $templates );

		$filtered_templates = [];

		foreach ( $template_pairs as $pair ) {
			$filtered_templates = array_merge( $filtered_templates, $pair );
		}

		return $filtered_templates;
	}

	/**
	 * Filter the core searchform html if a searchform.blade.php view exists.
	 *
	 * @param  string $html
	 * @return string
	 */
	public function filter_core_searchform( $html ) {
		try {
			$html = $this->make( ['searchform'] )->toString();
		} catch ( ViewNotFoundException $e ) {
			// No searchform.blade.php exists - ignore.
		}

		return $html;
	}

	/**
	 * Filter core template included to prioritize files with the .blade.php extension.
	 * Covers cases where *_template_hierarchy does not apply or has been overridden.
	 *
	 * @param  string $template
	 * @return string
	 */
	public function filter_core_template_include( $template ) {
		if ( ! $this->hasSuffix( $template, '.blade.php' ) ) {
			if ( $this->is_woocommerce_template( $template ) ) {
				return $this->proxy( $template );
			}

			$blade_template = $this->replaceSuffix( $template, '.php', '.blade.php' );

			if ( $this->exists( $blade_template ) ) {
				return $this->proxy( $blade_template );
			}
		}

		return $template;
	}
}

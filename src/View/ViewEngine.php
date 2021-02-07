<?php

namespace WPEmergeBlade\View;

use InvalidArgumentException;
use WPEmerge\Helpers\MixedType;
use WPEmerge\View\ViewEngineInterface;
use WPEmerge\View\ViewNotFoundException;

class ViewEngine implements ViewEngineInterface {
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
	 * @param callable      $compose
	 * @param Blade         $blade
	 * @param array<string> $directories
	 * @param string        $cache
	 */
	public function __construct( callable $compose, Blade $blade, $directories, $cache ) {
		// Ensure cache directory exists.
		wp_mkdir_p( $cache );

		$this->blade = $blade;
		$this->directories = $directories;

		$this->blade
			->container()
			->get( 'view' )
			->getDispatcher()
			->listen( 'composing: *', function( $event_name, $arguments ) use ( $compose ) {
				$blade_view = $arguments[0];
				$blade_data = $blade_view->getData();

				// Remove Blade internals from context.
				unset( $blade_data['obLevel'] );
				unset( $blade_data['__env'] );
				unset( $blade_data['app'] );

				$view = (new BladeView())
					->setName( $blade_view->getName() )
					->with( $blade_data );

				$compose( $view );

				$blade_view->with( $view->getContext() );
			} );
	}

	/**
	 * {@inheritDoc}
	 */
	public function exists( $view ) {
		$view = $this->bladeCanonical( $view );
		return $this->blade->container()->get( 'view' )->exists( $view );
	}

	/**
	 * {@inheritDoc}
	 */
	public function canonical( $view ) {
		$view = $this->bladeCanonical( $view );
		$finder = $this->blade->container()->get( 'view' )->getFinder();

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
		return $this->blade->compiler();
	}

	/**
	 * Pass any other methods to the view factory instance.
	 *
	 * @param  string $method
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function __call( $method, $parameters ) {
		$factory = $this->blade->container()->get( 'view' );
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
	 * @param  array  $arguments
	 * @return string
	 */
	protected function proxy( $template, $arguments = [] ) {
		$engine = $this;

		add_filter( 'wpemergeblade.proxy', function () use ( $engine, $template, $arguments ) {
			return [
				'engine' => $engine,
				'template' => $template,
				'arguments' => $arguments,
			];
		} );

		return WPEMERGEBLADE_DIR . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'proxy.php';
	}

	/**
	 * Get if a template is a WooCommerce one.
	 *
	 * @param  string $template
	 * @return boolean
	 */
	protected function isWooCommerceTemplate( $template ) {
		$normalized = MixedType::normalizePath( $template );
		$woocommerce = [
			MixedType::normalizePath( get_stylesheet_directory() . '/woocommerce.php' ),
			MixedType::normalizePath( get_stylesheet_directory() . '/woocommerce/' ),
			MixedType::normalizePath( get_template_directory() . '/woocommerce.php' ),
			MixedType::normalizePath( get_template_directory() . '/woocommerce/' ),
		];

		foreach ( $woocommerce as $path ) {
			if ( substr( $normalized, 0, strlen( $path ) ) === $path ) {
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
	public function filterCoreTemplateHierarchy( $templates ) {
		$pairs = array_map( function( $template ) {
			$pair = [$template];
			if ( ! $this->hasSuffix( $template, '.blade.php') ) {
				array_unshift( $pair, $this->replaceSuffix( $template, '.php', '.blade.php' ) );
			}
			return $pair;
		}, $templates );

		return call_user_func_array( 'array_merge', $pairs );
	}

	/**
	 * Filter the core searchform html if a searchform.blade.php view exists.
	 *
	 * @param  string $html
	 * @return string
	 */
	public function filterCoreSearchform( $html ) {
		try {
			$html = $this->make( ['searchform'] )->toString();
		} catch ( ViewNotFoundException $e ) {
			// No searchform.blade.php exists - ignore.
		}

		return $html;
	}

	/**
	 * Filter included core template to prioritize files with the .blade.php extension.
	 * Covers cases where *_template_hierarchy does not apply or has been overridden.
	 *
	 * @param  string $template
	 * @param  array  $arguments
	 * @return string
	 */
	public function filterCoreTemplateInclude( $template, $arguments = [] ) {
		if ( ! $this->hasSuffix( $template, '.blade.php' ) ) {
			if ( $this->isWooCommerceTemplate( $template ) ) {
				// Woo will not load .blade.php files so we must always use .php and always treat them as Blade.
				return $this->proxy( $template, $arguments );
			}

			$blade_template = $this->replaceSuffix( $template, '.php', '.blade.php' );

			if ( $this->exists( $blade_template ) ) {
				return $this->proxy( $blade_template, $arguments );
			}
		}

		return $template;
	}

	/**
	 * Filter included WooCommerce template identically to filterCoreTemplateInclude but also passing Woo arguments.
	 *
	 * @param  string $template
	 * @param  string $template_name
	 * @param  array  $args
	 * @return string
	 */
	public function filterWooTemplateInclude( $template, $template_name, $args ) {
		return $this->filterCoreTemplateInclude( $template, $args );
	}
}

<?php

namespace WPEmergeBlade\View;

use WPEmerge\Exceptions\ViewException;
use WPEmerge\Facades\View;
use WPEmerge\Helpers\MixedType;
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
		$this->views = MixedType::normalizePath( realpath( $views ) );

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
			$match_root = '/^' . preg_quote( $this->views . DIRECTORY_SEPARATOR, '/' ) . '/i';
			return preg_replace( $match_root, '', realpath( $finder->find( $view ) ) );
		} catch (InvalidArgumentException $e) {
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

		throw new ViewException( 'View not found for "' . implode( ', ', $views ) . '"' );
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
			$view = preg_replace( '~(\.blade)?\.php$~', '', $normalized );
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
	 * Filter core template hierarchy to prioritize files with the .blade.php extension.
	 *
	 * @param  array<string> $templates
	 * @return array<string>
	 */
	public function filter_core_template_hierarchy( $templates ) {
		$php_templates = array_filter( $templates, function( $template ) {
			return $this->hasSuffix( $template, '.php' ) && ! $this->hasSuffix( $template, '.blade.php');
		} );

		$template_pairs = array_map( function( $template ) {
			return [
				$this->replaceSuffix( $template, '.php', '.blade.php' ),
				$template,
			];
		}, $php_templates );

		$blade_templates = [];

		foreach ( $template_pairs as $pair ) {
			$blade_templates = array_merge( $blade_templates, $pair );
		}

		return $blade_templates;
	}

	/**
	 * Filter the core comments template to prioritize files with the .blade.php extension.
	 *
	 * @param  string  $template
	 * @param  boolean $proxy
	 * @return string
	 */
	public function filter_core_comments_template( $template, $proxy = true ) {
		$is_php = $this->hasSuffix( $template, '.php' );
		$is_blade = $this->hasSuffix( $template, '.blade.php' );
		$blade_template = $this->replaceSuffix( $template, '.php', '.blade.php' );
		$proxy_template = WPEMERGEBLADE_DIR . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'comments-proxy.php';

		if ( $is_php && ! $is_blade && $this->exists( $blade_template ) ) {
			$template = $proxy ? $proxy_template : $blade_template;
		}

		return $template;
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
		} catch ( ViewException $e ) {
			// No searchform.blade.php exists - ignore.
		}

		return $html;
	}
}

<?php

namespace WPEmergeBlade\View;

use WPEmerge\Helpers\MixedType;
use WPEmerge\ServiceProviders\ExtendsConfigTrait;
use WPEmerge\ServiceProviders\ServiceProviderInterface;
use WPEmerge\View\NameProxyViewEngine;

class ServiceProvider implements ServiceProviderInterface {
	use ExtendsConfigTrait;

	/**
	 * {@inheritDoc}
	 */
	public function register( $container ) {
		$cache_dir = $container[ WPEMERGE_CONFIG_KEY ]['cache']['path'];

		$this->extendConfig( $container, 'blade', [
			'replace_default_engine' => true,
			'proxy_php_views' => true,
			'options' => [
				'views' => [get_stylesheet_directory(), get_template_directory()],
				'cache' => MixedType::addTrailingSlash( $cache_dir ) . 'blade',
			],
		] );

		$container[ WPEMERGEBLADE_VIEW_BLADE_VIEW_ENGINE_KEY ] = function( $c ) {
			$options = $c[ WPEMERGE_CONFIG_KEY ]['blade']['options'];
			$views = MixedType::toArray( $options['views'] );
			$views = array_map( [MixedType::class, 'normalizePath'], $views );
			$views = array_filter( $views );
			$cache = MixedType::normalizePath( $options['cache'] );
			$blade = new Blade( $views, $cache );

			return new ViewEngine( $c[ WPEMERGE_VIEW_COMPOSE_ACTION_KEY ], $blade, $views, $cache );
		};

		if ( $container[ WPEMERGE_CONFIG_KEY ]['blade']['replace_default_engine'] ) {
			$container[ WPEMERGE_VIEW_ENGINE_KEY ] = function( $c ) {
				if ( $c[ WPEMERGE_CONFIG_KEY ]['blade']['proxy_php_views'] ) {
					return new NameProxyViewEngine(
						$c[ WPEMERGE_APPLICATION_KEY ],
						[
							'.blade.php' => WPEMERGEBLADE_VIEW_BLADE_VIEW_ENGINE_KEY,
							'.php' => WPEMERGE_VIEW_PHP_VIEW_ENGINE_KEY,
							// use Blade for all other cases as blade views can be referenced
							// in blade.format.as.well without an extension.
						],
						WPEMERGEBLADE_VIEW_BLADE_VIEW_ENGINE_KEY
					);
				}

				return $c[ WPEMERGEBLADE_VIEW_BLADE_VIEW_ENGINE_KEY ];
			};
		}

		$container[ WPEMERGEBLADE_VIEW_PROXY ] = '';
	}

	/**
	 * {@inheritDoc}
	 */
	public function bootstrap( $container ) {
		$view_engine = $container[ WPEMERGEBLADE_VIEW_BLADE_VIEW_ENGINE_KEY ];
		$hooks = [
			'index',
			'404',
			'archive',
			'author',
			'category',
			'tag',
			'taxonomy',
			'date',
			'embed',
			'home',
			'frontpage',
			'page',
			'paged',
			'search',
			'single',
			'singular',
			'attachment'
		];

		foreach ( $hooks as $hook ) {
			add_filter( "{$hook}_template_hierarchy", [$view_engine, 'filterCoreTemplateHierarchy'], 100 );
		}

		add_filter( 'get_search_form', [$view_engine, 'filterCoreSearchform'], 100 );
		add_filter( 'comments_template', [$view_engine, 'filterCoreTemplateInclude'], 100 );

		// Use lower priority than HttpKernel so it receives the filtered template.
		add_filter( 'template_include', [$view_engine, 'filterCoreTemplateInclude'], 3090 );
		add_filter( 'wc_get_template', [$view_engine, 'filterCoreTemplateInclude'], 3090 );
		add_filter( 'wc_get_template_part', [$view_engine, 'filterCoreTemplateInclude'], 3090 );
	}
}

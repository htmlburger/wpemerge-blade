<?php

namespace WPEmergeBlade\View;

use WPEmerge\Helpers\MixedType;
use WPEmerge\ServiceProviders\ExtendsConfigTrait;
use WPEmerge\ServiceProviders\ServiceProviderInterface;

class ServiceProvider implements ServiceProviderInterface {
	use ExtendsConfigTrait;

	/**
	 * {@inheritDoc}
	 */
	public function register( $container ) {
		$this->extendConfig( $container, 'blade', [
			'replace_default_engine' => true,
			'options' => [
				'views' => MixedType::normalizePath( get_stylesheet_directory() ),
				'cache' => MixedType::normalizePath( get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'blade' ),
			],
		] );

		$container[ WPEMERGEBLADE_VIEW_BLADE_VIEW_ENGINE_KEY ] = function( $c ) {
			$options = $c[ WPEMERGE_CONFIG_KEY ]['blade']['options'];
			$blade = new Blade( MixedType::toArray( $options['views'] ), $options['cache'] );
			return new ViewEngine( $blade, $options['views'], $options['cache'] );
		};

		if ( $container[ WPEMERGE_CONFIG_KEY ]['blade']['replace_default_engine'] ) {
			$container[ WPEMERGE_VIEW_ENGINE_KEY ] = $container->raw( WPEMERGEBLADE_VIEW_BLADE_VIEW_ENGINE_KEY );
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function boot( $container ) {
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
			add_filter( "{$hook}_template_hierarchy", [$container[ WPEMERGEBLADE_VIEW_BLADE_VIEW_ENGINE_KEY ], 'filter_core_template_hierarchy'], 100 );
		}

		add_filter( 'comments_template', [$container[ WPEMERGEBLADE_VIEW_BLADE_VIEW_ENGINE_KEY ], 'filter_core_comments_template'], 100 );
		add_filter( 'get_search_form', [$container[ WPEMERGEBLADE_VIEW_BLADE_VIEW_ENGINE_KEY ], 'filter_core_searchform'], 100 );
	}
}

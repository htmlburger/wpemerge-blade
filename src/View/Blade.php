<?php

namespace WPEmergeBlade\View;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\ViewServiceProvider;

/**
 * Modified version of jenssegers/blade
 */
class Blade
{
	/**
	 * Container
	 *
	 * @var Container
	 */
	protected $container = null;

	/**
	 * View service provider
	 *
	 * @var ViewServiceProvider
	 */
	protected $service_provider = null;

	/**
	 * Engine resolver
	 *
	 * @var \Illuminate\View\Engines\EngineResolver
	 */
	protected $engine_resolver = null;

	/**
	 * Constructor
	 *
	 * @param string[]          $view_paths
	 * @param string            $cache_path
	 * @param ContainerContract $container
	 */
	public function __construct( $view_paths, $cache_path, ContainerContract $container = null ) {
		$this->container = $container ? $container : new Container();

		$this->addBindingsToContainer( $this->container, $view_paths, $cache_path );

		$this->service_provider = new ViewServiceProvider( $this->container );
		$this->service_provider->register();

		$this->engine_resolver = $this->container->make( 'view.engine.resolver' );

		$this->get_view_factory()->addExtension( 'php', 'blade' );
	}

	/**
	 * Add all required bindings to a container
	 *
	 * @param ContainerContract $container
	 * @param string[]          $view_paths
	 * @param string            $cache_path
	 */
	protected function addBindingsToContainer( $container, $view_paths, $cache_path ) {
		$container->bindIf('files', function() {
			return new Filesystem();
		}, true);

		$container->bindIf('events', function() {
			return new Dispatcher();
		}, true);

		$container->bindIf('config', function() use ( $view_paths, $cache_path ) {
			return [
				'view.paths' => $view_paths,
				'view.compiled' => $cache_path,
			];
		}, true);
	}

	/**
	 * Render a view to a string
	 *
	 * @param  string $view
	 * @param  array  $data
	 * @param  array  $merge_data
	 *
	 * @return string
	 */
	public function render( $view, $data = [], $merge_data = [] ) {
		$view = $this->container['view']->make( $view, $data, $merge_data );
		return $view->render();
	}

	/**
	 * Get the compiler
	 *
	 * @return mixed
	 */
	public function get_compiler() {
		$blade_engine = $this->engine_resolver->resolve( 'blade' );
		return $blade_engine->getCompiler();
	}

	/**
	 * Get the view factory
	 *
	 * @return mixed
	 */
	public function get_view_factory() {
		return $this->container['view'];
	}
}

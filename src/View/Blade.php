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
class Blade {
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
	 * @param string            $namespace
	 * @param string[]          $view_paths
	 * @param string            $cache_path
	 * @param ContainerContract $container
	 */
	public function __construct( $namespace, $view_paths, $cache_path, ContainerContract $container = null ) {
		$this->setContainer( $container ? $container : new Container() );

		$this->addBindingsToContainer( $namespace, $this->container(), $view_paths, $cache_path );

		$this->service_provider = new ViewServiceProvider( $this->container() );
		$this->service_provider->register();

		$this->engine_resolver = $this->container()->make( 'view.engine.resolver' );

		$this->container()->get( 'view' )->addExtension( 'php', 'blade' );
	}

	/**
	 * Get container instance.
	 *
	 * @return Container
	 */
	public function container() {
		return Container::getInstance();
	}

	/**
	 * Set container instance.
	 *
	 * @param Container $container
	 * @return void
	 */
	public function setContainer( $container ) {
		Container::setInstance( $container );
	}

	/**
	 * Add all required bindings to a container
	 *
	 * @param string            $namespace
	 * @param ContainerContract $container
	 * @param string[]          $view_paths
	 * @param string            $cache_path
	 */
	protected function addBindingsToContainer( $namespace, $container, $view_paths, $cache_path ) {
		$container->bind( 'app', function () use ( $namespace ) {
			return new Application( $namespace );
		} );

		$container->bindIf( 'files', function() {
			return new Filesystem();
		}, true );

		$container->bindIf( 'events', function() {
			return new Dispatcher();
		}, true );

		$container->bindIf( 'config', function() use ( $view_paths, $cache_path ) {
			return [
				'view.paths' => $view_paths,
				'view.compiled' => $cache_path,
			];
		}, true );

		$container->alias( 'app', \Illuminate\Foundation\Application::class );
		$container->alias( 'app', \Illuminate\Contracts\Container\Container::class );
		$container->alias( 'app', \Illuminate\Contracts\Foundation\Application::class );
		$container->alias( 'app', \Psr\Container\ContainerInterface::class );

		$container->alias( 'view', \Illuminate\View\Factory::class );
		$container->alias( 'view', \Illuminate\Contracts\View\Factory::class );

		\Illuminate\Support\Facades\Facade::setFacadeApplication( $container->get( 'app' ) );
		// \Illuminate\Support\Facades\View::setFacadeApplication();
	}

	/**
	 * Get the compiler
	 *
	 * @return mixed
	 */
	public function compiler() {
		return $this->engine_resolver->resolve( 'blade' )->getCompiler();
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
		return $this->container()->get( 'view' )->make( $view, $data, $merge_data )->render();
	}
}

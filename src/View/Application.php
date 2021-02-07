<?php

namespace WPEmergeBlade\View;

/**
 * Stub for Laravel's Application.
 */
class Application {
	/**
	 * Base namespace.
	 *
	 * @var string
	 */
	protected $namespace = '';

	/**
	 * Constructor.
	 *
	 * @param string $namespace
	 */
	public function __construct( $namespace ) {
		$this->namespace = $namespace;
	}

	/**
	 * Get the application namespace.
	 *
	 * @return string
	 */
	public function getNamespace() {
		return $this->namespace;
	}
}

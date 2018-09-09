<?php

namespace WPEmergeBlade\View;

use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Response;
use WPEmerge\View\ViewException;
use WPEmerge\View\HasContextTrait;
use WPEmerge\View\HasNameTrait;
use WPEmerge\View\ViewInterface;

/**
 * Render a view file with php.
 */
class BladeView implements ViewInterface {
	use HasContextTrait, HasNameTrait;

	/**
	 * Blade engine.
	 *
	 * @var Blade
	 */
	protected $blade_engine = null;

	/**
	 * {@inheritDoc}
	 */
	public function getBladeEngine() {
		return $this->blade_engine;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setBladeEngine( Blade $blade_engine ) {
		$this->blade_engine = $blade_engine;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toString() {
		if ( empty( $this->getName() ) ) {
			throw new ViewException( 'View must have a name.' );
		}

		return $this->getBladeEngine()->render( $this->getName(), $this->getContext() );
	}

	/**
	 * {@inheritDoc}
	 */
	public function toResponse() {
		return (new Response())
			->withHeader( 'Content-Type', 'text/html' )
			->withBody( Psr7\stream_for( $this->toString() ) );
	}
}

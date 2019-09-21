<?php
/**
 * Absolute path to extension's directory.
 */
if ( ! defined( 'WPEMERGEBLADE_DIR' ) ) {
	define( 'WPEMERGEBLADE_DIR', __DIR__ );
}

/**
 * Service container keys.
 */
if ( ! defined( 'WPEMERGEBLADE_VIEW_BLADE_VIEW_ENGINE_KEY' ) ) {
	define( 'WPEMERGEBLADE_VIEW_BLADE_VIEW_ENGINE_KEY', 'wpemergeblade.view.blade_view_engine' );
}

if ( ! defined( 'WPEMERGEBLADE_VIEW_PROXY' ) ) {
	define( 'WPEMERGEBLADE_VIEW_PROXY', 'wpemergeblade.view.proxy' );
}

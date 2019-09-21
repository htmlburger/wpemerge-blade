<?php

use WPEmerge\Facades\WPEmerge;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$template = WPEmerge::resolve( WPEMERGEBLADE_VIEW_PROXY );

if ( ! empty( $template ) ) {
	$engine = WPEmerge::resolve( WPEMERGEBLADE_VIEW_BLADE_VIEW_ENGINE_KEY );
	echo $engine->make( [$template] )->toString();
}

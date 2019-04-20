<?php

use WPEmerge\Facades\View;
use WPEmerge\Facades\WPEmerge;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$engine = WPEmerge::resolve( WPEMERGEBLADE_VIEW_BLADE_VIEW_ENGINE_KEY );
$blade_template = $engine->filter_core_comments_template( $theme_template, false );
$proxy_template = $engine->filter_core_comments_template( $theme_template, true );

// Avoid accidental recursion.
if ( $blade_template === $proxy_template ) {
	/** @noinspection PhpIncludeInspection */
	require $theme_template;
	return;
}

echo View::make( [$blade_template] )->toString();

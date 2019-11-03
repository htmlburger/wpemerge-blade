<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$proxy = apply_filters( 'wpemergeblade.proxy', [] );
remove_all_filters( 'wpemergeblade.proxy' );

if ( ! empty( $proxy ) ) {
	echo $proxy['engine']->make( [$proxy['template']] )->toString();
}

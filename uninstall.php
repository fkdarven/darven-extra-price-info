<?php

register_uninstall_hook(__FILE__, 'darven-epi-uninstall');
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	wp_die( sprintf(
		__( '%s should only be called when uninstalling the plugin.', 'dpmi' ),
		__FILE__
	) );
	exit;
}
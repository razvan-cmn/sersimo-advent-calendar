<?php
// Șterge setările pluginului la dezinstalare (nu atinge cupoanele WooCommerce).
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;
delete_option( 'ssadv_settings' );
delete_option( 'ssadv_days' );
delete_option( 'ssadv_rev' );

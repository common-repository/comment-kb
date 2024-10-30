<?php

if (!defined( 'WP_UNINSTALL_PLUGIN')) {
	exit();
}

if (!function_exists('kbfc_delete_plugin')) {
  function kbfc_delete_plugin() {
  	delete_option('kbfc');
  }

  kbfc_delete_plugin();
}

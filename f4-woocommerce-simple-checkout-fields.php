<?php

/*
Plugin Name: F4 Simple Checkout Fields for WooCommerce
Plugin URI: https://github.com/faktorvier/f4-woocommerce-simple-checkout-fields
Description: Adds custom fields to the WooCommerce checkout and billing/shipping address.
Version: 1.0.8
Author: FAKTOR VIER
Author URI: https://www.faktorvier.ch
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: f4-woocommerce-simple-checkout-fields
Domain Path: /languages/
WC requires at least: 3.0
WC tested up to: 5.0

This plugin is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

This plugin is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this plugin. If not, see https://www.gnu.org/licenses/gpl-2.0.html.

*/

if(!defined('ABSPATH')) exit; // don't access directly

define('F4_WCSCF_VERSION', '1.0.8');

define('F4_WCSCF_SLUG', 'f4-woocommerce-simple-checkout-fields');
define('F4_WCSCF_MAIN_FILE', __FILE__);
define('F4_WCSCF_BASENAME', plugin_basename(F4_WCSCF_MAIN_FILE));
define('F4_WCSCF_PATH', dirname(F4_WCSCF_MAIN_FILE) . DIRECTORY_SEPARATOR);
define('F4_WCSCF_URL', plugins_url('/', F4_WCSCF_MAIN_FILE));
define('F4_WCSCF_PLUGIN_FILE', basename(F4_WCSCF_BASENAME));
define('F4_WCSCF_PLUGIN_FILE_PATH', F4_WCSCF_PATH . F4_WCSCF_PLUGIN_FILE);

// Add autoloader
spl_autoload_register(function($class) {
	$class = ltrim($class, '\\');
	$ns_prefix = 'F4\\WCSCF\\';

	if(strpos($class, $ns_prefix) !== 0) {
		return;
	}

	$class_name = str_replace($ns_prefix, '', $class);
	$class_path = str_replace('\\', DIRECTORY_SEPARATOR, $class_name);
	$class_file = F4_WCSCF_PATH . 'modules' . DIRECTORY_SEPARATOR . $class_path . '.php';

	if(file_exists($class_file)) {
		require_once $class_file;
	}
});

// Init core
F4\WCSCF\Core\Hooks::init();

?>

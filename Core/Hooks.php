<?php

namespace F4\WCSCF\Core;

/**
 * Core Hooks
 *
 * Hooks for the Core module
 *
 * @since 1.0.0
 * @package F4\WCSCF\Core
 */
class Hooks {
	/**
	 * @var array $settings All the module settings
	 */
	protected static $settings = array(

	);

	/**
	 * Initialize the hooks
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function init() {
		add_action('plugins_loaded', __NAMESPACE__ . '\\Hooks::core_loaded');
		add_action('init', __NAMESPACE__ . '\\Hooks::load_textdomain');
		add_action('F4/WCSCF/Core/set_constants', __NAMESPACE__ . '\\Hooks::set_default_constants', 98);
	}

	/**
	 * Fires once the core module is loaded
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function core_loaded() {
		do_action('F4/WCSCF/Core/set_constants');
		do_action('F4/WCSCF/Core/loaded');

		self::load_settings();
	}

	/**
	 * Load module settings
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function load_settings() {
		self::$settings = apply_filters('F4/WCSPE/load_settings', array(

		));
	}

	/**
	 * Load plugin textdomain
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function load_textdomain() {
		$locale = apply_filters('plugin_locale', get_locale(), 'f4-wc-simple-checkout-fields');
		load_plugin_textdomain('f4-wc-simple-checkout-fields', false, plugin_basename(F4_WCSCF_PATH . 'Core/Lang') . '/');
	}

	/**
	 * Sets the module default constants
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function set_default_constants() {
		if(!defined('DUMMY_CONSTANT')) {
			define('DUMMY_CONSTANT', '');
		}
	}
}

?>

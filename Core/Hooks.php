<?php

namespace F4\WCSCF\Core;

use F4\WCSCF\Core\Helpers as Helpers;

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

		// Load settings
		add_action('init', __NAMESPACE__ . '\\Hooks::load_textdomain');
		add_action('init', __NAMESPACE__ . '\\Hooks::load_settings', 11);

		// Checkout and account fields
		add_filter('woocommerce_checkout_fields', __NAMESPACE__ . '\\Hooks::add_checkout_fields');
		add_filter('woocommerce_billing_fields', __NAMESPACE__ . '\\Hooks::add_address_fields', 50, 2);
		add_filter('woocommerce_shipping_fields', __NAMESPACE__ . '\\Hooks::add_address_fields', 50, 2);

		// Formatted address
		add_filter('woocommerce_my_account_my_address_formatted_address', __NAMESPACE__ . '\\Hooks::add_fields_to_formatted_my_account_address', 50, 3);
		add_filter('woocommerce_order_formatted_billing_address', __NAMESPACE__ . '\\Hooks::add_fields_to_formatted_address', 50, 2);
		add_filter('woocommerce_order_formatted_shipping_address', __NAMESPACE__ . '\\Hooks::add_fields_to_formatted_address', 50, 2);
		add_filter('woocommerce_localisation_address_formats', __NAMESPACE__ . '\\Hooks::append_fields_to_localisation_address_formats', 50);
		add_filter('woocommerce_formatted_address_replacements', __NAMESPACE__ . '\\Hooks::replace_fields_in_formatted_address', 50, 2);

			// Backend
		add_filter('woocommerce_customer_meta_fields', __NAMESPACE__ . '\\Hooks::add_customer_meta_fields');
	}

	/**
	 * Load module settings
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function load_settings() {
		self::$settings = apply_filters('F4/WCSCF/load_settings', array(

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
	 * Add fields to the checkout address forms
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function add_checkout_fields($fields) {
		foreach(Helpers::get_registered_fields() as $registered_field) {
			foreach($registered_field['target'] as $field_target) {
				$fields[$field_target][$field_target . '_' . $registered_field['name']] = apply_filters(
					'F4/WCSCF/checkout_field_args',
					wp_parse_args(
						$registered_field['checkout_field_config'],
						array(
							'label' => $registered_field['label'],
							'description' => $registered_field['description'],
							'placeholder' => $registered_field['placeholder'],
							'required' => $registered_field['required'],
							'type' => $registered_field['type'],
							'options' => $registered_field['options'],
							'default' => $registered_field['default'],
							'class' => $registered_field['class'],
							'priority' => Helpers::get_registered_field_priority($registered_field, $field_target, $fields[$field_target])
						)
					),
					$registered_field['name'],
					$field_target,
					$registered_field
				);
			}
		}

		return $fields;
	}

	/**
	 * Add fields to edit address forms
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function add_address_fields($fields, $country) {
		$address_type = doing_filter('woocommerce_billing_fields') ? 'billing' : 'shipping';

		foreach(Helpers::get_registered_fields() as $registered_field) {
			foreach($registered_field['target'] as $field_target) {
				if($field_target !== $address_type) {
					continue;
				}

				$fields[$field_target . '_' . $registered_field['name']] = apply_filters(
					'F4/WCSCF/address_field_args',
					wp_parse_args(
						$registered_field['address_field_config'],
						array(
							'label' => $registered_field['label'],
							'description' => $registered_field['description'],
							'placeholder' => $registered_field['placeholder'],
							'required' => $registered_field['required'],
							'type' => $registered_field['type'],
							'options' => $registered_field['options'],
							'default' => $registered_field['default'],
							'class' => $registered_field['class'],
							'priority' => Helpers::get_registered_field_priority($registered_field, $field_target, $fields)
						)
					),
					$registered_field['name'],
					$field_target,
					$registered_field
				);
			}
		}

		return $fields;
	}

	/**
	 * Add fields to edit address dashboard
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @todo: wenn options dann label statt value
	 */
	public static function add_fields_to_formatted_my_account_address($address, $customer_id, $address_type) {
		foreach(Helpers::get_registered_fields() as $registered_field) {
			foreach($registered_field['target'] as $field_target) {
				if(!$registered_field['show_in_formatted_address'] || $field_target !== $address_type) {
					continue;
				}

				$address[$registered_field['name']] = get_user_meta($customer_id, $address_type . '_' . $registered_field['name'], true);
			}
		}

		return $address;
	}

	/**
	 * Add fields to formatted addresses
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @todo: wenn options dann label statt value
	 */
	public static function add_fields_to_formatted_address($address, $order) {
		$address_type = doing_filter('woocommerce_order_formatted_billing_address') ? 'billing' : 'shipping';

		foreach(Helpers::get_registered_fields() as $registered_field) {
			foreach($registered_field['target'] as $field_target) {
				if(!$registered_field['show_in_formatted_address'] || $field_target !== $address_type) {
					continue;
				}

				$address[$registered_field['name']] = get_post_meta($order->get_id(), '_' . $address_type . '_' . $registered_field['name'], true);
			}
		}

		return $address;
	}

	/**
	 * Add fields to localisation address formats
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @todo: free regex pattern ermoeglichen
	 * @todo: option für zeilenumbruch ja/nein
	 */
	public static function append_fields_to_localisation_address_formats($formats) {
		foreach(Helpers::get_registered_fields() as $registered_field) {
			if(!$registered_field['show_in_formatted_address']) {
				continue;
			}

			foreach($formats as $country => &$format) {
				switch($registered_field['position']) {
					case 'last':
						$format .= "\n" . '{' . $registered_field['name'] . '}';
						break;

					case 'before':
						$next_fields = array();

						foreach($registered_field['position_before'] as $position_before) {
							$next_fields[] = $position_before;
							$next_fields[] = $position_before . '_uppercase';

							if(in_array($position_before, array('first_name', 'last_name'))) {
								$next_fields[] = 'name';
								$next_fields[] = 'name_uppercase';
							}
						}

						$format = preg_replace(
							'/\{(' . implode('|', $next_fields). ')\}/im',
							'{' . $registered_field['name'] . '}' . "\n" . '{$1}',
							$format,
							1
						);

						break;

					case 'after':
						$previous_fields = array();

						foreach($registered_field['position_after'] as $position_after) {
							$previous_fields[] = $position_after;
							$previous_fields[] = $position_after . '_uppercase';

							if(in_array($position_after, array('first_name', 'last_name'))) {
								$previous_fields[] = 'name';
								$previous_fields[] = 'name_uppercase';
							}
						}

						$format = preg_replace(
							'/\{(' . implode('|', $previous_fields). ')\}/im',
							'{$1}' . "\n" . '{' .  $registered_field['name'] . '}',
							$format,
							1
						);

						break;

					default:
						$format = '{' . $registered_field['name'] . '}' . "\n" . $format;
						break;
				}
			}
		}

		return $formats;
	}

	/**
	 * Replace fields in formatted address
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function replace_fields_in_formatted_address($replace, $args) {
		foreach(Helpers::get_registered_fields() as $registered_field) {
			if(!$registered_field['show_in_formatted_address']) {
				continue;
			}

			if(isset($args[$registered_field['name']])) {
				$replace['{' . $registered_field['name'] . '}'] = $args[$registered_field['name']];
				$replace['{' . $registered_field['name'] . '_upper}'] = strtoupper($args[$registered_field['name']]);
			} else {
				$replace['{' . $registered_field['name'] . '_upper}'] = $replace['{' . $registered_field['name'] . '}'] = '';
			}
		}

		return $replace;
	}

	/**
	 * Add fields to backend user edit page
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function add_customer_meta_fields($fields) {
		foreach(Helpers::get_registered_fields() as $registered_field) {
			foreach($registered_field['target'] as $field_target) {
				if(!in_array($field_target, array('billing', 'shipping'))) {
					continue;
				}

				$field_config = apply_filters(
					'F4/WCSCF/user_field_args',
					wp_parse_args(
						$registered_field['user_field_config'],
						array(
							'label' => $registered_field['label'],
							'description' => '',
							'type' => $registered_field['type'],
							'options' => $registered_field['options'],
							'default' => $registered_field['default'],
						)
					),
					$registered_field['name'],
					$field_target,
					$registered_field
				);

				switch($registered_field['position']) {
					case 'last':
						$fields[$field_target]['fields'][$field_target . '_' . $registered_field['name']] = $field_config;
						break;

					case 'before':
						$next_fields = array();

						foreach($registered_field['position_before'] as $position_before) {
							$next_fields[] = $field_target . '_' . $position_before;
						}

						$fields[$field_target]['fields'] = \F4\WCSCF\Core\Helpers::insert_before_key(
							$fields[$field_target]['fields'],
							$next_fields,
							array(
								$field_target . '_' . $registered_field['name'] => $field_config
							)
						);

						break;

					case 'after':
						$previous_fields = array();

						foreach($registered_field['position_after'] as $position_after) {
							$previous_fields[] = $field_target . '_' . $position_after;
						}

						$fields[$field_target]['fields'] = \F4\WCSCF\Core\Helpers::insert_after_key(
							$fields[$field_target]['fields'],
							$previous_fields,
							array(
								$field_target . '_' . $registered_field['name'] => $field_config
							)
						);

						break;

					default:
						if($field_target === 'shipping') {
							$fields[$field_target]['fields'] = \F4\WCSCF\Core\Helpers::insert_after_key(
								$fields[$field_target]['fields'],
								array(
									'copy_billing'
								),
								array(
									$field_target . '_' . $registered_field['name'] => $field_config
								)
							);
						} else {
							$new_field = array($field_target . '_' . $registered_field['name'] => $field_config);
							$fields[$field_target]['fields'] = $new_field + $fields[$field_target]['fields'];
						}

						break;
				}
			}
		}

		return $fields;
	}
}

?>

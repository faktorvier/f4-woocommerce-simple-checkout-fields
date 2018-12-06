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

		// Checkout and account fields
		add_filter('woocommerce_checkout_fields', __NAMESPACE__ . '\\Hooks::add_order_fields');
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
		add_filter('woocommerce_admin_billing_fields', __NAMESPACE__ . '\\Hooks::add_admin_order_fields');
		add_filter('woocommerce_admin_shipping_fields', __NAMESPACE__ . '\\Hooks::add_admin_order_fields');

		// Privacy
		add_filter('woocommerce_privacy_export_customer_personal_data_props', __NAMESPACE__ . '\\Hooks::privacy_customer_personal_data_props', 10, 2);
		add_filter('woocommerce_privacy_erase_customer_personal_data_props', __NAMESPACE__ . '\\Hooks::privacy_customer_personal_data_props', 10, 2);
		add_filter('woocommerce_privacy_export_customer_personal_data_prop_value', __NAMESPACE__ . '\\Hooks::privacy_export_customer_personal_data_prop_value', 10, 3);
		add_filter('woocommerce_privacy_erase_customer_personal_data_prop', __NAMESPACE__ . '\\Hooks::privacy_erase_customer_personal_data_prop', 10, 3);

		add_filter('woocommerce_privacy_remove_order_personal_data_props', __NAMESPACE__ . '\\Hooks::privacy_order_personal_data_props', 10, 2);
		add_filter('woocommerce_privacy_export_order_personal_data_props', __NAMESPACE__ . '\\Hooks::privacy_order_personal_data_props', 10, 2);
		add_filter('woocommerce_privacy_export_order_personal_data_prop', __NAMESPACE__ . '\\Hooks::privacy_export_order_personal_data_prop', 10, 3);
		add_action('woocommerce_privacy_remove_order_personal_data', __NAMESPACE__ . '\\Hooks::privacy_remove_order_personal_data', 10);
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
	public static function add_order_fields($order_fields) {
		$registered_variations = Helpers::get_registered_fields_variations(array(
			'target' => 'order',
			'show_in_order_form' => true
		));

		foreach($registered_variations as $field_slug => $field) {
			$order_fields[$field['target']][$field_slug] = apply_filters(
				'F4/WCSCF/order_field_args',
				wp_parse_args(
					$field['order_field_config'],
					array(
						'label' => $field['label'],
						'description' => $field['description'],
						'placeholder' => $field['placeholder'],
						'required' => $field['required'],
						'type' => $field['type'],
						'options' => $field['options'],
						'default' => $field['default'],
						'class' => $field['class'],
						'priority' => Helpers::get_registered_field_priority($field, $field['target'], $order_fields[$field['target']])
					)
				),
				$field['name'],
				$field['target'],
				$field
			);
		}

		return $order_fields;
	}

	/**
	 * Add fields to edit address forms
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function add_address_fields($address_fields, $country) {
		$registered_variations = Helpers::get_registered_fields_variations(array(
			'target' => doing_filter('woocommerce_billing_fields') ? 'billing' : 'shipping',
			'show_in_address_form' => true
		));

		foreach($registered_variations as $field_slug => $field) {
			$address_fields[$field_slug] = apply_filters(
				'F4/WCSCF/address_field_args',
				wp_parse_args(
					$field['address_field_config'],
					array(
						'label' => $field['label'],
						'description' => $field['description'],
						'placeholder' => $field['placeholder'],
						'required' => $field['required'],
						'type' => $field['type'],
						'options' => $field['options'],
						'default' => $field['default'],
						'class' => $field['class'],
						'priority' => Helpers::get_registered_field_priority($field, $field['target'], $address_fields)
					)
				),
				$field['name'],
				$field['target'],
				$field,
				$country
			);
		}

		return $address_fields;
	}

	/**
	 * Add fields to edit address dashboard
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function add_fields_to_formatted_my_account_address($address, $customer_id, $address_type) {
		$registered_variations = Helpers::get_registered_fields_variations(array(
			'target' => $address_type,
			'show_in_formatted_address' => true
		));

		foreach($registered_variations as $field_slug => $field) {
			$address[$field['name']] = Helpers::maybe_get_registered_field_option_label(
				$field,
				get_user_meta($customer_id, $field_slug, true)
			);
		}

		return $address;
	}

	/**
	 * Add fields to formatted addresses
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function add_fields_to_formatted_address($address, $order) {
		$registered_variations = Helpers::get_registered_fields_variations(array(
			'target' => doing_filter('woocommerce_order_formatted_billing_address') ? 'billing' : 'shipping',
			'show_in_formatted_address' => true
		));

		foreach($registered_variations as $field_slug => $field) {
			$address[$field['name']] = Helpers::maybe_get_registered_field_option_label(
				$field,
				get_post_meta($order->get_id(), '_' . $field_slug, true)
			);
		}

		return $address;
	}

	/**
	 * Add fields to localisation address formats
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function append_fields_to_localisation_address_formats($formats) {
		$registered_fields = Helpers::get_registered_fields(array(
			'show_in_formatted_address' => true
		));

		foreach($registered_fields as $field) {
			foreach($formats as $country => &$format) {
				$format_search = '/(.*)/is';
				$format_replace = '$1' . $field['formatted_address_delimiter'] . '{' . $field['name'] . '}' . "\n";

				if(isset($field['position']['before'])) {
					$next_fields = array();

					foreach($field['position']['before'] as $position_before) {
						$next_fields[] = $position_before;
						$next_fields[] = $position_before . '_uppercase';

						if(in_array($position_before, array('first_name', 'last_name'))) {
							$next_fields[] = 'name';
							$next_fields[] = 'name_uppercase';
						}
					}

					$format_search = '/\{(' . implode('|', $next_fields). ')\}/is';
					$format_replace = "\n" . '{' . $field['name'] . '}' . $field['formatted_address_delimiter'] . '{$1}';
				} elseif(isset($field['position']['after'])) {
					$previous_fields = array();

					foreach($field['position']['after'] as $position_after) {
						$previous_fields[] = $position_after;
						$previous_fields[] = $position_after . '_uppercase';

						if(in_array($position_after, array('first_name', 'last_name'))) {
							$previous_fields[] = 'name';
							$previous_fields[] = 'name_uppercase';
						}
					}

					$format_search = '/\{(' . implode('|', $previous_fields). ')\}/is';
					$format_replace = '{$1}' . $field['formatted_address_delimiter'] . '{' .  $field['name'] . '}' . "\n";
				} elseif($field['position'] === 'first') {
					$format_search = '/^(.*)$/is';
					$format_replace = "\n" . '{' . $field['name'] . '}' . $field['formatted_address_delimiter'] . '$1';
				}

				$format = preg_replace(
					apply_filters(
						'F4/WCSCF/formatted_address_search_regex',
						$format_search,
						$field['name'],
						$field['target'],
						$field,
						$country
					),
					apply_filters(
						'F4/WCSCF/formatted_address_replace_regex',
						$format_replace,
						$field['name'],
						$field['target'],
						$field,
						$country
					),
					$format,
					1
				);
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
		$registered_fields = Helpers::get_registered_fields(array(
			'show_in_formatted_address' => true
		));

		foreach($registered_fields as $field) {
			if(isset($args[$field['name']])) {
				$replace['{' . $field['name'] . '}'] = $args[$field['name']];
				$replace['{' . $field['name'] . '_upper}'] = strtoupper($args[$field['name']]);
			} else {
				$replace['{' . $field['name'] . '_upper}'] = $replace['{' . $field['name'] . '}'] = '';
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
		$registered_variations = Helpers::get_registered_fields_variations(array(
			'target' => array('billing', 'shipping'),
			'show_in_admin_user_form' => true
		));

		foreach($registered_variations as $field_slug => $field) {
			$field_config = apply_filters(
				'F4/WCSCF/user_field_args',
				wp_parse_args(
					$field['user_field_config'],
					array(
						'label' => $field['label'],
						'description' => '',
						'type' => $field['type'],
						'options' => $field['options'],
						'default' => $field['default'],
					)
				),
				$field['name'],
				$field['target'],
				$field
			);

			if(isset($field['position']['before'])) {
				$next_fields = array();

				foreach($field['position']['before'] as $position_before) {
					$next_fields[] = $field['target'] . '_' . $position_before;
				}

				$fields[$field['target']]['fields'] = \F4\WCSCF\Core\Helpers::insert_before_key(
					$fields[$field['target']]['fields'],
					$next_fields,
					array(
						$field_slug => $field_config
					)
				);
			} elseif(isset($field['position']['after'])) {
				$previous_fields = array();

				foreach($field['position']['after'] as $position_after) {
					$previous_fields[] = $field['target'] . '_' . $position_after;
				}

				$fields[$field['target']]['fields'] = \F4\WCSCF\Core\Helpers::insert_after_key(
					$fields[$field['target']]['fields'],
					$previous_fields,
					array(
						$field_slug => $field_config
					)
				);
			} elseif($field['position'] === 'first') {
				if($field['target'] === 'shipping') {
					$fields[$field['target']]['fields'] = \F4\WCSCF\Core\Helpers::insert_after_key(
						$fields[$field['target']]['fields'],
						array(
							'copy_billing'
						),
						array(
							$field_slug => $field_config
						)
					);
				} else {
					$new_field = array($field_slug => $field_config);
					$fields[$field['target']]['fields'] = $new_field + $fields[$field['target']]['fields'];
				}
			} else {
				$fields[$field['target']]['fields'][$field_slug] = $field_config;
			}
		}

		return $fields;
	}

	/**
	 * Add fields to backend order addresses
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function add_admin_order_fields($fields) {
		$registered_variations = Helpers::get_registered_fields_variations(array(
			'target' => doing_filter('woocommerce_admin_billing_fields') ? 'billing' : 'shipping',
			'show_in_admin_order_form' => true
		));

		foreach($registered_variations as $field_slug => $field) {
			$field_config = apply_filters(
				'F4/WCSCF/order_field_args',
				wp_parse_args(
					$field['order_field_config'],
					array(
						'label' => $field['label'],
						'type' => $field['type'],
						'wrapper_class' => 'form-field-wide',
						'show' => $field['show_after_formatted_admin_order_address'],
						'options' => $field['options'],
						'default' => $field['default']
					)
				),
				$field['name'],
				$field['target'],
				$field
			);

			if(isset($field['position']['before'])) {
				$fields = \F4\WCSCF\Core\Helpers::insert_before_key(
					$fields,
					$field['position']['before'],
					array(
						$field['name'] => $field_config
					)
				);
			} elseif(isset($field['position']['after'])) {
				$fields = \F4\WCSCF\Core\Helpers::insert_after_key(
					$fields,
					$field['position']['after'],
					array(
						$field['name'] => $field_config
					)
				);
			} elseif($field['position'] === 'first') {
				$fields = array($field['name'] => $field_config) + $fields;
			} else {
				$fields[$field['name']] = $field_config;
			}
		}

		return $fields;
	}

	/**
	 * Add fields to the privacy customer data props
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function privacy_customer_personal_data_props($props, $customer) {
		$registered_variations = Helpers::get_registered_fields_variations(array(
			'target' => array('billing', 'shipping'),
			'show_in_privacy_customer_data' => true
		));

		foreach($registered_variations as $field_slug => $field) {
			$prop_label = $field['label'];

			if($field['target'] === 'billing') {
				$prop_label = __('Billing', 'woocommerce') . ' ' . $prop_label;
			} else {
				$prop_label = __('Shipping', 'woocommerce') . ' ' . $prop_label;
			}

			$prop_label = apply_filters(
				'F4/WCSCF/privacy_customer_prop',
				$prop_label,
				$field['name'],
				$field['target'],
				$field
			);

			if(isset($field['position']['before'])) {
				$next_fields = array();

				foreach($field['position']['before'] as $position_before) {
					$next_fields[] = $field['target'] . '_' . $position_before;
				}

				$props = \F4\WCSCF\Core\Helpers::insert_before_key(
					$props,
					$next_fields,
					array(
						$field_slug => $prop_label
					)
				);
			} elseif(isset($field['position']['after'])) {
				$previous_fields = array();

				foreach($field['position']['after'] as $position_after) {
					$previous_fields[] = $field['target'] . '_' . $position_after;
				}

				$props = \F4\WCSCF\Core\Helpers::insert_after_key(
					$props,
					$previous_fields,
					array(
						$field_slug => $prop_label
					)
				);
			} elseif($field['position'] === 'first') {
				$props = \F4\WCSCF\Core\Helpers::insert_before_key(
					$props,
					array(
						$field['target'] . '_(.*)'
					),
					array(
						$field_slug => $prop_label
					)
				);
			} else {
				$props = \F4\WCSCF\Core\Helpers::insert_after_key(
					$props,
					$field['target'] . '_(.*)',
					array(
						$field_slug => $prop_label
					),
					false
				);
			}
		}

		return $props;
	}

	/**
	 * Get privacy customer data props values
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function privacy_export_customer_personal_data_prop_value($value, $prop, $customer) {
		$registered_variations = Helpers::get_registered_fields_variations(array(
			'target' => array('billing', 'shipping'),
			'show_in_privacy_customer_data' => true
		));

		foreach($registered_variations as $field_slug => $field) {
			if($prop === $field_slug) {
				$value = Helpers::maybe_get_registered_field_option_label(
					$field,
					$customer->get_meta($field_slug)
				);
			}
		}

		return $value;
	}

	/**
	 * Remove privacy customer data props values
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function privacy_erase_customer_personal_data_prop($erased, $prop, $customer) {
		$registered_variations = Helpers::get_registered_fields_variations(array(
			'target' => array('billing', 'shipping')
		));

		foreach($registered_variations as $field_slug => $field) {
			if($prop === $field_slug) {
				$customer->delete_meta_data($field_slug);
			}
		}

		return $erased;
	}

	/**
	 * Add fields to the privacy order data props
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function privacy_order_personal_data_props($props, $order) {
		$registered_variations = Helpers::get_registered_fields_variations(array(
			'target' => array('billing', 'shipping', 'order'),
			'show_in_privacy_order_data' => true
		));

		foreach($registered_variations as $field_slug => $field) {
			$prop_label = $field['label'];

			if($field['target'] === 'billing') {
				$prop_label = __('Billing', 'woocommerce') . ' ' . $prop_label;
			} elseif($field['target'] === 'shipping') {
				$prop_label = __('Shipping', 'woocommerce') . ' ' . $prop_label;
			} elseif($field['target'] === 'order') {
				$prop_label = __('Order', 'woocommerce') . ' ' . $prop_label;
			}

			$prop_label = apply_filters(
				'F4/WCSCF/privacy_order_prop',
				$prop_label,
				$field['name'],
				$field['target'],
				$field
			);

			$props[$field_slug] = $prop_label;
		}

		return $props;
	}

	/**
	 * Get privacy order data props values
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function privacy_export_order_personal_data_prop($value, $prop, $order) {
		$registered_variations = Helpers::get_registered_fields_variations(array(
			'target' => array('billing', 'shipping', 'order'),
			'show_in_privacy_order_data' => true
		));

		foreach($registered_variations as $field_slug => $field) {
			if($prop === $field_slug) {
				$value = Helpers::maybe_get_registered_field_option_label(
					$field,
					get_post_meta($order->get_id(), '_' . $field_slug, true)
				);
			}
		}

		return $value;
	}

	/**
	 * Remove privacy order data props values
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function privacy_remove_order_personal_data($order) {
		$registered_variations = Helpers::get_registered_fields_variations(array(
			'target' => array('billing', 'shipping', 'order')
		));

		foreach($registered_variations as $field_slug => $field) {
			if($prop === $field_slug) {
				delete_post_meta($order->get_id(), '_' . $field_slug);
			}
		}
	}
}

?>
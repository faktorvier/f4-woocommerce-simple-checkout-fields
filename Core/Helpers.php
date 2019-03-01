<?php

namespace F4\WCSCF\Core;

/**
 * Core Helpers
 *
 * Helpers for the Core module
 *
 * @since 1.0.0
 * @package F4\WCSCF\Core
 */
class Helpers {
	private static $fields = array();

	/**
	 * Get plugin infos
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @param string $info_name The info name to show
	 * @return string The requested plugin info
	 */
	public static function get_plugin_info($info_name) {
		if(!function_exists('get_plugins')) {
			require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		}

		$info_value = null;
		$plugin_infos = get_plugin_data(F4_WCSCF_PLUGIN_FILE_PATH);

		if(isset($plugin_infos[$info_name])) {
			$info_value = $plugin_infos[$info_name];
		}

		return $info_value;
	}

	/**
	 * Checks if any/all of the values are in an array
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @param array $needle An array with values to search
	 * @param array $haystack The array
	 * @param bool $must_contain_all TRUE if all needes must be found in the haystack, FALSE if only one is needed
	 * @return bool Returns TRUE if one of the needles is found in the array, FALSE otherwise.
	 */
	public static function array_in_array($needle, $haystack, $must_contain_all = false) {
		if($must_contain_all) {
			return !array_diff($needle, $haystack);
		} else {
			return (count(array_intersect($haystack, $needle))) ? true : false;
		}
	}

	/**
	 * Insert one or more elements before a specific key
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @param array $array The original array
	 * @param string|array $search_key One or more keys to insert the values before
	 * @param array $target_values The associative array to insert
	 * @return array The new array
	 */
	public static function insert_before_key($array, $search_key, $target_values) {
		$array_new = array();
		$already_inserted = false;

		if(!is_array($search_key)) {
			$search_key = array($search_key);
		}

		foreach($array as $array_key => $array_value) {
			$is_match = false;

			foreach($search_key as $key) {
				if(preg_match('/' . $key . '/i', $array_key)) {
					$is_match = $array_key ;
					break;
				}
			}

			if($is_match && !$already_inserted) {
				foreach($target_values as $target_key => $target_value) {
					$array_new[$target_key] = $target_value;
				}

				$already_inserted = true;
			}

			$array_new[$array_key] = $array_value;
		}

		return $array_new;
	}

	/**
	 * Insert one or more elements after a specific key
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @param array $array The original array
	 * @param string|array $search_key One or more keys to insert the values after
	 * @param array $target_values The associative array to insert
	 * @return array The new array
	 */
	public static function insert_after_key($array, $search_key, $target_values, $after_first = true) {
		$array_new = array();
		$already_inserted = false;

		if(!is_array($search_key)) {
			$search_key = array($search_key);
		}

		if(!$after_first) {
			$array = array_reverse($array);
		}

		foreach($array as $array_key => $array_value) {
			if($after_first) {
				$array_new[$array_key] = $array_value;
			}

			$is_match = false;

			foreach($search_key as $key) {
				if(preg_match('/' . $key . '/i', $array_key)) {
					$is_match = $array_key ;
					break;
				}
			}

			if($is_match && !$already_inserted) {
				foreach($target_values as $target_key => $target_value) {
					$array_new[$target_key] = $target_value;
				}

				$already_inserted = true;
			}

			if(!$after_first) {
				$array_new[$array_key] = $array_value;
			}
		}

		if(!$after_first) {
			$array_new = array_reverse($array_new);
		}

		return $array_new;
	}

	/**
	 * Get field priority
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @param array $fields The fields array
	 * @param string|array $search_key One or more keys to get the priority
	 * @return integer The priority
	 */
	public static function get_field_priority($fields, $search_key) {
		$priority = 0;

		if(!is_array($search_key)) {
			$search_key = array($search_key);
		}

		foreach($fields as $name => $field) {
			if(in_array($name, $search_key)) {
				$priority = $field['priority'];
				break;
			}
		}

		return $priority;
	}

	/**
	 * Get highest field priority
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @param array $fields The fields array
	 * @return integer The priority
	 */
	public static function get_highest_field_priority($fields) {
		$priority = 0;

		foreach($fields as $name => $field) {
			if(isset($field['priority']) && $priority < $field['priority']) {
				$priority = $field['priority'];
			}
		}

		return $priority;
	}

	/**
	 * Get priority for registered field
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @param array $field The field
	 * @param array $field_target The field target (billing, shipping, account, order)
	 * @param array $all_fields All other already registered fields
	 * @return integer The priority
	 */
	public static function get_registered_field_priority($field, $field_target, $all_fields) {
		if(isset($field['position']['before'])) {
			$next_fields = array();

			foreach($field['position']['before'] as $position_before) {
				$next_fields[] = $field_target . '_' . $position_before;
			}

			$next_field_priority = self::get_field_priority(
				$all_fields,
				$next_fields
			);

			$priority = $next_field_priority - 1;
		} elseif(isset($field['position']['after'])) {
			$previous_fields = array();

			foreach($field['position']['after'] as $position_after) {
				$previous_fields[] = $field_target . '_' . $position_after;
			}

			$previous_field_priority = self::get_field_priority(
				$all_fields,
				$previous_fields
			);

			$priority = $previous_field_priority + 1;
		} elseif($field['position'] === 'first') {
			$priority = 0;
		} else {
			$priority = self::get_highest_field_priority($all_fields) + 1;
		}

		return $priority;
	}

	/**
	 * Registers a new checkout field
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @param array $config All the config data for the field
	 */
	public static function register_field($config) {
		$config = wp_parse_args($config, array(
			'target' => array('billing', 'shipping'), // billing, shipping, order (later account)
			'name' => '',
			'type' => 'text',
			'label' => '',
			'description' => '',
			'placeholder' => '',
			'required' => false,
			'default' => '',
			'class' => array(), // array('form-row-wide')
			'options' => array(),

			'position' => 'last', // 'first', 'last', array('before' => ''), array('after' => '')

			'formatted_address_delimiter' => "\n",

			'show_after_formatted_admin_order_address' => false,
			'show_formatted_address_label' => false,
			'show_in_address_form' => true,
			'show_in_order_form' => true,
			'show_in_formatted_address' => true,
			'show_in_admin_user_form' => true,
			'show_in_admin_order_form' => true,
			'show_in_privacy_customer_data' => true,
			'show_in_privacy_order_data' => true,

			'order_field_config' => array(), // overwrite front order field params
			'address_field_config' => array(), // overwrite front address field params
			'user_field_config' => array(), // overwrite backend user field params
			'order_field_config' => array(), // overwrite backend order field params
		));

		// Force arrays
		if(!is_array($config['target']) && !empty($config['target'])) {
			$config['target'] = array($config['target']);
		}

		// Set conditional values
		if(isset($config['position']['before']) && !is_array($config['position']['before'])) {
			$config['position']['before'] = array($config['position']['before']);
		}

		if(isset($config['position']['after']) && !is_array($config['position']['after'])) {
			$config['position']['after'] = array($config['position']['after']);
		}

		self::$fields[] = $config;
	}

	/**
	 * Get registered fields
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @return array All registered fields
	 */
	public static function get_registered_fields($filter = array()) {
		$filtered_fields = array();

		// Force arrays
		if(isset($filter['target']) && !is_array($filter['target']) && !empty($filter['target'])) {
			$filter['target'] = array($filter['target']);
		}

		foreach(self::$fields as $field) {
			$is_match = true;

			if(isset($filter['target']) && !empty($filter['target'])) {
				if(!self::array_in_array($filter['target'], $field['target'])) {
					$is_match = false;
				}

				foreach($field['target'] as $target_index => $target) {
					if(!in_array($target, $filter['target'])) {
						unset($field['target'][$target_index]);
					}
				}
			}

			if(isset($filter['show_in_address_form']) && $filter['show_in_address_form'] !== $field['show_in_address_form']) {
				$is_match = false;
			}

			if(isset($filter['show_in_order_form']) && $filter['show_in_order_form'] !== $field['show_in_order_form']) {
				$is_match = false;
			}

			if(isset($filter['show_in_formatted_address']) && $filter['show_in_formatted_address'] !== $field['show_in_formatted_address']) {
				$is_match = false;
			}

			if(isset($filter['show_in_admin_user_form']) && $filter['show_in_admin_user_form'] !== $field['show_in_admin_user_form']) {
				$is_match = false;
			}

			if(isset($filter['show_in_admin_order_form']) && $filter['show_in_admin_order_form'] !== $field['show_in_admin_order_form']) {
				$is_match = false;
			}

			if(isset($filter['show_in_privacy_customer_data']) && $filter['show_in_privacy_customer_data'] !== $field['show_in_privacy_customer_data']) {
				$is_match = false;
			}

			if(isset($filter['show_in_privacy_order_data']) && $filter['show_in_privacy_order_data'] !== $field['show_in_privacy_order_data']) {
				$is_match = false;
			}

			if($is_match) {
				$filtered_fields[] = $field;
			}
		}

		return $filtered_fields;
	}

	/**
	 * Get registered fields
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @return array All registered fields
	 */
	public static function get_registered_fields_variations($filter = array(), $use_prefix = true) {
		$variations = array();
		$registered_fields = self::get_registered_fields($filter);

		foreach($registered_fields as $registered_field) {
			foreach($registered_field['target'] as $field_target) {
				$field_name = $use_prefix ? $field_target . '_' . $registered_field['name']: $registered_field['name'];
				$registered_field['target'] = $field_target;
				$variations[$field_name] = $registered_field;
			}
		}

		return $variations;
	}

	/**
	 * Get option label for a field by value, it type = select or radio
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @return array All registered fields
	 */
	public static function maybe_get_registered_field_option_label($field, $field_value) {
		$value = '';

		if(in_array($field['type'], array('select', 'radio')) && isset($field['options'])) {
			if(isset($field['options'][$field_value])) {
				$value = $field['options'][$field_value];
			}
		} else {
			$value = $field_value;
		}

		return $value;
	}
}

?>

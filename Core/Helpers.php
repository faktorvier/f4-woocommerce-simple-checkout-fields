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
			if(in_array($array_key, $search_key) && !$already_inserted) {
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
	public static function insert_after_key($array, $search_key, $target_values) {
		$array_new = array();
		$already_inserted = false;

		if(!is_array($search_key)) {
			$search_key = array($search_key);
		}

		foreach($array as $array_key => $array_value) {
			$array_new[$array_key] = $array_value;

			if(in_array($array_key, $search_key) && !$already_inserted) {
				foreach($target_values as $target_key => $target_value) {
					$array_new[$target_key] = $target_value;
				}

				$already_inserted = true;
			}
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
	 * @param array $registered_field The field
	 * @param array $field_target The field target (billing, shipping, account, order)
	 * @param array $all_fields All other already registered fields
	 * @return integer The priority
	 */
	public static function get_registered_field_priority($registered_field, $field_target, $all_fields) {
		switch($registered_field['position']) {
			case 'last':
				$priority = self::get_highest_field_priority($all_fields) + 1;
				break;

			case 'before':
				$next_fields = array();

				foreach($registered_field['position_before'] as $position_before) {
					$next_fields[] = $field_target . '_' . $position_before;
				}

				$next_field_priority = self::get_field_priority(
					$all_fields,
					$next_fields
				);

				$priority = $next_field_priority - 1;
				break;

			case 'after':
				$previous_fields = array();

				foreach($registered_field['position_after'] as $position_after) {
					$previous_fields[] = $field_target . '_' . $position_after;
				}

				$previous_field_priority = self::get_field_priority(
					$all_fields,
					$previous_fields
				);

				$priority = $previous_field_priority + 1;
				break;

			default:
				$priority = 0;
				break;
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
			'target' => array(), // billing, shipping, order (later account)
			'name' => '',
			'type' => 'text',
			'label' => '',
			'description' => '',
			'placeholder' => '',
			'required' => false,
			'default' => '',
			'class' => array(), // array('form-row-wide')
			'options' => array(),

			'position' => 'last', // first, last, before, after
			'position_before' => array(),
			'position_after' => array(),

			'show_in_formatted_address' => true,

			'checkout_field_config' => array(), // overwrite front checkout field params
			'address_field_config' => array(), // overwrite front address field params
			'user_field_config' => array(), // overwrite backend user field params
		));

		// Force arrays
		if(!is_array($config['target']) && !empty($config['target'])) {
			$config['target'] = array($config['target']);
		}

		if(!is_array($config['position_before']) && !empty($config['position_before'])) {
			$config['position_before'] = array($config['position_before']);
		}

		if(!is_array($config['position_after']) && !empty($config['position_after'])) {
			$config['position_after'] = array($config['position_after']);
		}

		// Set conditional values
		if(!empty($config['position_before'])) {
			$config['position'] = 'before';
		}

		if(!empty($config['position_after'])) {
			$config['position'] = 'after';
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
	public static function get_registered_fields() {
		return self::$fields;
	}
}

?>

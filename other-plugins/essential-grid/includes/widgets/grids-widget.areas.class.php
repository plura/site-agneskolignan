<?php
/**
 * @package   Essential_Grid
 * @author    ThemePunch <info@themepunch.com>
 * @link      http://www.themepunch.com/essential/
 * @copyright 2023 ThemePunch
 */

if (!defined('ABSPATH')) exit();

/**
 * Handles the Essential based Widget Areas
 * @since 1.0.6
 */
class Essential_Grid_Widget_Areas
{
	/**
	 * Return all custom Widget Areas created by Essential Grid
	 * @since 1.0.6
	 */
	public function get_all_sidebars()
	{
		return get_option('esg-widget-areas', false);
	}

	/**
	 * Add new Widget Area
	 * @since 1.0.6
	 */
	public function add_new_sidebar($new_area)
	{
		if (!isset($new_area['handle']) || strlen($new_area['handle']) < 3) return esc_attr__('Wrong Handle received', ESG_TEXTDOMAIN);
		if (!isset($new_area['name']) || strlen($new_area['name']) < 3) return esc_attr__('Wrong Name received', ESG_TEXTDOMAIN);

		$sidebars = $this->get_all_sidebars();
		if ($sidebars !== false) {
			foreach ($sidebars as $handle => $name) {
				if ($handle == $new_area['handle']) return esc_attr__('Widget Area with handle already exist, choose a different handle', ESG_TEXTDOMAIN);
			}
		}
		$sidebars[$new_area['handle']] = $new_area['name'];
		update_option('esg-widget-areas', $sidebars);

		return true;
	}

	/**
	 * change Widget Area by handle
	 * @since 1.0.6
	 */
	public function edit_widget_area_by_handle($edit_widget)
	{
		if (!isset($edit_widget['handle']) || strlen($edit_widget['handle']) < 3) return esc_attr__('Wrong Handle received', ESG_TEXTDOMAIN);
		if (!isset($edit_widget['name']) || strlen($edit_widget['name']) < 3) return esc_attr__('Wrong Name received', ESG_TEXTDOMAIN);

		$sidebars = $this->get_all_sidebars();
		if (!$sidebars || !is_array($sidebars)) return esc_attr__('No Ess. Grid Widget Areas exist', ESG_TEXTDOMAIN);
		foreach ($sidebars as $handle => $name) {
			if ($handle == $edit_widget['handle']) {
				$sidebars[$handle] = $edit_widget['name'];
				update_option('esg-widget-areas', $sidebars);
				return true;
			}
		}

		return false;
	}

	/**
	 * Remove Widget Area
	 * @since 1.0.6
	 */
	public function remove_widget_area_by_handle($del_handle)
	{
		$sidebars = $this->get_all_sidebars();
		foreach ($sidebars as $handle => $name) {
			if ($handle == $del_handle) {
				unset($sidebars[$handle]);
				update_option('esg-widget-areas', $sidebars);
				return true;
			}
		}

		return esc_attr__('Widget Area not found! Wrong handle given.', ESG_TEXTDOMAIN);
	}

	/**
	 * Retrieve all registered Widget Areas from WordPress
	 * @since 1.0.6
	 */
	public function get_all_registered_sidebars()
	{
		global $wp_registered_sidebars;

		return !empty($wp_registered_sidebars);
	}
}

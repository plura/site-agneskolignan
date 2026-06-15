<?php
/**
 * @package   Essential_Grid
 * @author    ThemePunch <info@themepunch.com>
 * @link      http://www.themepunch.com/essential/
 * @copyright 2023 ThemePunch
 */

if (!defined('ABSPATH')) exit();

/**
 * Essential_Grid_Meta_Linking
 * @since: 1.5.0
 **/
class Essential_Grid_Meta_Linking
{

	/**
	 * @param array $meta
	 * @return string
	 */
	protected function _check_link_meta($meta)
	{
		if (!isset($meta['handle']) || strlen($meta['handle']) < 3) return esc_attr__('Wrong Meta Link Handle received', ESG_TEXTDOMAIN);
		if (!isset($meta['name']) || strlen($meta['name']) < 3) return esc_attr__('Wrong Meta Link Name received', ESG_TEXTDOMAIN);
		if (!isset($meta['original']) || strlen($meta['original']) < 3) return esc_attr__('Wrong Meta Link Linking received', ESG_TEXTDOMAIN);
		
		return '';
	}
	
	/**
	 * Add a new Meta
	 */
	public function add_new_link_meta($new_meta)
	{
		$check = $this->_check_link_meta($new_meta);
		if (!empty($check)) return $check;
		
		if (!isset($new_meta['sort-type'])) $new_meta['sort-type'] = 'alphabetic';

		$metas = $this->get_all_link_meta();
		foreach ($metas as $meta) {
			if ($meta['handle'] == $new_meta['handle']) return esc_attr__('Meta Link Handle already exist, choose a different handle', ESG_TEXTDOMAIN);
		}

		$new = array('handle' => $new_meta['handle'], 'name' => $new_meta['name'], 'sort-type' => $new_meta['sort-type'], 'original' => $new_meta['original']);
		$metas[] = $new;

		update_option('esg-custom-link-meta', apply_filters('essgrid_add_new_link_meta', $metas, $new_meta, $new));

		return true;
	}

	/**
	 * change meta by handle
	 */
	public function edit_link_meta_by_handle($edit_meta)
	{
		$check = $this->_check_link_meta($edit_meta);
		if (!empty($check)) return $check;

		$metas = $this->get_all_link_meta();
		foreach ($metas as $key => $meta) {
			if ($meta['handle'] == $edit_meta['handle']) {
				$before = $meta;
				$metas[$key]['name'] = $edit_meta['name'];
				$metas[$key]['original'] = @$edit_meta['original'];
				update_option('esg-custom-link-meta', apply_filters('essgrid_edit_link_meta_by_handle', $metas, $edit_meta, $before));
				return true;
			}
		}

		return false;
	}

	/**
	 * Remove Meta
	 */
	public function remove_link_meta_by_handle($handle)
	{
		$metas = $this->get_all_link_meta();
		foreach ($metas as $key => $meta) {
			if ($meta['handle'] == $handle) {
				$before = $meta;
				unset($metas[$key]);
				update_option('esg-custom-link-meta', apply_filters('essgrid_edit_link_meta_by_handle', $metas, $handle, $before));
				return true;
			}
		}

		return esc_attr__('Meta not found! Wrong handle given.', ESG_TEXTDOMAIN);
	}

	/**
	 * get all custom metas
	 */
	public function get_all_link_meta()
	{
		$meta = get_option('esg-custom-link-meta', array());
		return apply_filters('essgrid_get_all_link_meta', $meta);
	}

	/**
	 * get all handle of custom metas
	 */
	public function get_all_link_meta_handle()
	{
		$metas = array();
		$meta = get_option('esg-custom-link-meta', array());
		if (!empty($meta)) {
			foreach ($meta as $m) {
				$metas[] = 'egl-' . $m['handle'];
			}
		}

		return apply_filters('essgrid_get_all_link_meta_handle', $metas);
	}

	/**
	 * translate by handle to original handle and get the value
	 */
	public function get_link_meta_value_by_handle($post_id, $handle)
	{
		if (trim($handle) === '' || intval($post_id) === 0) return '';

		$orig = false;
		$metas = $this->get_all_link_meta();
		if (!empty($metas)) {
			foreach ($metas as $m) {
				if ($handle == 'egl-' . $m['handle']) {
					$orig = $m['original'];
					break;
				}
			}
		}

		if ($orig === false) return '';

		$metas = get_post_meta($post_id, $orig, true);
		if (is_array($metas))
			$text = @$metas[$orig];
		else
			$text = $metas;

		return apply_filters('essgrid_get_link_meta_value_by_handle', $text, $post_id, $handle);
	}

	/**
	 * save all link metas at once
	 * @since: 3.0.0
	 */
	public function save_all_link_metas($metas)
	{
		if (!empty($metas)) {
			foreach ($metas as $meta) {
				if (!isset($meta['handle']) || strlen($meta['handle']) < 3) return esc_attr__('Wrong Meta Link Handle received', ESG_TEXTDOMAIN);
				if (preg_replace('/[^a-zA-Z0-9\-_]/', '', $meta['handle']) != $meta['handle']) return esc_attr__('Meta Link Handle "' . $meta['handle'] . '" contain forbidden characters!', ESG_TEXTDOMAIN);
				if (!isset($meta['name']) || strlen($meta['name']) < 3) return esc_attr__('Wrong Meta Link Name received', ESG_TEXTDOMAIN);
				if (!isset($meta['original']) || strlen($meta['original']) < 3) return esc_attr__('Wrong Meta Link Linking received', ESG_TEXTDOMAIN);
			}
		}

		update_option('esg-custom-link-meta', apply_filters('essgrid_add_all_link_meta', $metas));

		return true;
	}

}

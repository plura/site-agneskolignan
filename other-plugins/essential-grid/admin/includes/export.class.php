<?php
/**
 * @package   Essential_Grid
 * @author    ThemePunch <info@themepunch.com>
 * @link      http://www.themepunch.com/essential/
 * @copyright 2023 ThemePunch
 */

if (!defined('ABSPATH')) exit();

class Essential_Grid_Export
{

	public function export_grids($export_grids)
	{
		$export_grids = apply_filters('essgrid_export_grids_pre', $export_grids);

		$return_grids = array();
		if (!empty($export_grids)) {
			$c_grid = new Essential_Grid();
			$base = new Essential_Grid_Base();
			$item_skin = new Essential_Grid_Item_Skin();

			$grids = $c_grid->get_essential_grids();
			if (!empty($grids)) {
				foreach ($export_grids as $e_grid_id) {
					foreach ($grids as $grid) {
						$grid = (array)$grid;
						if ($e_grid_id == $grid['id']) {
							if (!Essential_Grid_Base::isValid()) {
								$params = json_decode($grid['params'], true);
								$pg = $base->getVar($params, 'pg', 'false');
								if ($pg != 'false') continue;
							}
							
							//change categories/tags id to slug
							$check = json_decode($grid['postparams'], true);
							if (!empty($check['post_category'])) {
								$slug_cats = array();
								$the_cats = explode(',', $check['post_category']);
								foreach ($the_cats as $cat) {
									$raw = explode('_', $cat);
									$catSlug = $raw[count($raw) - 1];
									unset($raw[count($raw) - 1]);
									$cat = implode('_', $raw);

									$category = $base->get_categories_by_ids((array)$catSlug, $cat);
									foreach ($category as $cat_obj) {
										$slug_cats[] = $cat . '_' . $cat_obj->slug;
									}
								}
								$check['post_category'] = implode(',', $slug_cats);
								$grid['postparams'] = json_encode($check);
							}

							//change choosen skinid to skinhandle
							$check = json_decode($grid['params'], true);
							if (!empty($check['entry-skin']) && intval($check['entry-skin']) != 0) {
								$skin = $item_skin->get_handle_by_id($check['entry-skin']);
								if (!empty($skin)) {
									$check['entry-skin'] = $skin['handle'];
								}
								$grid['params'] = json_encode($check);
							}

							$return_grids[] = $grid;
							break;
						}
					}
				}
			}
		}

		return apply_filters('essgrid_export_grids_post', $return_grids);
	}

	public function export_skins($export_skins)
	{
		$export_skins = apply_filters('essgrid_export_skins_pre', $export_skins);

		$return_skins = array();
		if (!empty($export_skins)) {
			$item_skin = new Essential_Grid_Item_Skin();
			$skins = $item_skin->get_essential_item_skins('all', false); //false = do not decode params

			if (!empty($skins)) {
				foreach ($export_skins as $e_skin_id) {
					foreach ($skins as $skin) {
						if ($e_skin_id == $skin['id']) {
							$return_skins[] = $skin;
							break;
						}
					}
				}
			}
		}

		return apply_filters('essgrid_export_skins_post', $return_skins);
	}

	public function export_elements($export_elements)
	{
		$export_elements = apply_filters('essgrid_export_elements_pre', $export_elements);

		$return_elements = array();
		if (!empty($export_elements)) {
			$c_elements = new Essential_Grid_Item_Element();
			$elements = $c_elements->get_essential_item_elements();

			if (!empty($elements)) {
				foreach ($export_elements as $e_ele_id) {
					foreach ($elements as $element) {
						if ($e_ele_id == $element['id']) {
							$return_elements[] = $element;
							break;
						}
					}
				}
			}
		}

		return apply_filters('essgrid_export_elements_post', $return_elements);
	}

	public function export_navigation_skins($export_navigation_skins)
	{
		$export_navigation_skins = apply_filters('essgrid_export_navigation_skins_pre', $export_navigation_skins);

		$return_nav_skins = array();
		if (!empty($export_navigation_skins)) {
			$c_skins = new Essential_Grid_Navigation();
			$skins = $c_skins->get_essential_navigation_skins();

			if (!empty($skins)) {
				foreach ($export_navigation_skins as $e_skin_id) {
					foreach ($skins as $skin) {
						if ($e_skin_id == $skin['id']) {
							$return_nav_skins[] = $skin;
							break;
						}
					}
				}
			}
		}

		return apply_filters('essgrid_export_navigation_skins_post', $return_nav_skins);
	}

	public function export_custom_meta($export_custom_meta)
	{
		$export_custom_meta = apply_filters('essgrid_export_custom_meta_pre', $export_custom_meta);

		$return_metas = array();
		if (!empty($export_custom_meta)) {
			$metas = new Essential_Grid_Meta();
			$custom_metas = $metas->get_all_meta();
			if (!empty($custom_metas)) {
				foreach ($custom_metas as $c_meta) {
					foreach ($export_custom_meta as $meta) {
						if ($c_meta['handle'] == $meta) {
							$return_metas[] = $c_meta;
							break;
						}
					}
				}
			}
		}

		return apply_filters('essgrid_export_custom_meta_prost', $return_metas);
	}

	public function export_global_styles($export_global_styles)
	{
		$export_global_styles = apply_filters('essgrid_export_global_styles_pre', $export_global_styles);
		$global_styles = '';
		if ($export_global_styles == 'on') {
			$c_css = new Essential_Grid_Global_Css();
			$global_styles = $c_css->get_global_css_styles();
		}

		return apply_filters('essgrid_export_global_styles_post', $global_styles);
	}

	/**
	 * return all resources connected to grid ( skin, nav-skin, custom meta, global css )
	 * 
	 * @param Essential_Grid_Import $import
	 * @param array $data  grid / skins / nav skins / global css
	 * @return array
	 */
	function getGridResources($import, $data)
	{
		if (is_array($data['grid'])) $data['grid'] = (object)$data['grid'];
		
		$resources = array();
		$css_class_prefix = '';
		$params = json_decode($data['grid']->params, true);

		// get all keys to process and remove ones we do process manually
		$import_keys = $import->get_import_keys();
		$import_keys_flipped = array_flip($import_keys);
		unset(
			$import_keys_flipped['grids'],
			$import_keys_flipped['skins'],
			$import_keys_flipped['navigation-skins'],
			$import_keys_flipped['elements'],
			$import_keys_flipped['custom-meta'],
			$import_keys_flipped['global-css']
		);
		$import_keys = array_flip($import_keys_flipped);

		// grid skin stored in params -> entry-skin as ID
		$resources['skins'] = array($params['entry-skin']);

		// nav skin stored in params -> navigation-skin as handle
		foreach ($data['navigation_skins'] as $ns) {
			if ($params['navigation-skin'] == $ns['handle']) {
				$resources['navigation-skins'] = array($ns['id']);
				break;
			}
		}

		// custom meta
		$resources['custom-meta'] = array();

		foreach ($data['skins'] as $s) {
			if ($s['id'] != $params['entry-skin']) continue;

			// skin item link
			if (!empty($s['params']['link-link-type']) 
				&& $s['params']['link-link-type'] == 'meta'
				&& !empty($s['params']['link-meta-link'])
			) {
				$resources['custom-meta'][] = $s['params']['link-meta-link'];
			}

			foreach ($s['layers'] as $l) {
				// layer link
				if (!empty($l['settings']['link-type']) && $l['settings']['link-type'] == 'meta') {
					$resources['custom-meta'][] = $l['settings']['link-type-meta'];
				}

				// layer source
				if (!empty($l['settings']['source']) && $l['settings']['source'] == 'post' && $l['settings']['source-post'] == 'meta') {
					$resources['custom-meta'][] = $l['settings']['source-meta'];
				}

				// layer source = text - meta could be in text
				if (!empty($l['settings']['source']) && $l['settings']['source'] == 'text') {
					$matches = array();
					preg_match_all('/\%(egl?[^\%]+)\%/', $l['settings']['source-text'], $matches);
					if (!empty($matches[1]))
						$resources['custom-meta'] = array_merge($resources['custom-meta'], $matches[1]);
				}
			}
			
			foreach ($resources['custom-meta'] as $k => $v) {
				$resources['custom-meta'][$k] = str_replace(array('eg-', 'egl-'), '', $v);
			}

			$resources['custom-meta'] = array_values(array_unique($resources['custom-meta']));


			// update css class with skin handle to use in global css search
			$css_class_prefix = '.eg-' . $s['handle'] . '-';

			break;
		}

		// global css
		if (!empty($css_class_prefix) && strpos($data['global_css'], $css_class_prefix) !== false) {
			$resources['global-css'] = array('on');
		}

		// process other keys added by addons
		foreach ($import_keys as $k) {
			/**
			 * @param array $resources  grid resources
			 * @param string $k  import key, i.e. punch-fonts
			 * @param array $data  grid / skins / nav skins / global css
			 */
			$resources = apply_filters('essgrid_getGridResources', $resources, $k, $data);
		}

		return $resources;
	}

}

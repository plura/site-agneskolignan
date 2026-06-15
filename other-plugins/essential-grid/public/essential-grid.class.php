<?php
/**
 * Essential Grid.
 *
 * @package  Essential_Grid
 * @author    ThemePunch <info@themepunch.com>
 * @link      http://www.themepunch.com/essential/
 * @copyright 2023 ThemePunch
 */

if (!defined('ABSPATH')) exit();

global $esg_c_sort_direction,
       $esg_c_sort_handle,
       $esg_grid_serial,
       $esg_is_inited,
       $esg_grids_cache;

$esg_c_sort_direction = 'ASC';
$esg_c_sort_handle = 'title';
$esg_grid_serial = 0;
$esg_is_inited = false;
$esg_grids_cache = array();

class Essential_Grid
{
	private $grid_api_name = null;
	private $grid_div_name = null;
	private $grid_id = 0; //set to 0 at beginning for quick grids @since 2.0.2
	private $grid_name = '';
	private $grid_handle = '';
	private $grid_params = array();
	private $grid_postparams = array();
	private $grid_layers = array();
	private $grid_settings = array();
	private $grid_last_mod = '';
	private $grid_inline_js = '';
	private $is_gallery = false;

	public $custom_settings = null;
	public $custom_layers = null;
	public $custom_images = null;
	public $custom_posts = null;
	public $custom_special = null;

	//other changings
	private $filter_by_ids = array();
	private $load_more_post_array = array();

	/**
	 * Instance of this class.
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 */
	public function __construct()
	{
		global $esg_is_inited;

		if (!$esg_is_inited) {
			$esg_is_inited = true;

			// Load plugin text domain
			add_action('init', array($this, 'load_plugin_textdomain'));

			$add_cpt = apply_filters('essgrid_set_cpt', get_option('tp_eg_enable_custom_post_type', 'false'));

			if ($add_cpt == 'true' || $add_cpt === true)
				add_action('init', array($this, 'register_custom_post_type'));

			// Load public-facing style sheet and JavaScript.
			add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
			add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

			add_action('wp_ajax_Essential_Grid_Front_request_ajax', array($this, 'on_front_ajax_action'));
			add_action('wp_ajax_nopriv_Essential_Grid_Front_request_ajax', array($this, 'on_front_ajax_action')); //for not logged-in users

			add_action('essgrid_before_front_ajax_action', array($this, 'before_front_ajax_action'));
			
			// Post Like
			add_action('wp_ajax_nopriv_ess_grid_post_like', array($this, 'ess_grid_post_like'));
			add_action('wp_ajax_ess_grid_post_like', array( $this, 'ess_grid_post_like'));

			//Woo Add to Cart Updater
			add_filter('woocommerce_add_to_cart_fragments', array('Essential_Grid_Woocommerce', 'woocommerce_header_add_to_cart_fragment'));

			// 2.2 lightbox post content
			add_filter('essgrid_lightbox_post_content', array($this, 'on_lightbox_post_content'), 10, 2);
			
			//add support for divi shortcodes in ajax requests
			add_filter('et_builder_load_requests', array($this, 'add_divi_support'), 10, 1);

			//admin bar edit link
			add_action('wp_footer', array($this, 'add_admin_bar'), 100);
			add_action('wp_before_admin_bar_render', array($this, 'add_admin_menu_nodes'));
		}
	}

	/**
	 * @return int
	 */
	public function get_grid_id()
	{
		return $this->grid_id;
	}

	/**
	 * @return string
	 */
	public function get_grid_name()
	{
		return $this->grid_name;
	}

	/**
	 * @return string
	 */
	public function get_grid_handle()
	{
		return $this->grid_handle;
	}

	/**
	 * @return array
	 */
	public function get_grid_params()
	{
		return $this->grid_params;
	}

	/**
	 * @return array
	 */
	public function get_grid_postparams()
	{
		return $this->grid_postparams;
	}

	/**
	 * @return array
	 */
	public function get_grid_layers()
	{
		return $this->grid_layers;
	}
	
	/**
	 * @param array $layers
	 * @return void
	 */
	public function set_grid_layers($layers)
	{
		$this->grid_layers = $layers;
	}

	/**
	 * @return array
	 */
	public function get_grid_settings()
	{
		return $this->grid_settings;
	}

	/**
	 * Return the plugin slug.
	 * @return string  Plugin slug variable.
	 */
	public function get_plugin_slug()
	{
		return ESG_PLUGIN_SLUG;
	}

	/**
	 * Return an instance of this class.
	 * @return object  A single instance of this class.
	 */
	public static function get_instance()
	{
		// If the single instance hasn't been set, set it now.
		if (null == self::$instance) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain()
	{
		$locale = apply_filters('plugin_locale', get_locale(), ESG_TEXTDOMAIN);
		load_textdomain(ESG_TEXTDOMAIN, trailingslashit(WP_LANG_DIR) . ESG_TEXTDOMAIN . '/' . ESG_TEXTDOMAIN . '-' . $locale . '.mo');
		load_plugin_textdomain(ESG_TEXTDOMAIN, FALSE, dirname(dirname(plugin_basename(__FILE__))) . '/languages/');
		do_action('essgrid_load_plugin_textdomain', ESG_TEXTDOMAIN);
	}

	/**
	 * Register and enqueue public-facing style sheet.
	 */
	public function enqueue_styles()
	{
		$use_cache = get_option('tp_eg_use_cache', 'false') == 'true';

		wp_register_style('esg-plugin-settings', ESG_PLUGIN_URL . 'public/assets/css/settings.css', array(), ESG_REVISION);
		wp_enqueue_style('esg-plugin-settings');

		$font = new ThemePunch_Fonts();
		$font->register_icon_fonts("public");

		wp_register_style('esg-tp-boxextcss', ESG_PLUGIN_URL . 'public/assets/css/jquery.esgbox.min.css', array(), ESG_REVISION);

		// Enqueue Lightbox Style/Script
		if ($use_cache) {
			wp_enqueue_style('esg-tp-boxextcss');
		}

		do_action('essgrid_enqueue_styles', $use_cache, ESG_REVISION);
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 */
	public function enqueue_scripts()
	{
		global $esg_dev_mode, $wp_scripts;

		$use_cache = get_option('tp_eg_use_cache', 'false') == 'true';
		$js_to_footer = get_option('tp_eg_js_to_footer', 'false') == 'true';

		wp_enqueue_script('jquery');

		if (get_option('tp_eg_use_lightbox') !== 'disabled') {
			if ($esg_dev_mode) {
				wp_register_script('esg-tp-boxext', ESG_PLUGIN_URL . 'public/assets/js/dev/esgbox.js', array('jquery'), ESG_REVISION, $js_to_footer);
			} else {
				wp_register_script('esg-tp-boxext', ESG_PLUGIN_URL . 'public/assets/js/esgbox.min.js', array('jquery'), ESG_REVISION, $js_to_footer);
			}
		}

		/**
		 * dequeue tp-tools to make sure that always the latest is loaded
		 **/
		if (isset($wp_scripts->registered['tp-tools']->ver)) {
			if (version_compare($wp_scripts->registered['tp-tools']->ver, ESG_TP_TOOLS, '<')) {
				wp_deregister_script('tp-tools');
				wp_dequeue_script('tp-tools');
			}
		}

		wp_register_script('tp-tools', ESG_PLUGIN_URL . 'public/assets/js/rbtools.min.js', array('jquery'), ESG_TP_TOOLS, $js_to_footer);

		if ($esg_dev_mode) {
			wp_register_script('esg-essential-grid-script', ESG_PLUGIN_URL . 'public/assets/js/dev/esg.js', array('jquery', 'tp-tools'), ESG_REVISION, $js_to_footer);
		} else {
			wp_register_script('esg-essential-grid-script', ESG_PLUGIN_URL . 'public/assets/js/esg.min.js', array('jquery', 'tp-tools'), ESG_REVISION, $js_to_footer);
		}

		do_action('essgrid_enqueue_scripts', $use_cache, ESG_REVISION, $js_to_footer);
	}

	/**
	 * Register Shortcode
	 */
	public static function register_shortcode($args, $mid_content = null)
	{
		$args = apply_filters('essgrid_register_shortcode_pre', $args);

		$caching = get_option('tp_eg_use_cache', 'false');
		$use_cache = $caching == 'true';

		// Enqueue Scripts
		wp_enqueue_script('tp-tools');
		wp_enqueue_script('esg-essential-grid-script');
		wp_localize_script('esg-essential-grid-script', 'eg_ajax_var', array(
			'url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('eg-ajax-nonce')
		));

		// Enqueue Lightbox Style/Script
		if ($use_cache) {
			wp_enqueue_script('esg-tp-boxext');
		}

		$grid = new Essential_Grid;
		extract(shortcode_atts(array(
			'alias' => '',
			'settings' => '',
			'layers' => '',
			'images' => '',
			'posts' => '',
			'special' => ''
		), $args, 'ess_grid'));
		$eg_alias = ($alias != '') ? $alias : implode(' ', $args);

		if ($settings !== '')
			$grid->custom_settings = json_decode(str_replace(array('({', '})', "'"), array('[', ']', '"'), $settings), true);
		if ($layers !== '')
			$grid->custom_layers = json_decode(str_replace(array('({', '})', "'"), array('[', ']', '"'), $layers), true);
		if ($images !== '')
			$grid->custom_images = explode(',', $images);
		if ($posts !== '')
			$grid->custom_posts = explode(',', $posts);
		if ($special !== '')
			$grid->custom_special = $special;

		if ($settings !== '' || $layers !== '' || $images !== '' || $posts !== '' || $special !== '') { 
			//disable caching if one of this is set
			$caching = 'false';
		}

		//check for example on gallery shortcode and do stuff
		$grid->check_for_shortcodes($mid_content); 

		if ($grid->is_gallery)
			$caching = 'false';

		if ($eg_alias == '')
			$eg_alias = implode(' ', $args);

		$content = false;
		$grid_id = self::get_id_by_alias($eg_alias);

		if ($grid_id == '0') {
			//grid is created by custom settings. Check if layers and settings are set
			ob_start();
			$grid->output_essential_grid_by_settings();
			$content = ob_get_contents();
			ob_clean();
			ob_end_clean();
		} else {
			//get wpml lang to use in transient
			$lang_code = '';
			if (Essential_Grid_Wpml::is_wpml_exists()) {
				$lang_code = Essential_Grid_Wpml::get_current_lang_code();
			}

			if ($caching == 'true') {
				//check if we use total caching
				$addition = (wp_is_mobile()) ? '_m' : '';
				$addition .= '_' . Essential_Grid_Base::detect_device();
				$addition .= ($addition !== '' && $lang_code !== '') ? '_' : '';
				$content = get_transient('ess_grid_trans_full_grid_' . $grid_id . $addition . $lang_code);
			}

			if (!$content) {
				ob_start();
				$grid->output_essential_grid_by_alias($eg_alias);
				$content = ob_get_contents();
				ob_clean();
				ob_end_clean();

				//do not cache grids with random sort
				$order_by_start = $grid->get_param_by_handle('sorting-order-by-start', 'none');
				if ($caching == 'true' && 'rand' != $order_by_start) {
					set_transient('ess_grid_trans_full_grid_' . $grid_id . $addition . $lang_code, $content, 60 * 60 * 24 * 7);
				}
			}

		}

		$output_protection = get_option('tp_eg_output_protection', 'none');

		//handle output types
		switch ($output_protection) {
			case 'compress':
				$content = str_replace("\n", '', $content);
				$content = str_replace("\r", '', $content);
				return ($content);
				
			case 'echo':
				//bypass the filters
				echo $content; 
				break;
			default: 
				//normal output
				return ($content);
		}
	}

	/**
	 * Register Shortcode For Ajax Content
	 * @since: 1.5.0
	 */
	public static function register_shortcode_ajax_target($args, $mid_content = null)
	{
		$args = apply_filters('essgrid_register_shortcode_ajax_target_pre', $args);
		extract(shortcode_atts(array('alias' => ''), $args, 'ess_grid_ajax_target'));

		//no alias found
		if ($alias == '') return false; 

		$output_protection = get_option('tp_eg_output_protection', 'none');

		$content = '';
		$grid = new Essential_Grid;
		$grid_id = self::get_id_by_alias($alias);
		if ($grid_id > 0) {
			$grid->init_by_id($grid_id);
			//check if shortcode is allowed
			$is_sc_allowed = $grid->get_param_by_handle('ajax-container-position');
			if ($is_sc_allowed != 'shortcode') return false;
			$content = $grid->output_ajax_container();
		}

		//handle output types
		switch ($output_protection) {
			case 'compress':
				$content = str_replace("\n", '', $content);
				$content = str_replace("\r", '', $content);
				return ($content);
				
			case 'echo':
				echo $content; //bypass the filters
				break;
				
			default: //normal output
				return ($content);
				
		}
	}

	/**
	 * Register Shortcode For Filter
	 * @since: 1.5.0
	 */
	public static function register_shortcode_filter($args, $mid_content = null)
	{
		$args = apply_filters('essgrid_register_shortcode_filter_pre', $args);
		extract(shortcode_atts(array('alias' => '', 'id' => ''), $args, 'ess_grid_nav'));

		//no alias found
		if ($alias == '') return false;
		//no id found
		if ($id == '') return false; 

		$base = new Essential_Grid_Base();
		$meta_c = new Essential_Grid_Meta();
		$output_protection = get_option('tp_eg_output_protection', 'none');

		ob_start();

		$grid = new Essential_Grid;
		$grid_id = self::get_id_by_alias($alias);
		if ($grid_id > 0) {
			$navigation_c = new Essential_Grid_Navigation($grid_id);

			$grid->init_by_id($grid_id);

			$layout = $grid->get_param_by_handle('navigation-layout', array());
			$navig_special_class = $grid->get_param_by_handle('navigation-special-class', array()); // has all classes in an ordered list
			$navig_special_skin = $grid->get_param_by_handle('navigation-special-skin', array()); // has all classes in an ordered list

			if ($id == 'sort')
				$id = 'sorting';

			// Check if selected element is in external list and also get the key to use it to get class
			if (isset($layout[$id]['external'])) {
				$special_class = @$navig_special_class[$layout[$id]['external']];
				$special_skin = @$navig_special_skin[$layout[$id]['external']];
			} else { 
				// it's not in external set so break since its only allowed to use each element one time
				return false;
			}

			$navigation_c->set_special_class($special_class);
			$navigation_c->set_special_class($special_skin);
			$navigation_c->set_special_class('esg-fgc-' . $grid_id);

			$nav_special_class = $navigation_c->get_special_class();

			$filter = false;
			switch ($id) {
				case 'sorting':
					$order_by_start = $grid->get_param_by_handle('sorting-order-by-start', 'none');
					$order_type = $grid->get_param_by_handle('sorting-order-type', 'ASC');
					$sort_by_text = $grid->get_param_by_handle('sort-by-text', esc_attr__('Sort By ', ESG_TEXTDOMAIN));
					$order_by = explode(',', $grid->get_param_by_handle('sorting-order-by', 'date'));
					if (!is_array($order_by))
						$order_by = array(
							$order_by
						);
					//set order of filter
					$navigation_c->set_orders_text($sort_by_text);
					$navigation_c->set_orders_start($order_by_start);
					$navigation_c->set_orders_order($order_type);
					$navigation_c->set_orders($order_by);

					/* 2.3.7 */
					echo '<div class="esg-nav-by-shortcode' . $nav_special_class . ' ' . $special_skin . ' esg-filters">';
					echo $navigation_c->output_sorting();
					echo '</div>';
					break;
				case 'cart':
					/* 2.3.7 */
					echo '<div class="esg-nav-by-shortcode' . $nav_special_class . ' ' . $special_skin . ' esg-filters">';
					echo $navigation_c->output_cart();
					echo '</div>';
					break;
				case 'left':
					/* 2.3.7 */
					echo '<div class="esg-nav-by-shortcode' . $nav_special_class . ' ' . $special_skin . ' esg-navbutton-solo-left">';
					echo $navigation_c->output_navigation_left();
					echo '</div>';
					break;
				case 'right':
					/* 2.3.7 */
					echo '<div class="esg-nav-by-shortcode' . $nav_special_class . ' ' . $special_skin . ' esg-navbutton-solo-right">';
					echo $navigation_c->output_navigation_right();
					echo '</div>';
					break;
				case 'pagination':
					/* 2.3.7 */
					echo '<div class="esg-nav-by-shortcode' . $nav_special_class . ' ' . $special_skin . ' esg-filters">';
					echo $navigation_c->output_pagination();
					echo '</div>';
					break;
				case 'search-input':
					$search_text = $grid->get_param_by_handle('search-text', esc_attr__('Search...', ESG_TEXTDOMAIN));
					$navigation_c->set_search_text($search_text);

					/* 2.3.7 */
					echo '<div class="esg-nav-by-shortcode' . $nav_special_class . ' ' . $special_skin . '">';
					echo $navigation_c->output_search_input();
					echo '</div>';
					break;
				case 'filter':
					$id = 1;
					$filter = true;
					break;
				default:
					//check for filter
					if (strpos($id, 'filter-') !== false) {
						$id = intval(str_replace('filter-', '', $id));
						$filter = true;
					} else {
						return false;
					}
					break;
			}

			/*****
			 * Complex Filter Part
			 *****/
			$found_filter = array();

			if ($filter === true) {
				switch ($grid->get_postparam_by_handle('source-type')) {
					case 'custom':
						if (empty($grid->grid_layers) || count($grid->grid_layers) < 1) break;
						
						if ($id == 1) {
							$filter_selected = $grid->get_param_by_handle('filter-selected', array());
							$show_count = $grid->get_param_by_handle('filter-counter', 'off');
							$navigation_c->set_show_count($show_count);
						} else {
							$filter_selected = $grid->get_param_by_handle('filter-selected-' . $id, array());
							$show_count = $grid->get_param_by_handle('filter-counter-' . $id, 'off');
							$navigation_c->set_show_count($show_count, $id);
						}
						if (!is_array($filter_selected)) $filter_selected = (array)$filter_selected;
						if (empty($filter_selected)) break;
						foreach ($filter_selected as $k => $v) {
							$filter_selected[$k] = Essential_Grid_Base::sanitize_utf8_to_unicode($v);
						}

						foreach ($grid->grid_layers as $entry) {
							if (empty($entry['custom-filter'])) continue;

							$filters = array();
							$cats = explode(',', $entry['custom-filter']);
							if (!is_array($cats)) $cats = (array)$cats;

							foreach ($cats as $category) {
								$_v = Essential_Grid_Base::sanitize_utf8_to_unicode($category);
								if (!in_array($_v, $filter_selected)) continue;
								$filters[$_v] = array(
									'name' => $category,
									'slug' => $_v
								);
							}

							// these are the found filters, only show filter that the posts have
							$found_filter = $found_filter + $filters; 
						}
						
						break;
					case 'post':
						$start_sortby = $grid->get_param_by_handle('sorting-order-by-start', 'none');
						$start_sortby_type = $grid->get_param_by_handle('sorting-order-type', 'ASC');
						$post_category = $grid->get_postparam_by_handle('post_category');
						$post_types = $grid->get_postparam_by_handle('post_types');
						$page_ids = explode(',', $grid->get_postparam_by_handle('selected_pages', '-1'));

						$cat_relation = $grid->get_postparam_by_handle('category-relation', 'OR');

						$max_entries = $grid->get_maximum_entries($grid);

						$additional_query = $grid->get_postparam_by_handle('additional-query');
						if ($additional_query !== '')
							$additional_query = wp_parse_args($additional_query);

						$cat_tax = Essential_Grid_Base::getCatAndTaxData($post_category);

						$posts = Essential_Grid_Base::getPostsByCategory($grid_id, $cat_tax['cats'], $post_types, $cat_tax['tax'], $page_ids, $start_sortby, $start_sortby_type, $max_entries, $additional_query, true, $cat_relation);

						$nav_filters = array();

						$taxes = array(
							'post_tag'
						);
						if (!empty($cat_tax['tax']))
							$taxes = explode(',', $cat_tax['tax']);

						if (!empty($cat_tax['cats'])) {
							$cats = explode(',', $cat_tax['cats']);

							foreach ($cats as $cid) {
								if (Essential_Grid_Wpml::is_wpml_exists() && isset($sitepress)) {
									$new_id = icl_object_id($cid, 'category', true, $sitepress->get_default_language());
									$cat = get_category($new_id);
								} else {
									$cat = get_category($cid);
								}
								if (is_object($cat)) {
									$_v = Essential_Grid_Base::sanitize_utf8_to_unicode($cat->slug);
									$nav_filters[$cid] = array(
										'name' => $cat->cat_name,
										'slug' => $_v,
										'parent' => $cat->category_parent
									);
								}

								foreach ($taxes as $custom_tax) {
									$term = get_term_by('id', $cid, $custom_tax);
									if (is_object($term))
										$nav_filters[$cid] = array(
											'name' => $term->name,
											'slug' => Essential_Grid_Base::sanitize_utf8_to_unicode($term->slug),
											'parent' => $term->parent
										);
								}
							}

							if (!empty($filters_meta)) {
								$nav_filters = $filters_meta + $nav_filters;
							}
							asort($nav_filters);
						}

						if ($id == 1) {
							$filterall_visible = $grid->get_param_by_handle('filter-all-visible');
							$all_text = $grid->get_param_by_handle('filter-all-text');
							$listing_type = $grid->get_param_by_handle('filter-listing', 'list');
							$listing_text = $grid->get_param_by_handle('filter-dropdown-text');
							$show_count = $grid->get_param_by_handle('filter-counter', 'off');
							$selected = $grid->get_param_by_handle('filter-selected', array());
							$sort_alpha = $grid->get_param_by_handle('filter-sort-alpha', 'off');
							$sort_alpha_dir = $grid->get_param_by_handle('filter-sort-alpha-dir', 'asc');
						} else {
							$filterall_visible = $grid->get_param_by_handle('filter-all-visible-' . $id);
							$all_text = $grid->get_param_by_handle('filter-all-text-' . $id);
							$listing_type = $grid->get_param_by_handle('filter-listing-' . $id, 'list');
							$listing_text = $grid->get_param_by_handle('filter-dropdown-text-' . $id);
							$show_count = $grid->get_param_by_handle('filter-counter-' . $id, 'off');
							$selected = $grid->get_param_by_handle('filter-selected-' . $id, array());
							$sort_alpha = $grid->get_param_by_handle('filter-sort-alpha-' . $id, 'off');
							$sort_alpha_dir = $grid->get_param_by_handle('filter-sort-alpha-dir-' . $id, 'asc');
						}
						$filter_allow = $grid->get_param_by_handle('filter-arrows', 'single');
						$filter_start = $grid->get_param_by_handle('filter-start');
						$filter_grouping = $grid->get_param_by_handle('filter-grouping', 'false');

						//check the selected and change metas to correct fields
						$filters_arr['filter-grouping'] = $filter_grouping;
						$filters_arr['filter-listing'] = $listing_type;
						$filters_arr['filter-selected'] = $selected;
						$filters_arr['filter-sort-alpha'] = $sort_alpha;
						$filters_arr['filter-sort-alpha-dir'] = $sort_alpha_dir;

						if (!empty($filters_arr['filter-selected'])) {
							if (!empty($posts) && count($posts) > 0) {
								foreach ($filters_arr['filter-selected'] as $fk => $filter) {
									if (strpos($filter, 'meta-') === 0) {
										unset($filters_arr['filter-selected'][$fk]); 

										foreach ($posts as $post) {
											$fil = str_replace('meta-', '', $filter);
											$post_filter_meta = $meta_c->get_meta_value_by_handle($post['ID'], 'eg-' . $fil, false);
											$arr = json_decode($post_filter_meta, true);
											$cur_filter = (is_array($arr)) ? $arr : array(
												$post_filter_meta
											);
											
											$add_filter = array();
											if (!empty($cur_filter)) {
												foreach ($cur_filter as $v) {
													if (trim($v) !== '') {
														$_v = Essential_Grid_Base::sanitize_utf8_to_unicode($v);
														$add_filter[$_v] = array(
															'name' => $v,
															'slug' => $_v,
															'parent' => '0'
														);
														if (!empty($filters_arr['filter-selected'])) {
															$filter_found = false;
															foreach ($filters_arr['filter-selected'] as $fcheck) {
																if ($fcheck == $_v) {
																	$filter_found = true;
																	break;
																}
															}
															if (!$filter_found) {
																$filters_arr['filter-selected'][] = $_v; //add found meta
															}
														} else {
															$filters_arr['filter-selected'][] = $_v; //add found meta
														}
													}
												}
												if (!empty($add_filter)) {
													$navigation_c->set_filter($add_filter);
												}
											}
										}

										$meta_data = $meta_c->get_meta_by_handle($fil);
										if (in_array($meta_data['type'], array('select', 'multi-select'))) {
											//restore original meta values order
											$original_values = explode(',', $meta_data['select']);
											foreach ($original_values as $v) {
												$_v = Essential_Grid_Base::sanitize_utf8_to_unicode($v);
												if (($key = array_search($_v, $filters_arr['filter-selected'])) !== false) {
													unset($filters_arr['filter-selected'][$key]);
													$filters_arr['filter-selected'][] = $_v;
												}
											}
										}

									}
								}
							}
						}

						if ($all_text == '' || $listing_type == '' || $listing_text == '' || empty($filters_arr['filter-selected']))
							return false;

						$navigation_c->set_filter_settings('filter', $filters_arr);
						$navigation_c->set_filter_text($all_text);
						$navigation_c->set_filterall_visible($filterall_visible);
						$navigation_c->set_dropdown_text($listing_text);
						$navigation_c->set_show_count($show_count, $id);
						$navigation_c->set_filter_type($filter_allow);
						$navigation_c->set_filter_start_select($filter_start);

						if (!empty($posts) && count($posts) > 0) {
							foreach ($posts as $post) {

								// check if post should be visible or if its invisible on current grid settings
								$is_visible = $grid->check_if_visible($post['ID'], $grid_id);
								if (!$is_visible)
									continue;

								$filters = array();
								$categories = $base->get_custom_taxonomies_by_post_id($post['ID']);
								$tags = get_the_tags($post['ID']);

								if (!empty($categories)) {
									foreach ($categories as $category) {
										$_v = Essential_Grid_Base::sanitize_utf8_to_unicode($category->slug);
										$filters[$category->term_id] = array(
											'name' => $category->name,
											'slug' => $_v,
											'parent' => $category->parent
										);
									}
								}

								if (!empty($tags)) {
									foreach ($tags as $taxonomie) {
										$_v = Essential_Grid_Base::sanitize_utf8_to_unicode($taxonomie->slug);
										$filters[$taxonomie->term_id] = array(
											'name' => $taxonomie->name,
											'slug' => $_v,
											'parent' => '0'
										);
									}
								}

								$filter_meta_selected = $grid->get_param_by_handle('filter-selected', array());
								if (!empty($filter_meta_selected)) {
									foreach ($filter_meta_selected as $filter) {
										if (strpos($filter, 'meta-') === 0) {
											$fil = str_replace('meta-', '', $filter);
											$post_filter_meta = $meta_c->get_meta_value_by_handle($post['ID'], 'eg-' . $fil, false);
											$arr = json_decode($post_filter_meta, true);
											$cur_filter = (is_array($arr)) ? $arr : array(
												$post_filter_meta
											);
											if (!empty($cur_filter)) {
												foreach ($cur_filter as $v) {
													if (trim($v) !== '') {
														$_v = Essential_Grid_Base::sanitize_utf8_to_unicode($v);
														$filters[$_v] = array(
															'name' => $v,
															'slug' => $_v,
															'parent' => '0'
														);
													}
												}
											}
										}
									}
								}
								$found_filter = $found_filter + $filters; //these are the found filters, only show filter that the posts have
							}
						}

						$remove_filter = array_diff_key($nav_filters, $found_filter); //check if we have filter that no post has (comes through multilanguage)
						if (!empty($remove_filter)) {
							foreach ($remove_filter as $key => $rem) { //we have, so remove them from the filter list before setting the filter list
								unset($found_filter[$key]);
							}
						}
						break;
				}

				$navigation_c->set_filter($found_filter); //set filters $nav_filters $found_filter

				/* 2.3.7 */
				echo '<div class="esg-nav-by-shortcode' . $nav_special_class . ' ' . $special_skin . ' esg-filters">';
				echo $navigation_c->output_filter_unwrapped();
				echo '</div>';

			}

		}

		$content = ob_get_contents();
		ob_clean();
		ob_end_clean();

		//handle output types
		switch ($output_protection) {
			case 'compress':
				$content = str_replace("\n", '', $content);
				$content = str_replace("\r", '', $content);
				return ($content);
				
			case 'echo':
				echo $content; //bypass the filters
				break;
				
			default: //normal output
				return ($content);
				
		}
	}

	/**
	 * We check the content for gallery shortcode.
	 * If existing, create Grid based on the images
	 * @since: 1.2.0
	 * @moved: 1.5.4: moved to Essential_Grid_Base->get_all_gallery_images($mid_content);
	 **/
	public function check_for_shortcodes($mid_content)
	{
		$mid_content = apply_filters('essgrid_check_for_shortcodes', $mid_content);

		$base = new Essential_Grid_Base();
		$img = $base->get_all_gallery_images($mid_content);
		$this->custom_images = (empty($img)) ? null : $img;
		$this->is_gallery = !empty($img);
	}

	public static function fix_shortcodes($content)
	{
		$content = apply_filters('essgrid_fix_shortcodes_pre', $content);
		$columns = array("ess_grid");
		$block = join("|", $columns);

		// opening tag
		$rep = preg_replace("/(<p>)?\[($block)(\s[^\]]+)?\](<\/p>|<br \/>)?/", "[$2$3]", $content);

		// closing tag
		$rep = preg_replace("/(<p>)?\[\/($block)](<\/p>|<br \/>)/", "[/$2]", $rep);

		return apply_filters('essgrid_fix_shortcodes_post', $rep);
	}

	/**
	 * Register Custom Post Type & Taxonomy
	 */
	public function register_custom_post_type()
	{
		$postType = apply_filters('essgrid_PunchPost_custom_post_type', 'essential_grid');
		$taxonomy = apply_filters('essgrid_PunchPost_category', 'essential_grid_category');

		$taxArgs = array();
		$taxArgs["hierarchical"] = true;
		$taxArgs["label"] = esc_attr__("Categories", ESG_TEXTDOMAIN);
		$taxArgs["singular_label"] = esc_attr__("Category", ESG_TEXTDOMAIN);
		$taxArgs["rewrite"] = true;
		$taxArgs["public"] = true;
		$taxArgs["show_admin_column"] = true;

		$postArgs = array();
		$postArgs["label"] = esc_attr__("ESG Posts", ESG_TEXTDOMAIN);
		$postArgs["singular_label"] = esc_attr__("Grid Post", ESG_TEXTDOMAIN);
		$postArgs["public"] = true;
		$postArgs["capability_type"] = "post";
		$postArgs["hierarchical"] = false;
		$postArgs["show_ui"] = true;
		$postArgs["show_in_menu"] = true;
		$postArgs["show_in_rest"] = true;
		$postArgs["supports"] = array(
			'title',
			'editor',
			'thumbnail',
			'author',
			'comments',
			'excerpt'
		);
		$postArgs["show_in_admin_bar"] = false;
		$postArgs["taxonomies"] = array(
			$taxonomy,
			'post_tag'
		);

		$postArgs["rewrite"] = array(
			"slug" => $postType,
			"with_front" => true
		);

		$d = apply_filters('essgrid_register_custom_post_type', array(
			'postArgs' => $postArgs,
			'taxArgs' => $taxArgs
		));
		$postArgs = $d['postArgs'];
		$taxArgs = $d['taxArgs'];

		register_taxonomy($taxonomy, array(
			$postType
		), $taxArgs);
		register_post_type($postType, $postArgs);
	}

	/**
	 * Register Custom Sidebars, created in Grids
	 * @since 1.0.6
	 */
	public static function register_custom_sidebars()
	{
		// Register custom Sidebars
		$sidebars = apply_filters('essgrid_register_custom_sidebars', get_option('esg-widget-areas', false));

		if (is_array($sidebars) && !empty($sidebars)) {
			foreach ($sidebars as $handle => $name) {
				register_sidebar(array(
					'name' => $name,
					'id' => 'eg-' . $handle,
					'before_widget' => '',
					'after_widget' => ''
				));
			}
		}
	}

	/**
	 * Register the Custom Widget for Essential Grid
	 **/
	public static function register_custom_widget()
	{
		register_widget('Essential_Grids_Widget');
	}

	/**
	 * Get all Grids in Database
	 */
	public static function get_essential_grids($order = false, $raw = true)
	{
		global $wpdb, $esg_grids_cache;

		$order_fav = false;
		$additional = '';
		if (!empty($order)) {
			$ordertype = key($order);
			$orderby = reset($order);
			if ($ordertype != 'favorite') {
				$additional .= ' ORDER BY ' . $ordertype . ' ' . $orderby;
			} else {
				$order_fav = true;
			}
		}
		
		$cache_key = md5($additional);

		if (empty($esg_grids_cache[$cache_key])) {
			$table_name = Essential_Grid_Db::get_table('grids');
			$grids = $wpdb->get_results("SELECT * FROM $table_name" . $additional);
			$esg_grids_cache[$cache_key] = $grids;
		} else {
			$grids = $esg_grids_cache[$cache_key];
		}

		//check if we order by favorites here
		if ($order_fav === true) {
			$temp = array();
			$temp_not = array();
			foreach ($grids as $grid) {
				$settings = json_decode($grid->settings, true);
				if (!isset($settings['favorite']) || $settings['favorite'] == 'false') {
					$temp_not[] = $grid;
				} else {
					$temp[] = $grid;
				}
			}

			$g = ($orderby == 'ASC') ? $temp : $temp_not;
			$g2 = ($orderby == 'ASC') ? $temp_not : $temp;
			$grids = $g;
			if (!empty($g2)) {
				foreach ($g2 as $t) {
					$grids[] = $t;
				}
			}
		}

		if ($raw === false && !empty($grids)) {
			foreach ($grids as $k => $grid) {
				if (!is_array($grid->params)) {
					$grids[$k]->postparams = @json_decode($grid->postparams, true);
					$grids[$k]->params = @json_decode($grid->params, true);
					$grids[$k]->layers = @json_decode($grid->layers, true);
					$grids[$k]->settings = @json_decode($grid->settings, true);
					$grids[$k]->last_modified = @$grid->last_modified;
				}
			}
		}

		return apply_filters('essgrid_get_essential_grids', $grids);
	}

	/**
	 * Get all Grids in Database
	 */
	public static function clear_essential_grids_cache()
	{
		global $esg_grids_cache;

		$esg_grids_cache = array();
	}

	/**
	 * Get Grid by ID from Database
	 */
	public static function get_essential_grid_by_id($id = 0, $raw = false)
	{
		global $wpdb;

		$id = intval($id);
		if ($id == 0)
			return false;

		$table_name = Essential_Grid_Db::get_table('grids');
		$grid = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id), ARRAY_A);
		if (!empty($grid) && !$raw) {
			$grid['postparams'] = @json_decode($grid['postparams'], true);
			$grid['params'] = @json_decode($grid['params'], true);
			$grid['layers'] = @json_decode($grid['layers'], true);
			$grid['settings'] = @json_decode($grid['settings'], true);
		}

		return apply_filters('essgrid_get_essential_grid_by_id', $grid, $id);
	}

	/**
	 * Get Grid by Handle from Database
	 */
	public static function get_essential_grid_by_handle($handle = '', $raw = false)
	{
		global $wpdb;

		if (empty($handle))
			return false;

		$table_name = Essential_Grid_Db::get_table('grids');
		$grid = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE handle = %s", $handle), ARRAY_A);
		if (!empty($grid) && !$raw) {
			$grid['postparams'] = @json_decode($grid['postparams'], true);
			$grid['params'] = @json_decode($grid['params'], true);
			$grid['layers'] = @json_decode($grid['layers'], true);
			$grid['settings'] = @json_decode($grid['settings'], true);
		}

		return apply_filters('essgrid_get_essential_grid_by_handle', $grid, $handle);
	}


	/**
	 * get array of id -> title
	 */
	public static function get_grids_short($exceptID = null)
	{
		$arrGrids = self::get_essential_grids();

		$arrShort = array();
		foreach ($arrGrids as $grid) {
			$id = $grid->id;
			$title = $grid->name;
			//filter by except
			if (!empty($exceptID) && $exceptID == $id)
				continue;
			$arrShort[$id] = $title;
		}

		return apply_filters('essgrid_get_grids_short', $arrShort, $exceptID);
	}


	/**
	 * get array of id -> handle
	 * @since 1.0.6
	 */
	public static function get_grids_short_widgets($exceptID = null)
	{
		$arrGrids = self::get_essential_grids();
		$arrShort = array();
		foreach ($arrGrids as $grid) {
			//filter by except
			if (!empty($exceptID) && $exceptID == $grid->id)
				continue;
			$arrShort[$grid->id] = $grid->handle;
		}

		return apply_filters('essgrid_get_grids_short_widgets', $arrShort, $exceptID);
	}


	/**
	 * get array of id -> title
	 */
	public static function get_grids_short_vc($exceptID = null)
	{
		$arrGrids = self::get_essential_grids();
		$arrShort = array();
		foreach ($arrGrids as $grid) {
			$alias = $grid->handle;
			$title = $grid->name;

			//filter by except
			if (!empty($exceptID) && $exceptID == $grid->id)
				continue;

			$arrShort[$title] = $alias;
		}

		return apply_filters('essgrid_get_grids_short_vc', $arrShort, $exceptID);
	}

	/**
	 * Get Certain Parameter
	 * @since: 1.5.0
	 */
	public function get_param_by_handle($handle, $default = '')
	{
		$d = apply_filters('essgrid_get_param_by_handle', array(
			'handle' => $handle,
			'default' => $default
		));
		$handle = $d['handle'];
		$default = $d['default'];
		$base = new Essential_Grid_Base();
		return $base->getVar($this->grid_params, $handle, $default);
	}

	/**
	 * Get Certain Post Parameter
	 * @since: 1.5.0
	 */
	public function get_postparam_by_handle($handle, $default = '')
	{
		$d = apply_filters('essgrid_get_postparam_by_handle', array(
			'handle' => $handle,
			'default' => $default
		));
		$handle = $d['handle'];
		$default = $d['default'];
		$base = new Essential_Grid_Base();
		return $base->getVar($this->grid_postparams, $handle, $default);
	}

	/**
	 * Update Certain Parameter by Handle
	 * @since: 2.1.0
	 */
	public function set_param_by_handle($handle, $param)
	{
		$this->grid_params[$handle] = $param;
	}

	/**
	 * Update Certain Post Parameter by Handle
	 * @since: 2.1.0
	 */
	public function set_postparam_by_handle($handle, $param)
	{
		$this->grid_postparams[$handle] = $param;
	}

	/**
	 * Update Certain Post Parameter by Handle
	 * @since: 2.1.0
	 */
	public function save_params()
	{
		global $wpdb;

		$table_name = Essential_Grid_Db::get_table('grids');
		$wpdb->update($table_name, array(
			'postparams' => json_encode($this->grid_postparams),
			'params' => json_encode($this->grid_params)
		), array(
			'id' => $this->grid_id
		));
	}

	/**
	 * Output Essential Grid in Page by alias
	 */
	public function output_essential_grid_by_alias($eg_alias)
	{
		global $wpdb;

		$eg_alias = apply_filters('essgrid_output_essential_grid_by_alias', $eg_alias);
		$table_name = Essential_Grid_Db::get_table('grids');
		$grid = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE handle = %s", $eg_alias), ARRAY_A);
		if (!empty($grid)) {
			$this->output_essential_grid($grid['id']);
		} else {
			return false;
		}
	}

	/**
	 * Output Essential Grid in Page by Custom Settings and Layers
	 * @since: 1.2.0
	 */
	public function output_essential_grid_by_settings()
	{
		do_action('essgrid_output_essential_grid_by_settings', $this);

		if ($this->custom_special !== null) {
			if ($this->custom_settings !== null) //custom settings got added. Overwrite Grid Settings and element settings
				$this->apply_custom_settings(true);
			$this->apply_all_media_types();
			$this->output_by_posts();
		} else {
			if ($this->custom_settings == null || $this->custom_layers == null) {
				return false;
			} else {
				$this->output_essential_grid_custom();
			}
		}
	}

	/**
	 * Get Essential Grid ID by alias
	 * @since: 1.2.0
	 */
	public static function get_id_by_alias($eg_alias)
	{
		global $wpdb;

		$eg_alias = apply_filters('essgrid_get_id_by_alias', $eg_alias);
		$table_name = Essential_Grid_Db::get_table('grids');
		$grid = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE handle = %s", $eg_alias), ARRAY_A);
		if (!empty($grid)) {
			return $grid['id'];
		} else {
			return '0';
		}
	}

	/**
	 * Get Essential Grid alias by ID
	 * @since: 2.0
	 */
	public static function get_alias_by_id($eg_id)
	{
		global $wpdb;

		$eg_id = apply_filters('essgrid_get_alias_by_id', $eg_id);
		$table_name = Essential_Grid_Db::get_table('grids');
		$grid = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $eg_id), ARRAY_A);
		if (!empty($grid)) {
			return $grid['handle'];
		} else {
			return '';
		}
	}

	/**
	 * get all post values / layer values at custom grid
	 * @since: 2.1.0
	 */
	public function get_layer_values()
	{
		return apply_filters('essgrid_get_layer_values', $this->grid_layers);
	}

	/**
	 * Init essential data by id
	 */
	public function init_by_id($grid_id)
	{
		global $wpdb;

		$grid_id = apply_filters('essgrid_init_by_id_pre', $grid_id);
		$table_name = Essential_Grid_Db::get_table('grids');
		$grid = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $grid_id), ARRAY_A);
		if (empty($grid))
			return false;

		$this->grid_id = @$grid['id'];
		$this->grid_name = @$grid['name'];
		$this->grid_handle = @$grid['handle'];
		$this->grid_postparams = @json_decode($grid['postparams'], true);
		$this->grid_params = @json_decode($grid['params'], true);
		$this->grid_settings = @json_decode($grid['settings'], true);
		$this->grid_last_mod = @$grid['last_modified'];

		if (!empty($grid['layers'])) {
			$orig_layers = $grid['layers'];
			$grid['layers'] = @json_decode(stripslashes($orig_layers), true);
			if (empty($grid['layers']) || !is_array($grid['layers']))
				$grid['layers'] = @json_decode($orig_layers, true);

			if (!empty($grid['layers'])) {
				foreach ($grid['layers'] as $key => $layer) {
					$orig_layers_cur = $grid['layers'][$key];
					$grid['layers'][$key] = @json_decode($orig_layers_cur, true);
					if (empty($grid['layers'][$key]) || !is_array($grid['layers'][$key]))
						$grid['layers'][$key] = @json_decode(stripslashes($orig_layers_cur), true);
				}
			}
		}
		$this->grid_layers = @$grid['layers'];

		do_action('essgrid_init_by_id_post', $this, $grid);

		return true;
	}

	/**
	 * Init essential data by given data
	 */
	public function init_by_data($grid_data)
	{
		$grid_data = apply_filters('essgrid_init_by_data', $grid_data);

		$this->grid_id = (isset($grid_data['id'])) ? $grid_data['id'] : '';
		$this->grid_name = (isset($grid_data['name'])) ? $grid_data['name'] : '';
		$this->grid_handle = (isset($grid_data['handle'])) ? $grid_data['handle'] : '';
		$this->grid_postparams = (isset($grid_data['postparams'])) ? $grid_data['postparams'] : '';
		$this->grid_params = (isset($grid_data['params'])) ? $grid_data['params'] : '';
		$this->grid_settings = (isset($grid_data['settings'])) ? $grid_data['settings'] : '';
		$this->grid_last_mod = (isset($grid_data['last_modified'])) ? $grid_data['last_modified'] : '';

		$this->grid_layers = array();
		if (!empty($grid_data['layers'])) {
			foreach ($grid_data['layers'] as $key => $layer) {
				$temp_layer = @json_decode(stripslashes($grid_data['layers'][$key]), true);
				if (!empty($temp_layer)) {
					$grid_data['layers'][$key] = $temp_layer;
				} else {
					$temp_layer = @json_decode($grid_data['layers'][$key], true);
					if (!empty($temp_layer)) {
						$grid_data['layers'][$key] = $temp_layer;
					}
				}
			}
			$this->grid_layers = $grid_data['layers'];
		}

		return true;
	}

	/**
	 * Init essential data by id
	 */
	public function set_loading_ids($ids)
	{
		$this->filter_by_ids = apply_filters('essgrid_set_loading_ids', $ids);
	}

	/**
	 * Check if Grid is a Post
	 */
	public function is_custom_grid()
	{
		do_action('essgrid_is_custom_grid');
		if (isset($this->grid_postparams['source-type']) && $this->grid_postparams['source-type'] == 'custom')
			return true;
		else
			return false;
	}

	/**
	 * Check if Grid is a Stream
	 * @return bool
	 */
	public function is_stream_grid()
	{
		return apply_filters('essgrid_is_stream_grid', false, $this);
	}

	/**
	 * Output Essential Grid in Page
	 */
	public function output_essential_grid($grid_id, $data = array(), $grid_preview = false, $by_id = false)
	{
		try {
			
			do_action('essgrid_output_essential_grid', $grid_id, $data, $grid_preview, $by_id);
			
			if ($grid_preview) {
				$data['id'] = $grid_id;
				if (!$by_id) {
					$init = $this->init_by_data($data);
				} else {
					$init = $this->init_by_id($grid_id);
				}
				if (!$init)
					return false;
			} else {
				$init = $this->init_by_id($grid_id);
				if (!$init)
					return false;
				Essential_Grid_Global_Css::output_global_css_styles_wrapped();
			}

			if (!Essential_Grid_Base::isValid()) {
				$pg = Essential_Grid_Base::getVar($this->grid_params, 'pg', 'false');
				if ($pg != 'false') throw new Exception(__('Please register the Essential Grid plugin to use premium templates.', ESG_TEXTDOMAIN));
			}

			do_action('essgrid_output_essential_grid_after_init', $this);

			//custom_special is always posts related ( popular, recent etc... ), so we change to post
			if ($this->custom_special !== null) 
				$this->grid_postparams['source-type'] = 'post';

			//custom post IDs are added, so we change to post
			if ($this->custom_posts !== null) 
				$this->grid_postparams['source-type'] = 'post';

			//custom images are added, so we change to gallery
			if ($this->custom_images !== null) 
				$this->grid_postparams['source-type'] = 'gallery';

			//custom settings got added. Overwrite Grid Settings and element settings
			if ($this->custom_settings !== null) 
				$this->apply_custom_settings();

			//custom layers got added. Overwrite Grid Layers
			if ($this->custom_layers !== null) {
				$this->apply_custom_layers();
				$this->grid_postparams['source-type'] = 'custom';
			}

			$this->set_api_names(); //set correct names for javascript and div id
			
			$source_type = apply_filters('essgrid_output_essential_grid_get_source_type', $this->grid_postparams['source-type'], $this);
			switch ($source_type) {
				case 'post':
				case 'woocommerce':
					$this->output_by_posts($grid_preview);
					break;
				case 'custom':
					$this->output_by_custom($grid_preview);
					break;
				case 'gallery':
					$this->output_by_gallery($grid_preview);
					break;
				case 'stream':
					$this->output_by_stream($grid_preview);
					break;
				default:
					$this->display_grid_error_msg(false, true);
			}

		} catch (Exception $e) {
			$message = $e->getMessage();
			echo $message;
		}
	}

	/**
	 * set correct names for javascript and div id
	 * @since: 1.5.0
	 */
	public function set_api_names()
	{
		$ess_api = '';
		$ess_div = '';
		if ($this->grid_id != null) {
			$ess_api = $this->grid_id;
			$ess_div = $this->grid_id;
		}

		if ($this->custom_special !== null) {
			switch ($this->custom_special) {
				case 'related':
				case 'popular':
				case 'latest':
					$ess_api .= '_' . $this->custom_special;
					$ess_div .= '-' . $this->custom_special;
					break;
			}
		}
		if ($this->custom_posts !== null) {
			$ess_api .= '_custom_post';
			$ess_div .= '-custom_post';
		}
		if ($this->custom_settings !== null) {
			$ess_api .= '_custom';
			$ess_div .= '-custom';
		}
		if ($this->custom_layers !== null) {
			$ess_api .= '_layers';
			$ess_div .= '-layers';
		}
		if ($this->custom_images !== null) {
			$ess_api .= '_img';
			$ess_div .= '-img';
		}

		$this->grid_api_name = $ess_api;
		$this->grid_div_name = $ess_div;

		do_action('essgrid_set_api_names', $this);
	}

	/**
	 * Output Essential Grid in Page with Custom Layer and Settings
	 * @since: 1.2.0
	 */
	public function output_essential_grid_custom($grid_preview = false)
	{
		try {
			do_action('essgrid_output_essential_grid_custom', $this, $grid_preview);

			Essential_Grid_Global_Css::output_global_css_styles_wrapped();
			
			if ($this->custom_settings !== null) //custom settings got added. Overwrite Grid Settings and element settings
				$this->apply_custom_settings(true);

			if ($this->custom_layers !== null) //custom settings got added. Overwrite Grid Settings and element settings
				$this->apply_custom_layers();

			$this->apply_all_media_types();

			return $this->output_by_custom($grid_preview);

		} catch (Exception $e) {
			$message = $e->getMessage();
			echo $message;
		}
	}

	/**
	 * Apply all media types for custom grids that have not many settings
	 * @since: 1.2.0
	 */
	public function apply_all_media_types()
	{
		/**
		 * Add settings that need to be set
		 * - use all media sources, sorting does not matter since we only set one thing in each entry
		 * - use all poster sources for videos, sorting does not matter since we only set one thing in each entry
		 * - use all lightbox sources, sorting does not matter since we only set one thing in each entry
		 */
		$media_orders = Essential_Grid_Base::get_media_source_order();
		foreach ($media_orders as $handle => $vals) {
			if ($handle == 'featured-image' || $handle == 'alternate-image')
				continue;
			$this->grid_postparams['media-source-order'][] = $handle;
		}
		$this->grid_postparams['media-source-order'][] = 'featured-image'; //set this as the last entry
		$this->grid_postparams['media-source-order'][] = 'alternate-image'; //set this as the last entry

		$poster_orders = Essential_Grid_Base::get_poster_source_order();
		if (!empty($poster_orders)) {
			foreach ($poster_orders as $handle => $vals) {
				$this->grid_params['poster-source-order'][] = $handle;
			}
		}

		$lb_orders = Essential_Grid_Base::get_lb_source_order();
		foreach ($lb_orders as $handle => $vals) {
			$this->grid_params['lb-source-order'][] = $handle;
		}

		$lb_buttons = Essential_Grid_Base::get_lb_button_order();
		foreach ($lb_buttons as $handle => $vals) {
			$this->grid_params['lb-button-order'][] = $handle;
		}

		do_action('essgrid_apply_all_media_types', $this);
	}

	/**
	 * Apply Custom Settings to the Grid, so users can change everything in the settings they want to
	 * This allows to modify grid_params and grid_post_params
	 * @since: 1.2.0
	 */
	private function apply_custom_settings($has_handle = false)
	{
		if (empty($this->custom_settings) || !is_array($this->custom_settings))
			return false;

		$base = new Essential_Grid_Base();
		$translate_variables = array(
			'grid-layout' => 'layout'
		);

		foreach ($this->custom_settings as $handle => $new_setting) {
			if (isset($translate_variables[$handle])) {
				$handle = $translate_variables[$handle];
			}
			if ($has_handle) { //p- is in front of postparameters
				if (strpos($handle, 'p-') === 0)
					$this->grid_postparams[substr($handle, 2)] = $new_setting;
				else
					$this->grid_params[$handle] = $new_setting;
			} else {
				if (isset($this->grid_params[$handle])) {
					$this->grid_params[$handle] = $new_setting;
				} elseif (isset($this->grid_postparams[$handle])) {
					$this->grid_postparams[$handle] = $new_setting;
				} else {
					$this->grid_params[$handle] = $new_setting;
				}
			}
		}

		if (isset($this->grid_params['columns'])) { //change columns
			$columns = $base->set_basic_colums_custom($this->grid_params['columns']);
			$this->grid_params['columns'] = $columns;
		}

		if (isset($this->grid_params['rows-unlimited']) && $this->grid_params['rows-unlimited'] == 'off') { //add pagination
			$this->grid_params['navigation-layout']['pagination']['bottom-1'] = '0';
			$this->grid_params['bottom-1-margin-top'] = '10';
		}

		do_action('essgrid_apply_custom_settings', $this);

		return true;
	}

	/**
	 * Apply Custom Layers to the Grid
	 * @since: 1.2.0
	 */
	private function apply_custom_layers()
	{
		$this->grid_layers = array();
		if (!empty($this->custom_layers) && is_array($this->custom_layers)) {
			$add_poster_img = array();
			foreach ($this->custom_layers as $handle => $val_arr) {
				if (!empty($val_arr) && is_array($val_arr)) {
					//$custom_poster = false;
					foreach ($val_arr as $id => $value) {
						//if ($handle == 'custom-poster') $custom_poster = array($id, $value);
						if ($handle == 'custom-poster') {
							$add_poster_img[$id] = $value;
							continue;
						}
						$this->grid_layers[$id][$handle] = $value;
					}
				}
			}

			if (!empty($add_poster_img)) {
				foreach ($add_poster_img as $id => $value) {
					$this->grid_layers[$id]['custom-image'] = $value;
				}
			}
		}

		do_action('essgrid_apply_custom_layers', $this);
	}

	/**
	 * Output by Specific Stream
	 * @since: 2.1.0
	 */
	public function output_by_specific_stream()
	{
		ob_start();
		$this->output_by_stream(false, true, $this->filter_by_ids);
		$stream_html = ob_get_contents();
		ob_clean();
		ob_end_clean();

		return apply_filters('essgrid_output_by_specific_stream', $stream_html, $this);
	}

	/**
	 * Output by Stream
	 * @since: 2.1.0
	 */
	public function output_by_stream($grid_preview = false, $only_elements = false, $specific_ids = array())
	{
		do_action('essgrid_output_by_stream_pre', $grid_preview, $only_elements, $specific_ids);

		$this->grid_layers = array();
		$base = new Essential_Grid_Base();

		$stream_found = apply_filters('essgrid_output_by_stream_get_grid_layers', false, $this);
		if (!$stream_found || empty($this->grid_layers)) {
			$this->display_grid_error_msg(true);
			return;
		}
		
		$order_by_start = $base->getVar($this->grid_params, 'sorting-order-by-start', 'none');
		$order_by_dir = $base->getVar($this->grid_params, 'sorting-order-type', 'ASC');
		$this->order_by_custom($order_by_start, $order_by_dir);

		if (!empty($specific_ids)) { //remove all that we do not have in this array
			foreach ($this->grid_layers as $key => $layer) {
				if (!in_array($key, $specific_ids))
					unset($this->grid_layers[$key]);
			}
		}

		//limit layers in preview to default level
		if ($grid_preview === 'preview') {
			$stream_limit = apply_filters('essgrid_output_by_stream_preview_limit', 60);
			if (count($this->grid_layers) > $stream_limit) {
				$this->grid_layers = array_slice($this->grid_layers, 0, $stream_limit);
				echo '<div class="preview-notice esg-margin-b-15">' . 
					esc_html(sprintf(
					/* translators: %s: stream items count limit */
						__( 'Stream items in preview mode is limited to %s items!', ESG_TEXTDOMAIN ),
						$stream_limit
					)) . '</div>';
			}
		}

		do_action('essgrid_output_by_stream_post', $this, $grid_preview, $only_elements);

		$do_load_more = !empty($specific_ids);
		return $this->output_by_custom($grid_preview, $only_elements, $do_load_more);
	}

	public function find_biggest_photo($image_urls, $wanted_size, $avail_sizes)
	{
		$d = apply_filters('essgrid_find_biggest_photo', array(
			'image_urls' => $image_urls,
			'wanted_size' => $wanted_size,
			'avail_sizes' => $avail_sizes
		));

		$image_urls = $d['image_urls'];
		$wanted_size = $d['wanted_size'];
		$avail_sizes = $d['avail_sizes'];

		if (isset($image_urls[$wanted_size]) && !$this->isEmpty($image_urls[$wanted_size]))
			return $image_urls[$wanted_size];
		$wanted_size_pos = array_search($wanted_size, $avail_sizes);
		for ($i = $wanted_size_pos; $i < 7; $i++) {
			if (isset($avail_sizes[$i]) && !$this->isEmpty($image_urls[$avail_sizes[$i]]))
				return $image_urls[$avail_sizes[$i]];
		}
		for ($i = $wanted_size_pos; $i >= 0; $i--) {
			if (!$this->isEmpty($image_urls[$avail_sizes[$i]]))
				return $image_urls[$avail_sizes[$i]];
		}
	}

	public function isEmpty($stringOrArray)
	{
		$stringOrArray = apply_filters('essgrid_isEmpty', $stringOrArray);
		if (is_array($stringOrArray)) {
			foreach ($stringOrArray as $value) {
				if (!$this->isEmpty($value)) {
					return false;
				}
			}
			return true;
		}

		return !strlen($stringOrArray); // this properly checks on empty string ('')
	}

	/**
	 * Output by gallery
	 * Remove all custom elements, add image elements
	 * @since: 1.2.0
	 */
	public function output_by_gallery($grid_preview = false, $only_elements = false, $from_ajax = false)
	{
		$this->grid_layers = array();

		if (!empty($this->custom_images)) {
			foreach ($this->custom_images as $image_id) {
				
				$attachment = get_post($image_id);
				if (!is_object($attachment)) continue;
				
				$this->grid_layers[$image_id] = array(
					'custom-image' => $image_id,
					'excerpt' => $attachment->post_excerpt,
					'caption' => $attachment->post_excerpt,
					'title' => $attachment->post_title,
					'content' => $attachment->post_content,
					'description' => $attachment->post_content
				);
			}
		}

		do_action('essgrid_output_by_gallery', $this, $grid_preview, $only_elements);

		return $this->output_by_custom($grid_preview, $only_elements, false, $from_ajax);
	}

	/**
	 * Output by custom grid
	 */
	public function output_by_custom($grid_preview = false, $only_elements = false, $set_load_more = false, $from_ajax = false)
	{
		$post_limit = 99999;

		do_action('essgrid_output_by_custom_pre', $this, $grid_preview, $only_elements);

		$base = new Essential_Grid_Base();
		$meta_c = new Essential_Grid_Meta();
		$navigation_c = new Essential_Grid_Navigation($this->grid_id);
		$item_skin = new Essential_Grid_Item_Skin();
		$item_skin->grid_id = $this->grid_id;
		$item_skin->grid_params = $this->grid_params;
		$item_skin->set_grid_type($base->getVar($this->grid_params, 'layout', 'even'));

		$item_skin->set_default_image_by_id($base->getVar($this->grid_postparams, 'default-image', 0, 'i'));
		$item_skin->set_default_youtube_image_by_id($base->getVar($this->grid_postparams, 'youtube-default-image', 0, 'i'));
		$item_skin->set_default_vimeo_image_by_id($base->getVar($this->grid_postparams, 'vimeo-default-image', 0, 'i'));
		$item_skin->set_default_html_image_by_id($base->getVar($this->grid_postparams, 'html-default-image', 0, 'i'));

		// 2.1.6.2
		$item_skin->set_grid_item_animation($base, $this->grid_params);
		if ($set_load_more)
			$item_skin->set_load_more();

		$m = new Essential_Grid_Meta();
		$skins_html = '';
		$skins_css = '';

		$rows_unlimited = $base->getVar($this->grid_params, 'rows-unlimited', 'on');
		$load_more = $base->getVar($this->grid_params, 'load-more', 'none');
		$load_more_start = $base->getVar($this->grid_params, 'load-more-start', 3, 'i');

		if ($rows_unlimited == 'on' && $load_more !== 'none' && !$grid_preview) { 
			// grid_preview means disable load more in preview
			$post_limit = $load_more_start;
		}

		$nav_layout = $base->getVar($this->grid_params, 'navigation-layout', array());
		$filter_allow = $base->getVar($this->grid_params, 'filter-arrows', 'single');
		$filter_start = $base->getVar($this->grid_params, 'filter-start');
		
		// search for filters in layout and grid params
		// filter could have no checked elements and no filter-selected parameter 
		$filters_in_params_layout = array_filter(
			$this->grid_params, 
			function($k) {
				return strpos($k, 'filter-selected') !== false;
			}, 
			ARRAY_FILTER_USE_KEY
		);

		foreach ($nav_layout as $item => $position) {
			if (strpos($item, 'filter') === false) continue;
			$filter_name = str_replace('filter', 'filter-selected', $item);
			if (!isset($filters_in_params_layout[$filter_name])) {
				$filters_in_params_layout[$filter_name] = $filter_name;
			}
		}
		
		$search_text = $base->getVar($this->grid_params, 'search-text', esc_attr__('Search...', ESG_TEXTDOMAIN));
		$fil_id = '';
		$filters_arr = array();
		$filter_found = false;
		foreach ($filters_in_params_layout as $gkey => $gdata) {
			$filter_found = true;
			$fil_id = intval(str_replace('filter-selected-', '', $gkey));
			$fil_id = ($fil_id == 0) ? '' : '-' . $fil_id;
			if (isset($filters_arr['filter' . $fil_id])) continue;

			// 3.0.12
			// moved inside the loop
			// https://themepunch.monday.com/boards/313036946/pulses/1193179221
			$filter_all_text = $base->getVar($this->grid_params, 'filter-all-text' . $fil_id, esc_attr__('Filter - All', ESG_TEXTDOMAIN));
			$filterall_visible = $base->getVar($this->grid_params, 'filter-all-visible' . $fil_id, 'on');
			$show_count = $base->getVar($this->grid_params, 'filter-counter' . $fil_id, 'off');

			$filter_dropdown_text = $base->getVar($this->grid_params, 'filter-dropdown-text' . $fil_id, esc_attr__('Filter Categories', ESG_TEXTDOMAIN));
			$filters_arr['filter' . $fil_id]['filter-grouping'] = $base->getVar($this->grid_params, 'filter-grouping' . $fil_id, 'false');
			$filters_arr['filter' . $fil_id]['filter-listing'] = $base->getVar($this->grid_params, 'filter-listing' . $fil_id, 'list');
			$filters_arr['filter' . $fil_id]['filter-selected'] = $base->getVar($this->grid_params, 'filter-selected' . $fil_id, array());
			$filters_arr['filter' . $fil_id]['custom'] = true;
			$filters_arr['filter' . $fil_id]['filter-sort-alpha'] = $base->getVar($this->grid_params, 'filter-sort-alpha' . $fil_id, 'off');
			$filters_arr['filter' . $fil_id]['filter-sort-alpha-dir'] = $base->getVar($this->grid_params, 'filter-sort-alpha-dir' . $fil_id, 'asc');

			if (!empty($filters_arr['filter' . $fil_id]['filter-selected']) && !empty($this->grid_layers) && count($this->grid_layers) > 0) {
				foreach ($filters_arr['filter' . $fil_id]['filter-selected'] as $fk => $filter) {
					$filters_arr['filter' . $fil_id]['filter-selected'][$fk] = Essential_Grid_Base::sanitize_utf8_to_unicode($filter);
					if (strpos($filter, 'meta-') !== 0) continue;

					unset($filters_arr['filter' . $fil_id]['filter-selected'][$fk]); //delete entry

					$fil = str_replace('meta-', '', $filter);
					foreach ($this->grid_layers as $entry) {
						if (empty($entry['eg-' . $fil])) continue;
						
						$post_filter_meta = $entry['eg-' . $fil];
						if (is_array($post_filter_meta)) {
							$post_filter_meta = explode(',', $post_filter_meta[0]);
						}
						
						$cur_filter = (is_array($post_filter_meta)) ? $post_filter_meta : array($post_filter_meta);
						$add_filter = array();
						if (!empty($cur_filter)) {
							foreach ($cur_filter as $v) {
								if (trim($v) == '') continue;
								$_v = Essential_Grid_Base::sanitize_utf8_to_unicode($v);
								$add_filter[$_v] = array(
									'name' => $v,
									'slug' => $_v,
									'parent' => '0'
								);
								if (!empty($filters_arr['filter' . $fil_id]['filter-selected'])) {
									$filter_found = false;
									foreach ($filters_arr['filter' . $fil_id]['filter-selected'] as $fcheck) {
										if ($fcheck == $_v) {
											$filter_found = true;
											break;
										}
									}
									if (!$filter_found) {
										$filters_arr['filter' . $fil_id]['filter-selected'][] = $_v; //add found meta
									}
								} else {
									$filters_arr['filter' . $fil_id]['filter-selected'][] = $_v; //add found meta
								}
							}

							if (!empty($add_filter)) {
								$navigation_c->set_filter($add_filter);
							}
						}
					}

					$meta_data = $meta_c->get_meta_by_handle($fil);
					if (in_array($meta_data['type'], array('select', 'multi-select'))) {
						//restore original meta values order
						$original_values = explode(',', $meta_data['select']);
						foreach ($original_values as $v) {
							$_v = Essential_Grid_Base::sanitize_utf8_to_unicode($v);
							if (($key = array_search($_v, $filters_arr['filter' . $fil_id]['filter-selected'])) !== false) {
								unset($filters_arr['filter' . $fil_id]['filter-selected'][$key]);
								$filters_arr['filter' . $fil_id]['filter-selected'][] = $_v;
							}
						}
					}
				}
			}

			$navigation_c->set_filter_settings('filter' . $fil_id, $filters_arr['filter' . $fil_id]);

			$navigation_c->set_filter_text($filter_all_text, $fil_id);
			$navigation_c->set_filterall_visible($filterall_visible, $fil_id);
			$navigation_c->set_dropdown_text($filter_dropdown_text, $fil_id);
			$navigation_c->set_show_count($show_count, $fil_id);
		}

		$nav_type = $base->getVar($this->grid_params, 'nagivation-type', 'internal');
		$do_nav = $nav_type == 'internal';

		$order_by = explode(',', $base->getVar($this->grid_params, 'sorting-order-by', 'date'));
		if (!is_array($order_by))
			$order_by = array(
				$order_by
			);
		$order_by_start = $base->getVar($this->grid_params, 'sorting-order-by-start', 'none');
		$order_by_dir = $base->getVar($this->grid_params, 'sorting-order-type', 'ASC');
		$sort_by_text = $base->getVar($this->grid_params, 'sort-by-text', esc_attr__('Sort By ', ESG_TEXTDOMAIN));
		$module_spacings = $base->getVar($this->grid_params, 'module-spacings', '5');

		$top_1_align = $base->getVar($this->grid_params, 'top-1-align', 'center');
		$top_2_align = $base->getVar($this->grid_params, 'top-2-align', 'center');
		$bottom_1_align = $base->getVar($this->grid_params, 'bottom-1-align', 'center');
		$bottom_2_align = $base->getVar($this->grid_params, 'bottom-2-align', 'center');
		$top_1_margin = $base->getVar($this->grid_params, 'top-1-margin-bottom', 0, 'i');
		$top_2_margin = $base->getVar($this->grid_params, 'top-2-margin-bottom', 0, 'i');
		$bottom_1_margin = $base->getVar($this->grid_params, 'bottom-1-margin-top', 0, 'i');
		$bottom_2_margin = $base->getVar($this->grid_params, 'bottom-2-margin-top', 0, 'i');

		$left_margin = $base->getVar($this->grid_params, 'left-margin-left', 0, 'i');
		$right_margin = $base->getVar($this->grid_params, 'right-margin-right', 0, 'i');

		$nav_styles['top-1'] = array(
			'margin-bottom' => $top_1_margin . 'px',
			'text-align' => $top_1_align
		);
		$nav_styles['top-2'] = array(
			'margin-bottom' => $top_2_margin . 'px',
			'text-align' => $top_2_align
		);
		$nav_styles['left'] = array(
			'margin-left' => $left_margin . 'px'
		);
		$nav_styles['right'] = array(
			'margin-right' => $right_margin . 'px'
		);
		$nav_styles['bottom-1'] = array(
			'margin-top' => $bottom_1_margin . 'px',
			'text-align' => $bottom_1_align
		);
		$nav_styles['bottom-2'] = array(
			'margin-top' => $bottom_2_margin . 'px',
			'text-align' => $bottom_2_align
		);

		if ($do_nav) { //only do if internal is selected
			$navigation_c->set_special_class('esg-fgc-' . $this->grid_id);
			if ($filter_found === false) {
				$navigation_c->set_dropdown_text($base->getVar($this->grid_params, 'filter-dropdown-text', esc_attr__('Filter Categories', ESG_TEXTDOMAIN)));
				$navigation_c->set_show_count($base->getVar($this->grid_params, 'filter-counter', 'off'));
				$navigation_c->set_filterall_visible($base->getVar($this->grid_params, 'filter-all-visible', 'on'));
				$navigation_c->set_filter_text($base->getVar($this->grid_params, 'filter-all-text' . $fil_id, esc_attr__('Filter - All', ESG_TEXTDOMAIN)));
			}
			$navigation_c->set_specific_styles($nav_styles);
			$navigation_c->set_search_text($search_text);
			$navigation_c->set_layout($nav_layout); //set the layout

			$navigation_c->set_orders($order_by); //set order of filter
			$navigation_c->set_orders_text($sort_by_text);
			$navigation_c->set_orders_start($order_by_start); //set order of filter
			$navigation_c->set_orders_order($order_by_dir);
		}
		$item_skin->init_by_id($base->getVar($this->grid_params, 'entry-skin', 0, 'i'));
		$this->grid_params['entry-skin-handle'] = $item_skin->get_handle();

		$lazy_load = $base->getVar($this->grid_params, 'lazy-loading', 'off');
		if ($lazy_load == 'on') {
			$item_skin->set_lazy_load(true);
			$lazy_load_blur = $base->getVar($this->grid_params, 'lazy-loading-blur', 'on');
			if ($lazy_load_blur == 'on')
				$item_skin->set_lazy_load_blur(true);
		}

		//2.3.7 YouTube Playlist overview gets featured images media, not videos
		$default_media_source_order = $base->getVar($this->grid_postparams, 'media-source-order');
		if ($default_media_source_order[0] == 'youtube' && $base->getVar($this->grid_postparams, 'youtube-type-source') == 'playlist_overview') {
			$default_media_source_order[0] = 'featured-image';
		}
		$item_skin->set_default_media_source_order($default_media_source_order);
		$default_lightbox_source_order = $base->getVar($this->grid_params, 'lb-source-order');
		$item_skin->set_default_lightbox_source_order($default_lightbox_source_order);

		/* 2.2 */
		$item_skin->set_fancybox_three_options($base->getVar($this->grid_params, 'lightbox-title', 'off'));
		$default_aj_source_order = $base->getVar($this->grid_params, 'aj-source-order');
		$item_skin->set_default_ajax_source_order($default_aj_source_order);
		$post_media_source_type = $base->getVar($this->grid_postparams, 'image-source-type', 'full');

		/* 2.1.6 */
		if (wp_is_mobile()) {
			$post_media_source_type = $base->getVar($this->grid_postparams, 'image-source-type-mobile', $post_media_source_type);
		}

		$default_video_poster_order = $base->getVar($this->grid_params, 'poster-source-order');
		if ($default_video_poster_order == '')
			$default_video_poster_order = $base->getVar($this->grid_postparams, 'poster-source-order');
		$item_skin->set_default_video_poster_order($default_video_poster_order);
		$layout = $base->getVar($this->grid_params, 'layout', 'even');
		$layout_sizing = $base->getVar($this->grid_params, 'layout-sizing', 'boxed');
		$ajax_container_position = $base->getVar($this->grid_params, 'ajax-container-position', 'top');
		if ($layout_sizing !== 'fullwidth' && $layout == 'masonry') {
			$item_skin->set_poster_cropping(true);
		}

		$found_filter = array();
		$i = 1;
		$this->order_by_custom($order_by_start, $order_by_dir);
		do_action('essgrid_output_by_custom_order_by', $this);

		if (!empty($this->grid_layers) && count($this->grid_layers) > 0) {

			$image_sizes = false;
			$image_source_smart = $base->getVar($this->grid_postparams, 'image-source-smart', 'off');
			if ('on' === $image_source_smart) {
				$image_sizes = $base->getVar($this->grid_postparams, 'image-source-smart-size', false);
			}
			
			foreach ($this->grid_layers as $key => $entry) {
				if (!is_array($entry)) continue;
				
				$post_media_source_data = $base->get_custom_media_source_data($entry, $post_media_source_type, $image_sizes);
				$post_video_ratios = $m->get_custom_video_ratios($entry);
				$filters = array();

				if (is_array($order_by) && !empty($order_by)) {
					$sort = $this->prepare_sorting_array_by_custom($entry, $order_by);
					$item_skin->set_sorting($sort);
				}
				if (!empty($entry['custom-filter'])) {
					$cats = explode(',', $entry['custom-filter']);
					if (!is_array($cats)) $cats = (array)$cats;

					foreach ($cats as $category) {
						$cur_filter = array();
						
						//check if it is meta filter
						if (strpos($category, 'meta-') === 0) {
							$fil = str_replace('meta-', '', $category);
							if (!empty($entry['eg-' . $fil])) {
								$post_filter_meta = $entry['eg-' . $fil];
								if (is_array($post_filter_meta)) {
									$post_filter_meta = explode(',', $post_filter_meta[0]);
								}
								$cur_filter = (is_array($post_filter_meta)) ? $post_filter_meta : array($post_filter_meta);
							}
						} else {
							$cur_filter = array($category);
						}
						
						foreach ($cur_filter as $v) {
							if (trim($v) == '') continue;
							$_v = Essential_Grid_Base::sanitize_utf8_to_unicode($v);
							$filters[$_v] = array(
								'name' => trim($v),
								'slug' => $_v
							);
						}
					}
				}

				// these are the found filters, only show filter that the posts have
				$found_filter = $found_filter + $filters; 
				
				// switch to different skin
				$use_item_skin_id = $base->getVar($entry, 'use-skin', '-1');
				if (intval($use_item_skin_id) === 0) {
					$use_item_skin_id = -1;
				}
				$item_skin->switch_item_skin($use_item_skin_id);
				$item_skin->register_layer_css();
				$item_skin->register_skin_css();

				if ($i > $post_limit) {
					$this->load_more_post_array[$key] = $filters; //set for load more, only on elements that will not be loaded from beginning
					continue; //Load only selected numbers of items at start (for load more)
				}
				$i++;

				$item_skin->set_filter($filters);
				$item_skin->set_media_sources($post_media_source_data);
				$item_skin->set_media_sources_type($post_media_source_type);
				$item_skin->set_video_ratios($post_video_ratios);
				$item_skin->set_layer_values($entry);

				// 2.1.6.2
				$item_skin->set_grid_item_animation($base, $this->grid_params);

				// 2.2.6
				$item_skin->set_post_values($entry);

				if (isset($entry['title'])) $item_skin->set_item_title($entry['title']);

				ob_start();
				$item_skin->output_item_skin($grid_preview);
				$skins_html .= ob_get_contents();
				ob_clean();
				ob_end_clean();

				// 2.2.6
				$id = (isset($entry['post_id'])) ? $entry['post_id'] : '';
				if (!empty($id)) {
					ob_start();
					$item_skin->output_element_css_by_meta($id);
					$skins_css .= ob_get_contents();
					ob_clean();
					ob_end_clean();
				}
			}
		} else {
			$skins_html .= apply_filters('essgrid_output_by_custom_no_items', '', $this);
		}

		if ($grid_preview !== false && !$only_elements) { 
			// add the add more box at the end
			ob_start();
			$item_skin->output_add_more();
			$skins_html .= ob_get_contents();
			ob_clean();
			ob_end_clean();
		}

		if ($do_nav) { 
			// only do if internal is selected
			$navigation_c->set_filter($found_filter);
			$navigation_c->set_filter_type($filter_allow);
			$navigation_c->set_filter_start_select($filter_start);
		}

		if (!$only_elements) {

			ob_start();
			$item_skin->generate_element_css($grid_preview);
			$skins_css .= ob_get_contents();
			ob_clean();
			ob_end_clean();

			$load_lightbox = $item_skin->do_lightbox_loading();
			if ($load_lightbox) {
				echo $item_skin->output_lighbox_css($this->grid_div_name, $this->grid_params);
			}

			if ($do_nav) { //only do if internal is selected
				$navigation_skin = $base->getVar($this->grid_params, 'navigation-skin', 'minimal-light');
				echo $navigation_c->output_navigation_skin($navigation_skin);
			}

			echo $skins_css;

			if ($item_skin->ajax_loading && $ajax_container_position == 'top') {
				echo $this->output_ajax_container();
			}

			$this->output_wrapper_pre($grid_preview);

			if ($do_nav) { //only do if internal is selected
				$navigation_c->output_layout('top-1', $module_spacings);
				$navigation_c->output_layout('top-2', $module_spacings);
			}

			$this->output_grid_pre();
		}

		if (!$from_ajax) {
			echo $skins_html;
		} else {
			return $skins_html;
		}

		if (!$only_elements) {
			$this->output_grid_post();

			if ($do_nav) { //only do if internal is selected
				$navigation_c->output_layout('bottom-1', $module_spacings);
				$navigation_c->output_layout('bottom-2', $module_spacings);
				$navigation_c->output_layout('left');
				$navigation_c->output_layout('right');

				//check if search was added. If yes, we also need to add the "Filter All" filter if not existing
				echo $navigation_c->check_for_search();
			}

			$this->output_wrapper_post();

			if ($item_skin->ajax_loading && $ajax_container_position == 'bottom') {
				echo $this->output_ajax_container();
			}

			$load_lightbox = $item_skin->do_lightbox_loading();

			if ($grid_preview === false) {
				$this->output_grid_javascript($load_lightbox);
			} elseif ($grid_preview !== 'preview' && $grid_preview !== 'custom') {
				$this->output_grid_javascript($load_lightbox, true);
			}

			do_action('essgrid_output_by_custom_post', $this, $grid_preview, $only_elements);
		}
	}

	/**
	 * Output by posts
	 */
	public function output_by_posts($grid_preview = false)
	{
		global $sitepress;

		do_action('essgrid_output_by_posts_pre', $this, $grid_preview);

		$post_limit = 99999;

		$base = new Essential_Grid_Base();
		$navigation_c = new Essential_Grid_Navigation($this->grid_id);
		$meta_c = new Essential_Grid_Meta();
		$meta_link_c = new Essential_Grid_Meta_Linking();
		$item_skin = new Essential_Grid_Item_Skin();
		$item_skin->grid_id = $this->grid_id;
		$item_skin->grid_params = $this->grid_params;
		$item_skin->set_grid_type($base->getVar($this->grid_params, 'layout', 'even'));

		$item_skin->set_default_image_by_id($base->getVar($this->grid_postparams, 'default-image', 0, 'i'));
		$item_skin->set_default_youtube_image_by_id($base->getVar($this->grid_postparams, 'youtube-default-image', 0, 'i'));
		$item_skin->set_default_vimeo_image_by_id($base->getVar($this->grid_postparams, 'vimeo-default-image', 0, 'i'));
		$item_skin->set_default_html_image_by_id($base->getVar($this->grid_postparams, 'html-default-image', 0, 'i'));

		// 2.1.6.2
		$item_skin->set_grid_item_animation($base, $this->grid_params);
		$m = new Essential_Grid_Meta();
		$skins_html = '';
		$skins_css = '';

		$rows_unlimited = $base->getVar($this->grid_params, 'rows-unlimited', 'on');
		$load_more = $base->getVar($this->grid_params, 'load-more', 'none');
		$load_more_start = $base->getVar($this->grid_params, 'load-more-start', 3, 'i');

		// grid_preview means disable load more in preview
		if ($rows_unlimited == 'on' && $load_more !== 'none' && !$grid_preview) { 
			$post_limit = $load_more_start;
		}

		$start_sortby = $base->getVar($this->grid_params, 'sorting-order-by-start', 'none');
		$start_sortby_type = $base->getVar($this->grid_params, 'sorting-order-type', 'ASC');
		$post_category = $base->getVar($this->grid_postparams, 'post_category');
		$post_types = $base->getVar($this->grid_postparams, 'post_types');
		$page_ids = explode(',', $base->getVar($this->grid_postparams, 'selected_pages', '-1'));
		$cat_relation = $base->getVar($this->grid_postparams, 'category-relation', 'OR');
		$max_entries = $this->get_maximum_entries($this);
		$additional_query = $base->getVar($this->grid_postparams, 'additional-query');
		if ($additional_query !== '')
			$additional_query = wp_parse_args($additional_query);

		$cat_tax = array(
			'cats' => '',
			'tax' => ''
		);

		if ($this->custom_posts !== null) { 
			// output by specific set posts
			$posts = Essential_Grid_Base::get_posts_by_ids($this->custom_posts, $start_sortby, $start_sortby_type);
			$cat_tax_obj = Essential_Grid_Base::get_categories_by_posts($posts);
			if (!empty($cat_tax_obj)) {
				$cat_tax['cats'] = Essential_Grid_Base::translate_categories_to_string($cat_tax_obj);
			}
		} elseif ($this->custom_special !== null) { 
			// output by some special rule
			$max_entries = intval($base->getVar($this->grid_params, 'max-entries', '20'));
			if (!$max_entries) $max_entries = 20;
	
			switch ($this->custom_special) {
				case 'related':
					$related_by = $base->getVar($this->grid_params, 'relatedbased', 'both');
					$posts = Essential_Grid_Base::get_related_posts($max_entries, $related_by);
					break;
					
				case 'popular':
					$posts = Essential_Grid_Base::get_wp_posts($max_entries,"any", "comment_count","popular");
					break;
					
				case 'latest':
					$posts = Essential_Grid_Base::get_wp_posts( $max_entries, "any", "date", "latest" );
					break;
					
				default:
					$posts = apply_filters('essgrid_get_posts_custom_special', array(), $this->custom_special, $this);
			}

			$cat_tax_obj = Essential_Grid_Base::get_categories_by_posts($posts);
			if (!empty($cat_tax_obj)) {
				$cat_tax['cats'] = Essential_Grid_Base::translate_categories_to_string($cat_tax_obj);
			}
		} else { 
			// output with the grid settings from an existing grid
			$cat_tax = Essential_Grid_Base::getCatAndTaxData($post_category);
			$posts = Essential_Grid_Base::getPostsByCategory($this->grid_id, $cat_tax['cats'], $post_types, $cat_tax['tax'], $page_ids, $start_sortby, $start_sortby_type, $max_entries, $additional_query, true, $cat_relation);
		}

		$nav_layout = $base->getVar($this->grid_params, 'navigation-layout', array());
		$filter_allow = $base->getVar($this->grid_params, 'filter-arrows', 'single');
		$filter_start = $base->getVar($this->grid_params, 'filter-start');

		$nav_type = $base->getVar($this->grid_params, 'nagivation-type', 'internal');
		$do_nav = $nav_type == 'internal';

		$order_by = explode(',', $base->getVar($this->grid_params, 'sorting-order-by', 'date'));
		if (!is_array($order_by)) $order_by = array($order_by);
		
		$order_by_start = $base->getVar($this->grid_params, 'sorting-order-by-start', 'none');
		if (strpos($order_by_start, 'eg-') === 0 || strpos($order_by_start, 'egl-') === 0) { 
			//add meta at the end for meta sorting
			//if essential Meta, replace to meta name. Else -> replace - and _ with space, set each word uppercase
			$metas = $m->get_all_meta();
			$f = false;
			if (!empty($metas)) {
				foreach ($metas as $meta) {
					if ('eg-' . $meta['handle'] == $order_by_start || 'egl-' . $meta['handle'] == $order_by_start) {
						$f = true;
						$order_by_start = $meta['name'];
						break;
					}
				}
			}

			if ($f === false) {
				$order_by_start = ucwords(str_replace(array(
					'-',
					'_'
				), array(
					' ',
					' '
				), $order_by_start));
			}
		}

		$sort_by_text = $base->getVar($this->grid_params, 'sort-by-text', esc_attr__('Sort By ', ESG_TEXTDOMAIN));
		$search_text = $base->getVar($this->grid_params, 'search-text', esc_attr__('Search...', ESG_TEXTDOMAIN));

		$module_spacings = $base->getVar($this->grid_params, 'module-spacings', '5');

		$top_1_align = $base->getVar($this->grid_params, 'top-1-align', 'center');
		$top_2_align = $base->getVar($this->grid_params, 'top-2-align', 'center');
		$bottom_1_align = $base->getVar($this->grid_params, 'bottom-1-align', 'center');
		$bottom_2_align = $base->getVar($this->grid_params, 'bottom-2-align', 'center');

		$top_1_margin = $base->getVar($this->grid_params, 'top-1-margin-bottom', 0, 'i');
		$top_2_margin = $base->getVar($this->grid_params, 'top-2-margin-bottom', 0, 'i');
		$bottom_1_margin = $base->getVar($this->grid_params, 'bottom-1-margin-top', 0, 'i');
		$bottom_2_margin = $base->getVar($this->grid_params, 'bottom-2-margin-top', 0, 'i');

		$left_margin = $base->getVar($this->grid_params, 'left-margin-left', 0, 'i');
		$right_margin = $base->getVar($this->grid_params, 'right-margin-right', 0, 'i');

		$nav_styles['top-1'] = array(
			'margin-bottom' => $top_1_margin . 'px',
			'text-align' => $top_1_align
		);
		$nav_styles['top-2'] = array(
			'margin-bottom' => $top_2_margin . 'px',
			'text-align' => $top_2_align
		);
		$nav_styles['left'] = array(
			'margin-left' => $left_margin . 'px'
		);
		$nav_styles['right'] = array(
			'margin-right' => $right_margin . 'px'
		);
		$nav_styles['bottom-1'] = array(
			'margin-top' => $bottom_1_margin . 'px',
			'text-align' => $bottom_1_align
		);
		$nav_styles['bottom-2'] = array(
			'margin-top' => $bottom_2_margin . 'px',
			'text-align' => $bottom_2_align
		);

		$ajax_container_position = $base->getVar($this->grid_params, 'ajax-container-position', 'top');
		if ($do_nav) { //only do if internal is selected
			$navigation_c->set_special_class('esg-fgc-' . $this->grid_id);

			$filters_meta = array();
			$filters_extra = array();
			$filters_arr = array();

			foreach ($this->grid_params as $gkey => $gparam) {
				if (strpos($gkey, 'filter-selected') === false) continue;

				$fil_id = intval(str_replace('filter-selected-', '', $gkey));
				$fil_id = ($fil_id == 0) ? '' : '-' . $fil_id;

				$filters_arr['filter' . $fil_id]['filter-grouping'] = $base->getVar($this->grid_params, 'filter-grouping' . $fil_id, 'false');
				$filters_arr['filter' . $fil_id]['filter-listing'] = $base->getVar($this->grid_params, 'filter-listing' . $fil_id, 'list');
				$filters_arr['filter' . $fil_id]['filter-selected'] = $base->getVar($this->grid_params, 'filter-selected' . $fil_id, array());
				$filters_arr['filter' . $fil_id]['filter-sort-alpha'] = $base->getVar($this->grid_params, 'filter-sort-alpha' . $fil_id, 'off');
				$filters_arr['filter' . $fil_id]['filter-sort-alpha-dir'] = $base->getVar($this->grid_params, 'filter-sort-alpha-dir' . $fil_id, 'asc');

				$filterall_visible = $base->getVar($this->grid_params, 'filter-all-visible' . $fil_id, 'on');
				$filter_all_text = $base->getVar($this->grid_params, 'filter-all-text' . $fil_id, esc_attr__('Filter - All', ESG_TEXTDOMAIN));
				$filter_dropdown_text = $base->getVar($this->grid_params, 'filter-dropdown-text' . $fil_id, esc_attr__('Filter Categories', ESG_TEXTDOMAIN));
				$show_count = $base->getVar($this->grid_params, 'filter-counter' . $fil_id, 'off');

				if (!empty($filters_arr['filter' . $fil_id]['filter-selected'])) {
					if (!empty($posts) && count($posts) > 0) {
						foreach ($filters_arr['filter' . $fil_id]['filter-selected'] as $fk => $filter) {
							if (strpos($filter, 'meta-') === 0) {
								unset($filters_arr['filter' . $fil_id]['filter-selected'][$fk]); //delete entry

								$fil = str_replace('meta-', '', $filter);
								foreach ($posts as $post) {
									$post_filter_meta = $meta_c->get_meta_value_by_handle($post['ID'], 'eg-' . $fil, false);
									if ($post_filter_meta == '') { //check if we are linking
										$post_filter_meta = $meta_link_c->get_link_meta_value_by_handle($post['ID'], 'egl-' . $fil);
									}
									$arr = json_decode($post_filter_meta, true);
									$cur_filter = (is_array($arr)) ? $arr : array(
										$post_filter_meta
									);
									$add_filter = array();
									if (!empty($cur_filter)) {
										foreach ($cur_filter as $v) {
											if (trim($v) !== '') {
												$_v = Essential_Grid_Base::sanitize_utf8_to_unicode($v);
												$add_filter[$_v] = array(
													'name' => $v,
													'slug' => $_v,
													'parent' => '0'
												);
												if (!empty($filters_arr['filter' . $fil_id]['filter-selected'])) {
													$filter_found = false;
													foreach ($filters_arr['filter' . $fil_id]['filter-selected'] as $fcheck) {
														if ($fcheck == $_v) {
															$filter_found = true;
															break;
														}
													}
													if (!$filter_found) {
														$filters_arr['filter' . $fil_id]['filter-selected'][] = $_v; //add found meta
													}
												} else {
													$filters_arr['filter' . $fil_id]['filter-selected'][] = $_v; //add found meta
												}
											}
										}
										$filters_meta = $filters_meta + $add_filter;

										if (!empty($add_filter)) {
											$navigation_c->set_filter($add_filter);
										}
									}
								}

								$meta_data = $meta_c->get_meta_by_handle($fil);
								if (in_array($meta_data['type'], array('select', 'multi-select'))) {
									//restore original meta values order
									$original_values = explode(',', $meta_data['select']);
									foreach ($original_values as $v) {
										$_v = Essential_Grid_Base::sanitize_utf8_to_unicode($v);
										if (($key = array_search($_v, $filters_arr['filter' . $fil_id]['filter-selected'])) !== false) {
											unset($filters_arr['filter' . $fil_id]['filter-selected'][$key]);
											$filters_arr['filter' . $fil_id]['filter-selected'][] = $_v;
										}
									}
								}
							}
						}
					}
					$filters_extra = array_merge($filters_arr['filter' . $fil_id]['filter-selected'], $filters_extra);
				}
				$navigation_c->set_filter_settings('filter' . $fil_id, $filters_arr['filter' . $fil_id]);
				$navigation_c->set_filter_text($filter_all_text, $fil_id);
				$navigation_c->set_filterall_visible($filterall_visible, $fil_id);
				$navigation_c->set_dropdown_text($filter_dropdown_text, $fil_id);
				$navigation_c->set_show_count($show_count, $fil_id);
			}
			$navigation_c->set_filter_type($filter_allow);
			$navigation_c->set_filter_start_select($filter_start);
			$navigation_c->set_specific_styles($nav_styles);
			$navigation_c->set_layout($nav_layout); //set the layout
			$navigation_c->set_orders($order_by); //set order of sort
			$navigation_c->set_orders_text($sort_by_text); //set text of sort
			$navigation_c->set_orders_start($order_by_start); //set start of sort
			$navigation_c->set_orders_order($start_sortby_type); //set order of sort
			$navigation_c->set_search_text($search_text);
		}

		$nav_filters = array();
		$taxes = array('post_tag');
		if (!empty($cat_tax['tax'])) $taxes = explode(',', $cat_tax['tax']);
		if (!empty($cat_tax['cats'])) {
			$cats = explode(',', $cat_tax['cats']);
			foreach ($cats as $id) {
				if (Essential_Grid_Wpml::is_wpml_exists() && isset($sitepress)) {
					$new_id = icl_object_id($id, 'category', true, $sitepress->get_default_language());
					$cat = get_category($new_id);
				} else {
					$cat = get_category($id);
				}
				if (is_object($cat)) {
					$nav_filters[$id] = array(
						'name' => $cat->cat_name,
						'slug' => Essential_Grid_Base::sanitize_utf8_to_unicode($cat->slug),
						'parent' => $cat->category_parent
					);
				}
				foreach ($taxes as $custom_tax) {
					$term = get_term_by('id', $id, $custom_tax);
					if (is_object($term))
						$nav_filters[$id] = array(
							'name' => $term->name,
							'slug' => Essential_Grid_Base::sanitize_utf8_to_unicode($term->slug),
							'parent' => $term->parent
						);
				}
			}

			if (!empty($filters_meta)) {
				$nav_filters = $filters_meta + $nav_filters;
			}
			if (!empty($add_filter)) {
				$nav_filters = $nav_filters + $add_filter;
			}
			asort($nav_filters);
		}

		$item_skin->init_by_id($base->getVar($this->grid_params, 'entry-skin', 0, 'i'));
		$this->grid_params['entry-skin-handle'] = $item_skin->get_handle();

		$lazy_load = $base->getVar($this->grid_params, 'lazy-loading', 'off');
		if ($lazy_load == 'on') {
			$item_skin->set_lazy_load(true);
			$lazy_load_blur = $base->getVar($this->grid_params, 'lazy-loading-blur', 'on');
			if ($lazy_load_blur == 'on')
				$item_skin->set_lazy_load_blur(true);
		}

		$default_media_source_order = $base->getVar($this->grid_postparams, 'media-source-order');
		$item_skin->set_default_media_source_order($default_media_source_order);

		$default_lightbox_source_order = $base->getVar($this->grid_params, 'lb-source-order');
		$item_skin->set_default_lightbox_source_order($default_lightbox_source_order);

		$default_aj_source_order = $base->getVar($this->grid_params, 'aj-source-order');
		$item_skin->set_default_ajax_source_order($default_aj_source_order);

		$lightbox_mode = $base->getVar($this->grid_params, 'lightbox-mode', 'single');
		$lightbox_include_media = $base->getVar($this->grid_params, 'lightbox-exclude-media', 'off');

		/* 2.2 */
		$item_skin->set_fancybox_three_options($base->getVar($this->grid_params, 'lightbox-title', 'off'));
		$post_media_source_type = $base->getVar($this->grid_postparams, 'image-source-type', 'full');
		
		/* 2.1.6 */
		if (wp_is_mobile()) {
			$post_media_source_type = $base->getVar($this->grid_postparams, 'image-source-type-mobile', $post_media_source_type);
		}
		$default_video_poster_order = $base->getVar($this->grid_params, 'poster-source-order');
		if ($default_video_poster_order == '')
			$default_video_poster_order = $base->getVar($this->grid_postparams, 'poster-source-order');
		$item_skin->set_default_video_poster_order($default_video_poster_order);

		$layout = $base->getVar($this->grid_params, 'layout', 'even');
		$layout_sizing = $base->getVar($this->grid_params, 'layout-sizing', 'boxed');

		if ($layout_sizing !== 'fullwidth' && $layout == 'masonry') {
			$item_skin->set_poster_cropping(true);
		}

		$found_filter = array();
		$i = 1;

		if (!empty($posts) && count($posts) > 0) {

			$media_sources = array_merge(
				(array)$default_media_source_order,
				(array)$default_lightbox_source_order,
				(array)$default_aj_source_order
			);
			$media_sources = array_unique(array_filter($media_sources));

			$image_sizes = false;
			$image_source_smart = $base->getVar($this->grid_postparams, 'image-source-smart', 'off');
			if ('on' === $image_source_smart) {
				$image_sizes = $base->getVar($this->grid_postparams, 'image-source-smart-size', false);
			}

			foreach ($posts as $post) {
				// check if post should be visible or if its invisible on current grid settings
				if (!$grid_preview && !$this->check_if_visible($post['ID'], $this->grid_id)) continue;

				if ($lightbox_mode == 'content' || $lightbox_mode == 'content-gallery' || $lightbox_mode == 'woocommerce-gallery') {
					$item_skin->set_lightbox_rel('ess-' . $post['ID']);
				}
				
				$post_media_source_data = $base->get_post_media_source_data($post['ID'], $post_media_source_type, $media_sources, $image_sizes, $post); //PLURA
				$post_video_ratios = $m->get_post_video_ratios($post['ID']);
				$filters = array();

				$default_filter_add = $base->getVar($this->grid_params, 'add-filters-by', 'default');
				if (in_array($default_filter_add, array('default', 'categories'), true)) {
					$categories = $base->get_custom_taxonomies_by_post_id($post['ID']);
					if (!empty($categories)) {
						foreach ($categories as $category) {
							$filters[$category->term_id] = array(
								'name' => $category->name,
								'slug' => Essential_Grid_Base::sanitize_utf8_to_unicode($category->slug),
								'parent' => $category->parent
							);
						}
					}
				}

				if (in_array($default_filter_add, array('default', 'tags'), true)) {
					$tags = get_the_tags($post['ID']);
					if (!empty($tags)) {
						foreach ($tags as $taxonomie) {
							$filters[$taxonomie->term_id] = array(
								'name' => $taxonomie->name,
								'slug' => Essential_Grid_Base::sanitize_utf8_to_unicode($taxonomie->slug),
								'parent' => '0'
							);
						}
					}
				}

				foreach ($this->grid_params as $gp_handle => $gp_values) {
					if (strpos($gp_handle, 'filter-selected') !== 0) continue;

					$filter_meta_selected = $base->getVar($this->grid_params, $gp_handle, array());
					if (!empty($filter_meta_selected)) {
						foreach ($filter_meta_selected as $filter) {
							if (strpos($filter, 'meta-') === 0) {
								$fil = str_replace('meta-', '', $filter);
								$post_filter_meta = $meta_c->get_meta_value_by_handle($post['ID'], 'eg-' . $fil, false);
								if ($post_filter_meta == '') { 
									// check if we are linking
									$post_filter_meta = $meta_link_c->get_link_meta_value_by_handle($post['ID'], 'egl-' . $fil);
								}

								$arr = json_decode($post_filter_meta, true);
								$cur_filter = (is_array($arr)) ? $arr : array(
									$post_filter_meta
								);
								if (!empty($cur_filter)) {
									foreach ($cur_filter as $v) {
										if (trim($v) !== '') {
											$_v = Essential_Grid_Base::sanitize_utf8_to_unicode($v);
											$filters[$_v] = array(
												'name' => $v,
												'slug' => $_v,
												'parent' => '0'
											);
										}
									}
								}
							}
						}
					}
				}

				if (is_array($order_by) && !empty($order_by)) {
					$sort = $this->prepare_sorting_array_by_post($post, $order_by);
					$item_skin->set_sorting($sort);
				}

				// these are the found filters, only show filter that the posts have
				$found_filter = $found_filter + $filters; 

				// switch to different skin
				$use_item_skin_id = json_decode(get_post_meta($post['ID'], 'eg_use_skin', true), true);
				if ($use_item_skin_id !== false && isset($use_item_skin_id[$this->grid_id]['use-skin'])) {
					$use_item_skin_id = $use_item_skin_id[$this->grid_id]['use-skin'];
				} else {
					$use_item_skin_id = -1;
				}
				$use_item_skin_id = apply_filters('essgrid_modify_post_item_skin', $use_item_skin_id, $post, $this->grid_id);
				$item_skin->switch_item_skin($use_item_skin_id);
				$item_skin->register_layer_css();
				$item_skin->register_skin_css();

				if ($i > $post_limit) {
					// Load only selected numbers of items at start (for load more)
					// set for load more, only on elements that will not be loaded from beginning
					$this->load_more_post_array[$post['ID']] = $filters; 
					continue; 
				}
				$i++;

				$this->_is_lightbox_additions($lightbox_mode, $lightbox_include_media, $item_skin, $post);

				$item_skin->set_filter($filters);
				$item_skin->set_media_sources($post_media_source_data);
				$item_skin->set_media_sources_type($post_media_source_type);
				$item_skin->set_video_ratios($post_video_ratios);
				$item_skin->set_post_values($post);
				if (isset($post['post_title'])) $item_skin->set_item_title($post['post_title']);

				ob_start();
				$item_skin->output_item_skin($grid_preview);
				$skins_html .= ob_get_contents();
				ob_clean();
				ob_end_clean();

				// 2.2.6
				ob_start();
				$item_skin->output_element_css_by_meta($post['ID']);
				$skins_css .= ob_get_contents();
				ob_clean();
				ob_end_clean();
			}
		} else {
			if (is_admin()) {
				$this->display_grid_error_msg();
			}
			/* 3.0.13 comment out as this output grid based on defaults while it should be empty on frontend
			 * else {
				require_once(ESG_PLUGIN_PATH . 'includes/defaults.php');
				$defaults = new EssentialGridDefaults();
				$defaults = $defaults->getData($this->grid_id);
				$this->init_by_data($defaults);
				$this->output_by_posts();
			}*/

			$no_items_msg = apply_filters('essgrid_output_by_posts_no_items', '', $this);
			if (!empty($no_items_msg)) {
				echo $no_items_msg;
			}
			
			return false;
		}

		if (!empty($filters_extra)) {
			foreach ($filters_extra as $f_extra) {
				$f_extra = explode('_', $f_extra);
				if (is_array($f_extra) && !empty($f_extra)) {
					$cid = end($f_extra);
					if (Essential_Grid_Wpml::is_wpml_exists() && isset($sitepress)) {
						$new_id = icl_object_id($cid, 'category', true, $sitepress->get_default_language());
						$ncat = get_category($new_id);
						if (!is_wp_error($ncat) && !is_null($ncat)) {
							$found_filter[$ncat->term_id] = array(
								'name' => $ncat->name,
								'slug' => $ncat->slug,
								'parent' => $ncat->{'parent'}
							);
							$nav_filters[$ncat->term_id] = array(
								'name' => $ncat->name,
								'slug' => $ncat->slug,
								'parent' => $ncat->{'parent'}
							);
						}
					} else {
						/* 2.1.5 */
						$ncat = get_category($cid);
						if (empty($ncat))
							$ncat = get_tag($cid);
						if (!is_wp_error($ncat) && !empty($ncat)) {
							$found_filter[$ncat->term_id] = array(
								'name' => $ncat->name,
								'slug' => $ncat->slug,
								'parent' => $ncat->{'parent'}
							);
							$nav_filters[$ncat->term_id] = array(
								'name' => $ncat->name,
								'slug' => $ncat->slug,
								'parent' => $ncat->{'parent'}
							);
						}
					}
				}
			}
		}

		$remove_filter = array_diff_key($nav_filters, $found_filter); //check if we have filter that no post has (comes through multilanguage)
		if (!empty($remove_filter)) {
			foreach ($remove_filter as $key => $rem) { //we have, so remove them from the filter list before setting the filter list
				unset($nav_filters[$key]);
			}
		}

		if ($do_nav) { //only do if internal is selected
			$navigation_c->set_filter($nav_filters); //set filters $nav_filters $found_filter
			$navigation_c->set_filter_type($filter_allow);
			$navigation_c->set_filter_start_select($filter_start);
		}

		ob_start();
		$item_skin->generate_element_css();
		$skins_css .= ob_get_contents();
		ob_clean();
		ob_end_clean();

		$load_lightbox = $item_skin->do_lightbox_loading();
		if ($load_lightbox) {
			echo $item_skin->output_lighbox_css($this->grid_div_name, $this->grid_params);
		}

		if ($do_nav) { //only do if internal is selected
			$found_skin = array();
			$navigation_skin = $base->getVar($this->grid_params, 'navigation-skin', 'minimal-light');
			$navigation_special_skin = $base->getVar($this->grid_params, 'navigation-special-skin', array());
			ob_start();
			echo $navigation_c->output_navigation_skin($navigation_skin);
			$found_skin[$navigation_skin] = true;

			if (!empty($navigation_special_skin)) {
				foreach ($navigation_special_skin as $spec_skin) {
					if (!isset($found_skin[$spec_skin])) {
						echo $navigation_c->output_navigation_skin($spec_skin);
						$found_skin[$spec_skin] = true;
					}
				}
			}
			$nav_css = ob_get_contents();
			ob_clean();
			ob_end_clean();

			echo $nav_css;
		}

		echo $skins_css;

		if ($item_skin->ajax_loading && $ajax_container_position == 'top') {
			echo $this->output_ajax_container();
		}

		$this->output_wrapper_pre($grid_preview);
		if ($do_nav) { //only do if internal is selected
			$navigation_c->output_layout('top-1', $module_spacings);
			$navigation_c->output_layout('top-2', $module_spacings);
		}
		$this->output_grid_pre();
		echo $skins_html;
		$this->output_grid_post();
		if ($do_nav) { //only do if internal is selected
			$navigation_c->output_layout('bottom-1', $module_spacings);
			$navigation_c->output_layout('bottom-2', $module_spacings);
			$navigation_c->output_layout('left');
			$navigation_c->output_layout('right');

			//check if search was added. If yes, we also need to add the "Filter All" filter if not existing
			echo $navigation_c->check_for_search();
		}

		$this->output_wrapper_post();

		if ($item_skin->ajax_loading && $ajax_container_position == 'bottom') {
			echo $this->output_ajax_container();
		}

		$load_lightbox = $item_skin->do_lightbox_loading();

		if ($grid_preview === false) {
			$this->output_grid_javascript($load_lightbox);
		} elseif ($grid_preview !== 'preview') {
			$this->output_grid_javascript($load_lightbox, true);
		}

		do_action('essgrid_output_by_posts_post', $this, $grid_preview);
	}

	/**
	 * Output by specific posts for load more
	 */
	public function output_by_specific_posts()
	{
		do_action('essgrid_output_by_specific_posts_pre', $this);

		$base = new Essential_Grid_Base();
		$item_skin = new Essential_Grid_Item_Skin();
		$item_skin->grid_id = $this->grid_id;
		$item_skin->grid_params = $this->grid_params;
		$item_skin->set_grid_type($base->getVar($this->grid_params, 'layout', 'even'));
		$meta_c = new Essential_Grid_Meta();
		$meta_link_c = new Essential_Grid_Meta_Linking();
		
		$item_skin->set_default_image_by_id($base->getVar($this->grid_postparams, 'default-image', 0, 'i'));
		$item_skin->set_default_youtube_image_by_id($base->getVar($this->grid_postparams, 'youtube-default-image', 0, 'i'));
		$item_skin->set_default_vimeo_image_by_id($base->getVar($this->grid_postparams, 'vimeo-default-image', 0, 'i'));
		$item_skin->set_default_html_image_by_id($base->getVar($this->grid_postparams, 'html-default-image', 0, 'i'));

		// 2.1.6.2
		$item_skin->set_grid_item_animation($base, $this->grid_params);
		$m = new Essential_Grid_Meta();
		$start_sortby = $base->getVar($this->grid_params, 'sorting-order-by-start', 'none');
		$start_sortby_type = $base->getVar($this->grid_params, 'sorting-order-type', 'ASC');
		if (!empty($this->filter_by_ids)) {
			$posts = Essential_Grid_Base::get_posts_by_ids($this->filter_by_ids, $start_sortby, $start_sortby_type);
		} else {
			return false;
		}

		$item_skin->init_by_id($base->getVar($this->grid_params, 'entry-skin', 0, 'i'));
		$this->grid_params['entry-skin-handle'] = $item_skin->get_handle();
		
		$order_by = explode(',', $base->getVar($this->grid_params, 'sorting-order-by', 'date'));
		if (!is_array($order_by)) $order_by = array($order_by);

		$lazy_load = $base->getVar($this->grid_params, 'lazy-loading', 'off');
		if ($lazy_load == 'on') {
			$item_skin->set_lazy_load(true);
			$lazy_load_blur = $base->getVar($this->grid_params, 'lazy-loading-blur', 'on');
			if ($lazy_load_blur == 'on') $item_skin->set_lazy_load_blur(true);
		}

		$default_media_source_order = $base->getVar($this->grid_postparams, 'media-source-order');
		$item_skin->set_default_media_source_order($default_media_source_order);

		$default_lightbox_source_order = $base->getVar($this->grid_params, 'lb-source-order');
		$item_skin->set_default_lightbox_source_order($default_lightbox_source_order);

		$lightbox_mode = $base->getVar($this->grid_params, 'lightbox-mode', 'single');
		$lightbox_include_media = $base->getVar($this->grid_params, 'lightbox-exclude-media', 'off');

		/* 2.2 */
		$item_skin->set_fancybox_three_options($base->getVar($this->grid_params, 'lightbox-title', 'off'));
		$default_aj_source_order = $base->getVar($this->grid_params, 'aj-source-order');
		$item_skin->set_default_ajax_source_order($default_aj_source_order);
		$post_media_source_type = $base->getVar($this->grid_postparams, 'image-source-type', 'full');

		/* 2.1.6 */
		if (wp_is_mobile()) {
			$post_media_source_type = $base->getVar($this->grid_postparams, 'image-source-type-mobile', $post_media_source_type);
		}

		$default_video_poster_order = $base->getVar($this->grid_params, 'poster-source-order');
		if ($default_video_poster_order == '')
			$default_video_poster_order = $base->getVar($this->grid_postparams, 'poster-source-order');

		$item_skin->set_default_video_poster_order($default_video_poster_order);

		$layout = $base->getVar($this->grid_params, 'layout', 'even');
		$layout_sizing = $base->getVar($this->grid_params, 'layout-sizing', 'boxed');

		if ($layout_sizing !== 'fullwidth' && $layout == 'masonry') {
			$item_skin->set_poster_cropping(true);
		}

		$skins_html = '';
		
		if (!empty($posts) && count($posts) > 0) {

			$media_sources = array_merge(
				(array)$default_media_source_order,
				(array)$default_lightbox_source_order,
				(array)$default_aj_source_order
			);
			$media_sources = array_unique(array_filter($media_sources));

			$image_sizes = false;
			$image_source_smart = $base->getVar($this->grid_postparams, 'image-source-smart', 'off');
			if ('on' === $image_source_smart) {
				$image_sizes = $base->getVar($this->grid_postparams, 'image-source-smart-size', false);
			}
			
			foreach ($posts as $post) {
				// check if post should be visible or if its invisible on current grid settings
				$is_visible = $this->check_if_visible($post['ID'], $this->grid_id);

				if (!$is_visible) continue;

				if ($lightbox_mode == 'content' || $lightbox_mode == 'content-gallery' || $lightbox_mode == 'woocommerce-gallery') {
					$item_skin->set_lightbox_rel('ess-' . $post['ID']);
				}

				$post_media_source_data = $base->get_post_media_source_data($post['ID'], $post_media_source_type, $media_sources, $image_sizes);
				$post_video_ratios = $m->get_post_video_ratios($post['ID']);

				$filters = array();
				$default_filter_add = $base->getVar($this->grid_params, 'add-filters-by', 'default');
				if (in_array($default_filter_add, array('default', 'categories'), true)) {
					$categories = $base->get_custom_taxonomies_by_post_id($post['ID']);
					if (!empty($categories)) {
						foreach ($categories as $category) {
							$filters[$category->term_id] = array(
								'name' => $category->name,
								'slug' => Essential_Grid_Base::sanitize_utf8_to_unicode($category->slug)
							);
						}
					}
				}

				if (in_array($default_filter_add, array('default', 'tags'), true)) {
					$tags = get_the_tags($post['ID']);
					if (!empty($tags)) {
						foreach ($tags as $taxonomie) {
							$filters[$taxonomie->term_id] = array(
								'name' => $taxonomie->name,
								'slug' => Essential_Grid_Base::sanitize_utf8_to_unicode($taxonomie->slug)
							);
						}
					}
				}

				foreach ($this->grid_params as $gp_handle => $gp_values) {
					if (strpos($gp_handle, 'filter-selected') !== 0) continue;

					$filter_meta_selected = $base->getVar($this->grid_params, $gp_handle, array());
					if (!empty($filter_meta_selected)) {
						foreach ($filter_meta_selected as $filter) {
							if (strpos($filter, 'meta-') === 0) {
								$fil = str_replace('meta-', '', $filter);
								$post_filter_meta = $meta_c->get_meta_value_by_handle($post['ID'], 'eg-' . $fil, false);
								if ($post_filter_meta == '') { //check if we are linking
									$post_filter_meta = $meta_link_c->get_link_meta_value_by_handle($post['ID'], 'egl-' . $fil);
								}

								$arr = json_decode($post_filter_meta, true);
								$cur_filter = (is_array($arr)) ? $arr : array(
									$post_filter_meta
								);
								if (!empty($cur_filter)) {
									foreach ($cur_filter as $v) {
										if (trim($v) !== '') {
											$_v = Essential_Grid_Base::sanitize_utf8_to_unicode($v);
											$filters[$_v] = array(
												'name' => $v,
												'slug' => $_v,
												'parent' => '0'
											);
										}
									}
								}
							}
						}
					}
				}

				if (is_array($order_by) && !empty($order_by)) {
					$sort = $this->prepare_sorting_array_by_post($post, $order_by);
					$item_skin->set_sorting($sort);
				}

				$this->_is_lightbox_additions($lightbox_mode, $lightbox_include_media, $item_skin, $post);

				$item_skin->set_filter($filters);
				$item_skin->set_media_sources($post_media_source_data);
				$item_skin->set_media_sources_type($post_media_source_type);
				$item_skin->set_video_ratios($post_video_ratios);
				$item_skin->set_post_values($post);
				$item_skin->set_load_more();
				if (isset($post['post_title'])) $item_skin->set_item_title($post['post_title']);

				//switch to different skin
				$use_item_skin_id = json_decode(get_post_meta($post['ID'], 'eg_use_skin', true), true);
				if ($use_item_skin_id !== false && isset($use_item_skin_id[$this->grid_id]['use-skin'])) {
					$use_item_skin_id = $use_item_skin_id[$this->grid_id]['use-skin'];
				} else {
					$use_item_skin_id = -1;
				}

				$use_item_skin_id = apply_filters('essgrid_modify_post_item_skin', $use_item_skin_id, $post, $this->grid_id);
				$item_skin->switch_item_skin($use_item_skin_id);
				$item_skin->register_layer_css();
				$item_skin->register_skin_css();

				ob_start();
				$item_skin->output_item_skin();
				$skins_html .= ob_get_contents();
				ob_clean();
				ob_end_clean();
			}
		} else {
			$skins_html = apply_filters('essgrid_output_by_specific_posts_no_items', false, $this);
		}

		do_action('essgrid_output_by_specific_posts_post', $this, $skins_html);

		return apply_filters('essgrid_output_by_specific_posts_return', $skins_html, $this);
	}

	/**
	 * Output by specific ids for load more custom grid
	 */
	public function output_by_specific_ids($gal = false)
	{
		do_action('essgrid_output_by_specific_ids_pre', $this);

		$base = new Essential_Grid_Base();
		$item_skin = new Essential_Grid_Item_Skin();
		$item_skin->grid_id = $this->grid_id;
		$item_skin->grid_params = $this->grid_params;
		$item_skin->set_grid_type($base->getVar($this->grid_params, 'layout', 'even'));

		$item_skin->set_default_image_by_id($base->getVar($this->grid_postparams, 'default-image', 0, 'i'));
		$item_skin->set_default_youtube_image_by_id($base->getVar($this->grid_postparams, 'youtube-default-image', 0, 'i'));
		$item_skin->set_default_vimeo_image_by_id($base->getVar($this->grid_postparams, 'vimeo-default-image', 0, 'i'));
		$item_skin->set_default_html_image_by_id($base->getVar($this->grid_postparams, 'html-default-image', 0, 'i'));

		// 2.1.6.2
		$item_skin->set_grid_item_animation($base, $this->grid_params);
		$m = new Essential_Grid_Meta();
		
		$order_by = explode(',', $base->getVar($this->grid_params, 'sorting-order-by', 'date'));
		if (!is_array($order_by))
			$order_by = array(
				$order_by
			);
		$item_skin->init_by_id($base->getVar($this->grid_params, 'entry-skin', 0, 'i'));
		$this->grid_params['entry-skin-handle'] = $item_skin->get_handle();

		$lazy_load = $base->getVar($this->grid_params, 'lazy-loading', 'off');
		if ($lazy_load == 'on') {
			$item_skin->set_lazy_load(true);
			$lazy_load_blur = $base->getVar($this->grid_params, 'lazy-loading-blur', 'on');
			if ($lazy_load_blur == 'on')
				$item_skin->set_lazy_load_blur(true);
		}

		$default_media_source_order = $base->getVar($this->grid_postparams, 'media-source-order');
		$item_skin->set_default_media_source_order($default_media_source_order);

		$default_lightbox_source_order = $base->getVar($this->grid_params, 'lb-source-order');
		$item_skin->set_default_lightbox_source_order($default_lightbox_source_order);

		/* 2.2 */
		$item_skin->set_fancybox_three_options($base->getVar($this->grid_params, 'lightbox-title', 'off'));

		$default_aj_source_order = $base->getVar($this->grid_params, 'aj-source-order');
		$item_skin->set_default_ajax_source_order($default_aj_source_order);

		$post_media_source_type = $base->getVar($this->grid_postparams, 'image-source-type', 'full');

		/* 2.1.6 */
		if (wp_is_mobile()) {
			$post_media_source_type = $base->getVar($this->grid_postparams, 'image-source-type-mobile', $post_media_source_type);
		}

		$default_video_poster_order = $base->getVar($this->grid_params, 'poster-source-order');
		if ($default_video_poster_order == '')
			$default_video_poster_order = $base->getVar($this->grid_postparams, 'poster-source-order');

		$item_skin->set_default_video_poster_order($default_video_poster_order);

		$layout = $base->getVar($this->grid_params, 'layout', 'even');
		$layout_sizing = $base->getVar($this->grid_params, 'layout-sizing', 'boxed');

		if ($layout_sizing !== 'fullwidth' && $layout == 'masonry') {
			$item_skin->set_poster_cropping(true);
		}

		$skins_html = '';

		$found_filter = array();

		$order_by_start = $base->getVar($this->grid_params, 'sorting-order-by-start', 'none');
		$order_by_dir = $base->getVar($this->grid_params, 'sorting-order-type', 'ASC');
		$this->order_by_custom($order_by_start, $order_by_dir);

		if (!empty($this->grid_layers) && count($this->grid_layers) > 0) {

			$image_sizes = false;
			$image_source_smart = $base->getVar($this->grid_postparams, 'image-source-smart', 'off');
			if ('on' === $image_source_smart) {
				$image_sizes = $base->getVar($this->grid_postparams, 'image-source-smart-size', false);
			}
			
			foreach ($this->grid_layers as $key => $entry) {
				if (!in_array($key, $this->filter_by_ids)) continue;

				$post_media_source_data = $base->get_custom_media_source_data($entry, $post_media_source_type, $image_sizes);
				$post_video_ratios = $m->get_custom_video_ratios($entry);
				$filters = array();

				if (is_array($order_by) && !empty($order_by)) {
					$sort = $this->prepare_sorting_array_by_custom($entry, $order_by);
					$item_skin->set_sorting($sort);
				}
				if (!empty($entry['custom-filter'])) {
					$cats = explode(',', $entry['custom-filter']);
					if (!is_array($cats))
						$cats = (array)$cats;
					foreach ($cats as $category) {
						$_v = Essential_Grid_Base::sanitize_utf8_to_unicode($category);
						$filters[$_v] = array(
							'name' => $category,
							'slug' => $_v
						);
					}
				}

				$found_filter = $found_filter + $filters; //these are the found filters, only show filter that the posts have

				$item_skin->set_filter($filters);
				$item_skin->set_media_sources($post_media_source_data);
				$item_skin->set_media_sources_type($post_media_source_type);
				$item_skin->set_video_ratios($post_video_ratios);
				$item_skin->set_layer_values($entry);
				$item_skin->set_load_more();

				//switch to different skin
				$use_item_skin_id = $base->getVar($entry, 'use-skin', '-1');
				if (intval($use_item_skin_id) === 0) {
					$use_item_skin_id = -1;
				}
				$item_skin->switch_item_skin($use_item_skin_id);
				$item_skin->register_layer_css();
				$item_skin->register_skin_css();

				// 2.2.6
				$item_skin->set_post_values($entry);

				if (isset($entry['title'])) $item_skin->set_item_title($entry['title']);

				ob_start();
				$item_skin->output_item_skin();
				$skins_html .= ob_get_contents();
				ob_clean();
				ob_end_clean();

				// 2.2.6
				$id = (isset($entry['post_id'])) ? $entry['post_id'] : '';
				if (!empty($id)) {
					ob_start();
					$item_skin->output_element_css_by_meta($id);
					$skins_html .= ob_get_contents();
					ob_clean();
					ob_end_clean();
				}
			}
		} else {
			$skins_html = apply_filters('essgrid_output_by_specific_ids_no_items', false, $this);
		}

		do_action('essgrid_output_by_specific_ids_post', $this, $skins_html);

		return apply_filters('essgrid_output_by_specific_ids_return', $skins_html, $this);
	}

	public function prepare_sorting_array_by_post($post, $order_by)
	{
		$d = apply_filters('essgrid_prepare_sorting_array_by_post_pre', array(
			'post' => $post,
			'order_by' => $order_by
		));
		$post = $d['post'];
		$order_by = $d['order_by'];

		$base = new Essential_Grid_Base();
		$link_meta = new Essential_Grid_Meta_Linking();
		$meta = new Essential_Grid_Meta();

		$m = $meta->get_all_meta(false);
		$lm = $link_meta->get_all_link_meta();

		$sorts = array();
		foreach ($order_by as $order) {
			switch ($order) {
				case 'date':
					$sorts['date'] = strtotime($base->getVar($post, 'post_date'));
					break;
				case 'title':
					$sorts['title'] = $base->getVar($post, 'post_title');
					$sorts['title'] = (strlen($sorts['title']) > 32) ? substr($sorts['title'], 0, 32) : $sorts['title'];
					break;
				case 'excerpt':
					$sorts['excerpt'] = $base->getVar($post, 'post_excerpt');
					$sorts['excerpt'] = (strlen($sorts['excerpt']) > 32) ? substr($sorts['excerpt'], 0, 32) : $sorts['excerpt'];
					break;
				case 'id':
					$sorts['id'] = $base->getVar($post, 'ID');
					break;
				case 'slug':
					$sorts['slug'] = $base->getVar($post, 'post_name');
					break;
				case 'author':
					$authorID = $base->getVar($post, 'post_author');
					$sorts['author'] = get_the_author_meta('display_name', $authorID);
					break;
				case 'last-modified':
					$sorts['last-modified'] = strtotime($base->getVar($post, 'post_modified'));
					break;
				case 'number-of-comments':
					$sorts['number-of-comments'] = $base->getVar($post, 'comment_count');
					break;
				case 'likespost':
					$post_id = $base->getVar($post, 'ID');
					$like_count = get_post_meta($post_id, "eg_votes_count", 0);
					$sorts['likespost'] = isset($like_count[0]) ? intval($like_count[0]) : 0;
					break;
				case 'random':
					$sorts['random'] = rand(0, 9999);
					break;
				default: //check if meta. If yes, add meta values
					if (strpos($order, 'eg-') === 0) {
						if (!empty($m)) {
							foreach ($m as $me) {
								if ('eg-' . $me['handle'] == $order) {
									$sorts[$order] = $meta->get_meta_value_by_handle($post['ID'], $order);
									break;
								}
							}
						}
					} elseif (strpos($order, 'egl-') === 0) {
						if (!empty($lm)) {
							foreach ($lm as $me) {
								if ('egl-' . $me['handle'] == $order) {
									$sorts[$order] = $link_meta->get_link_meta_value_by_handle($post['ID'], $order);
									break;
								}
							}
						}
					}
					break;
			}
		}

		//add woocommerce sortings
		if (Essential_Grid_Woocommerce::is_woo_exists()) {
			$is_30 = Essential_Grid_Woocommerce::version_check('3.0');
			$product = ($is_30) ? wc_get_product($post['ID']) : get_product($post['ID']);

			if (!empty($product)) {
				foreach ($order_by as $order) {
					switch ($order) {
						case 'meta_num_total_sales':
							$sorts['total-sales'] = get_post_meta($post['ID'], 'total_sales', true);
							break;
						case 'meta_num__regular_price':
							$sorts['regular-price'] = $product->get_price();
							break;
						case 'meta__featured':
							$sorts['featured'] = ($product->is_featured()) ? '1' : '0';
							break;
						case 'meta__sku':
							$sorts['sku'] = $product->get_sku();
							break;
						case 'meta_num_stock':
							$sorts['in-stock'] = $product->get_stock_quantity();
							break;
					}
				}
			}
		}

		return apply_filters('essgrid_prepare_sorting_array_by_post_post', $sorts, $post, $order_by);
	}

	public function prepare_sorting_array_by_custom($post, $order_by)
	{
		$d = apply_filters('essgrid_prepare_sorting_array_by_custom_pre', array(
			'post' => $post,
			'order_by' => $order_by
		));
		$post = $d['post'];
		$order_by = $d['order_by'];

		$base = new Essential_Grid_Base();

		$sorts = array();
		foreach ($order_by as $order) {
			switch ($order) {
				case 'date':
					$sorts['date'] = strtotime($base->getVar($post, 'date', date('Y-m-d H:i:s')));
					break;
				case 'title':
					$sorts['title'] = $base->getVar($post, 'title');
					$sorts['title'] = (strlen($sorts['title']) > 32) ? substr($sorts['title'], 0, 32) : $sorts['title'];
					break;
				case 'excerpt':
					$sorts['excerpt'] = $base->getVar($post, 'excerpt');
					$sorts['excerpt'] = (strlen($sorts['excerpt']) > 32) ? substr($sorts['excerpt'], 0, 32) : $sorts['excerpt'];
					break;
				case 'id':
					$sorts['id'] = $base->getVar($post, 'post_id');
					break;
				case 'slug':
					$sorts['slug'] = $base->getVar($post, 'alias');
					break;
				case 'author':
					$sorts['author'] = $base->getVar($post, 'author_name');
					break;
				case 'last-modified':
					$sorts['last-modified'] = strtotime($base->getVar($post, 'date_modified'));
					break;
				case 'number-of-comments':
					$sorts['number-of-comments'] = $base->getVar($post, 'num_comments');
					break;
				case 'random':
					$sorts['random'] = rand(0, 9999);
					break;
				case 'views':
					$sorts['views'] = $base->getVar($post, 'views');
					break;
				case 'likespost':
					$post_id = $base->getVar($post, 'ID');
					$like_count = get_post_meta($post_id, "eg_votes_count", 0);
					$sorts['likespost'] = isset($like_count[0]) ? $like_count[0] : 0;
					break;
				case 'likes':
					$sorts['likes'] = $base->getVar($post, 'likes');
					break;
				case 'dislikes':
					$sorts['dislikes'] = $base->getVar($post, 'dislikes');
					break;
				case 'retweets':
					$sorts['retweets'] = $base->getVar($post, 'retweets');
					break;
				case 'favorites':
					$sorts['favorites'] = $base->getVar($post, 'favorites');
					break;
				case 'itemCount':
					$sorts['itemCount'] = $base->getVar($post, 'itemCount');
					break;
				case 'duration':
					$sorts['duration'] = $base->getVar($post, 'duration');
					break;
				default: //check if meta. If yes, add meta values
					if (strpos($order, 'eg-') === 0 || strpos($order, 'egl-') === 0) {
						$sorts[$order] = $base->getVar($post, $order);
					}
					break;
			}
		}
		return apply_filters('essgrid_prepare_sorting_array_by_custom_post', $sorts, $post, $order_by);
	}

	public function prepare_sorting_array_by_stream($post, $order_by)
	{
		$d = apply_filters('essgrid_prepare_sorting_array_by_stream_pre', array(
			'post' => $post,
			'order_by' => $order_by
		));
		$post = $d['post'];
		$order_by = $d['order_by'];

		$base = new Essential_Grid_Base();

		$sorts = array();
		foreach ($order_by as $order) {
			switch ($order) {
				case 'date':
					$sorts['date'] = strtotime($base->getVar($post, 'date'));
					break;
				case 'title':
					$sorts['title'] = $base->getVar($post, 'title');
					$sorts['title'] = (strlen($sorts['title']) > 32) ? substr($sorts['title'], 0, 32) : $sorts['title'];
					break;
				case 'excerpt':
					$sorts['excerpt'] = $base->getVar($post, 'excerpt');
					$sorts['excerpt'] = (strlen($sorts['excerpt']) > 32) ? substr($sorts['excerpt'], 0, 32) : $sorts['excerpt'];
					break;
				case 'id':
					$sorts['id'] = $base->getVar($post, 'post_id');
					break;
				case 'slug':
					$sorts['slug'] = $base->getVar($post, 'alias');
					break;
				case 'author':
					$sorts['author'] = $base->getVar($post, 'author_name');
					break;
				case 'last-modified':
					$sorts['last-modified'] = strtotime($base->getVar($post, 'date_modified'));
					break;
				case 'number-of-comments':
					$sorts['number-of-comments'] = $base->getVar($post, 'num_comments');
					break;
				case 'random':
					$sorts['random'] = rand(0, 9999);
					break;
				case 'likespost':
					$post_id = $base->getVar($post, 'ID');
					$like_count = get_post_meta($post_id, "eg_votes_count", 0);
					$sorts['likespost'] = isset($like_count[0]) ? $like_count[0] : 0;
					break;
				case 'views':
					$sorts['views'] = $base->getVar($post, 'views');
					break;
				default: 
					// check if meta. If yes, add meta values
					if (strpos($order, 'eg-') === 0 || strpos($order, 'egl-') === 0) {
						$sorts[$order] = $base->getVar($post, $order);
					}
					break;
			}
		}
		return apply_filters('essgrid_prepare_sorting_array_by_stream_post', $sorts, $post, $order_by);
	}

	public function output_wrapper_pre($grid_preview = false)
	{
		global $esg_grid_serial;
		$esg_grid_serial++;
		
		$base = new Essential_Grid_Base();
		if ($this->grid_div_name === null) $this->grid_div_name = $this->grid_id;

		$grid_id = ($grid_preview !== false) ? 'esg-preview-grid' : 'esg-grid-' . $this->grid_div_name . '-' . $esg_grid_serial;
		$grid_id_wrap = $grid_id . '-wrap';
		$article_id = ($grid_preview !== false) ? ' esg-preview-skinlevel' : '';

		$hide_markup_before_load = $base->getVar($this->grid_params, 'hide-markup-before-load', 'off');
		$background_color = $base->getVar($this->grid_params, 'main-background-color', 'transparent');
		$navigation_skin = $base->getVar($this->grid_params, 'navigation-skin', 'minimal-light');
		$paddings = $base->getVar($this->grid_params, 'grid-padding', 0);
		$css_id = $base->getVar($this->grid_params, 'css-id');
		$source_type = $base->getVar($this->grid_postparams, 'source-type', 'post');
		$entry_skin_handle = $base->getVar($this->grid_params, 'entry-skin-handle');

		/* 2.1.6 */
		if (class_exists('ESGColorpicker')) {
			$background_col = ESGColorpicker::process($background_color);
			if (!empty($background_col) && is_array($background_col)) {
				$background_color = $background_col[0];
				if (empty($background_color))
					$background_color = '#FFFFFF';
			}
		}

		$pad_style = '';
		if (is_array($paddings) && !empty($paddings)) {
			$pad_style = 'padding: ';
			foreach ($paddings as $size) {
				$pad_style .= $size . 'px ';
			}
			$pad_style .= ';';
			$pad_style .= ' box-sizing:border-box;';
			$pad_style .= ' -moz-box-sizing:border-box;';
			$pad_style .= ' -webkit-box-sizing:border-box;';
		}

		$div_style = ' style="';
		$div_style .= 'background: ' . $background_color . ';';
		$div_style .= $pad_style;
		if ($hide_markup_before_load == 'on') $div_style .= ' display:none';
		$div_style .= '"';

		if ($css_id == '') $css_id = $grid_id_wrap;

		$do_fix_height = $this->add_start_height_css($css_id);

		$this->remove_load_more_button($css_id);

		$fix_height_class = ($do_fix_height) ? ' eg-startheight' : '';

		$entry_skin_handle_class = $entry_skin_handle ? ' esg-entry-skin-' . $entry_skin_handle : '';

		$n = '<!-- THE ESSENTIAL GRID ' . ESG_REVISION . ' ' . strtoupper($source_type) . ' -->' . "\n\n";
		$n .= '<article class="myportfolio-container esg-grid-wrap-container ' . $navigation_skin . $fix_height_class . $entry_skin_handle_class . ' source_type_' . $source_type . '" id="' . $css_id . $article_id . '" data-alias="' . $this->grid_handle . '">' . "\n\n"; //fullwidthcontainer-with-padding
		$n .= '   <div id="' . $grid_id . '" class="esg-grid"' . $div_style . '>' . "\n";

		echo apply_filters('essgrid_output_wrapper_pre', $n, $grid_preview);
	}

	public function output_wrapper_post()
	{
		$n = '    </div>' . "\n\n";
		$n .= '</article>' . "\n";
		$n .= '<div class="clear"></div>' . "\n";

		echo apply_filters('essgrid_output_wrapper_post', $n);
	}

	public function output_grid_pre()
	{
		$n = '<ul>' . "\n";
		echo apply_filters('essgrid_output_grid_pre', $n);
	}

	public function output_grid_post()
	{
		$n = '</ul>' . "\n";
		echo apply_filters('essgrid_output_grid_post', $n);
	}

	public function output_grid_javascript($load_lightbox = false, $is_demo = false)
	{
		global $esg_grid_serial;

		$base = new Essential_Grid_Base();
		$hide_markup_before_load = $base->getVar($this->grid_params, 'hide-markup-before-load', 'off');
		$layout = $base->getVar($this->grid_params, 'layout', 'even');
		$content_push = $base->getVar($this->grid_params, 'content-push', 'off');

		$rows_unlimited = $base->getVar($this->grid_params, 'rows-unlimited', 'on');
		$rows = $base->getVar($this->grid_params, 'rows', 4, 'i');

		if (wp_is_mobile()) {
			$mobile_rows = $base->getVar($this->grid_params, 'enable-rows-mobile', 'off') === 'on';
			if ($mobile_rows) $rows = $base->getVar($this->grid_params, 'rows-mobile', 3, 'i');
		}

		$columns = $base->getVar($this->grid_params, 'columns');
		$columns = $base->set_basic_colums($columns);

		$columns_advanced = $base->getVar($this->grid_params, 'columns-advanced', 'off');
		if ($columns_advanced == 'on') {
			$columns_width = $base->getVar($this->grid_params, 'columns-width');
			if ($layout == 'masonry') {
				$masonry_content_height = $base->getVar($this->grid_params, 'mascontent-height');
			} else {
				$masonry_content_height = array(); //get defaults
			}
		} else {
			$columns_width = array(); //get defaults
			$masonry_content_height = array(); //get defaults
		}

		$columns_width = $base->set_basic_colums_width($columns_width);
		$masonry_content_height = $base->set_basic_masonry_content_height($masonry_content_height);

		// 2.2.6
		$hide_blankitems_at = $base->getVar($this->grid_params, 'blank-item-breakpoint', '1');
		$space = $base->getVar($this->grid_params, 'spacings', 0, 'i');
		$page_animation = $base->getVar($this->grid_params, 'grid-animation', 'scale');
		$layout_sizing = $base->getVar($this->grid_params, 'layout-sizing', 'boxed');
		$layout_offset_container = $base->getVar($this->grid_params, 'fullscreen-offset-container');

		// 2.2.5
		$start_animation = $base->getVar($this->grid_params, 'grid-start-animation', 'reveal');
		$start_animation_speed = $base->getVar($this->grid_params, 'grid-start-animation-speed', 1000, 'i');
		$start_animation_delay = $base->getVar($this->grid_params, 'grid-start-animation-delay', 100, 'i');
		$start_animation_type = $base->getVar($this->grid_params, 'grid-start-animation-type', 'item');
		$animation_type = $base->getVar($this->grid_params, 'grid-animation-type', 'item');

		// 2.2.5
		$in_viewport = $base->getVar($this->grid_params, 'start-anime-in-viewport', 'off') === 'off' ? 'true' : 'false';
		$viewport_buffer = $base->getVar($this->grid_params, 'start-anime-viewport-buffer', 20, 'i');
		$viewport_buffer = intval($viewport_buffer);
		$viewport_buffer = max($viewport_buffer, 0);
		$viewport_buffer = min($viewport_buffer, 80);

		if ($start_animation === 'reveal' || $start_animation === 'none')
			$in_viewport = 'true';
		if ($start_animation === 'reveal') {
			if ($layout_sizing !== 'fullscreen') {
				$start_animation = 'none';
				$hide_markup_before_load = 'on';
			} else {
				$start_animation = 'scale';
				$start_animation_delay = 0;
				$hide_markup_before_load = 'off';
			}

		}

		// 2.2.6
		if ($rows_unlimited === 'off') {
			$touchswipe = $base->getVar($this->grid_params, 'pagination-touchswipe', 'off');
			$dragvertical = $base->getVar($this->grid_params, 'pagination-dragvertical', 'on');
			$swipebuffer = $base->getVar($this->grid_params, 'pagination-swipebuffer', 30, 'i');
		} else {
			$touchswipe = 'off';
			$dragvertical = 'off';
			$swipebuffer = 30;
		}

		$anim_speed = $base->getVar($this->grid_params, 'grid-animation-speed', 800, 'i');
		$delay_basic = $base->getVar($this->grid_params, 'grid-animation-delay', 1, 'i');
		$delay_hover = $base->getVar($this->grid_params, 'hover-animation-delay', 1, 'i');
		$filter_type = $base->getVar($this->grid_params, 'filter-arrows', 'single');
		$filter_logic = $base->getVar($this->grid_params, 'filter-logic', 'or');
		$filter_show_on = $base->getVar($this->grid_params, 'filter-show-on', 'hover');

		$lightbox_mode = $base->getVar($this->grid_params, 'lightbox-mode', 'single');
		$lightbox_mode = ($lightbox_mode == 'content' || $lightbox_mode == 'content-gallery' || $lightbox_mode == 'woocommerce-gallery') ? 'contentgroup' : $lightbox_mode;

		/* 2.2 */
		$lb_button_order = $base->getVar($this->grid_params, 'lb-button-order', array('share', 'thumbs', 'close'));
		$lb_post_max_width = $base->getVar($this->grid_params, 'lightbox-post-content-max-width', '75');
		$lb_post_max_perc = $base->getVar($this->grid_params, 'lightbox-post-content-max-perc', 'on') == 'on' ? '%' : 'px';
		$lb_post_max_width = intval($lb_post_max_width) . $lb_post_max_perc;

		$lb_post_min_width = $base->getVar($this->grid_params, 'lightbox-post-content-min-width', '75');
		$lb_post_min_perc = $base->getVar($this->grid_params, 'lightbox-post-content-min-perc', 'on') == 'on' ? '%' : 'px';
		$lb_post_min_width = intval($lb_post_min_width) . $lb_post_min_perc;

		$no_filter_match_message = get_option('tp_eg_no_filter_match_message', 'No Items for the Selected Filter');

		/* 2.1.6 for lightbox post content addition */
		$lb_post_spinner = $base->getVar($this->grid_params, 'lightbox-post-spinner', 'off');
		$lb_featured_img = $base->getVar($this->grid_params, 'lightbox-post-content-img', 'off');
		$lb_featured_pos = $base->getVar($this->grid_params, 'lightbox-post-content-img-position', 'top');
		$lb_featured_width = $base->getVar($this->grid_params, 'lightbox-post-content-img-width', '100');
		$lb_featured_margin = $base->getVar($this->grid_params, 'lightbox-post-content-img-margin', array('0', '0', '0', '0'));
		$lb_post_title = $base->getVar($this->grid_params, 'lightbox-post-content-title', 'off');
		$lb_post_title_tag = $base->getVar($this->grid_params, 'lightbox-post-content-title-tag', 'h2');

		// 2.2 Deeplinking
		$filter_deep_linking = $base->getVar($this->grid_params, 'filter-deep-link', 'off');

		// 2.2.5 Mobile Filter Conversion
		$single_filters = $base->getVar($this->grid_params, 'filter-arrows', 'single');
		$filter_mobile_conversion = $single_filters === 'single' ? $base->getVar($this->grid_params, 'convert-mobile-filters', 'off') : false;
		$filter_mobile_conversion = $filter_mobile_conversion === 'on' ? 'true' : 'false';
		$filter_mobile_conversion_width = $base->getVar($this->grid_params, 'convert-mobile-filters-width', '768');

		if (!is_array($lb_featured_margin) || count($lb_featured_margin) !== 4) $lb_featured_margin = array('0', '0', '0', '0');
		$lb_featured_margin = implode('|', $lb_featured_margin);

		$aspect_ratio_x = $base->getVar($this->grid_params, 'x-ratio', 4, 'i');
		$aspect_ratio_y = $base->getVar($this->grid_params, 'y-ratio', 3, 'i');
		$auto_ratio = $base->getVar($this->grid_params, 'auto-ratio', 'true');

		$lazy_load = $base->getVar($this->grid_params, 'lazy-loading', 'off');
		$lazy_load_color = $base->getVar($this->grid_params, 'lazy-load-color', '#FFFFFF');

		$spinner = $base->getVar($this->grid_params, 'use-spinner', '0');
		$spinner_color = $base->getVar($this->grid_params, 'spinner-color', '#FFFFFF');

		/* 2.1.6 */
		if (class_exists('ESGColorpicker')) {
			$spinner_col = ESGColorpicker::process($spinner_color);
			$lazy_load_col = ESGColorpicker::process($lazy_load_color);
			if (!empty($spinner_col) && is_array($spinner_col)) {
				$spinner_color = $spinner_col[0];
				if (empty($spinner_color))
					$spinner_color = '#FFFFFF';
			}
			if (!empty($lazy_load_col) && is_array($lazy_load_col)) {
				$lazy_load_color = $lazy_load_col[0];
				if (empty($lazy_load_color))
					$lazy_load_color = '#FFFFFF';
			}
		}

		$lightbox_effect_open_close = $base->getVar($this->grid_params, 'lightbox-effect-open-close', 'fade');
		if ($lightbox_effect_open_close != 'false')
			$lightbox_effect_open_close = '"' . $lightbox_effect_open_close . '"';

		$lightbox_effect_open_close_speed = $base->getVar($this->grid_params, 'lightbox-effect-open-close-speed', '500');
		if (!is_numeric($lightbox_effect_open_close_speed))
			$lightbox_effect_open_close_speed = '500';

		$lightbox_effect_next_prev = $base->getVar($this->grid_params, 'lightbox-effect-next-prev', 'fade');
		if ($lightbox_effect_next_prev != 'false')
			$lightbox_effect_next_prev = '"' . $lightbox_effect_next_prev . '"';

		$lightbox_effect_next_prev_speed = $base->getVar($this->grid_params, 'lightbox-effect-next-prev-speed', '366');
		if (!is_numeric($lightbox_effect_next_prev_speed))
			$lightbox_effect_next_prev_speed = '366';

		$lightbox_deep_link = $base->getVar($this->grid_params, 'lightbox-deep-link', 'group');
		if (empty($lightbox_deep_link))
			$lightbox_deep_link = 'group';

		$lightbox_mousewheel = $base->getVar($this->grid_params, 'lightbox-mousewheel', 'off') == 'on' ? '"auto"' : 'false';
		$lightbox_arrows = $base->getVar($this->grid_params, 'lightbox-arrows', 'off') == 'on' ? 'true' : 'false';
		$lightbox_caption_position = $base->getVar($this->grid_params, 'lightbox-title-position', 'bottom');
		$lightbox_base_class = 'esgbox-container-' . $this->grid_div_name;

		$lbox_autoplay = $base->getVar($this->grid_params, 'lightbox-autoplay', 'off') == 'on' ? 'true' : 'false';
		$lbox_videoautoplay = $base->getVar($this->grid_params, 'lightbox-videoautoplay', 'on') == 'on' ? 'true' : 'false';
		$lbox_playspeed = $base->getVar($this->grid_params, 'lbox-playspeed', '3000');
		$lbox_padding = $base->getVar($this->grid_params, 'lbox-padding', array('0', '0', '0', '0'));
		$lbox_numbers = $base->getVar($this->grid_params, 'lightbox-numbers', 'on') === 'on' ? 'true' : 'false';
		$lbox_loop = $base->getVar($this->grid_params, 'lightbox-loop', 'on') === 'on' ? 'true' : 'false';
		
		$lbox_margin = $base->getVar($this->grid_params, 'lbox-padding', array('0', '0', '0', '0'));
		if (!is_array($lbox_margin) || count($lbox_margin) !== 4) $lbox_margin = array('0', '0', '0', '0');
		$lbox_margin = implode('|', $lbox_margin);

		$lbox_inpadding = $base->getVar($this->grid_params, 'lbox-content_padding', array('0', '0', '0','0'));
		if (!is_array($lbox_inpadding) || count($lbox_inpadding) !== 4) $lbox_inpadding = array('0', '0', '0', '0');
		$lbox_inpadding = implode('|', $lbox_inpadding);

		$lbox_overflow = $base->getVar($this->grid_params, 'lightbox-post-content-overflow', 'on') == 'on' ? 'auto' : 'hidden';

		$rtl = $base->getVar($this->grid_params, 'rtl', 'off');

		$pagination_numbers = $base->getVar($this->grid_params, 'pagination-numbers', 'smart');
		$pagination_scroll = $base->getVar($this->grid_params, 'pagination-scroll', 'off');
		$pagination_scroll_offset = $base->getVar($this->grid_params, 'pagination-scroll-offset', '0', 'i');

		if ($base->getVar($this->grid_params, 'rows-unlimited', 'on') == 'off') {
			$pagination_autoplay = $base->getVar($this->grid_params, 'pagination-autoplay', 'off');
			$pagination_autoplay_delay = $base->getVar($this->grid_params, 'pagination-autoplay-speed', '5000', 'i');
		} else {
			$pagination_autoplay = 'off';
			$pagination_autoplay_delay = 5000;
		}

		$ajax_callback = $base->getVar($this->grid_params, 'ajax-callback');
		$ajax_css_url = $base->getVar($this->grid_params, 'ajax-css-url');
		$ajax_js_url = $base->getVar($this->grid_params, 'ajax-js-url');
		$ajax_scroll_onload = $base->getVar($this->grid_params, 'ajax-scroll-onload', 'on');
		$ajax_callback_argument = $base->getVar($this->grid_params, 'ajax-callback-arg', 'on');
		$ajax_content_id = $base->getVar($this->grid_params, 'ajax-container-id');
		$ajax_scrollto_offset = $base->getVar($this->grid_params, 'ajax-scrollto-offset', '0');
		$ajax_close_button = $base->getVar($this->grid_params, 'ajax-close-button', 'off');
		$ajax_button_nav = $base->getVar($this->grid_params, 'ajax-nav-button', 'off');
		$ajax_content_sliding = $base->getVar($this->grid_params, 'ajax-content-sliding', 'on');
		$ajax_button_type = $base->getVar($this->grid_params, 'ajax-button-type', 'button');
		if ($ajax_button_type == 'type2') {
			$ajax_button_text = $base->getVar($this->grid_params, 'ajax-button-text', esc_attr__('Close', ESG_TEXTDOMAIN));
		}
		$ajax_button_skin = $base->getVar($this->grid_params, 'ajax-button-skin', 'light');
		$ajax_button_inner = $base->getVar($this->grid_params, 'ajax-button-inner', 'false');
		$ajax_button_h_pos = $base->getVar($this->grid_params, 'ajax-button-h-pos', 'r');
		$ajax_button_v_pos = $base->getVar($this->grid_params, 'ajax-button-v-pos', 't');

		$cobbles_pattern = $base->getVar($this->grid_params, 'cobbles-pattern', array());
		$cobbles_to_even = $base->getVar($this->grid_params, 'show-even-on-device', '0');
		$use_cobbles_pattern = $base->getVar($this->grid_params, 'use-cobbles-pattern', 'off');

		$cookie_time = intval($base->getVar($this->grid_params, 'cookie-save-time', '30'));
		$cookie_search = $base->getVar($this->grid_params, 'cookie-save-search', 'off');
		$cookie_filter = $base->getVar($this->grid_params, 'cookie-save-filter', 'off');
		$cookie_pagination = $base->getVar($this->grid_params, 'cookie-save-pagination', 'off');

		$load_more_start = $base->getVar($this->grid_params, 'load-more-start', 3, 'i');
		$load_more_error = $base->getVar($this->grid_params, 'load-more-error');

		$js_to_footer = get_option('tp_eg_js_to_footer', 'false') == 'true';

		//add inline style into the footer
		if ($js_to_footer && !$is_demo) {
			ob_start();
		}

		echo '<script type="text/javascript">' . "\n";
		if ($hide_markup_before_load == 'off') {
			echo 'function eggbfc(winw,resultoption) {' . "\n";
			echo '  var lasttop = winw,' . "\n";
			echo '  lastbottom = 0,' . "\n";
			echo '  smallest =9999,' . "\n";
			echo '  largest = 0,' . "\n";
			echo '  samount = 0,' . "\n";
			echo '  lamount = 0,' . "\n";
			echo '  lastamount = 0,' . "\n";
			echo '  resultid = 0,' . "\n";
			echo '  resultidb = 0,' . "\n";
			echo '  responsiveEntries = [' . "\n";
			$amount = count(Essential_Grid_Base::get_basic_devices());
			for ($i = 0; $i < $amount; $i++) {
				echo '            { width:' . $columns_width[$i] . ',amount:' . $columns[$i] . ',mmheight:' . $masonry_content_height[$i] . '},' . "\n";
			}
			echo '            ];' . "\n";
			echo '  if (responsiveEntries!=undefined && responsiveEntries.length>0)' . "\n";
			echo '    jQuery.each(responsiveEntries, function(index,obj) {' . "\n";
			echo '      var curw = obj.width != undefined ? obj.width : 0,' . "\n";
			echo '        cura = obj.amount != undefined ? obj.amount : 0;' . "\n";
			echo '      if (smallest>curw) {' . "\n";
			echo '        smallest = curw;' . "\n";
			echo '        samount = cura;' . "\n";
			echo '        resultidb = index;' . "\n";
			echo '      }' . "\n";
			echo '      if (largest<curw) {' . "\n";
			echo '        largest = curw;' . "\n";
			echo '        lamount = cura;' . "\n";
			echo '      }' . "\n";
			echo '      if (curw>lastbottom && curw<=lasttop) {' . "\n";
			echo '        lastbottom = curw;' . "\n";
			echo '        lastamount = cura;' . "\n";
			echo '        resultid = index;' . "\n";
			echo '      }' . "\n";
			echo '    });' . "\n";
			echo '    if (smallest>winw) {' . "\n";
			echo '      lastamount = samount;' . "\n";
			echo '      resultid = resultidb;' . "\n";
			echo '    }' . "\n";
			echo '    var obj = new Object;' . "\n";
			echo '    obj.index = resultid;' . "\n";
			echo '    obj.column = lastamount;' . "\n";
			echo '    if (resultoption=="id")' . "\n";
			echo '      return obj;' . "\n";
			echo '    else' . "\n";
			echo '      return lastamount;' . "\n";
			echo '  }' . "\n";
			echo '  var coh=0,' . "\n";
			echo '    container = jQuery("#esg-grid-' . $this->grid_div_name . '-' . $esg_grid_serial . '");' . "\n";
			if ($layout_sizing == 'fullscreen') {
				echo 'coh = jQuery(window).height();' . "\n";

				if ($layout_offset_container !== '') {
					echo 'try{' . "\n";
					echo '  var offcontainers = "' . $layout_offset_container . '".split(",");' . "\n";
					echo '  jQuery.each(offcontainers,function(index,searchedcont) {' . "\n";
					echo '    coh = coh - jQuery(searchedcont).outerHeight(true);' . "\n";
					echo '  })' . "\n";
					echo '} catch(e) {}' . "\n";
				}
			} else {
				echo '  var cwidth = "' . $layout_sizing . '" == "boxed" ? container.width() : jQuery(window).width(),' . "\n";
				echo '    ar = "' . $aspect_ratio_x . ':' . $aspect_ratio_y . '",' . "\n";
				echo '    gbfc = eggbfc(cwidth,"id"),' . "\n";
				if ($rows_unlimited == 'on') {
					$load_more_start = $base->getVar($this->grid_params, 'load-more-start', 3, 'i');
					echo '  row = Math.ceil(' . $load_more_start . ' / gbfc.column);' . "\n";
				} else {
					echo '  row = ' . $rows . ';' . "\n";
				}
				echo 'ar = ar.split(":");' . "\n";
				echo 'var aratio=parseInt(ar[0],0) / parseInt(ar[1],0);' . "\n";
				echo 'coh = cwidth / aratio;' . "\n";
				echo 'coh = coh/gbfc.column*row;' . "\n";
			}
			echo '  var ul = container.find("ul").first();' . "\n";
			echo '  ul.css({display:"block",height:coh+"px"});' . "\n";
		}

		echo 'var essapi_' . $this->grid_api_name . '_' . $esg_grid_serial . ';' . "\n";
		echo 'function esginit_' . $this->grid_api_name . '_' . $esg_grid_serial . '() {' . "\n";
		echo 'jQuery(document).ready(function() {' . "\n";



		/////////////////
		// lightbox:start
		
		/* 2.2 */
		/* lightbox options written first, then custom JS from grid can override them if desired */
		echo '  var lightboxOptions = {' . "\n";

		echo '        margin : [' . $lbox_padding[0] . ',' . $lbox_padding[1] . ',' . $lbox_padding[2] . ',' . $lbox_padding[3] . '],' . "\n";
		// implode arguments switched for PHP 7.4
		echo '        buttons : ["' . implode('","', $lb_button_order) . '"],' . "\n";
		echo '        infobar : ' . $lbox_numbers . ',' . "\n";
		echo '        loop : ' . $lbox_loop . ',' . "\n";
		echo '        slideShow : {"autoStart": ' . $lbox_autoplay . ', "speed": ' . $lbox_playspeed . '},' . "\n";
		echo '        videoAutoPlay : ' . $lbox_videoautoplay . ',' . "\n";
		echo '        animationEffect : ' . $lightbox_effect_open_close . ',' . "\n";
		echo '        animationDuration : ' . $lightbox_effect_open_close_speed . ',' . "\n";

		echo '        beforeShow: function(a, c) {' . "\n";
		if ($lightbox_arrows !== 'true') {
			echo '              jQuery("body").addClass("esgbox-hidearrows");' . "\n";
		}
		echo '          var i = 0,' . "\n";
		echo '              multiple = false;' . "\n";
		echo '          a = a.slides;' . "\n";
		echo '          for(var b in a) {' . "\n";
		echo '            i++;' . "\n";
		echo '            if (i > 1) {' . "\n";
		echo '              multiple = true;' . "\n";
		echo '              break;' . "\n";
		echo '            }' . "\n";
		echo '          }' . "\n";
		echo '          if (!multiple) jQuery("body").addClass("esgbox-single");' . "\n";
		echo '          if (c.type === "image") jQuery(".esgbox-button--zoom").show();' . "\n";
		echo '          if (c.contentType === "html") c.$slide.addClass("esgbox-slide--overflow-" + c.opts.overflow);' . "\n";
		echo '        },' . "\n";

		echo '        beforeLoad: function(a, b) {' . "\n";
		echo '          jQuery("body").removeClass("esg-four-by-three");' . "\n";
		echo '          if (b.opts.$orig.data("ratio") === "4:3") jQuery("body").addClass("esg-four-by-three");' . "\n";
		echo '        },' . "\n";

		echo '        afterLoad: function() {jQuery(window).trigger("resize.esglb");},' . "\n";
		echo '        afterClose : function() {jQuery("body").removeClass("esgbox-hidearrows esgbox-single");},' . "\n";

		echo '        transitionEffect : ' . $lightbox_effect_next_prev . ',' . "\n";
		echo '        transitionDuration : ' . $lightbox_effect_next_prev_speed . ',' . "\n";

		echo '        hash : "' . $lightbox_deep_link . '",' . "\n";
		echo '        arrows : ' . $lightbox_arrows . ',' . "\n";
		echo '        wheel : ' . $lightbox_mousewheel . ',' . "\n";
		echo '        baseClass : "' . $lightbox_base_class . '",' . "\n";
		echo '        captionPosition : "' . $lightbox_caption_position . '",' . "\n";
		echo '        overflow : "' . $lbox_overflow . '",' . "\n";

		echo '  };' . "\n\n";

		echo '  jQuery("#esg-grid-' . $this->grid_div_name . '-' . $esg_grid_serial . '").data("lightboxsettings", lightboxOptions);' . "\n\n";

		// lightbox:end
		///////////////
		
		echo '  essapi_' . $this->grid_api_name . '_' . $esg_grid_serial . ' = jQuery("#esg-grid-' . $this->grid_div_name . '-' . $esg_grid_serial . '").tpessential({' . "\n";

		echo '        gridID:' . $this->grid_id . ',' . "\n";
		echo '        layout:"' . $layout . '",' . "\n";

		if ($rtl == 'on')
			echo '        rtl:"on",' . "\n";

		echo '        lazyLoad:"' . $lazy_load . '",' . "\n";
		if ($lazy_load == 'on')
			echo '        lazyLoadColor:"' . $lazy_load_color . '",' . "\n";

		if ($rows_unlimited == 'on') {
			$load_more = $base->getVar($this->grid_params, 'load-more', 'button');
			$load_more_amount = $base->getVar($this->grid_params, 'load-more-amount', 3, 'i');
			$load_more_show_number = $base->getVar($this->grid_params, 'load-more-show-number', 'on');

			if ($load_more !== 'none') {
				$load_more_text = $base->getVar($this->grid_params, 'load-more-text', esc_attr__('Load More', ESG_TEXTDOMAIN));
				echo '        loadMoreType:"' . $load_more . '",' . "\n";
				echo '        loadMoreAmount:' . $load_more_amount . ',' . "\n";
				echo '        loadMoreTxt:"' . $load_more_text . '",' . "\n";
				echo '        loadMoreNr:"' . $load_more_show_number . '",' . "\n";
				echo '        loadMoreEndTxt:"' . esc_attr__('No More Items for the Selected Filter', ESG_TEXTDOMAIN) . '",' . "\n";
				echo '        loadMoreItems:';
				$this->output_load_more_list();
				echo ',' . "\n";

				/* 2.1.5 */
				if (!empty($this->custom_images)) {
					echo '        customGallery: true,' . "\n";
				}
			}
			echo '        row:9999,' . "\n";
		} else {
			echo '        row:' . $rows . ',' . "\n";
		}
		$token = wp_create_nonce('Essential_Grid_Front');
		echo '        apiName: "essapi_' . $this->grid_api_name . '_' . $esg_grid_serial . '",' . "\n";
		echo '        loadMoreAjaxToken:"' . $token . '",' . "\n";
		echo '        loadMoreAjaxUrl:"' . admin_url('admin-ajax.php') . '",' . "\n";
		echo '        loadMoreAjaxAction:"Essential_Grid_Front_request_ajax",' . "\n";
		if (!empty($load_more_error)) {
			echo '        loadMoreErrorMessage:"' . addslashes($load_more_error) . '",' . "\n";
		}

		echo '        ajaxContentTarget:"' . $ajax_content_id . '",' . "\n";
		echo '        ajaxScrollToOffset:"' . $ajax_scrollto_offset . '",' . "\n";
		echo '        ajaxCloseButton:"' . $ajax_close_button . '",' . "\n";
		echo '        ajaxContentSliding:"' . $ajax_content_sliding . '",' . "\n";
		if ($ajax_callback !== '')
			echo '        ajaxCallback:"' . stripslashes($ajax_callback) . '",' . "\n";
		if ($ajax_css_url !== '')
			echo '        ajaxCssUrl:"' . $ajax_css_url . '",' . "\n";
		if ($ajax_js_url !== '')
			echo '        ajaxJsUrl:"' . $ajax_js_url . '",' . "\n";
		if ($ajax_scroll_onload !== 'off')
			echo '        ajaxScrollToOnLoad:"on",' . "\n";

		if ($ajax_callback_argument === 'on' || $ajax_callback_argument == 'true')
			echo '        ajaxCallbackArgument:"on",' . "\n";
		else
			echo '        ajaxCallbackArgument:"off",' . "\n";

		echo '        ajaxNavButton:"' . $ajax_button_nav . '",' . "\n";
		echo '        ajaxCloseType:"' . $ajax_button_type . '",' . "\n";
		if ($ajax_button_type == 'type2') {
			echo '        ajaxCloseTxt:"' . $ajax_button_text . '",' . "\n";
		}
		echo '        ajaxCloseInner:"' . $ajax_button_inner . '",' . "\n";
		echo '        ajaxCloseStyle:"' . $ajax_button_skin . '",' . "\n";

		if ($ajax_button_h_pos == 'c') {
			echo '        ajaxClosePosition:"' . $ajax_button_v_pos . '",' . "\n";
		} else {
			echo '        ajaxClosePosition:"' . $ajax_button_v_pos . $ajax_button_h_pos . '",' . "\n";
		}

		echo '        space:' . $space . ',' . "\n";
		echo '        pageAnimation:"' . $page_animation . '",' . "\n";

		// 2.3.7
		$videoplaybackingrid = $base->getVar($this->grid_params, 'videoplaybackingrid', 'on');
		$videoloopingrid = $base->getVar($this->grid_params, 'videoloopingrid', 'on');
		$videoplaybackonhover = $base->getVar($this->grid_params, 'videoplaybackonhover', 'off');
		$videomuteinline = $base->getVar($this->grid_params, 'videomuteinline', 'on');
		$videocontrolsinline = $base->getVar($this->grid_params, 'videocontrolsinline', 'off');
		$keeplayersovermedia = $base->getVar($this->grid_params, 'keeplayersovermedia', 'off');

		echo '        videoPlaybackInGrid: "' . $videoplaybackingrid . '",' . "\n";
		echo '        videoLoopInGrid: "' . $videoloopingrid . '",' . "\n";
		echo '        videoPlaybackOnHover: "' . $videoplaybackonhover . '",' . "\n";
		echo '        videoInlineMute: "' . $videomuteinline . '",' . "\n";
		echo '        videoInlineControls: "' . $videocontrolsinline . '",' . "\n";
		echo '        keepLayersInline: "' . $keeplayersovermedia . '",' . "\n";

		// 2.2.5
		echo '        startAnimation: "' . $start_animation . '",' . "\n";
		echo '        startAnimationSpeed: ' . $start_animation_speed . ',' . "\n";
		echo '        startAnimationDelay: ' . $start_animation_delay . ',' . "\n";
		echo '        startAnimationType: "' . $start_animation_type . '",' . "\n";
		echo '        animationType: "' . $animation_type . '",' . "\n";

		if ($pagination_numbers == 'full')
			echo '        smartPagination:"off",' . "\n";

		echo '        paginationScrollToTop:"' . $pagination_scroll . '",' . "\n";
		if ($pagination_scroll == 'on') {
			echo '        paginationScrollToTopOffset:' . $pagination_scroll_offset . ',' . "\n";
		}

		echo '        paginationAutoplay:"' . $pagination_autoplay . '",' . "\n";
		if ($pagination_autoplay == 'on') {
			echo '        paginationAutoplayDelay:' . $pagination_autoplay_delay . ',' . "\n";
		}

		echo '        spinner:"spinner' . $spinner . '",' . "\n";
		
		if ($spinner != '0' && $spinner != '5')
			echo '        spinnerColor:"' . $spinner_color . '",' . "\n";

		if ($layout_sizing == 'fullwidth') {
			echo '        forceFullWidth:"on",' . "\n";
		} elseif ($layout_sizing == 'fullscreen') {
			echo '        forceFullScreen:"on",' . "\n";
			if ($layout_offset_container !== '') {
				echo '        fullScreenOffsetContainer:"' . $layout_offset_container . '",' . "\n";
			}
		}

		echo '        minVisibleItems:' . $load_more_start . "," . "\n";

		if ($layout == 'even')
			echo '        evenGridMasonrySkinPusher:"' . $content_push . '",' . "\n";

		echo '        lightBoxMode:"' . $lightbox_mode . '",' . "\n";

		/* 2.2 */
		echo '        lightboxHash:"' . $lightbox_deep_link . '",' . "\n";
		echo '        lightboxPostMinWid:"' . $lb_post_max_width . '",' . "\n";
		echo '        lightboxPostMaxWid:"' . $lb_post_min_width . '",' . "\n";

		/* 2.1.6 */
		echo '        lightboxSpinner:"' . $lb_post_spinner . '",' . "\n";
		echo '        lightBoxFeaturedImg:"' . $lb_featured_img . '",' . "\n";
		if ($lb_featured_img === 'on') {
			echo '        lightBoxFeaturedPos:"' . $lb_featured_pos . '",' . "\n";
			echo '        lightBoxFeaturedWidth:"' . $lb_featured_width . '",' . "\n";
			echo '        lightBoxFeaturedMargin:"' . $lb_featured_margin . '",' . "\n";
		}
		echo '        lightBoxPostTitle:"' . $lb_post_title . '",' . "\n";
		echo '        lightBoxPostTitleTag:"' . $lb_post_title_tag . '",' . "\n";
		echo '        lightboxMargin : "' . $lbox_margin . '",' . "\n";
		echo '        lbContentPadding : "' . $lbox_inpadding . '",' . "\n";
		echo '        lbContentOverflow : "' . $lbox_overflow . '",' . "\n";

		if (!empty($cobbles_pattern) && $layout == 'cobbles' && $use_cobbles_pattern == 'on') {
			echo '        cobblesPattern:"' . implode(',', $cobbles_pattern) . '",' . "\n";
		}
		if (!empty($cobbles_to_even) && $layout == 'cobbles' && $cobbles_to_even !== '0') {
			echo '      cobblesToEven:' . $cobbles_to_even . ',' . "\n";
		}
		echo '        animSpeed:' . $anim_speed . ',' . "\n";
		echo '        delayBasic:' . $delay_basic . ',' . "\n";
		echo '        mainhoverdelay:' . $delay_hover . ',' . "\n";


		echo '        filterType:"' . $filter_type . '",' . "\n";

		if ($filter_type == 'multi') {
			echo '        filterLogic:"' . $filter_logic . '",' . "\n";
		}
		echo '        showDropFilter:"' . $filter_show_on . '",' . "\n";

		echo '        filterGroupClass:"esg-fgc-' . $this->grid_id . '",' . "\n";

		// 2.2
		echo '        filterNoMatch:"' . $no_filter_match_message . '",' . "\n";
		echo '        filterDeepLink:"' . $filter_deep_linking . '",' . "\n";

		// 2.2.5
		echo '        hideMarkups: "' . $hide_markup_before_load . '",' . "\n";
		echo '        inViewport: ' . $in_viewport . ',' . "\n";
		echo '        viewportBuffer: ' . $viewport_buffer . ',' . "\n";
		echo '        youtubeNoCookie:"' . get_option('tp_eg_enable_youtube_nocookie', 'false') . '",' . "\n";
		echo '        convertFilterMobile:' . $filter_mobile_conversion . ',' . "\n";
		echo '        convertFilterMobileWidth:' . $filter_mobile_conversion_width . ',' . "\n";

		// 2.2.6
		echo '        paginationSwipe: "' . $touchswipe . '",' . "\n";
		echo '        paginationDragVer: "' . $dragvertical . '",' . "\n";
		echo '        pageSwipeThrottle: ' . $swipebuffer . ',' . "\n";

		if ($cookie_search === 'on' || $cookie_filter === 'on' || $cookie_pagination === 'on') {
			echo '        cookies: {' . "\n";
			if ($cookie_search == 'on')
				echo '                search:"' . $cookie_search . '",' . "\n";
			if ($cookie_filter == 'on')
				echo '                filter:"' . $cookie_filter . '",' . "\n";
			if ($cookie_pagination == 'on')
				echo '                pagination:"' . $cookie_pagination . '",' . "\n";
			echo '                timetosave:"' . $cookie_time . '"' . "\n";
			echo '        },' . "\n";
		}

		if ($layout != 'masonry' || $auto_ratio != 'true') {
			echo '        aspectratio:"' . $aspect_ratio_x . ':' . $aspect_ratio_y . '",' . "\n";
		}

		// 2.2.6
		echo '        hideBlankItemsAt: "' . $hide_blankitems_at . '",' . "\n";

		echo '        responsiveEntries: [' . "\n";
		$amount = count(Essential_Grid_Base::get_basic_devices());
		for ($i = 0; $i < $amount; $i++) {
			echo '            { width:' . $columns_width[$i] . ',amount:' . $columns[$i] . ',mmheight:' . $masonry_content_height[$i] . '},' . "\n";
		}
		echo '            ]';

		if ($columns_advanced == 'on')
			$this->output_ratio_list();

		do_action('essgrid_output_grid_javascript_options', $this);

		echo "\n";
		echo '  });' . "\n\n";

		//output custom javascript if any is set
		$custom_javascript = stripslashes($base->getVar($this->grid_params, 'custom-javascript'));
		if ($custom_javascript !== '') {
			echo $custom_javascript;
		}

		do_action('essgrid_output_grid_javascript_custom', $this);
		echo "\n";
		echo '});' . "\n";

		echo '} // End of EsgInitScript' . "\n";
		echo 'var once_' . $this->grid_api_name . '_' . $esg_grid_serial . ' = false;' . "\n";
		echo 'if (document.readyState === "loading") document.addEventListener(\'readystatechange\',function() { if ((document.readyState === "interactive" || document.readyState === "complete") && !once_' . $this->grid_api_name . '_' . $esg_grid_serial . ' ) { once_' . $this->grid_api_name . '_' . $esg_grid_serial . ' = true; esginit_' . $this->grid_api_name . '_' . $esg_grid_serial . '();}}); else {once_' . $this->grid_api_name . '_' . $esg_grid_serial . ' = true; esginit_' . $this->grid_api_name . '_' . $esg_grid_serial . '();}' . "\n";


		echo '</script>' . "\n";

		if ($js_to_footer && !$is_demo) {
			$js_content = ob_get_contents();
			ob_clean();
			ob_end_clean();

			$this->grid_inline_js = $js_content;

			add_action('wp_footer', array(
				$this,
				'add_inline_js'
			));
		}
	}

	/**
	 * Output the Load More list of posts
	 */
	public function output_load_more_list()
	{
		if (empty($this->load_more_post_array)) {
			echo '[]';
			return;
		}
		
		$rows = array();
		foreach ($this->load_more_post_array as $id => $filter) {
			$filters = empty($filter) ? array() : array_keys($filter);
			array_unshift($filters, -1);
			$rows[$id] = '            ' . wp_json_encode(array($id, $filters));
		}

		echo '[' . "\n";
		echo implode(',' . "\n", $rows);
		echo ']';
	}

	/**
	 * Output the custom row sizes if its set
	 */
	public function output_ratio_list()
	{
		$base = new Essential_Grid_Base;

		$columns = $base->getVar($this->grid_params, 'columns');
		$columns = $base->set_basic_colums($columns);

		$columns_advanced = $base->get_advanced_colums($this->grid_params);

		$found_rows = 0;
		foreach ($columns_advanced as $adv) {
			if (empty($adv))
				continue;
			$found_rows++;
		}

		if ($found_rows > 0) {
			echo ',' . "\n";
			echo '    rowItemMultiplier: [' . "\n";

			echo '            [';
			echo $columns[0] . ',';
			echo $columns[1] . ',';
			echo $columns[2] . ',';
			echo $columns[3] . ',';
			echo $columns[4] . ',';
			echo $columns[5] . ',';
			echo $columns[6];
			echo ']';

			foreach ($columns_advanced as $adv) {
				if (empty($adv))
					continue;

				echo ',' . "\n";
				echo '            [';

				$entry_first = true;
				foreach ($adv as $val) {
					echo (!$entry_first) ? ',' : '';
					echo $val;
					$entry_first = false;
				}

				echo ']';
			}

			echo "\n" . '          ]';
		}
	}

	/**
	 * check if post is visible in grid
	 */
	public function check_if_visible($post_id, $grid_id)
	{
		$pr_visibility = json_decode(get_post_meta($post_id, 'eg_visibility', true), true);

		$is_visible = true;

		// check if element is visible in grid
		if (!empty($pr_visibility) && is_array($pr_visibility)) {
			foreach ($pr_visibility as $pr_grid => $pr_setting) {
				if ($pr_grid == $grid_id) {
					$is_visible = $pr_setting;
					break;
				}
			}
		}

		return apply_filters('essgrid_check_if_visible', $is_visible, $post_id, $grid_id);
	}

	/**
	 * Output Filter from current Grid (used for Widgets)
	 * @since 1.0.6
	 */
	public function output_grid_filter()
	{
		do_action('essgrid_output_grid_filter_pre', $this);

		switch ($this->grid_postparams['source-type']) {
			case 'post':
				$this->output_filter_by_posts();
				break;
			case 'custom':
				$this->output_filter_by_custom();
				break;
			case 'streams':
				break;
		}

		do_action('essgrid_output_grid_filter_post', $this);
	}

	/**
	 * Output Sorting from current Grid (used for Widgets)
	 * @since 1.0.6
	 */
	public function output_grid_sorting()
	{
		do_action('essgrid_output_grid_sorting_pre', $this);

		switch ($this->grid_postparams['source-type']) {
			case 'post':
				$this->output_sorting_by_posts();
				break;
			case 'custom':
				$this->output_sorting_by_custom();
				break;
			case 'streams':
				break;
		}

		do_action('essgrid_output_grid_sorting_post', $this);
	}

	/**
	 * Output Sorting from post based
	 * @since 1.0.6
	 */
	public function output_sorting_by_posts()
	{
		do_action('essgrid_output_sorting_by_posts_pre', $this);
		$this->output_sorting_by_all_types();
		do_action('essgrid_output_sorting_by_posts_post', $this);
	}

	/**
	 * Output Sorting from custom grid
	 * @since 1.0.6
	 */
	public function output_sorting_by_custom()
	{
		do_action('essgrid_output_sorting_by_custom_pre', $this);
		$this->output_sorting_by_all_types();
		do_action('essgrid_output_sorting_by_custom_post', $this);
	}

	/**
	 * Output Sorting from custom grid
	 * @since 1.0.6
	 */
	public function output_sorting_by_all_types()
	{
		do_action('essgrid_output_sorting_by_all_types', $this);

		$base = new Essential_Grid_Base();
		$nav = new Essential_Grid_Navigation();
		$m = new Essential_Grid_Meta();

		$order_by = explode(',', $base->getVar($this->grid_params, 'sorting-order-by', 'date'));
		if (!is_array($order_by))
			$order_by = array(
				$order_by
			);

		$order_by_dir = $base->getVar($this->grid_params, 'sorting-order-type', 'ASC');
		$order_by_start = $base->getVar($this->grid_params, 'sorting-order-by-start', 'none');
		if (strpos($order_by_start, 'eg-') === 0 || strpos($order_by_start, 'egl-') === 0) { //add meta at the end for meta sorting
			//if essential Meta, replace to meta name. Else -> replace - and _ with space, set each word uppercase
			$metas = $m->get_all_meta();
			$f = false;
			if (!empty($metas)) {
				foreach ($metas as $meta) {
					if ('eg-' . $meta['handle'] == $order_by_start || 'egl-' . $meta['handle'] == $order_by_start) {
						$f = true;
						$order_by_start = $meta['name'];
						break;
					}
				}
			}

			if ($f === false) {
				$order_by_start = ucwords(str_replace(array(
					'-',
					'_'
				), array(
					' ',
					' '
				), $order_by_start));
			}
		}

		$nav->set_orders($order_by); //set order of filter
		$nav->set_orders_start($order_by_start); //set order of filter
		$nav->set_orders_order($order_by_dir);

		echo $nav->output_sorting();
	}

	/**
	 * Output Filter from post based
	 * @since 1.0.6
	 */
	public function output_filter_by_posts()
	{
		do_action('essgrid_output_filter_by_posts', $this);

		$base = new Essential_Grid_Base();
		$nav = new Essential_Grid_Navigation();

		$filter_allow = $base->getVar($this->grid_params, 'filter-arrows', 'single');
		$filter_start = $base->getVar($this->grid_params, 'filter-start');
		$filterall_visible = $base->getVar($this->grid_params, 'filter-all-visible', 'on');
		$filter_all_text = $base->getVar($this->grid_params, 'filter-all-text', esc_attr__('Filter - All', ESG_TEXTDOMAIN));
		$filter_dropdown_text = $base->getVar($this->grid_params, 'filter-dropdown-text', esc_attr__('Filter Categories', ESG_TEXTDOMAIN));
		$show_count = $base->getVar($this->grid_params, 'filter-counter', 'off');

		$nav->set_filter_text($filter_all_text);
		$nav->set_filterall_visible($filterall_visible);
		$nav->set_dropdown_text($filter_dropdown_text);
		$nav->set_show_count($show_count);

		$start_sortby = $base->getVar($this->grid_params, 'sorting-order-by-start', 'none');
		$start_sortby_type = $base->getVar($this->grid_params, 'sorting-order-type', 'ASC');

		$post_category = $base->getVar($this->grid_postparams, 'post_category');
		$post_types = $base->getVar($this->grid_postparams, 'post_types');
		$page_ids = explode(',', $base->getVar($this->grid_postparams, 'selected_pages', '-1'));
		$cat_relation = $base->getVar($this->grid_postparams, 'category-relation', 'OR');

		$additional_query = $base->getVar($this->grid_postparams, 'additional-query');
		if ($additional_query !== '')
			$additional_query = wp_parse_args($additional_query);

		$cat_tax = Essential_Grid_Base::getCatAndTaxData($post_category);

		$posts = Essential_Grid_Base::getPostsByCategory($this->grid_id, $cat_tax['cats'], $post_types, $cat_tax['tax'], $page_ids, $start_sortby, $start_sortby_type, -1, $additional_query, true, $cat_relation);

		$nav_filters = array();

		$taxes = array('post_tag');
		if (!empty($cat_tax['tax'])) $taxes = explode(',', $cat_tax['tax']);

		if (!empty($cat_tax['cats'])) { 
			$cats = explode(',', $cat_tax['cats']);

			foreach ($cats as $id) {
				$cat = get_category($id);
				if (is_object($cat))
					$nav_filters[$id] = array(
						'name' => $cat->cat_name,
						'slug' => Essential_Grid_Base::sanitize_utf8_to_unicode($cat->slug)
					);

				foreach ($taxes as $custom_tax) {
					$term = get_term_by('id', $id, $custom_tax);
					if (is_object($term))
						$nav_filters[$id] = array(
							'name' => $term->name,
							'slug' => Essential_Grid_Base::sanitize_utf8_to_unicode($term->slug)
						);
				}
			}

			asort($nav_filters);
		}

		$found_filter = array();
		if (!empty($posts) && count($posts) > 0) {
			foreach ($posts as $post) {
				// check if post should be visible or if its invisible on current grid settings
				$is_visible = $this->check_if_visible($post['ID'], $this->grid_id);
				if (!$is_visible) continue;

				$filters = array();
				$default_filter_add = $base->getVar($this->grid_params, 'add-filters-by', 'default');
				if (in_array($default_filter_add, array('default', 'categories'), true)) {
					$categories = $base->get_custom_taxonomies_by_post_id($post['ID']);
					if (!empty($categories)) {
						foreach ($categories as $category) {
							$filters[$category->term_id] = array(
								'name' => $category->name,
								'slug' => Essential_Grid_Base::sanitize_utf8_to_unicode($category->slug)
							);
						}
					}
				}

				if (in_array($default_filter_add, array('default', 'tags'), true)) {
					$tags = get_the_tags($post['ID']);
					if (!empty($tags)) {
						foreach ($tags as $taxonomie) {
							$filters[$taxonomie->term_id] = array(
								'name' => $taxonomie->name,
								'slug' => Essential_Grid_Base::sanitize_utf8_to_unicode($taxonomie->slug)
							);
						}
					}
				}

				$found_filter = $found_filter + $filters; //these are the found filters, only show filter that the posts have
			}
		}

		$remove_filter = array_diff_key($nav_filters, $found_filter); //check if we have filter that no post has (comes through multilanguage)
		if (!empty($remove_filter)) {
			foreach ($remove_filter as $key => $rem) { //we have, so remove them from the filter list before setting the filter list
				unset($nav_filters[$key]);
			}
		}

		$nav->set_filter($nav_filters); //set filters $nav_filters $found_filter
		$nav->set_filter_type($filter_allow);
		$nav->set_filter_start_select($filter_start);
		echo $nav->output_filter();
	}

	/**
	 * Output Filter from custom grid
	 * @since 1.0.6
	 */
	public function output_filter_by_custom()
	{
		do_action('essgrid_output_filter_by_custom', $this);

		$base = new Essential_Grid_Base();
		$nav = new Essential_Grid_Navigation();

		$filter_allow = $base->getVar($this->grid_params, 'filter-arrows', 'single');
		$filter_start = $base->getVar($this->grid_params, 'filter-start');
		$filterall_visible = $base->getVar($this->grid_params, 'filter-all-visible', 'on');
		$filter_all_text = $base->getVar($this->grid_params, 'filter-all-text', esc_attr__('Filter - All', ESG_TEXTDOMAIN));
		$filter_dropdown_text = $base->getVar($this->grid_params, 'filter-dropdown-text', esc_attr__('Filter Categories', ESG_TEXTDOMAIN));
		$show_count = $base->getVar($this->grid_params, 'filter-counter', 'off');

		$nav->set_dropdown_text($filter_dropdown_text);
		$nav->set_show_count($show_count);

		$nav->set_filter_text($filter_all_text);
		$nav->set_filterall_visible($filterall_visible);

		$found_filter = array();

		if (!empty($this->grid_layers) && count($this->grid_layers) > 0) {
			foreach ($this->grid_layers as $entry) {
				$filters = array();

				if (!empty($entry['custom-filter'])) {
					$cats = explode(',', $entry['custom-filter']);
					if (!is_array($cats))
						$cats = (array)$cats;
					foreach ($cats as $category) {
						$_v = Essential_Grid_Base::sanitize_utf8_to_unicode($category);
						$filters[$_v] = array(
							'name' => $category,
							'slug' => $_v
						);

						$found_filter = $found_filter + $filters; //these are the found filters, only show filter that the posts have

					}
				}
			}
		}

		$nav->set_filter($found_filter); //set filters $nav_filters $found_filter
		$nav->set_filter_type($filter_allow);
		$nav->set_filter_start_select($filter_start);

		echo $nav->output_filter();
	}

	/**
	 * Output Ajax Container
	 * @since 1.5.0
	 */
	public function output_ajax_container()
	{
		$base = new Essential_Grid_Base();

		$container_id = $base->getVar($this->grid_params, 'ajax-container-id');
		$container_css = $base->getVar($this->grid_params, 'ajax-container-css');
		$container_pre = $base->getVar($this->grid_params, 'ajax-container-pre');
		$container_post = $base->getVar($this->grid_params, 'ajax-container-post');

		$cont = '<div class="eg-ajax-target-container-wrapper" id="' . $container_id . '">' . "\n";
		$cont .= '  <div class="eg-ajax-target-prefix-wrapper">' . "\n";
		$cont .= html_entity_decode($container_pre);
		$cont .= '  </div>' . "\n";
		$cont .= '  <div class="eg-ajax-target"></div>' . "\n";
		$cont .= '  <div class="eg-ajax-target-sufffix-wrapper">' . "\n";
		$cont .= html_entity_decode($container_post);
		$cont .= '  </div>' . "\n";
		$cont .= '</div>' . "\n";

		if ($container_css !== '' && $container_id !== '') {
			$cont .= '<style type="text/css">' . "\n";
			$cont .= '#' . $container_id . ' {' . "\n";
			$cont .= $container_css;
			$cont .= '}' . "\n";
			$cont .= '</style>';
		}

		$cont = do_shortcode($cont);
		return apply_filters('essgrid_output_ajax_container', $cont, $this);
	}

	/**
	 * Output Inline JS
	 * @since 1.1.0
	 */
	public function add_inline_js()
	{
		echo apply_filters('essgrid_add_inline_js', $this->grid_inline_js);
	}

	/**
	 * Check the maximum entries that should be loaded
	 * @since: 1.5.3
	 */
	public function get_maximum_entries($grid)
	{
		$base = new Essential_Grid_Base();
		$max_entries = intval($grid->get_postparam_by_handle('max_entries', '-1'));

		//2.2
		if (is_admin()) $max_entries = intval($grid->get_postparam_by_handle('max_entries_preview', '-1'));
		if ($max_entries !== -1) return $max_entries;

		$layout = $grid->get_param_by_handle('navigation-layout', array());
		if (isset($layout['pagination']) || isset($layout['left']) || isset($layout['right']))
			return $max_entries;

		$rows_unlimited = $grid->get_param_by_handle('rows-unlimited', 'on');

		$rows = intval($grid->get_param_by_handle('rows', '3'));

		$columns_advanced = $grid->get_param_by_handle('columns-advanced', 'off');

		$columns = $grid->get_param_by_handle('columns', ''); //this is the first line
		$columns = $base->set_basic_colums($columns);

		$max_column = 0;
		foreach ($columns as $column) {
			if ($max_column < $column)
				$max_column = $column;
		}

		if ($columns_advanced === 'on') {
			$columns_advanced = $base->get_advanced_colums($this->grid_params, $columns);

			$match = array_fill(0, count($columns_advanced), 0);
			for ($i = 0; $i <= $rows; $i++) {
				foreach ($columns_advanced as $col_adv) {
					if (!empty($col_adv)) {
						foreach ($col_adv as $key => $val) {
							$match[$key] += $val;
						}
						$i++;
					}
					if ($i >= $rows)
						break;
				}
			}

			foreach ($match as $highest) {
				if ($max_column < $highest)
					$max_column = $highest;
			}
		}
		
		if ($rows_unlimited === 'off') {
			if ($columns_advanced === 'off') {
				$max_entries = $max_column * $rows;
			} else {
				$max_entries = $max_column;
			}
		}

		return apply_filters('essgrid_get_maximum_entries', $max_entries, $this, $grid);
	}

	/**
	 * Adds functionality for authors to modify things at activation of plugin
	 * @since 1.1.0
	 */
	public static function activation_hooks($networkwide = false)
	{
		//set all starting options
		$options = array();
		$options = apply_filters('essgrid_mod_activation_option', $options);
		if (function_exists('is_multisite') && is_multisite() && $networkwide) { //do for each existing site
			global $wpdb;

			// 2.2.5
			// Get all blog ids and create tables
			$blogids = $wpdb->get_col("SELECT blog_id FROM " . $wpdb->blogs);
			foreach ($blogids as $blog_id) {
				switch_to_blog($blog_id);
				foreach ($options as $opt => $val) {
					update_option('tp_eg_' . $opt, $val);
				}
				// 2.2.5
				restore_current_blog();
			}
		} else {
			foreach ($options as $opt => $val) {
				update_option('tp_eg_' . $opt, $val);
			}
		}
	}

	/**
	 * Hide Load More button
	 * @since 2.1.0
	 */
	public function remove_load_more_button($grid_id_wrap)
	{
		$base = new Essential_Grid_Base();

		$css = '';
		if ($base->getVar($this->grid_params, 'load-more-hide', 'off') == 'on' && $base->getVar($this->grid_params, 'load-more', 'none') == 'scroll') {
			$css = '<style type="text/css">';
			$css .= '#' . $grid_id_wrap . ' .esg-loadmore { display: none !important; }';
			$css .= '</style>';
		}
		echo apply_filters('essgrid_remove_load_more_button', $css, $grid_id_wrap);
	}

	/**
	 * Adds start height CSS for the Grid, to prevent jumping of Site on loading
	 * @since 2.0.4
	 */
	public function add_start_height_css($grid_id_wrap)
	{
		$base = new Essential_Grid_Base();

		$columns_advanced = $base->getVar($this->grid_params, 'columns-advanced', 'off');
		if ($columns_advanced == 'on') {
			$columns_width = $base->getVar($this->grid_params, 'columns-width');
			$columns_height = $base->getVar($this->grid_params, 'columns-height');
			$columns_width = $base->set_basic_colums_height($columns_width);
			$columns_height = $base->set_basic_colums_height($columns_height);

			// 2.2.5
			if (!is_array($columns_width))
				$columns_width = array(0, 0, 0, 0, 0, 0);
			if (!is_array($columns_height))
				$columns_height = array(0, 0, 0, 0, 0, 0);

			$col_height = array_reverse($columns_height); // reverse to start with the lowest value
			$col_width = array_reverse($columns_width); // reverse to start with the lowest value

			$first = true;

			$css = '<style type="text/css">';
			foreach ($col_height as $key => $height) {
				if ($height > 0) {
					$height = intval($height);
					$mw = intval($col_width[$key] - 1);
					if ($first) { //first set up without restriction of width
						$first = false;
						$css .= '
  #' . $grid_id_wrap . '.eg-startheight{ height: ' . $height . 'px; }';
					} else {
						$css .= '
  @media only screen and (min-width: ' . $mw . 'px) {
  #' . $grid_id_wrap . '.eg-startheight{ height: ' . $height . 'px; }
  }';
					}
				}
			}
			$css .= '</style>';

			echo $css . "\n";
			if ($css !== '<style type="text/css"></style>')
				return true;
		}

		return false;
	}

	/**
	 * Does the uninstallation process, also multisite checks
	 * @since 1.5.0
	 */
	public static function uninstall_plugin($networkwide = false)
	{
		global $wpdb;
		
		// If uninstall not called from WordPress, then exit
		if (!defined('WP_UNINSTALL_PLUGIN')) {
			exit;
		}
		if (function_exists('is_multisite') && is_multisite() && $networkwide) { //do for each existing site
			// Get all blog ids and create tables
			$blogids = $wpdb->get_col("SELECT blog_id FROM " . $wpdb->blogs);
			foreach ($blogids as $blog_id) {
				switch_to_blog($blog_id);
				self::_uninstall_plugin();
				// 2.2.5
				restore_current_blog();
			}
		} else {
			self::_uninstall_plugin();
		}
	}

	/**
	 * Does the uninstallation process
	 * @since 1.5.0
	 * @moved from uninstall.php
	 */
	public static function _uninstall_plugin()
	{
		global $wpdb;
		
		// If uninstall not called from WordPress, then exit
		if (!defined('WP_UNINSTALL_PLUGIN')) {
			exit;
		}

		//Delete Database Tables
		$wpdb->query("DROP TABLE " . Essential_Grid_Db::get_table('grids'));
		$wpdb->query("DROP TABLE " . Essential_Grid_Db::get_table('skins'));
		$wpdb->query("DROP TABLE " . Essential_Grid_Db::get_table('elements'));
		$wpdb->query("DROP TABLE " . Essential_Grid_Db::get_table('nav_skins'));

		//Delete Options
		delete_option('tp_eg_role');
		delete_option('tp_eg_grids_version');
		delete_option('tp_eg_custom_css');

		delete_option('tp_eg_output_protection');
		delete_option('tp_eg_tooltips');
		delete_option('tp_eg_js_to_footer');
		delete_option('tp_eg_use_cache');
		delete_option('tp_eg_query_type');
		delete_option('tp_eg_enable_log');
		delete_option('tp_eg_show_stream_failure_msg');
		delete_option('tp_eg_stream_failure_custom_msg');
		delete_option('tp_eg_use_lightbox');

		delete_option('tp_eg_update-check');
		delete_option('tp_eg_update-check-short');
		delete_option('tp_eg_latest-version');
		delete_option('tp_eg_code');
		delete_option('tp_eg_valid');
		delete_option('tp_eg_valid-notice');

		delete_option('esg-widget-areas');
		delete_option('esg-custom-meta');
		delete_option('esg-custom-link-meta');
		delete_option('esg-search-settings');

		delete_option('tp_eg_custom_css_imported');

		do_action('essgrid__uninstall_plugin');

	}

	/* format lightbox post content wrapper */
	public static function on_lightbox_post_content($settings, $id)
	{
		global $rs_loaded_by_editor;

		$content = '';
		$raw_content = '';
		if (!empty($settings)) {
			$settings = json_decode(stripslashes($settings), true);
			if (empty($settings)) return '';

			foreach($settings as $name => $setting){
				$settings[$name] = esc_attr($setting);
			}
			$settings['lbTag'] = sanitize_key($settings['lbTag']);
			if (!in_array($settings['lbTag'], array('h1', 'h2', 'h3', 'h4', 'h5', 'h6'))) $settings['lbTag'] = 'h2';
			
			$rs_loaded_by_editor = true;
			
			$featured = (isset($settings['featured'])) ? $settings['featured'] : '';
			$titl = (isset($settings['titl'])) ? $settings['titl'] : '';
			$lbTitle = (isset($settings['lbTitle'])) ? $settings['lbTitle'] : '';
			$lbTag = (isset($settings['lbTag'])) ? $settings['lbTag'] : '';
			$lbImg = (isset($settings['lbImg'])) ? $settings['lbImg'] : '';

			$wid = (isset($settings['lbWidth'])) ? $settings['lbWidth'] : '';
			$lbPos = (isset($settings['lbPos'])) ? $settings['lbPos'] : '';

			$minW = (isset($settings['lbMin'])) ? $settings['lbMin'] : '';
			$maxW = (isset($settings['lbMax'])) ? $settings['lbMax'] : '';

			$margin = (isset($settings['margin'])) ? $settings['margin'] : '';
			$margin = explode('|', $margin);

			$padding = (isset($settings['padding'])) ? $settings['padding'] : '';
			$padding = explode('|', $padding);

			if (!empty($margin) && count($margin) === 4) {
				$margin = intval($margin[0]) . 'px ' . intval($margin[1]) . 'px ' . intval($margin[2]) . 'px ' . intval($margin[3]) . 'px';
			} else {
				$margin = '0';
			}

			if (!empty($padding) && count($padding) === 4) {
				$padding = intval($padding[0]) . 'px ' . intval($padding[1]) . 'px ' . intval($padding[2]) . 'px ' . intval($padding[3]) . 'px';
			} else {
				$padding = '0';
			}

			$html = '<div class="eg-lightbox-post-content" style="width: ' . $maxW . ';min-width: ' . $minW . '; max-width: ' . $maxW . '; margin: ' . $margin . '">' . '<div class="eg-lightbox-post-content-inner" style="padding: ' . $padding . '">';

			if (!empty($settings['revslider']) && class_exists('RevSliderSlider')) {

				$slider_id = $settings['revslider'];
				if (is_numeric($slider_id)) {

					$rev_slider = new RevSliderSlider();
					if (method_exists($rev_slider, 'get_slider_for_admin_menu')) {

						$sliders = $rev_slider->get_slider_for_admin_menu();
						if (!empty($sliders) && array_key_exists($slider_id, $sliders)) {
							
							ob_start();
							if (method_exists('RevSliderFront','js_set_start_size')) {
								RevSliderFront::js_set_start_size();
							}
							$slider = new RevSliderOutput();
							$slider->set_ajax_loaded();
							
							$slider->add_slider_to_stage($slider_id);
							$_html = ob_get_contents();
							ob_clean();
							ob_end_clean();

							if (!empty($_html)) {
								return $html . $_html . '</div></div>';
							}
						}
					}
				}
			} else if (!empty($settings['essgrid'])) {
				$esg_alias = $settings['essgrid'];
				if (!is_numeric($esg_alias)) {
					$grids = Essential_Grid::get_essential_grids();
					foreach ($grids as $grid) {
						$alias = $grid->handle;
						if ($alias === $esg_alias) {
							$content = do_shortcode('[ess_grid alias="' . $alias . '"][/ess_grid]');
							if ($content)
								return $html . $content . '</div></div>';
							break;
						}
					}
				}
			} else {
				if (!empty($settings['ispost']) && $id > 0) {
					$raw_content = get_post_field('post_content', $id);
				} else {
					$gridid = isset($settings['gridid']) ? $settings['gridid'] : false;
					if (is_numeric($gridid)) {
						$grid = new Essential_Grid();
						$result = $grid->init_by_id($gridid);
						if ($result) {
							$itm = $grid->get_layer_values();
							if (!empty($itm) && isset($itm[$id])) {
								$itm = $itm[$id];
								$raw_content = !empty($itm['content']) ? $itm['content'] : '';
							}
						}
					}
				}

				if (!is_wp_error($raw_content)) {
					$content = apply_filters('essgrid_the_content', $raw_content);
					if (method_exists('WPBMap', 'addAllMappedShortcodes')) {
						WPBMap::addAllMappedShortcodes();
					}
					
					$rs_loaded_by_editor = true;
					$content = do_shortcode($content);
					$rs_loaded_by_editor = false;
				}
			}

			if (!empty($titl) && $lbTitle === 'on') {
				if (empty($lbTag))
					$lbTag = 'h2';
				$titl = '<' . $lbTag . '>' . stripslashes($titl) . '</' . $lbTag . '>';
			} else {
				$titl = '';
			}

			if (!empty($featured) && $lbImg === 'on') {
				$margin = $settings['lbMargin'];
				$margin = explode('|', $margin);
				if (!empty($margin) && count($margin) === 4) {
					$margin = $margin[0] . 'px ' . $margin[1] . 'px ' . $margin[2] . 'px ' . $margin[3] . 'px';
				} else {
					$margin = '0';
				}

				if (!is_numeric($wid)) $wid = 50;
				$wid = intval($wid);

				$dif = 100 - $wid;
				$dif = 'width: ' . $dif . '%';
				$wid = 'width: ' . $wid . '%';
				$featured = '<img class="esg-post-featured-img" src="' . $featured . '" style="width: 100%; height: auto; padding: ' . $margin . '" />';

				switch ($lbPos) {

					case 'top':
						$html .= $featured . $titl . $content;
						break;

					case 'left':
						$html .= '<div class="esg-f-left" style="' . $wid . '">' . $featured . '</div>';
						$html .= '<div class="esg-f-left" style="' . $dif . '">' . $titl . $content . '</div>';
						$html .= '<div class="esg-clearfix" ></div>';
						break;

					case 'right':
						$html .= '<div class="esg-f-left" style="' . $dif . '">' . $titl . $content . '</div>';
						$html .= '<div class="esg-f-left" style="' . $wid . '">' . $featured . '</div>';
						$html .= '<div class="esg-clearfix" ></div>';
						break;

					case 'bottom':
						$html .= $titl . $content . $featured;
						break;

				}

			} else {
				$html .= $titl . $content;
			}
			return $html . '</div></div>';
		}

		return $content;
	}

	/**
	 * action fired before actually processing any ajax actions
	 * @return void 
	 */
	public function before_front_ajax_action()
	{
	}

	/**
	 * Handle Ajax Requests
	 */
	public static function on_front_ajax_action()
	{
		do_action('essgrid_before_front_ajax_action');
		
		$base = new Essential_Grid_Base();

		//verify the token, removed in 2.1.0
		$isVerified = true;
		
		$error = false;
		if ($isVerified) {
			$data = $base->getPostVar('data', false);
			$action = !isset($_GET['client_action']) ? $base->getPostVar('client_action', false) : $_GET['client_action'];
			switch ($action) {

				case 'load_more_items':
					$gridid = $base->getPostVar('gridid', 0, 'i');
					if (!empty($data) && $gridid > 0) {
						$grid = new Essential_Grid();

						$result = $grid->init_by_id($gridid);
						if (!$result) {
							$error = esc_attr__('Grid not found', ESG_TEXTDOMAIN);
						} else {
							// set to only load choosen items
							$grid->set_loading_ids($data); 

							//check if we are custom grid
							if ($grid->is_custom_grid()) {
								$html = $grid->output_by_specific_ids();
							} elseif ($grid->is_stream_grid()) {
								$html = $grid->output_by_specific_stream();
							} else {
								$html = $grid->output_by_specific_posts();
							}

							/* 2.1.5 */
							if (!empty($html)) {
								self::ajaxResponseData($html);
							} else {
								/* 2.1.5 */
								$customGallery = $base->getPostVar('customgallery', false);
								if (!empty($customGallery)) {
									$grid->custom_images = $data;
									$html = $grid->output_by_gallery(false, true, true);
								}
								if (!empty($html)) {
									self::ajaxResponseData($html);
								} else {
									$error = esc_attr__('Items Not Found', ESG_TEXTDOMAIN);
								}
							}
						}
					} else {
						$error = esc_attr__('No Data Received', ESG_TEXTDOMAIN);
					}
					break;
					
				case 'load_more_content':
					$postid = $base->getPostVar('postid', 0, 'i');
					if ($postid > 0) {
						$raw_content = get_post_field('post_content', $postid);
						if (!is_wp_error($raw_content)) {
							$content = apply_filters('essgrid_the_content', $raw_content); //filter apply for qTranslate and other

							if (method_exists('WPBMap', 'addAllMappedShortcodes')) {
								WPBMap::addAllMappedShortcodes();
							}
							$content = do_shortcode($content);

							if (strpos($content, '<rs-module-wrap') !== false && class_exists('RevSliderFront')) {
								ob_start();
								RevSliderFront::add_inline_js();
								$content .= ob_get_clean();
							}

							self::ajaxResponseData($content);
						}
					}
					$error = esc_attr__('Post Not Found', ESG_TEXTDOMAIN);
					break;

				case 'load_post_content':
					$postid = isset($_GET['postid']) ? $_GET['postid'] : 0;
					if (is_numeric($postid)) {
						$settings = isset($_GET['settings']) ? $_GET['settings'] : false;
						echo apply_filters('essgrid_lightbox_post_content', $settings, $postid);
						die();
					}
					$error = esc_attr__('Post Not Found', ESG_TEXTDOMAIN);
					break;
					
				case 'get_search_results':
					$search_string = $base->getVar($data, 'search');
					$search_skin = $base->getVar($data, 'skin', 0, 'i');
					if ($search_string !== '' && $search_skin > 0) {
						$search = new Essential_Grid_Search();
						$return = $search->output_search_result($search_string, $search_skin);
						self::ajaxResponseData($return);
					}
					$error = esc_attr__('Not found', ESG_TEXTDOMAIN);
					break;
					
				case 'get_grid_search_ids':
					$search_string = $base->getVar($data, 'search');
					$grid_id = $base->getVar($data, 'id', 0, 'i');
					if ($search_string !== '' && $grid_id > 0) {
						$return = Essential_Grid_Search::output_search_result_ids($search_string, $grid_id);
						if (is_array($return)) {
							self::ajaxResponseSuccess('', $return);
						}
					}
					$error = esc_attr__('Not found', ESG_TEXTDOMAIN);
					break;
					
			}

			$error = apply_filters('essgrid_on_front_ajax_action', $error, $data);
		} else {
			$error = true;
		}

		if ($error !== false) {
			$showError = esc_attr__('Loading Error', ESG_TEXTDOMAIN);
			if ($error !== true) $showError = $error;
			self::ajaxResponseError($showError, false);
		}
		exit();
	}

	/**
	 * echo json ajax response
	 */
	public static function ajaxResponse($success, $message, $arrData = null)
	{
		$response = array();
		$response["success"] = $success;
		$response["message"] = $message;
		if (!empty($arrData)) {
			if (gettype($arrData) == "string" || gettype($arrData) == "boolean")
				$arrData = array(
					"data" => $arrData
				);
			$response = array_merge($response, $arrData);
		}
		$json = json_encode($response);
		echo $json;
		exit();
	}

	/**
	 * echo json ajax response, without message, only data
	 */
	public static function ajaxResponseData($arrData)
	{
		if (gettype($arrData) == "string")
			$arrData = array(
				"data" => $arrData
			);

		self::ajaxResponse(true, "", $arrData);
	}

	/**
	 * echo json ajax response
	 */
	public static function ajaxResponseError($message, $arrData = null)
	{
		self::ajaxResponse(false, $message, $arrData);
	}

	/**
	 * echo ajax success response
	 */
	public static function ajaxResponseSuccess($message, $arrData = null)
	{
		self::ajaxResponse(true, $message, $arrData);
	}

	/**
	 * echo ajax success response
	 */
	public static function ajaxResponseSuccessRedirect($message, $url)
	{
		$arrData = array(
			"is_redirect" => true,
			"redirect_url" => $url
		);
		self::ajaxResponse(true, $message, $arrData);
	}

	public static function custom_sorter_int($x, $y)
	{
		global $esg_c_sort_direction, $esg_c_sort_handle;

		if (!isset($x[$esg_c_sort_handle])) $x[$esg_c_sort_handle] = 0;
		if (!isset($y[$esg_c_sort_handle])) $y[$esg_c_sort_handle] = 0;
		if (in_array($esg_c_sort_handle, array('date_modified', 'date', 'modified'))) {
			$x[$esg_c_sort_handle] = strtotime($x[$esg_c_sort_handle]);
			$y[$esg_c_sort_handle] = strtotime($y[$esg_c_sort_handle]);
		} elseif ($esg_c_sort_handle == 'duration') {
			$x[$esg_c_sort_handle] = Essential_Grid::time_to_seconds($x[$esg_c_sort_handle]);
			$y[$esg_c_sort_handle] = Essential_Grid::time_to_seconds($y[$esg_c_sort_handle]);
		}

		if (!is_numeric($x[$esg_c_sort_handle])) $x[$esg_c_sort_handle] = intval(preg_replace("/[^0-9]/", "", $x[$esg_c_sort_handle]));
		if (!is_numeric($y[$esg_c_sort_handle])) $y[$esg_c_sort_handle] = intval(preg_replace("/[^0-9]/", "", $y[$esg_c_sort_handle]));

		if ($esg_c_sort_direction == 'ASC') {
			return $x[$esg_c_sort_handle] - $y[$esg_c_sort_handle];
		} else {
			return $y[$esg_c_sort_handle] - $x[$esg_c_sort_handle];
		}
	}

	public static function time_to_seconds($time_string)
	{
		$timeArr = array_reverse(explode(":", $time_string));
		$seconds = 0;
		foreach ($timeArr as $key => $value) {
			if ($key > 2) break;
			$seconds += pow(60, $key) * $value;
		}
		return $seconds;
	}

	public static function custom_sorter($x, $y)
	{
		global $esg_c_sort_direction, $esg_c_sort_handle;

		$sort_x = !isset($x[$esg_c_sort_handle]) ? '' : sanitize_title($x[$esg_c_sort_handle]);
		$sort_y = !isset($y[$esg_c_sort_handle]) ? '' : sanitize_title($y[$esg_c_sort_handle]);

		if ($esg_c_sort_direction == 'ASC') {
			return strcasecmp($sort_x, $sort_y);
		} else {
			return strcasecmp($sort_y, $sort_x);
		}
	}

	public function set_custom_sorter($handle, $direction)
	{
		global $esg_c_sort_direction, $esg_c_sort_handle;

		$esg_c_sort_direction = $direction;
		$esg_c_sort_handle = $handle;
	}

	public function order_by_custom($order_by_start, $order_by_dir)
	{
		$base = new Essential_Grid_Base();

		if (!empty($order_by_start) && !empty($this->grid_layers)) {
			if (is_array($order_by_start)) {
				$order_by_start = $order_by_start[0];
			}

			switch ($order_by_start) {
				case 'rand':
					$this->grid_layers = $base->shuffle_assoc($this->grid_layers);
					break;
				case 'title':
				case 'post_url':
				case 'excerpt':
				case 'meta':
				case 'alias':
				case 'name':
				case 'content':
				case 'author_name':
				case 'author':
				case 'cat_list':
				case 'tag_list':
					if ($order_by_start == 'name')
						$order_by_start = 'alias';
					if ($order_by_start == 'author')
						$order_by_start = 'author_name';
					//check if values are existing and if not, add them to the layers
					$this->set_custom_sorter($order_by_start, $order_by_dir);
					usort($this->grid_layers, array(
						'Essential_Grid',
						'custom_sorter'
					));
					break;
				case 'post_id':
				case 'ID':
				case 'num_comments':
				case 'comment_count':
				case 'date':
				case 'modified':
				case 'date_modified':
				case 'views':
				case 'likes':
				case 'dislikes':
				case 'retweets':
				case 'favorites':
				case 'itemCount':
				case 'duration':
					if ($order_by_start == 'comment_count')
						$order_by_start = 'num_comments';
					if ($order_by_start == 'modified')
						$order_by_start = 'date_modified';
					if ($order_by_start == 'ID')
						$order_by_start = 'post_id';

					$this->set_custom_sorter($order_by_start, $order_by_dir);
					usort($this->grid_layers, array(
						'Essential_Grid',
						'custom_sorter_int'
					));
					break;
			}
		}
	}

	/**
	 * Ajax Call to save Post Like
	 *
	 * @since   2.2
	 */
	public function ess_grid_post_like()
	{
		// Check for nonce security
		$nonce = $_POST['nonce'];
		if (!wp_verify_nonce($nonce, 'eg-ajax-nonce')) die('Busted!');

		if (isset($_POST['post_like'])) {
			// Retrieve user IP address
			$ip = $_SERVER['REMOTE_ADDR'];
			$post_id = $_POST['post_id'];

			// Get voters'IPs for the current post
			$meta_IP = get_post_meta($post_id, "eg_voted_IP");
			$voted_IP = $meta_IP[0];
			if (!is_array($voted_IP)) $voted_IP = array();

			// Get votes count for the current post
			$meta_count = get_post_meta($post_id, "eg_votes_count", true);

			// Use has already voted ?
			if (!$this->hasAlreadyVoted($post_id)) {
				$voted_IP[$ip] = time();

				// Save IP and increase votes count
				update_post_meta($post_id, "eg_voted_IP", $voted_IP);
				update_post_meta($post_id, "eg_votes_count", ++$meta_count);

				// Display count (ie jQuery return value)
				echo $meta_count;
			} else
				esc_html_e("already", ESG_TEXTDOMAIN);
		}
		exit;
	}

	/**
	 * Check if Post was already voted for
	 *
	 * @since   2.2
	 */
	public function hasAlreadyVoted($post_id)
	{
		$timebeforerevote = get_option('tp_eg_post_like_ip_lockout', '');
		if (empty($timebeforerevote))
			return false;

		// Retrieve post votes IPs
		$meta_IP = get_post_meta($post_id, "eg_voted_IP");
		$voted_IP = $meta_IP[0];
		if (!is_array($voted_IP)) $voted_IP = array();

		// Retrieve current user IP
		$ip = $_SERVER['REMOTE_ADDR'];

		// If user has already voted
		if (in_array($ip, array_keys($voted_IP))) {
			$time = $voted_IP[$ip];
			$now = time();

			// Compare between current time and vote time
			if (round(($now - $time) / 60) > $timebeforerevote) return false;

			return true;
		}

		return false;
	}

	public static function post_thumbnail_replace($html, $post_id, $post_thumbnail_id, $size, $attr)
	{
		$post_grid_id = get_post_meta($post_id, 'eg_featured_grid', true);
		if (!empty($post_grid_id)) $html = do_shortcode('[ess_grid alias="' . $post_grid_id . '"][/ess_grid]');
		
		return $html;
	}

	/**
	 * Improve UX for empty grids or when social stream data isn't available
	 * @since: 3.0.12
	 */
	private function display_grid_error_msg($fromStream = false, $sourceType = false)
	{
		if (!is_admin()) {
			$show_stream_failure_msg = get_option('tp_eg_show_stream_failure_msg', 'true');
			if (is_user_logged_in()) {
				if ($show_stream_failure_msg == 'true') {
					echo '<p><span class="esg-font-italic">';
					esc_html_e('No grid items found', ESG_TEXTDOMAIN);
					echo '</span><br><a href="' . admin_url() . 'admin.php?page=' . ESG_PLUGIN_SLUG . '&view=grid-create&create=' . $this->grid_id . '&sourcetab=true">';
					esc_html_e('Review source settings', ESG_TEXTDOMAIN);
					echo '</a></p>';
					return;
				}
			}
			if ($show_stream_failure_msg === 'custom') {
				$stream_failure_custom_msg = get_option('tp_eg_stream_failure_custom_msg', '');
				echo urldecode($stream_failure_custom_msg);
			}
		} else {
			echo '<div>';
			if ($sourceType) {
				esc_html_e('Grid "Source type" is empty! It might be connected to missing addons ( Like Social Media, Real Media Library or NextGen )', ESG_TEXTDOMAIN);
				echo '<br><br>';
				echo '<a id="go-to-source" class="esg-btn esg-purple" href="#">';
				esc_html_e('Edit Source Settings', ESG_TEXTDOMAIN);
				echo '</a> ';
				
			} elseif (!$fromStream) {
				esc_html_e('No posts found for this Grid.', ESG_TEXTDOMAIN);
				echo '<br><br>';
				echo '<a id="go-to-source" class="esg-btn esg-purple" href="#">';
				esc_html_e('Edit Source Settings', ESG_TEXTDOMAIN);
				echo '</a> <a class="esg-btn esg-purple" href="' . admin_url() . 'edit.php" target="_blank">Create Posts</a>';
			} else {
				esc_html_e('Please check stream source type settings.', ESG_TEXTDOMAIN);
				echo '<br><br><a id="go-to-source" class="esg-btn esg-purple" href="#">';
				esc_html_e('Check Settings', ESG_TEXTDOMAIN);
				echo '</a>';
			}
			echo '</div>';
		}
	}

	/**
	 * check if lightbox need additions
	 * 
	 * @param string $lightbox_mode
	 * @param string $lightbox_include_media
	 * @param Essential_Grid_Item_Skin $item_skin
	 * @param array $post
	 * @return void
	 */
	private function _is_lightbox_additions($lightbox_mode, $lightbox_include_media, $item_skin, $post)
	{
		$base = new Essential_Grid_Base();
		
		if ($lightbox_mode == 'content' || $lightbox_mode == 'content-gallery' || $lightbox_mode == 'woocommerce-gallery') {
			$lb_add_images = array();
			$lb_add_images_thumb = array();
			switch ($lightbox_mode) {
				case 'content':
					$lb_add_images = $base->get_all_content_images($post['ID']);
					$lb_add_images_thumb = $base->get_all_content_images($post['ID'], false, 'thumbnail');
					break;
				case 'content-gallery':
					$lb_add_images = $base->get_all_gallery_images($post['post_content'], true);
					$lb_add_images_thumb = $base->get_all_gallery_images($post['post_content'], true, 'thumbnail');
					break;
				case 'woocommerce-gallery':
					if (Essential_Grid_Woocommerce::is_woo_exists()) {
						$lb_add_images = Essential_Grid_Woocommerce::get_image_attachements($post['ID'], true);
						$lb_add_images_thumb = Essential_Grid_Woocommerce::get_image_attachements($post['ID'], true, 'thumbnail');
					}
					break;
			}

			$item_skin->set_lightbox_addition(array(
				'items' => $lb_add_images,
				'thumbs' => $lb_add_images_thumb,
				'base' => $lightbox_include_media
			));
		}
	}

	/**
	 * add esg ajax action to divi actions list to load builder and process divi shortcodes
	 * 
	 * @param array $actions
	 * @return array
	 */
	public function add_divi_support($actions)
	{
		if (empty($actions['action'])) {
			$actions['action'] = array();
		}
		
		$actions['action'][] = 'Essential_Grid_Front_request_ajax';
		
		return $actions;
	}

	/**
	 * add admin menu points in ToolBar Top
	 * @return void
	 */
	public function add_admin_bar()
	{
		if (is_admin() || !is_super_admin() || !is_admin_bar_showing()) return;
		?>
		<script>
			function esg_adminBarToolBarTopFunction() {
				if(jQuery('#wp-admin-bar-esg-default').length > 0 && jQuery('.esg-grid-wrap-container').length > 0){
					var aliases = [];
					jQuery('.esg-grid-wrap-container').each(function(){
						aliases.push(jQuery(this).data('alias'));
					});

					if(aliases.length > 0){
						jQuery('#wp-admin-bar-esg-default li').each(function(){
							var li = jQuery(this),
								t = li.find('.ab-item .esg-label').data('alias'); //text()
							t = t!==undefined && t!==null ? t.trim() : t;
							if(jQuery.inArray(t,aliases)!=-1){
							}else{
								li.remove();
							}
						});
					}
				}else{
					jQuery('#wp-admin-bar-esg').remove();
				}
			}
			
			var esg_adminBarLoaded_once = false;
			if (document.readyState === "loading")
				document.addEventListener('readystatechange',function(){
					if ((document.readyState === "interactive" || document.readyState === "complete") && !esg_adminBarLoaded_once) {
						esg_adminBarLoaded_once = true;
						esg_adminBarToolBarTopFunction()
					}
				});
			else {
				esg_adminBarLoaded_once = true;
				esg_adminBarToolBarTopFunction();
			}
		</script>
		<?php
	}

	/**
	 * add admin nodes
	 * @return void
	 */
	public function add_admin_menu_nodes(){
		if(is_admin() || !is_super_admin() || !is_admin_bar_showing()){
			return;
		}

		$this->_add_node(
			'<span class="esg-label">Essential Grid</span>',
			false,
			admin_url('admin.php?page=essential-grid'),
			array('class' => 'esg-menu'),
			'esg'
		);

		//add all nodes of all Slider
		$arrGrids = self::get_essential_grids();
		if (empty($arrGrids)) return;
		
		foreach ($arrGrids as $grid){
			$this->_add_node(
				'<span class="esg-label" data-alias="' . esc_attr($grid->handle) . '">' . esc_html($grid->name) . '</span>',
				'esg',
				admin_url('admin.php?page=essential-grid&view=grid-create&create=' . $grid->id),
				array('class' => 'esg-sub-menu'),
				esc_attr($grid->handle)
			);
		}
	}

	/**
	 * add admin node
	 * @return void
	 */
	protected function _add_node($title, $parent = false, $href = '', $custom_meta = array(), $id = ''){
		global $wp_admin_bar;
		
		if (is_admin() || !is_super_admin() || !is_admin_bar_showing()) return;

		$id = ($id == '') ? strtolower(str_replace(' ', '-', $title)) : $id;
		$meta = (strpos($href, site_url()) !== false) ? array() : array('target' => '_blank');
		$meta = array_merge($meta, $custom_meta);
		
		$wp_admin_bar->add_node(array('parent' => $parent, 'id' => $id, 'title' => $title, 'href' => $href, 'meta' => $meta));
	}
	
}

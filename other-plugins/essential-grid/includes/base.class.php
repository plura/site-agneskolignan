<?php
/**
 * @package   Essential_Grid
 * @author    ThemePunch <info@themepunch.com>
 * @link      http://www.themepunch.com/essential/
 * @copyright 2023 ThemePunch
 */

if (!defined('ABSPATH')) exit();

class Essential_Grid_Base
{
	/**
	 * @return mixed
	 */
	public static function getValid()
	{
		return get_option('tp_eg_valid', 'false');
	}
	
	/**
	 * @param string $status  can be true or false
	 * @return bool
	 */
	public static function setValid($status)
	{
		if (!in_array($status, array('true', 'false'))) $status = 'false';
		return update_option('tp_eg_valid', $status);
	}

	/**
	 * @return bool
	 */
	public static function isValid()
	{
		return get_option('tp_eg_valid', 'false') === 'true';
	}

	/**
	 * @return mixed
	 */
	public static function getValidNotice()
	{
		return get_option('tp_eg_valid-notice', 'true');
	}

	/**
	 * @param string $status  can be true or false
	 * @return bool
	 */
	public static function setValidNotice($status)
	{
		if (!in_array($status, array('true', 'false'))) $status = 'false';
		return update_option('tp_eg_valid-notice', $status);
	}
	
	/**
	 * @return mixed
	 */
	public static function getCode()
	{
		return get_option('tp_eg_code', '');
	}

	/**
	 * @param string $code
	 * @return bool
	 */
	public static function setCode($code)
	{
		return update_option('tp_eg_code', $code);
	}
	
	/**
	 * @return array
	 */
	public static function getExcludePostTypes()
	{
		$exclude_post_types = get_option('tp_eg_exclude_post_types', '');
		if (!empty($exclude_post_types)) $exclude_post_types = explode(',', $exclude_post_types); else $exclude_post_types = array();
		
		return $exclude_post_types;
	}

	/**
	 * @param string|array $post_types
	 * @return bool
	 */
	public static function setExcludePostTypes($post_types)
	{
		if (is_array($post_types)) $post_types = implode(',', $post_types);
		return update_option('tp_eg_exclude_post_types', $post_types);
	}

	/**
	 * Get $_GET Parameter
	 *
	 * @param string $key
	 * @param string $default
	 * @param string $type
	 * @return mixed
	 */
	public static function getGetVar($key, $default = "", $type = "")
	{
		$val = self::getVar($_GET, $key, $default, $type);
		return apply_filters('essgrid_getGetVar', $val, $key, $default, $type);
	}

	/**
	 * Get $_POST Parameter
	 *
	 * @param string $key
	 * @param string $default
	 * @param string $type
	 * @return mixed
	 */
	public static function getPostVar($key, $default = "", $type = "")
	{
		$val = self::getVar($_POST, $key, $default, $type);
		return apply_filters('essgrid_getPostVar', $val, $key, $default, $type);
	}
	
	public static function varToType($val, $type = '')
	{
		//scalar =  int, float, string и bool
		if(!is_scalar($val)) return Essential_Grid_Base::stripslashes_deep($val);
		
		switch ($type) {
			case 'i': //int
				$val = intval($val);
				break;
			case 'f': //float
				$val = floatval($val);
				break;
			case 'r': //raw meaning, do nothing
				break;
			default:
				$val = Essential_Grid_Base::stripslashes_deep($val);
				break;
		}
		
		return $val;
	}

	public static function getVar($arr, $key, $default = '', $type = '')
	{
		//scalar =  int, float, string и bool
		if(is_scalar($arr)) return $default;
		//convert obj to array 
		if(is_object($arr)) $arr = (array)$arr;
		//if key is string, check immediately 
		if(!is_array($key)) {
			$val = (isset($arr[$key])) ? $arr[$key] : $default;
			return self::varToType($val, $type);
		}
		
		//loop through keys
		foreach($key as $v){
			if(is_object($arr)) $arr = (array)$arr;
			if(isset($arr[$v])) {
				$arr = $arr[$v];
			} else {
				return $default;
			}
		}
		
		return self::varToType($arr, $type);
	}

	public static function varToBool($v){
		if($v === 'false' || $v === false || $v === 'off' || $v ===	NULL || $v === 0 || $v === -1){
			$v = false;
		}elseif($v === 'true' || $v === true || $v === 'on'){
			$v = true;
		}

		return $v;
	}

	/**
	 * Throw exception
	 */
	public static function throw_error($message, $code = null)
	{
		$a = apply_filters('essgrid_throw_error', array('message' => $message, 'code' => $code));

		if (!empty($code))
			throw new Exception($a['message'], $a['code']);
		else
			throw new Exception($a['message']);
	}

	/**
	 * Sort Array by Value order
	 */
	public static function sort_by_order($a, $b)
	{
		if (!isset($a['order']) || !isset($b['order'])) return 0;
		$a = $a['order'];
		$b = $b['order'];
		return (($a < $b) ? -1 : (($a > $b) ? 1 : 0));
	}

	/**
	 * change hex to rgba
	 */
	public static function hex2rgba($hex, $transparency = false)
	{
		if ($transparency !== false) {
			$transparency = ($transparency > 0) ? number_format(($transparency / 100), 2, ".", "") : 0;
		} else {
			$transparency = 1;
		}

		$hex = str_replace("#", "", $hex);
		$r = 0;
		$g = 0;
		$b = 0;
		if (strlen($hex) == 3) {
			$r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
			$g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
			$b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
		} else {
			if (strlen($hex) >= 6) {
				$r = hexdec(substr($hex, 0, 2));
				$g = hexdec(substr($hex, 2, 2));
				$b = hexdec(substr($hex, 4, 2));
			}
		}

		return apply_filters('essgrid_hex2rgba', 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . $transparency . ')', $hex, $transparency);
	}

	/**
	 * strip slashes recursive
	 */
	public static function stripslashes_deep($value)
	{
		if (!empty($value)) {
			$value = is_array($value) ?
				array_map(array('Essential_Grid_Base', 'stripslashes_deep'), $value) :
				stripslashes($value);
		}
		
		return apply_filters('essgrid_stripslashes_deep', $value);
	}

	/**
	 * get text intro, limit by number of words
	 */
	public static function get_text_intro($text, $limit, $type = 'words')
	{
		$intro = $text;
		if (empty($text)) {
			// Do Nothing
		} elseif ($type == 'words') {
			$arrIntro = explode(' ', $text);
			if (count($arrIntro) > $limit) {
				$arrIntro = array_slice($arrIntro, 0, $limit);
				$intro = trim(implode(" ", $arrIntro));
				if (!empty($intro))
					$intro .= '...';
			} else {
				$intro = implode(" ", $arrIntro);
			}
		} elseif ($type == 'chars') {
			$text = strip_tags($text);
			$intro = mb_substr($text, 0, $limit, 'utf-8');
			if (strlen($text) > $limit) $intro .= '...';
		} elseif ($type == 'sentence') {
			$text = strip_tags($text);
			$intro = Essential_Grid_Base::bac_variable_length_excerpt($text, $limit);
		}
		$intro = preg_replace('`\[[^\]]*\]`', '', $intro);

		return apply_filters('essgrid_get_text_intro', $intro, $text, $limit, $type);
	}

	public static function bac_variable_length_excerpt($text, $length = 1, $finish_sentence = 1)
	{
		$tokens = array();
		$out = '';
		$word = 0;

		//Divide the string into tokens; HTML tags, or words, followed by any whitespace.
		$regex = '/(<[^>]+>|[^<>\s]+)\s*/u';
		preg_match_all($regex, $text, $tokens);
		foreach ($tokens[0] as $t) {
			//Parse each token
			if ($word >= $length && !$finish_sentence) {
				//Limit reached
				break;
			}
			if ($t[0] != '<') {
				//Token is not a tag.
				//Regular expression that checks for the end of the sentence: '.', '?' or '!'
				$regex1 = '/[\?\.\!]\s*$/uS';
				if ($word >= $length && $finish_sentence && preg_match($regex1, $t) == 1) {
					//Limit reached, continue until ? . or ! occur to reach the end of the sentence.
					$out .= trim($t);
					break;
				}
				$word++;
			}
			//Append what's left of the token.
			$out .= $t;
		}
		//Add the excerpt ending as a link.
		$excerpt_end = '';

		//Append the excerpt ending to the token.
		$out .= $excerpt_end;

		return trim(force_balance_tags($out));
	}

	/**
	 * Get all images sizes + custom added sizes
	 * since: 1.0.2
	 */
	public function get_all_image_sizes()
	{
		$custom_sizes = array();
		$added_image_sizes = get_intermediate_image_sizes();
		if (!empty($added_image_sizes) && is_array($added_image_sizes)) {
			foreach ($added_image_sizes as $img_size_handle) {
				$custom_sizes[$img_size_handle] = ucwords(str_replace('_', ' ', $img_size_handle));
			}
		}
		$img_orig_sources = array(
			'full' => esc_attr__('Original Size', ESG_TEXTDOMAIN),
			'thumbnail' => esc_attr__('Thumbnail', ESG_TEXTDOMAIN),
			'medium' => esc_attr__('Medium', ESG_TEXTDOMAIN),
			'large' => esc_attr__('Large', ESG_TEXTDOMAIN)
		);

		return apply_filters('essgrid_get_all_image_sizes', array_merge($img_orig_sources, $custom_sizes));
	}

	/**
	 * convert date to the date format that the user chose.
	 */
	public static function convert_post_date($date)
	{
		if (empty($date))
			return ($date);
		$date = date_i18n(get_option('date_format'), strtotime($date));
		return apply_filters('essgrid_convert_post_date', $date);
	}

	/**
	 * Create Multilanguage for JavaScript
	 */
	protected static function get_javascript_multilanguage()
	{
		$lang = array(
			'aj_please_wait' => esc_attr__('Please wait...', ESG_TEXTDOMAIN),
			'aj_ajax_error' => esc_attr__('Ajax Error!!!', ESG_TEXTDOMAIN),
			'aj_success_must' => esc_attr__('The \'success\' param is a must!', ESG_TEXTDOMAIN),
			'aj_error_not_found' => esc_attr__('Ajax Error! Action not found!', ESG_TEXTDOMAIN),
			'aj_empty_response' => esc_attr__('Empty ajax response!', ESG_TEXTDOMAIN),
			'aj_wrong_alias' => esc_attr__('Wrong ajax alias!', ESG_TEXTDOMAIN),
			'delete_item_skin' => esc_attr__('Really delete choosen Item Skin?', ESG_TEXTDOMAIN),
			'delete_grid' => esc_attr__('Really delete the Grid?', ESG_TEXTDOMAIN),
			'choose_image' => esc_attr__('Choose Image', ESG_TEXTDOMAIN),
			'select_choose' => esc_attr__('--- choose ---', ESG_TEXTDOMAIN),
			'new_element' => esc_attr__('New Element', ESG_TEXTDOMAIN),
			'bottom_on_hover' => esc_attr__('Bottom on Hover', ESG_TEXTDOMAIN),
			'top_on_hover' => esc_attr__('Top on Hover', ESG_TEXTDOMAIN),
			'hidden' => esc_attr__('Hidden', ESG_TEXTDOMAIN),
			'full_price' => esc_attr__('$99 $999', ESG_TEXTDOMAIN),
			'regular_price' => esc_attr__('$99', ESG_TEXTDOMAIN),
			'regular_price_no_cur' => esc_attr__('99', ESG_TEXTDOMAIN),
			'top' => esc_attr__('Top', ESG_TEXTDOMAIN),
			'right' => esc_attr__('Right', ESG_TEXTDOMAIN),
			'bottom' => esc_attr__('Bottom', ESG_TEXTDOMAIN),
			'left' => esc_attr__('Left', ESG_TEXTDOMAIN),
			'hide' => esc_attr__('Hide', ESG_TEXTDOMAIN),
			'single' => esc_attr__('Add Single Image', ESG_TEXTDOMAIN),
			'bulk' => esc_attr__('Add Bulk Images', ESG_TEXTDOMAIN),
			'choose_images' => esc_attr__('Choose Images', ESG_TEXTDOMAIN),
			'import_demo_post_heavy_loading' => esc_attr__('The following demo data will be imported: Ess. Grid Posts, Custom Meta, PunchFonts. This can take a while, please do not leave the site until the import is finished', ESG_TEXTDOMAIN),
			'import_demo_grids_210' => esc_attr__('The following demo data will be imported: Grids of the 2.1.0 update. This can take a while, please do not leave the site until the import is finished', ESG_TEXTDOMAIN),
			'save_settings' => esc_attr__('Save Settings', ESG_TEXTDOMAIN),
			'add_element' => esc_attr__('Add Element', ESG_TEXTDOMAIN),
			'edit_element' => esc_attr__('Edit Element', ESG_TEXTDOMAIN),
			'update_element' => esc_attr__('Update without Refresh', ESG_TEXTDOMAIN),
			'update_element_refresh' => esc_attr__('Update & Refresh Grid', ESG_TEXTDOMAIN),
			'globalcoloractive' => esc_attr__('Color Skin Active', ESG_TEXTDOMAIN),
			'editskins' => esc_attr__('Edit Skins', ESG_TEXTDOMAIN),
			'remove_this_element' => esc_attr__('Really remove this element?', ESG_TEXTDOMAIN),
			'choose_skins' => esc_attr__('Choose Skins', ESG_TEXTDOMAIN),
			'add_selected' => esc_attr__('Add Selected', ESG_TEXTDOMAIN),
			'deleting_nav_skin_message' => esc_attr__('Deleting a Navigation Skin may result in missing Skins in other Grids. Proceed?', ESG_TEXTDOMAIN),
			'add_meta' => esc_attr__('Add Meta', ESG_TEXTDOMAIN),
			'backtooverview' => esc_attr__('Back to Overview', ESG_TEXTDOMAIN),
			'openimportdgrid' => esc_attr__('Open Imported Grid', ESG_TEXTDOMAIN),
			'add_widget_area' => esc_attr__('Add Widget Area', ESG_TEXTDOMAIN),
			'add_font' => esc_attr__('Add Google Font', ESG_TEXTDOMAIN),
			'save_post_meta' => esc_attr__('Save Post Meta', ESG_TEXTDOMAIN),
			'really_change_widget_area_name' => esc_attr__('Are you sure the change the Widget Area name?', ESG_TEXTDOMAIN),
			'really_delete_widget_area' => esc_attr__('Really delete this Widget Area? This can\'t be undone and if may affect existing Posts/Pages that use this Widget Area.', ESG_TEXTDOMAIN),
			'really_delete_meta' => esc_attr__('Really delete this meta? This can\'t be undone.', ESG_TEXTDOMAIN),
			'really_change_meta_effects' => esc_attr__('If you change this settings, it may affect current Posts that use this meta, proceed?', ESG_TEXTDOMAIN),
			'really_change_font_effects' => esc_attr__('If you change this settings, it may affect current Posts that use this Font, proceed?', ESG_TEXTDOMAIN),
			'handle_and_name_at_least_3' => esc_attr__('The handle and name has to be at least three characters long!', ESG_TEXTDOMAIN),
			'handle_already_exists' => esc_attr__('Handle already exists!', ESG_TEXTDOMAIN),
			'layout_settings' => esc_attr__('Layout Settings', ESG_TEXTDOMAIN),
			'close' => esc_attr__('Close', ESG_TEXTDOMAIN),
			'reset_nav_skin' => esc_attr__('Reset from Template', ESG_TEXTDOMAIN),
			'create_nav_skin' => esc_attr__('Create Navigation Skin', ESG_TEXTDOMAIN),
			'save_nav_skin' => esc_attr__('Save Navigation Skin', ESG_TEXTDOMAIN),
			'apply_changes' => esc_attr__('Save Changes', ESG_TEXTDOMAIN),
			'new_element_sanitize' => esc_attr__('new-element', ESG_TEXTDOMAIN),
			'really_delete_element_permanently' => esc_attr__('This will delete this element permanently, really proceed?', ESG_TEXTDOMAIN),
			'element_name_exists_do_overwrite' => esc_attr__('Element with chosen name already exists. Really overwrite the Element?', ESG_TEXTDOMAIN),
			'element_was_not_changed' => esc_attr__('Element was not created/changed', ESG_TEXTDOMAIN),
			'not_selected' => esc_attr__('Not Selected', ESG_TEXTDOMAIN),
			'class_name' => esc_attr__('Class:', ESG_TEXTDOMAIN),
			'class_name_short' => esc_attr__('Class', ESG_TEXTDOMAIN),
			'save_changes' => esc_attr__('Save Changes', ESG_TEXTDOMAIN),
			'add_category' => esc_attr__('Add Category', ESG_TEXTDOMAIN),
			'category_already_exists' => esc_attr__('The Category existing already.', ESG_TEXTDOMAIN),
			'edit_category' => esc_attr__('Edit Category', ESG_TEXTDOMAIN),
			'update_category' => esc_attr__('Update Category', ESG_TEXTDOMAIN),
			'delete_category' => esc_attr__('Delete Category', ESG_TEXTDOMAIN),
			'select_skin' => esc_attr__('Select From Skins', ESG_TEXTDOMAIN),
			'enter_position' => esc_attr__('Enter a Position', ESG_TEXTDOMAIN),
			'leave_not_saved' => esc_attr__('By leaving now, all changes since the last saving will be lost. Really leave now?', ESG_TEXTDOMAIN),
			'please_enter_unique_item_name' => esc_attr__('Please enter a unique item name', ESG_TEXTDOMAIN),
			'fontello_icons' => esc_attr__('Choose Icon', ESG_TEXTDOMAIN),
			'please_enter_unique_element_name' => esc_attr__('Please enter a unique element name', ESG_TEXTDOMAIN),
			'please_enter_unique_skin_name' => esc_attr__('Please enter a unique Navigation Skin name', ESG_TEXTDOMAIN),
			'item_name_too_short' => esc_attr__('Item name too short', ESG_TEXTDOMAIN),
			'skin_name_too_short' => esc_attr__('Navigation Skin name too short', ESG_TEXTDOMAIN),
			'skin_name_already_registered' => esc_attr__('Navigation Skin with choosen name already exists, please choose a different name', ESG_TEXTDOMAIN),
			'withvimeo' => esc_attr__('Vimeo', ESG_TEXTDOMAIN),
			'withyoutube' => esc_attr__('YouTube', ESG_TEXTDOMAIN),
			'withwistia' => esc_attr__('Wistia', ESG_TEXTDOMAIN),
			'withimage' => esc_attr__('Image', ESG_TEXTDOMAIN),
			'withthtml5' => esc_attr__('HTML5 Video', ESG_TEXTDOMAIN),
			'withsoundcloud' => esc_attr__('SoundCloud', ESG_TEXTDOMAIN),
			'withoutmedia' => esc_attr__('Without Media', ESG_TEXTDOMAIN),
			'withblank' => esc_attr__('Blank Item', ESG_TEXTDOMAIN),
			'selectyouritem' => esc_attr__('Select Your Item', ESG_TEXTDOMAIN),
			'add_at_least_one_element' => esc_attr__('Please add at least one element in Custom Grid mode', ESG_TEXTDOMAIN),
			'dontforget_title' => esc_attr__('Please set a Title for the Grid', ESG_TEXTDOMAIN),
			'dontforget_alias' => esc_attr__('Please set an Alias for the Grid', ESG_TEXTDOMAIN),
			'script_will_try_to_load_last_working' => esc_attr__('Ess. Grid will now try to go to the last working version of this grid', ESG_TEXTDOMAIN),
			'save_rules' => esc_attr__('Save Rules', ESG_TEXTDOMAIN),
			'discard_changes' => esc_attr__('Discard Changes', ESG_TEXTDOMAIN),
			'really_discard_changes' => esc_attr__('Really discard changes?', ESG_TEXTDOMAIN),
			'reset_fields' => esc_attr__('Reset Fields', ESG_TEXTDOMAIN),
			'really_reset_fields' => esc_attr__('Really reset fields?', ESG_TEXTDOMAIN),
			'meta_val' => esc_attr__('(Meta)', ESG_TEXTDOMAIN),
			'deleting_this_cant_be_undone' => esc_attr__('Deleting this can\'t be undone, continue?', ESG_TEXTDOMAIN),
			'shortcode' => esc_attr__('ShortCode', ESG_TEXTDOMAIN),
			'filter' => esc_attr__('Filter', ESG_TEXTDOMAIN),
			'skin' => esc_attr__('Skin', ESG_TEXTDOMAIN),
			'custom_filter' => esc_attr__('--- Custom Filter ---', ESG_TEXTDOMAIN),
			'delete_this_element' => esc_attr__('Are you sure you want to delete this element?', ESG_TEXTDOMAIN),
			'editnavinfo' => esc_attr__('Edit the selected navigation skin style', ESG_TEXTDOMAIN),
			'editnavinfodep' => esc_attr__('Nav. Skin deprecated. Edit or reset from template!', ESG_TEXTDOMAIN),
			'select_skin_template' => esc_attr__('Select Navigation Template', ESG_TEXTDOMAIN),
			'pagination_autoplay_notice' => esc_attr__('Autoplay allowed only with Pagination or Navigation Arrows!', ESG_TEXTDOMAIN),
			'select_items_export' => esc_attr__('Please select item(s) to export!', ESG_TEXTDOMAIN),
			'select' => esc_attr__('Select ', ESG_TEXTDOMAIN),
			'unselect' => esc_attr__('Unselect ', ESG_TEXTDOMAIN),
			'select_from_list' => esc_attr__('Select From List', ESG_TEXTDOMAIN),
			'show' => esc_attr__('Show', ESG_TEXTDOMAIN),
			'per_page' => esc_attr__('per page', ESG_TEXTDOMAIN),
			'cannotbeundone' => esc_attr__('This action can not be undone !!', ESG_TEXTDOMAIN),
			'deletegrid' => esc_attr__('Delete Grid', ESG_TEXTDOMAIN),
			'areyousuretodelete' => esc_attr__('Are you sure you want to delete ', ESG_TEXTDOMAIN),
			'yesdelete' => esc_attr__('Yes, Delete Grid', ESG_TEXTDOMAIN),
			'cancel' => esc_attr__('Cancel', ESG_TEXTDOMAIN),
			'embedgrid' => esc_attr__('Embed Grid', ESG_TEXTDOMAIN),
			'copy' => esc_attr__('Copy', ESG_TEXTDOMAIN),
			'copytoclipboard' => esc_attr__('Click on button below to copy shortcode to clipboard', ESG_TEXTDOMAIN),
			'shortcodecopied' => esc_attr__('Shortcode copied to clipboard', ESG_TEXTDOMAIN),
			/* ADDONS */
			'addon' => esc_attr__('Add-On', ESG_TEXTDOMAIN),
			'installingaddon' => esc_attr__('Installing Add-On', ESG_TEXTDOMAIN),
			'updatingaddon' => esc_attr__('Updating Add-On', ESG_TEXTDOMAIN),
			'enableaddon' => esc_attr__('Enable Add-On', ESG_TEXTDOMAIN),
			'enablingaddon' => esc_attr__('Enabling Add-On', ESG_TEXTDOMAIN),
			'disableaddon' => esc_attr__('Disable Add-On', ESG_TEXTDOMAIN),
			'disablingaddon' => esc_attr__('Disabling Add-On', ESG_TEXTDOMAIN),
			'activateaddon' => esc_attr__('Activate Add-On', ESG_TEXTDOMAIN),
			'activatingaddon' => esc_attr__('Activating Add-On', ESG_TEXTDOMAIN),
			'enableglobaladdon' => esc_attr__('Enable Global Add-On', ESG_TEXTDOMAIN),
			'disableglobaladdon' => esc_attr__('Disable Global Add-On', ESG_TEXTDOMAIN),
			'activateglobaladdon' => esc_attr__('Activate Global Add-On', ESG_TEXTDOMAIN),
			'err_slug_missing' => esc_attr__('Add-On slug is missing!', ESG_TEXTDOMAIN),
			'fix' => esc_attr__('Fix', ESG_TEXTDOMAIN),
			'fix_addons_title_missing_desc' => esc_attr__('These Essential Grid addons are deactivated or not installed:', ESG_TEXTDOMAIN),
			'fix_addons_title_updated_desc' => esc_attr__('These Essential Grid addons require updating:', ESG_TEXTDOMAIN),
			'fix_addons_missing_desc' => esc_attr__('Press the button below to install & activate all required addons', ESG_TEXTDOMAIN),
			'fix_addons_updated_desc' => esc_attr__('Press the button below to update all required addons', ESG_TEXTDOMAIN),
			/* LIBRARY */
			'library_install_grid' => esc_attr__('Install Grid', ESG_TEXTDOMAIN),
			'library_install_grid_addons' => esc_attr__('Install Grid & Addon(s)', ESG_TEXTDOMAIN),
			/* SHORTCODE */
			'essential_grid_shortcode_creator' => esc_attr__('Essential Grid Shortcode Creator', ESG_TEXTDOMAIN),
			'shortcode_generator' => esc_attr__('Shortcode Creator', ESG_TEXTDOMAIN),
			'no_pregrid_selected' => esc_attr__('No Predefined Essential Grid has been selected !', ESG_TEXTDOMAIN),
			'insert_shortcode' => esc_attr__('Insert / Update', ESG_TEXTDOMAIN),
			'read_shortcode' => esc_attr__('Read Shortcode', ESG_TEXTDOMAIN),
			'import_shortcode' => esc_attr__('Import Shortcode', ESG_TEXTDOMAIN),
			'edit_custom_item' => esc_attr__('Edit Custom Item', ESG_TEXTDOMAIN),
			'quickbuilder' => esc_attr__('Quick Builder', ESG_TEXTDOMAIN),
			'please_add_at_least_one_layer' => esc_attr__('Please add at least one Layer.', ESG_TEXTDOMAIN),
			'shortcode_parsing_successfull' => esc_attr__('Shortcode parsing successfull. Items can be found in step 3', ESG_TEXTDOMAIN),
			'shortcode_could_not_be_correctly_parsed' => esc_attr__('Shortcode could not be parsed.', ESG_TEXTDOMAIN),
		);

		return apply_filters('essgrid_get_javascript_multilanguage', $lang);
	}

	/**
	 * get grid animations
	 */
	public static function get_grid_animations()
	{
		$animations = array(
			'fade' => esc_attr__('Fade', ESG_TEXTDOMAIN),
			'scale' => esc_attr__('Scale', ESG_TEXTDOMAIN),
			'rotatescale' => esc_attr__('Rotate Scale', ESG_TEXTDOMAIN),
			'fall' => esc_attr__('Fall', ESG_TEXTDOMAIN),
			'rotatefall' => esc_attr__('Rotate Fall', ESG_TEXTDOMAIN),
			'horizontal-slide' => esc_attr__('Horizontal Slide', ESG_TEXTDOMAIN),
			'vertical-slide' => esc_attr__('Vertical Slide', ESG_TEXTDOMAIN),
			'horizontal-flip' => esc_attr__('Horizontal Flip', ESG_TEXTDOMAIN),
			'vertical-flip' => esc_attr__('Vertical Flip', ESG_TEXTDOMAIN),
			'horizontal-flipbook' => esc_attr__('Horizontal Flipbook', ESG_TEXTDOMAIN),
			'vertical-flipbook' => esc_attr__('Vertical Flipbook', ESG_TEXTDOMAIN)
		);
		
		return apply_filters('essgrid_get_grid_animations', $animations);
	}

	/**
	 * get grid animations
	 */
	public static function get_start_animations()
	{
		$animations = array(
			'none' => esc_attr__('None', ESG_TEXTDOMAIN),
			'reveal' => esc_attr__('Reveal', ESG_TEXTDOMAIN),
			'fade' => esc_attr__('Fade', ESG_TEXTDOMAIN),
			'scale' => esc_attr__('Scale', ESG_TEXTDOMAIN),
			'slideup' => esc_attr__('Slide Up (short)', ESG_TEXTDOMAIN),
			'covergrowup' => esc_attr__('Slide Up (long)', ESG_TEXTDOMAIN),
			'slideleft' => esc_attr__('Slide Left', ESG_TEXTDOMAIN),
			'slidedown' => esc_attr__('Slide Down', ESG_TEXTDOMAIN),
			'flipvertical' => esc_attr__('Flip Vertical', ESG_TEXTDOMAIN),
			'fliphorizontal' => esc_attr__('Flip Horizontal', ESG_TEXTDOMAIN),
			'flipup' => esc_attr__('Flip Up', ESG_TEXTDOMAIN),
			'flipdown' => esc_attr__('Flip Down', ESG_TEXTDOMAIN),
			'flipright' => esc_attr__('Flip Right', ESG_TEXTDOMAIN),
			'flipleft' => esc_attr__('Flip Left', ESG_TEXTDOMAIN),
			'skewleft' => esc_attr__('Skew', ESG_TEXTDOMAIN),
			'zoomin' => esc_attr__('Rotate Zoom', ESG_TEXTDOMAIN),
			'flyleft' => esc_attr__('Fly Left', ESG_TEXTDOMAIN),
			'flyright' => esc_attr__('Fly Right', ESG_TEXTDOMAIN)
		);

		return apply_filters('essgrid_get_grid_start_animations', $animations);
	}

	/**
	 * get grid item animations, since 2.1.6.2
	 */
	public static function get_grid_item_animations()
	{
		$animations = array(
			'none' => esc_attr__('None', ESG_TEXTDOMAIN),
			'zoomin' => esc_attr__('Zoom In', ESG_TEXTDOMAIN),
			'zoomout' => esc_attr__('Zoom Out', ESG_TEXTDOMAIN),
			'fade' => esc_attr__('Fade Out', ESG_TEXTDOMAIN),
			'blur' => esc_attr__('Blur', ESG_TEXTDOMAIN),
			'shift' => esc_attr__('Shift', ESG_TEXTDOMAIN),
			'rotate' => esc_attr__('Rotate', ESG_TEXTDOMAIN)
		);

		return apply_filters('essgrid_get_grid_item_animations', $animations);
	}

	/**
	 * get grid animations
	 */
	public static function get_hover_animations($inout = false)
	{
		if (!$inout) {
			$animations = array(
				'none' => esc_attr__(' None', ESG_TEXTDOMAIN),
				'fade' => esc_attr__('Fade', ESG_TEXTDOMAIN),
				'flipvertical' => esc_attr__('Flip Vertical', ESG_TEXTDOMAIN),
				'fliphorizontal' => esc_attr__('Flip Horizontal', ESG_TEXTDOMAIN),
				'flipup' => esc_attr__('Flip Up', ESG_TEXTDOMAIN),
				'flipdown' => esc_attr__('Flip Down', ESG_TEXTDOMAIN),
				'flipright' => esc_attr__('Flip Right', ESG_TEXTDOMAIN),
				'flipleft' => esc_attr__('Flip Left', ESG_TEXTDOMAIN),
				'turn' => esc_attr__('Turn', ESG_TEXTDOMAIN),
				'slide' => esc_attr__('Slide', ESG_TEXTDOMAIN),
				'scaleleft' => esc_attr__('Scale Left', ESG_TEXTDOMAIN),
				'scaleright' => esc_attr__('Scale Right', ESG_TEXTDOMAIN),
				'slideleft' => esc_attr__('Slide Left', ESG_TEXTDOMAIN),
				'slideright' => esc_attr__('Slide Right', ESG_TEXTDOMAIN),
				'slideup' => esc_attr__('Slide Up', ESG_TEXTDOMAIN),
				'slidedown' => esc_attr__('Slide Down', ESG_TEXTDOMAIN),
				'slideshortleft' => esc_attr__('Slide Short Left', ESG_TEXTDOMAIN),
				'slideshortright' => esc_attr__('Slide Short Right', ESG_TEXTDOMAIN),
				'slideshortup' => esc_attr__('Slide Short Up', ESG_TEXTDOMAIN),
				'slideshortdown' => esc_attr__('Slide Short Down', ESG_TEXTDOMAIN),
				'skewleft' => esc_attr__('Skew Left', ESG_TEXTDOMAIN),
				'skewright' => esc_attr__('Skew Right', ESG_TEXTDOMAIN),
				'rollleft' => esc_attr__('Roll Left', ESG_TEXTDOMAIN),
				'rollright' => esc_attr__('Roll Right', ESG_TEXTDOMAIN),
				'falldown' => esc_attr__('Fall Down', ESG_TEXTDOMAIN),
				'rotatescale' => esc_attr__('Rotate Scale', ESG_TEXTDOMAIN),
				'zoomback' => esc_attr__('Zoom from Back', ESG_TEXTDOMAIN),
				'zoomfront' => esc_attr__('Zoom from Front', ESG_TEXTDOMAIN),
				'flyleft' => esc_attr__('Fly Left', ESG_TEXTDOMAIN),
				'flyright' => esc_attr__('Fly Right', ESG_TEXTDOMAIN),
				'covergrowup' => esc_attr__('Cover Grow', ESG_TEXTDOMAIN),
				'collapsevertical' => esc_attr__('Collapse Vertical', ESG_TEXTDOMAIN),
				'collapsehorizontal' => esc_attr__('Collapse Horizontal', ESG_TEXTDOMAIN),
				'linediagonal' => esc_attr__('Line Diagonal', ESG_TEXTDOMAIN),
				'linehorizontal' => esc_attr__('Line Horizontal', ESG_TEXTDOMAIN),
				'linevertical' => esc_attr__('Line Vertical', ESG_TEXTDOMAIN),
				'spiralzoom' => esc_attr__('Spiral Zoom', ESG_TEXTDOMAIN),
				'circlezoom' => esc_attr__('Circle Zoom', ESG_TEXTDOMAIN)
			);
		} else {
			$animations = array(
				'none' => esc_attr__(' None', ESG_TEXTDOMAIN),
				'fade' => esc_attr__('Fade In', ESG_TEXTDOMAIN),
				'fadeout' => esc_attr__('Fade Out', ESG_TEXTDOMAIN),
				'flipvertical' => esc_attr__('Flip Vertical In', ESG_TEXTDOMAIN),
				'flipverticalout' => esc_attr__('Flip Vertical Out', ESG_TEXTDOMAIN),
				'fliphorizontal' => esc_attr__('Flip Horizontal In', ESG_TEXTDOMAIN),
				'fliphorizontalout' => esc_attr__('Flip Horizontal Out', ESG_TEXTDOMAIN),
				'flipup' => esc_attr__('Flip Up In Out', ESG_TEXTDOMAIN),
				'flipupout' => esc_attr__('Flip Up Out', ESG_TEXTDOMAIN),
				'flipdown' => esc_attr__('Flip Down In', ESG_TEXTDOMAIN),
				'flipdownout' => esc_attr__('Flip Down Out', ESG_TEXTDOMAIN),
				'flipright' => esc_attr__('Flip Right In', ESG_TEXTDOMAIN),
				'fliprightout' => esc_attr__('Flip Right Out', ESG_TEXTDOMAIN),
				'flipleft' => esc_attr__('Flip Left In', ESG_TEXTDOMAIN),
				'flipleftout' => esc_attr__('Flip Left Out', ESG_TEXTDOMAIN),
				'turn' => esc_attr__('Turn In', ESG_TEXTDOMAIN),
				'turnout' => esc_attr__('Turn Out', ESG_TEXTDOMAIN),
				'slideleft' => esc_attr__('Slide Left In', ESG_TEXTDOMAIN),
				'slideleftout' => esc_attr__('Slide Left Out', ESG_TEXTDOMAIN),
				'slideright' => esc_attr__('Slide Right In', ESG_TEXTDOMAIN),
				'sliderightout' => esc_attr__('Slide Right Out', ESG_TEXTDOMAIN),
				'slideup' => esc_attr__('Slide Up In', ESG_TEXTDOMAIN),
				'slideupout' => esc_attr__('Slide Up Out', ESG_TEXTDOMAIN),
				'slidedown' => esc_attr__('Slide Down In', ESG_TEXTDOMAIN),
				'slidedownout' => esc_attr__('Slide Down Out', ESG_TEXTDOMAIN),
				'slideshortleft' => esc_attr__('Slide Short Left In', ESG_TEXTDOMAIN),
				'slideshortleftout' => esc_attr__('Slide Short Left Out', ESG_TEXTDOMAIN),
				'slideshortright' => esc_attr__('Slide Short Right In', ESG_TEXTDOMAIN),
				'slideshortrightout' => esc_attr__('Slide Short Right Out', ESG_TEXTDOMAIN),
				'slideshortup' => esc_attr__('Slide Short Up In', ESG_TEXTDOMAIN),
				'slideshortupout' => esc_attr__('Slide Short Up Out', ESG_TEXTDOMAIN),
				'slideshortdown' => esc_attr__('Slide Short Down In', ESG_TEXTDOMAIN),
				'slideshortdownout' => esc_attr__('Slide Short Down Out', ESG_TEXTDOMAIN),
				'skewleft' => esc_attr__('Skew Left In', ESG_TEXTDOMAIN),
				'skewleftout' => esc_attr__('Skew Left Out', ESG_TEXTDOMAIN),
				'skewright' => esc_attr__('Skew Right In', ESG_TEXTDOMAIN),
				'skewrightout' => esc_attr__('Skew Right Out', ESG_TEXTDOMAIN),
				'rollleft' => esc_attr__('Roll Left In', ESG_TEXTDOMAIN),
				'rollleftout' => esc_attr__('Roll Left Out', ESG_TEXTDOMAIN),
				'rollright' => esc_attr__('Roll Right In', ESG_TEXTDOMAIN),
				'rollrightout' => esc_attr__('Roll Right Out', ESG_TEXTDOMAIN),
				'falldown' => esc_attr__('Fall Down In', ESG_TEXTDOMAIN),
				'falldownout' => esc_attr__('Fall Down Out', ESG_TEXTDOMAIN),
				'rotatescale' => esc_attr__('Rotate Scale In', ESG_TEXTDOMAIN),
				'rotatescaleout' => esc_attr__('Rotate Scale Out', ESG_TEXTDOMAIN),
				'zoomback' => esc_attr__('Zoom from Back In', ESG_TEXTDOMAIN),
				'zoombackout' => esc_attr__('Zoom from Back Out', ESG_TEXTDOMAIN),
				'zoomfront' => esc_attr__('Zoom from Front In', ESG_TEXTDOMAIN),
				'zoomfrontout' => esc_attr__('Zoom from Front Out', ESG_TEXTDOMAIN),
				'flyleft' => esc_attr__('Fly Left In', ESG_TEXTDOMAIN),
				'flyleftout' => esc_attr__('Fly Left Out', ESG_TEXTDOMAIN),
				'flyright' => esc_attr__('Fly Right In', ESG_TEXTDOMAIN),
				'flyrightout' => esc_attr__('Fly Right Out', ESG_TEXTDOMAIN),
				'covergrowup' => esc_attr__('Cover Grow In', ESG_TEXTDOMAIN),
				'covergrowupout' => esc_attr__('Cover Grow Out', ESG_TEXTDOMAIN),
				'collapsevertical' => esc_attr__('Collapse Vertical', ESG_TEXTDOMAIN),
				'collapsehorizontal' => esc_attr__('Collapse Horizontal', ESG_TEXTDOMAIN),
				'linediagonal' => esc_attr__('Line Diagonal', ESG_TEXTDOMAIN),
				'linehorizontal' => esc_attr__('Line Horizontal', ESG_TEXTDOMAIN),
				'linevertical' => esc_attr__('Line Vertical', ESG_TEXTDOMAIN),
				'spiralzoom' => esc_attr__('Spiral Zoom', ESG_TEXTDOMAIN),
				'circlezoom' => esc_attr__('Circle Zoom', ESG_TEXTDOMAIN)
			);
		}
		asort($animations);

		return apply_filters('essgrid_get_hover_animations', $animations);
	}

	/**
	 * get media animations (only out animations!)
	 */
	public static function get_media_animations()
	{
		$media_anim = array(
			'none' => esc_attr__(' None', ESG_TEXTDOMAIN),
			'flipverticalout' => esc_attr__('Flip Vertical', ESG_TEXTDOMAIN),
			'fliphorizontalout' => esc_attr__('Flip Horizontal', ESG_TEXTDOMAIN),
			'fliprightout' => esc_attr__('Flip Right', ESG_TEXTDOMAIN),
			'flipleftout' => esc_attr__('Flip Left', ESG_TEXTDOMAIN),
			'flipupout' => esc_attr__('Flip Up', ESG_TEXTDOMAIN),
			'flipdownout' => esc_attr__('Flip Down', ESG_TEXTDOMAIN),
			'shifttotop' => esc_attr__('Shift To Top', ESG_TEXTDOMAIN),
			'turnout' => esc_attr__('Turn', ESG_TEXTDOMAIN),
			'3dturnright' => esc_attr__('3D Turn Right', ESG_TEXTDOMAIN),
			'pressback' => esc_attr__('Press Back', ESG_TEXTDOMAIN),
			'zoomouttocorner' => esc_attr__('Zoom Out To Side', ESG_TEXTDOMAIN),
			'zoomintocorner' => esc_attr__('Zoom In To Side', ESG_TEXTDOMAIN),
			'zoomtodefault' => esc_attr__('Zoom To Default', ESG_TEXTDOMAIN),
			'zoomdefaultblur' => esc_attr__('Zoom Default Blur', ESG_TEXTDOMAIN),
			'mediazoom' => esc_attr__('Zoom', ESG_TEXTDOMAIN),
			'blur' => esc_attr__('Blur', ESG_TEXTDOMAIN),
			'fadeblur' => esc_attr__('Fade Blur', ESG_TEXTDOMAIN),
			'grayscalein' => esc_attr__('GrayScale In', ESG_TEXTDOMAIN),
			'grayscaleout' => esc_attr__('GrayScale Out', ESG_TEXTDOMAIN),
			'zoomblur' => esc_attr__('Zoom Blur', ESG_TEXTDOMAIN),
			'zoombackout' => esc_attr__('Zoom to Back', ESG_TEXTDOMAIN),
			'zoomfrontout' => esc_attr__('Zoom to Front', ESG_TEXTDOMAIN),
			'zoomandrotate' => esc_attr__('Zoom And Rotate', ESG_TEXTDOMAIN)
		);

		return apply_filters('essgrid_get_media_animations', $media_anim);
	}

	/**
	 * set basic columns if empty
	 */
	public static function set_basic_colums($columns)
	{
		if (!is_array($columns)) {
			$columns = (array)$columns;
			//added for quick live grids
			//all desktop sizes are the same size
			for($i=0;$i<3;$i++){
				$columns[] = $columns[0];
			}
		}
		$devices = self::get_basic_devices();
		foreach ($devices as $k => $v) {
			if (!isset($columns[$k]) || intval($columns[$k]) == 0) $columns[$k] = $v['columns'];
		}

		return apply_filters('essgrid_set_basic_colums', $columns);
	}

	/**
	 * set basic columns if empty
	 */
	public static function set_basic_colums_custom($columns)
	{
		$new_columns = self::set_basic_colums($columns);
		return apply_filters('essgrid_set_basic_colums_custom', $new_columns);
	}

	/**
	 * set basic height of Masonry Content if Empty
	 */
	public static function set_basic_mascontent_height($mascontent_height)
	{
		if (!is_array($mascontent_height)) $mascontent_height = (array)$mascontent_height;
		$amount = count(self::get_basic_devices());
		for ($i = 0; $i < $amount; $i++) {
			if (!isset($mascontent_height[$i]) || intval($mascontent_height[$i]) == 0) $mascontent_height[$i] = 0;
		}

		return apply_filters('essgrid_set_basic_mascontent_height', $mascontent_height);
	}

	/**
	 * set basic columns width if empty
	 */
	public static function set_basic_colums_width($columns_width = null)
	{
		if (!is_array($columns_width)) $columns_width = (array)$columns_width;
		$columns_width = array_map('intval', $columns_width);
		$devices = self::get_basic_devices();
		foreach ($devices as $k => $v) {
			if (!isset($columns_width[$k]) || $columns_width[$k] == 0) $columns_width[$k] = $v['width'];
		}

		return apply_filters('essgrid_set_basic_colums_width', $columns_width);
	}

	/**
	 * set basic columns width if empty
	 */
	public static function set_basic_masonry_content_height($mas_con_height)
	{
		if (!is_array($mas_con_height)) $mas_con_height = (array)$mas_con_height;
		$amount = count(self::get_basic_devices());
		for ($i = 0; $i < $amount; $i++) {
			if (!isset($mas_con_height[$i])) $mas_con_height[$i] = 0;
		}

		return apply_filters('essgrid_set_basic_masonry_content_height', $mas_con_height);
	}

	/**
	 * set basic columns height if empty
	 * @since: 2.0.4
	 */
	public static function set_basic_colums_height($columns_height)
	{
		$amount = count(self::get_basic_devices());
		for ($i = 0; $i < $amount; $i++) {
			if (!isset($columns_height[$i]) || intval($columns_height[$i]) == 0) $columns_height[$i] = 0;
		}

		return apply_filters('essgrid_set_basic_colums_height', $columns_height);
	}

	/**
	 * get advanced columns from parameters
	 * @since: 3.0.14
	 * @param array $params
	 * @param bool | array $columns
	 * @return array
	 */
	public static function get_advanced_colums($params, $columns = false)
	{
		$result = array();
		
		//if columns passed, prepend advanced columns with columns
		if (is_array($columns)) {
			$result[] = $columns;
		}
		
		for ($i = 0; $i <= 8; $i++) {
			$result[] = self::getVar($params, 'columns-advanced-rows-' . $i);
		}

		return apply_filters('essgrid_get_advanced_colums', $result);
	}

	/**
	 * get basic devices names
	 * @since: 2.0.4
	 * @return array
	 */
	public static function get_basic_devices()
	{
		$devices = array(
			array(
				'label' => 'Desktop XL',
				'plural' => 'XL desktop screens',
				'width' => 1900,
				'columns' => 5,
			),
			array(
				'label' => 'Desktop Large',
				'plural' => 'large desktop screens',
				'width' => 1400,
				'columns' => 5,
			),
			array(
				'label' => 'Desktop Medium',
				'plural' => 'medium sized desktop screens',
				'width' => 1170,
				'columns' => 4,
			),
			array(
				'label' => 'Desktop Small',
				'plural' => 'small sized desktop screens',
				'width' => 1024,
				'columns' => 4,
			),
			array(
				'label' => 'Tablet Landscape',
				'plural' => 'tablets in landscape view',
				'width' => 960,
				'columns' => 3,
			),
			array(
				'label' => 'Tablet',
				'plural' => 'tablets in portrait view',
				'width' => 778,
				'columns' => 3,
			),
			array(
				'label' => 'Mobile Landscape',
				'plural' => 'mobiles in landscape view',
				'width' => 640,
				'columns' => 3,
			),
			array(
				'label' => 'Mobile',
				'plural' => 'mobiles in portrait view',
				'width' => 480,
				'columns' => 1,
			),
		);

		return apply_filters('essgrid_get_basic_devices', $devices);
	}

	/**
	 * encode array into json for client side
	 * 
	 * @param array $arr
	 * @return string
	 */
	public static function jsonEncodeForClientSide($arr)
	{
		$json = json_encode($arr);
		$json = addslashes($json);
		$json = "'" . $json . "'";

		return apply_filters('essgrid_jsonEncodeForClientSide', $json, $arr);
	}

	/**
	 * Get url to secific view.
	 */
	public static function getViewUrl($viewName = "", $urlParams = "", $slug = "")
	{
		$params = "";
		$plugin = Essential_Grid::get_instance();
		if ($slug == "") $slug = $plugin->get_plugin_slug();
		if ($viewName != "") $params = "&view=" . $viewName;
		$params .= (!empty($urlParams)) ? "&" . $urlParams : "";
		$link = admin_url("admin.php?page=" . $slug . $params);

		return apply_filters('essgrid_getViewUrl', $link, $viewName, $urlParams, $slug);
	}

	/**
	 * Get url to secific view.
	 */
	public static function getSubViewUrl($viewName = "", $urlParams = "", $slug = "")
	{
		$params = "";
		$plugin = Essential_Grid::get_instance();
		if ($slug == "") $slug = $plugin->get_plugin_slug();
		if ($viewName != "") $params = "-" . $viewName;
		$params .= (!empty($urlParams)) ? "&" . $urlParams : "";
		$link = admin_url("admin.php?page=" . $slug . $params);

		return apply_filters('essgrid_getSubViewUrl', $link, $viewName, $urlParams, $slug);
	}

	/**
	 * Get Post Types + Custom Post Types
	 */
	public static function getPostTypesAssoc($arrPutToTop = array())
	{
		$arrBuiltIn = array("post" => "post", "page" => "page");
		$arrCustomTypes = get_post_types(array('_builtin' => false));

		//top items validation - add only items that in the customtypes list
		$arrPutToTopUpdated = array();
		foreach ($arrPutToTop as $topItem) {
			if (in_array($topItem, $arrCustomTypes)) {
				$arrPutToTopUpdated[$topItem] = $topItem;
				unset($arrCustomTypes[$topItem]);
			}
		}

		$arrPostTypes = array_merge($arrPutToTopUpdated, $arrBuiltIn, $arrCustomTypes);

		//update label
		foreach ($arrPostTypes as $key => $type) {
			$objType = get_post_type_object($type);

			if (empty($objType)) {
				$arrPostTypes[$key] = $type;
				continue;
			}

			// Remove NextGen Post Types from the list
			if (!strpos($objType->labels->singular_name, 'extGEN')) {
				$arrPostTypes[$key] = $objType->labels->singular_name;
			} else {
				unset($arrPostTypes[$key]);
			}
		}
		
		// remove excluded
		$exclude_post_types = Essential_Grid_Base::getExcludePostTypes();
		foreach ($exclude_post_types as $key) unset($arrPostTypes[$key]);

		/**
		 * @param array $arrPostTypes  Post Types + Custom Post Types
		 * @param array $arrPutToTop  contain post types that you want to put on the top of list
		 */
		return apply_filters('essgrid_getPostTypesAssoc', $arrPostTypes, $arrPutToTop);
	}

	/**
	 * Translate the Categories depending on selected language (needed for backend)
	 * @since: 1.5.0
	 */
	public function translate_base_categories_to_cur_lang($postTypes)
	{
		if (Essential_Grid_Wpml::is_wpml_exists()) {
			if (is_array($postTypes)) {
				foreach ($postTypes as $key => $type) {
					$tarr = explode('_', $type);
					$id = array_pop($tarr);
					$post_type = implode('_', $tarr);
					$id = icl_object_id(intval($id), $post_type, true, ICL_LANGUAGE_CODE);
					$postTypes[$key] = $post_type . '_' . $id;
				}
			}
		}

		return apply_filters('essgrid_translate_base_categories_to_cur_lang', $postTypes);
	}

	/**
	 * Get post types with categories.
	 */
	public static function getPostTypesWithCatsForClient()
	{
		global $sitepress;

		$arrPostTypes = self::getPostTypesWithCats();
		$globalCounter = 0;
		$arrOutput = array();
		foreach ($arrPostTypes as $postType => $arrTaxWithCats) {
			$arrCats = array();
			foreach ($arrTaxWithCats as $tax) {
				$taxName = $tax["name"];
				$taxTitle = $tax["title"];
				$globalCounter++;
				$arrCats["option_disabled_" . $globalCounter] = "---- " . $taxTitle . " ----";
				foreach ($tax["cats"] as $catID => $catTitle) {
					if (Essential_Grid_Wpml::is_wpml_exists() && isset($sitepress)) {
						$catID = icl_object_id($catID, $taxName, true, $sitepress->get_default_language());
					}
					$arrCats[$taxName . "_" . $catID] = $catTitle;
				}
			}//loop tax
			$arrOutput[$postType] = $arrCats;
		}//loop types

		return apply_filters('essgrid_getPostTypesWithCatsForClient', $arrOutput);
	}

	/**
	 * get array of post types with categories (the taxonomies is between).
	 * get only those taxomonies that have some categories in it.
	 */
	public static function getPostTypesWithCats()
	{
		$arrPostTypes = self::getPostTypesWithTaxomonies();
		$arrPostTypesOutput = array();
		foreach ($arrPostTypes as $name => $arrTax) {
			$arrTaxOutput = array();
			foreach ($arrTax as $taxName => $taxTitle) {
				$cats = self::getCategoriesAssoc($taxName);
				if (!empty($cats))
					$arrTaxOutput[] = array(
						"name" => $taxName,
						"title" => $taxTitle,
						"cats" => $cats);
			}
			$arrPostTypesOutput[$name] = $arrTaxOutput;
		}

		return apply_filters('essgrid_getPostTypesWithCats', $arrPostTypesOutput);
	}

	/**
	 * get current language code
	 */
	public static function get_current_lang_code()
	{
		$langTag = get_bloginfo('language');
		$data = explode('-', $langTag);
		$code = $data[0];
		return apply_filters('essgrid_get_current_lang_code', $code);
	}

	/**
	 * get post types array with taxomonies
	 */
	public static function getPostTypesWithTaxomonies()
	{
		$arrPostTypes = self::getPostTypesAssoc();
		foreach ($arrPostTypes as $postType => $title) {
			$arrTaxomonies = self::getPostTypeTaxomonies($postType);
			$arrPostTypes[$postType] = $arrTaxomonies;
		}

		return apply_filters('essgrid_getPostTypesWithTaxomonies', $arrPostTypes);
	}

	/**
	 * get post categories list assoc - id / title
	 */
	public static function getCategoriesAssoc($taxonomy = "category")
	{
		if (strpos($taxonomy, ",") !== false) {
			$arrTax = explode(",", $taxonomy);
			$arrCats = array();
			foreach ($arrTax as $tax) {
				$cats = self::getCategoriesAssoc($tax);
				$arrCats = array_merge($arrCats, $cats);
			}
		} else {
			$args = array("taxonomy" => $taxonomy);
			$cats = get_categories($args);
			$arrCats = array();
			foreach ($cats as $cat) {
				$numItems = $cat->count;
				$itemsName = "items";
				if ($numItems == 1)
					$itemsName = "item";
				$title = $cat->name . " ($numItems $itemsName) [slug: " . $cat->slug . "]"; //ADD SLUG HERE
				$id = $cat->cat_ID;
				$id = Essential_Grid_Wpml::get_id_from_lang_id($id, $cat->taxonomy);
				$arrCats[$id] = $title;
			}
		}

		return apply_filters('essgrid_getCategoriesAssoc', $arrCats, $taxonomy);
	}

	/**
	 * get post type taxomonies
	 */
	public static function getPostTypeTaxomonies($postType)
	{
		$arrTaxonomies = get_object_taxonomies(array('post_type' => $postType), 'objects');
		$arrNames = array();
		foreach ($arrTaxonomies as $objTax) {
			$arrNames[$objTax->name] = $objTax->labels->name;
		}

		return apply_filters('essgrid_getPostTypeTaxomonies', $arrNames, $postType);
	}

	/**
	 * get first category from categories list
	 */
	private static function getFirstCategory($cats)
	{
		$ret = '';
		foreach ($cats as $key => $value) {
			if (strpos($key, "option_disabled") === false) {
				$ret = $key;
				break;
			}
		}

		return apply_filters('essgrid_getFirstCategory', $ret, $cats);
	}

	/**
	 * set category by post type, with specific name (can be regular or woocommerce)
	 */
	public static function setCategoryByPostTypes($postTypes, $postTypesWithCats)
	{
		//update the categories list by the post types
		if (strpos($postTypes, ",") !== false)
			$postTypes = explode(",", $postTypes);
		else
			$postTypes = array($postTypes);

		$arrCats = array();
		foreach ($postTypes as $postType) {
			if (empty($postTypesWithCats[$postType])) continue;
			$arrCats = array_merge($arrCats, $postTypesWithCats[$postType]);
		}

		return apply_filters('essgrid_setCategoryByPostTypes', $arrCats, $postTypes, $postTypesWithCats);
	}

	/**
	 * function return the custom query.
	 *
	 * @since 3.0.13
	 * @global Object $wpdb WordPress db object.
	 * @param string $search Search query.
	 * @param object $wp_query WP query.
	 * @return string $search Search query.
	 */
	public static function esg_custom_query($search, $wp_query)
	{
		global $wpdb;

		if (empty($wp_query->is_search) || empty($wp_query->get('s'))) {
			return $search; // Do not proceed if does not match our search conditions.
		}

		$q = $wp_query->query_vars;
		if (empty($q['search_terms'])) $q['search_terms'] = array();
		if (!is_array($q['search_terms'])) $q['search_terms'] = (array)$q['search_terms'];
		
		$search  = '';
		$search_operator = '';

		foreach ($q['search_terms'] as $term) {

			$term = '%' . $wpdb->esc_like( $term ) . '%';

			$search .= "{$search_operator} (";
			$search .= $wpdb->prepare( "($wpdb->posts.post_title LIKE %s) OR ($wpdb->posts.post_content LIKE %s) OR ($wpdb->posts.post_excerpt LIKE %s)", $term, $term, $term );

			// post meta search
			$meta = new Essential_Grid_Meta();
			$m = $meta->get_all_meta(false);
			if (!empty($m)) {
				foreach ($m as $me) {
					$search .= ' OR ';
					$search .= $wpdb->prepare( '(esg_pm.meta_key = %s AND esg_pm.meta_value LIKE %s)', 'eg-'.$me['handle'], $term );
				}
			}

			// taxonomies search - tags & categories
			$taxonomies = array('category', 'post_tag');
			foreach ($taxonomies as $tax) {
				$search .= ' OR ';
				$search .= $wpdb->prepare( '(esg_tt.taxonomy = %s AND esg_t.name LIKE %s)', $tax, $term );
			}

			$search .= ')';

			$search_operator = " OR ";
		}

		if ( ! empty( $search ) ) {
			$search = " AND ({$search}) ";
			if ( ! is_user_logged_in() ) {
				$search .= " AND ($wpdb->posts.post_password = '') ";
			}
		}

		// Join Table.
		add_filter( 'posts_join_request', array( 'Essential_Grid_Base', 'esg_custom_query_join_table' ) );

		// Request distinct results.
		add_filter( 'posts_distinct_request', array( 'Essential_Grid_Base', 'esg_custom_query_distinct' ) );

		/**
		 * Filter search query return by plugin.
		 *
		 * @since 1.0.1
		 * @param string $search SQL query.
		 * @param object $wp_query global wp_query object.
		 */
		return apply_filters( 'essgrid_posts_search', $search, $wp_query );
	}

	/**
	 * Join tables.
	 *
	 * @since 1.0
	 * @global Object $wpdb WPDB object.
	 * @param string $join query for join.
	 * @return string $join query for join.
	 */
	public static function esg_custom_query_join_table( $join ) {
		global $wpdb;

		// join post meta table.
		$join .= " LEFT JOIN $wpdb->postmeta esg_pm ON ($wpdb->posts.ID = esg_pm.post_id) ";

		// join taxonomies table.
		if (strpos($join, $wpdb->term_relationships) === false) {
			$join .= " LEFT JOIN $wpdb->term_relationships ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id) ";
		}
		$join .= " LEFT JOIN $wpdb->term_taxonomy esg_tt ON ($wpdb->term_relationships.term_taxonomy_id = esg_tt.term_taxonomy_id) ";
		$join .= " LEFT JOIN $wpdb->terms esg_t ON (esg_tt.term_id = esg_t.term_id) ";

		return $join;
	}

	/**
	 * Request distinct results.
	 *
	 * @since 1.0
	 * @param string $distinct DISTINCT Keyword.
	 * @return string $distinct
	 */
	public static function esg_custom_query_distinct( $distinct ) {
		return 'DISTINCT';
	}

	/**
	 * get posts by categorys/tags
	 */
	public static function getPostsByCategory($grid_id, $catID, $postTypes = "any", $taxonomies = "category", $pages = array(), $sortBy = 'ID', $direction = 'DESC', $numPosts = -1, $arrAddition = array(), $enable_caching = true, $relation = 'OR')
	{ 
		global $sitepress;

		// Filter to modify search query.
		$enable_extended_search = get_option('tp_eg_enable_extended_search', 'false');
		if ('true' === $enable_extended_search) {
			add_filter('posts_search', array('Essential_Grid_Base', 'esg_custom_query'), 500, 2);
		}

		//get post types
		if (strpos($postTypes, ",") !== false) {
			$postTypes = explode(",", $postTypes);
			if (array_search("any", $postTypes) !== false)
				$postTypes = "any";
		}

		if (empty($postTypes))
			$postTypes = "any";

		if (strpos($catID, ",") !== false)
			$catID = explode(",", $catID);
		else
			$catID = array($catID);

		$query = array(
			'order' => $direction,
			'posts_per_page' => $numPosts,
			'showposts' => $numPosts,
			'post_status' => 'publish',
			'post_type' => $postTypes,
		);
		$enable_caching = false;

		if (strpos($sortBy, 'eg-') === 0) {
			$meta = new Essential_Grid_Meta();
			$m = $meta->get_all_meta(false);
			if (!empty($m)) {
				foreach ($m as $me) {
					if ('eg-' . $me['handle'] == $sortBy) {
						$sortBy = (isset($me['sort-type']) && $me['sort-type'] == 'numeric') ? 'meta_num_' . $sortBy : 'meta_' . $sortBy;
						break;
					}
				}
			}
		} elseif (strpos($sortBy, 'egl-') === 0) { 
			//change to meta_num_ or meta_ depending on setting
			$sortfound = false;
			$link_meta = new Essential_Grid_Meta_Linking();
			$m = $link_meta->get_all_link_meta();
			if (!empty($m)) {
				foreach ($m as $me) {
					if ('egl-' . $me['handle'] == $sortBy) {
						$sortBy = (isset($me['sort-type']) && $me['sort-type'] == 'numeric') ? 'meta_num_' . $me['original'] : 'meta_' . $me['original'];
						$sortfound = true;
						break;
					}
				}
			}
			if (!$sortfound) {
				$sortBy = 'none';
			}
		}

		//add sort by (could be by meta)
		if (strpos($sortBy, "meta_num_") === 0) {
			$metaKey = str_replace("meta_num_", "", $sortBy);
			$query["orderby"] = "meta_value_num";
			$query["meta_key"] = $metaKey;
		} else if (strpos($sortBy, "meta_") === 0) {
			$metaKey = str_replace("meta_", "", $sortBy);
			$query["orderby"] = "meta_value";
			$query["meta_key"] = $metaKey;
		} else {
			$query["orderby"] = $sortBy;
		}

		if ($query["orderby"] == "likespost") {
			$query["orderby"] = "meta_value";
			$query["meta_key"] = "eg_votes_count";
		}

		if (isset($query['meta_key'])) {
			$query['meta_key'] = ($query['meta_key'] === 'stock') ? '_stock' : $query['meta_key'];
		}

		if (!empty($taxonomies)) {
			$taxQuery = array();
			//add taxomonies to the query
			if (strpos($taxonomies, ",") !== false) {
				//multiple taxomonies
				$taxonomies = explode(",", $taxonomies);
				foreach ($taxonomies as $taxomony) {
					$taxArray = array(
						'taxonomy' => $taxomony,
						'field' => 'id',
						'terms' => $catID
					);
					if ($relation == 'AND') $taxArray['operator'] = 'IN';
					$taxQuery[] = $taxArray;
				}
			} else {
				//single taxomony
				$taxArray = array(
					'taxonomy' => $taxonomies,
					'field' => 'id',
					'terms' => $catID
				);
				if ($relation == 'AND') $taxArray['operator'] = 'AND';
				$taxQuery[] = $taxArray;
			}
			$taxQuery['relation'] = $relation;
			$query['tax_query'] = $taxQuery;
		}

		$query['suppress_filters'] = false;

		if (!empty($arrAddition) && is_array($arrAddition)) {
			foreach ($arrAddition as $han => $val) {
				if (strlen($val) >= 5 && strtolower(substr($val, 0, 5)) == 'array') {
					$val = explode(',', str_replace(array('(', ')'), '', substr($val, 5)));
					$arrAddition[$han] = $val;
				}
			}
			$query = array_merge($query, $arrAddition);
			if (isset($arrAddition['offset'])) {
				if (isset($query['posts_per_page']) && ($query['posts_per_page'] == '-1' || $query['posts_per_page'] == -1)) {
					$query['posts_per_page'] = '9999';
					$query['showposts'] = '9999';
				}
			}
		}

		if ($query['orderby'] == 'none') $query['orderby'] = 'post__in';

		if (empty($grid_id)) $grid_id = time();

		//add wpml transient
		$lang_code = '';
		if (Essential_Grid_Wpml::is_wpml_exists()) {
			$lang_code = Essential_Grid_Wpml::get_current_lang_code();
		}

		$objQuery = false;

		$query_type = get_option('tp_eg_query_type', 'wp_query');

		if ($objQuery === false) {
			$query = apply_filters('essgrid_get_posts', $query, $grid_id);
			if ($query_type == 'wp_query') {
				$wp_query = new WP_Query();
				$wp_query->parse_query($query);
				$objQuery = $wp_query->get_posts();
			} else {
				$objQuery = get_posts($query);
			}

			//select again the pages
			if (is_array($postTypes) && in_array('page', $postTypes) && count($postTypes) > 1 || $postTypes == 'page') { //Page is selected and also another custom category
				$query['post_type'] = 'page';
				unset($query['tax_query']); //delete category/tag filtering

				$query['post__in'] = $pages;

				if ($query_type == 'wp_query') {
					$wp_query = new WP_Query();
					$wp_query->parse_query($query);
					$objQueryPages = $wp_query->get_posts();
				} else {
					$objQueryPages = get_posts($query);
				}

				if ($query_type == 'wp_query') {
					if (is_object($objQueryPages) && is_object($objQuery)) {
						$objQuery->posts = array_merge($objQuery->posts, $objQueryPages->posts);
					}
					if (is_object($objQueryPages) && !is_object($objQuery)) {
						$objQuery = $objQueryPages;
					}
				} else {
					if (is_array($objQueryPages) && is_array($objQuery)) {
						$objQuery = array_merge($objQuery, $objQueryPages);
					}
					if (is_array($objQueryPages) && !is_array($objQuery)) {
						$objQuery = $objQueryPages;
					}
				}
				
				if (is_array($objQueryPages) && is_array($objQuery)) {
					$objQuery = array_merge($objQuery, $objQueryPages);
				}
				if (is_array($objQueryPages) && !is_array($objQuery)) {
					$objQuery = $objQueryPages;
				}
				
				if (!empty($objQuery)) {
					$fIDs = array();
					foreach ($objQuery as $objID => $objPost) {
						if (isset($fIDs[$objPost->ID])) {
							unset($objQuery[$objID]);
							continue;
						}
						$fIDs[$objPost->ID] = true;
					}
				}
			}

			if ($enable_caching) {
				$addition = (wp_is_mobile()) ? '_m' : '';
				$addition .= ($addition !== '' && $lang_code !== '') ? '_' : '';
				set_transient('ess_grid_trans_query_' . $grid_id . $addition . $lang_code, $objQuery, 60 * 60 * 24);
			}
		}
		
		$arrPosts = $objQuery;

		//check if we should rnd the posts
		if ($sortBy == 'rand' && !empty($arrPosts)) {
			shuffle($arrPosts);
		}

		if (!empty($arrPosts)) {
			foreach ($arrPosts as $key => $post) {

				if (method_exists($post, "to_array"))
					$arrPost = $post->to_array();
				else
					$arrPost = (array)$post;

				if ($arrPost['post_type'] == 'page') {
					if (!empty($pages)) {
						//filter to pages if array is set
						$delete = true;
						foreach ($pages as $page) {
							if (!empty($page)) {
								if ($arrPost['ID'] == $page) {
									$delete = false;
									break;
								} elseif (isset($sitepress)) {
									//WPML
									$current_main_id = icl_object_id($arrPost['ID'], 'page', true, $sitepress->get_default_language());
									if ($current_main_id == $page) {
										$delete = false;
										break;
									}
								}
							}
						}
						if ($delete) {
							//if not wanted, go to next
							unset($arrPosts[$key]);
							continue;
						}
					}
				}
				$arrPosts[$key] = $arrPost;
			}
		}

		// remove filter to modify search query.
		if ('true' === $enable_extended_search) {
			remove_filter('posts_search', array('Essential_Grid_Base', 'esg_custom_query'), 500);
		}

		return apply_filters('essgrid_modify_posts', $arrPosts, $grid_id);
	}

	/**
	 * Get taxonomies by post ID
	 */
	public static function get_custom_taxonomies_by_post_id($post_id)
	{
		// get post by post id
		$post = get_post($post_id);

		// get post type by post
		$post_type = $post->post_type;

		// get post type taxonomies
		$taxonomies = get_object_taxonomies($post_type, 'objects');

		$terms = array();
		foreach ($taxonomies as $taxonomy_slug => $taxonomy) {
			// get the terms related to post
			$c_terms = get_the_terms($post->ID, $taxonomy_slug);

			if (!empty($c_terms)) {
				$terms = array_merge($terms, $c_terms);
			}
		}

		return apply_filters('essgrid_get_custom_taxonomies_by_post_id', $terms, $post_id);
	}

	/**
	 * Receive all Posts by given IDs
	 */
	public static function get_posts_by_ids($ids, $sort_by = 'none', $sort_order = 'DESC')
	{
		$query = array(
			'post__in' => $ids,
			'post_type' => 'any',
			'order' => $sort_order,
			'numberposts' => count($ids)
		);

		if (strpos($sort_by, 'eg-') === 0) {
			$meta = new Essential_Grid_Meta();
			$m = $meta->get_all_meta(false);
			if (!empty($m)) {
				foreach ($m as $me) {
					if ('eg-' . $me['handle'] == $sort_by) {
						$sort_by = (isset($me['sort-type']) && $me['sort-type'] == 'numeric') ? 'meta_num_' . $sort_by : 'meta_' . $sort_by;
						break;
					}
				}
			}
		} elseif (strpos($sort_by, 'egl-') === 0) {
			//change to meta_num_ or meta_ depending on setting
			$sortfound = false;
			$link_meta = new Essential_Grid_Meta_Linking();
			$m = $link_meta->get_all_link_meta();
			if (!empty($m)) {
				foreach ($m as $me) {
					if ('egl-' . $me['handle'] == $sort_by) {
						$sort_by = (isset($me['sort-type']) && $me['sort-type'] == 'numeric') ? 'meta_num_' . $me['original'] : 'meta_' . $me['original'];
						$sortfound = true;
						break;
					}
				}
			}
			if (!$sortfound) {
				$sort_by = 'none';
			}
		}

		//add sort by (could be by meta)
		if (strpos($sort_by, "meta_num_") === 0) {
			$metaKey = str_replace("meta_num_", "", $sort_by);
			$query["orderby"] = "meta_value_num";
			$query["meta_key"] = $metaKey;
		} else if (strpos($sort_by, "meta_") === 0) {
			$metaKey = str_replace("meta_", "", $sort_by);
			$query["orderby"] = "meta_value";
			$query["meta_key"] = $metaKey;
		} else {
			$query["orderby"] = $sort_by;
		}

		if ($query['orderby'] == 'none') $query['orderby'] = 'post__in';
		$query = apply_filters('essgrid_get_posts_by_ids_query', $query, $ids);

		$objQuery = get_posts($query);
		$arrPosts = $objQuery;
		foreach ($arrPosts as $key => $post) {
			if (method_exists($post, "to_array"))
				$arrPost = $post->to_array();
			else
				$arrPost = (array)$post;
			$arrPosts[$key] = $arrPost;
		}

		return apply_filters('essgrid_get_posts_by_ids', $arrPosts);
	}

	/**
	 * Receive all Posts
	 * @since: 3.0.17
	 */
	public static function get_wp_posts($max_posts = 100, $post_type = "any", $orderby = "date", $filter = "latest")
	{
		$post_id = get_the_ID();
		$my_posts = array();
		$args = array(
			'post_type' => $post_type,
			'posts_per_page' => $max_posts,
			'suppress_filters' => 0,
			'meta_key' => '_thumbnail_id',
			'orderby' => $orderby,
			'order' => 'DESC',
			'post__not_in'   => [$post_id],
		);
		$args = apply_filters('essgrid_get_' . $filter . '_posts_query', $args, $post_id);

		$posts = get_posts($args);
		foreach ($posts as $post) {
			if (method_exists($post, "to_array"))
				$my_posts[] = $post->to_array();
			else
				$my_posts[] = (array)$post;
		}

		return apply_filters('essgrid_get_' . $filter . '_posts', $my_posts);
	}
	

	/**
	 * Receive all Posts that are related to the current post
	 * @since: 1.2.0
	 * changed: 3.0.8 (added distinction between categories or tags or both)
	 * changed: 3.0.17 (works with all post types and taxonomies)
	 */

	public static function get_related_posts($max_posts = 20, $related_by = "both")
	{
		$my_posts = array();
		$post_id = get_the_ID();
		$max_posts++; 
		
		if (in_array($related_by, array("both", "tags"))) {
			$tags_string = '';
			$post_tags = get_the_tags();
			if ($post_tags) {
				foreach ($post_tags as $post_tag) {
					$tags_string .= $post_tag->slug . ',';
				}
			}

			$query = array(
				'exclude' => $post_id,
				'numberposts' => $max_posts,
				'tag' => $tags_string
			);

			$get_relateds = apply_filters('essgrid_get_related_posts', $query, $post_id);
			$tag_related_posts = get_posts($get_relateds);
		} else {
			$tag_related_posts = array();
		}

		if ($related_by == "categories" || ($related_by == "both" && count($tag_related_posts) < $max_posts)) {
			$ignore = array();
			foreach ($tag_related_posts as $tag_related_post) {
				$ignore[] = $tag_related_post->ID;
			}
			$article_categories = get_the_category($post_id);
			$category_string = '';
			foreach ($article_categories as $category) {
				$category_string .= $category->cat_ID . ',';
			}
			$max = $max_posts - count($tag_related_posts);

			$excl = implode(',', $ignore);
			$query = array(
				'exclude' => $excl,
				'numberposts' => $max,
				'category' => $category_string
			);

			$get_relateds = apply_filters('essgrid_get_related_posts_query', $query, $post_id);
			$cat_related_posts = get_posts($get_relateds);
			$tag_related_posts = $tag_related_posts + $cat_related_posts;
		}

		foreach ($tag_related_posts as $post) {
			if (method_exists($post, "to_array"))
				$the_post = $post->to_array();
			else
				$the_post = (array)$post;
			if ($the_post['ID'] == $post_id) continue;
			$my_posts[] = $the_post;
		}

		return apply_filters('essgrid_get_related_posts', $my_posts);
	}
	

	/**
	 * get post categories by postID and taxonomies
	 * the postID can be post object or array too
	 */
	public static function getPostCategories($postID, $arrTax)
	{
		if (!is_numeric($postID)) {
			$postID = (array)$postID;
			$postID = $postID["ID"];
		}
		$arrCats = wp_get_post_terms($postID, $arrTax);
		$arrCats = self::convertStdClassToArray($arrCats);

		return apply_filters('essgrid_getPostCategories', $arrCats, $postID, $arrTax);
	}

	/**
	 * Convert std class to array, with all sons
	 * @param mixed $arr
	 */
	public static function convertStdClassToArray($arr)
	{
		$arr = (array)$arr;
		$arrNew = array();
		foreach ($arr as $key => $item) {
			$item = (array)$item;
			$arrNew[$key] = $item;
		}

		return apply_filters('essgrid_convertStdClassToArray', $arrNew, $arr);
	}

	/**
	 * get cats and taxanomies data from the category id's
	 */
	public static function getCatAndTaxData($catIDs)
	{
		if (is_string($catIDs)) {
			$catIDs = trim($catIDs);
			if (empty($catIDs))
				return (array("tax" => "", "cats" => ""));
			$catIDs = explode(",", $catIDs);
		}

		$strCats = "";
		$arrTax = array();
		foreach ($catIDs as $cat) {
			if (strpos($cat, "option_disabled") === 0)
				continue;
			$pos = strrpos($cat, "_");
			$taxName = substr($cat, 0, $pos);
			$catID = substr($cat, $pos + 1, strlen($cat) - $pos - 1);
			//translate catID to current language if wpml exists
			$catID = Essential_Grid_Wpml::change_cat_id_by_lang($catID, $taxName);
			$arrTax[$taxName] = $taxName;
			if (!empty($strCats))
				$strCats .= ",";
			$strCats .= $catID;
		}

		$strTax = "";
		foreach ($arrTax as $taxName) {
			if (!empty($strTax))
				$strTax .= ",";
			$strTax .= $taxName;
		}
		$output = array("tax" => $strTax, "cats" => $strCats);

		return apply_filters('essgrid_getCatAndTaxData', $output, $catIDs);
	}

	/**
	 * get categories list, copy the code from default wp functions
	 */
	public static function get_categories_html_list($catIDs, $do_type, $seperator = ',', $tax = false)
	{
		global $wp_rewrite;

		$categories = self::get_categories_by_ids($catIDs, $tax);
		$rel = (is_object($wp_rewrite) && $wp_rewrite->using_permalinks()) ? 'rel="category tag"' : 'rel="category"';
		$thelist = '';
		if (!empty($categories)) {
			foreach ($categories as $key => $category) {
				if ($key > 0) $thelist .= $seperator;
				switch ($do_type) {
					case 'none':
						$thelist .= $category->name;
						break;
					case 'filter':
						$thelist .= '<a href="#" class="eg-triggerfilter" data-filter="filter-' . $category->slug . '">' . $category->name . '</a>';
						break;
					case 'link':
					default:
						if ($tax !== false) {
							$url = get_term_link($category, $tax);
							if (is_wp_error($url)) $url = '';
						} else {
							$url = get_category_link($category->term_id);
						}
						/* translators: %s: Category Name. */
						$title = sprintf(__('View all posts in %s', ESG_TEXTDOMAIN), $category->name);
						$thelist .= '<a href="' . esc_url($url) . '" title="' . esc_attr($title) . '" ' . $rel . '>' . $category->name . '</a>';
						break;
				}
			}
		}

		return apply_filters('essgrid_get_categories_html_list', $thelist, $catIDs, $do_type, $seperator, $tax);
	}

	/**
	 * get categories by post IDs
	 * @since: 1.2.0
	 */
	public static function get_categories_by_posts($posts)
	{
		$post_ids = array();
		$categories = array();
		if (!empty($posts)) {
			foreach ($posts as $post) {
				$post_ids[] = $post['ID'];
			}
		}
		if (!empty($post_ids)) {
			foreach ($post_ids as $post_id) {
				$cats = self::get_custom_taxonomies_by_post_id($post_id);
				$categories = array_merge($categories, $cats);
			}
		}

		return apply_filters('essgrid_get_categories_by_posts', $categories, $posts);
	}

	/**
	 * translate categories obj to string
	 * @since: 1.2.0
	 */
	public static function translate_categories_to_string($cats)
	{
		$categories = array();
		if (!empty($cats)) {
			foreach ($cats as $cat) {
				$categories[] = $cat->term_id;
			}
		}
		$categories = implode(',', $categories);

		return apply_filters('essgrid_translate_categories_to_string', $categories, $cats);
	}

	/**
	 * get categories by id's
	 */
	public static function get_categories_by_ids($arrIDs, $tax = false)
	{
		if (empty($arrIDs))
			return (array());
		$strIDs = implode(',', $arrIDs);
		$args['include'] = $strIDs;
		if ($tax !== false)
			$args['taxonomy'] = $tax;
		$arrCats = get_categories($args);

		return apply_filters('essgrid_get_categories_by_ids', $arrCats, $arrIDs, $tax);
	}

	/**
	 * get categories by id's
	 */
	public static function get_create_category_by_slug($cat_slug, $cat_name)
	{
		$cat = term_exists($cat_slug, $cat_name);
		if ($cat !== 0 && $cat !== null) {
			if (is_array($cat))
				return $cat['term_id'];
			else
				return $cat;
		}

		//create category if possible
		$new_name = ucwords(str_replace('-', ' ', $cat_slug));
		$category_array = wp_insert_term(
			$new_name,
			$cat_name,
			array(
				'description' => '',
				'slug' => $cat_slug
			)
		);

		$category_array = apply_filters('essgrid_get_create_category_by_slug', $category_array, $cat_slug, $cat_name);
		if (is_array($category_array) && !empty($category_array))
			return $category_array['term_id'];

		return false;
	}

	/**
	 * get post taxonomies html list
	 */
	public static function get_tax_html_list($postID, $taxonomy, $seperator = ',', $do_type = 'link', $taxmax = false)
	{
		$taxList = array();
		if (empty($seperator)) $seperator = '&nbsp;';
		
		$terms = get_the_terms($postID, $taxonomy);
		if (!empty($terms) && !is_wp_error($terms)) {
			foreach ($terms as $term) {
				$taxList[] = '<a href="' . get_term_link($term->term_id) . '" class="esg-display-inline">' . $term->name . '</a>';
			}
			if ($taxmax && !empty($taxList) && is_array($taxList) && count($taxList) >= $taxmax) {
				$taxList = array_slice($taxList, 0, $taxmax, true);
			}
			switch ($do_type) {
				case 'none':
					$taxList = implode($seperator, $taxList);
					$taxList = strip_tags($taxList);
					break;
				case 'filter':
					$text = '';
					if (!empty($taxList)) {
						foreach ($taxList as $key => $tax) {
							if ($key > 0) $text .= $seperator;
							$tax = strip_tags($tax);
							$text .= '<a href="#" class="eg-triggerfilter" data-filter="filter-' . $tax . '">' . sanitize_title($tax) . '</a>';
						}
					}
					$taxList = $text;
					break;
				case 'link':
					$taxList = implode($seperator, $taxList);
					break;
			}
		}
		
		return apply_filters('essgrid_get_tax_html_list', $taxList, $postID, $seperator, $do_type);
	}

	/**
	 * get post tags html list
	 */
	public static function get_tags_html_list($postID, $seperator = ',', $do_type = 'link', $tagmax = false)
	{
		if (empty($seperator)) $seperator = '&nbsp;';

		$tagList = get_the_tag_list("", $seperator, "", $postID);
		if (!empty($tagList) && !is_wp_error($tagList)) {
			if ($tagmax) {
				$tags = explode($seperator, $tagList);
				$tags = array_slice($tags, 0, $tagmax, true);
				$tagList = implode($seperator, $tags);
			}

			switch ($do_type) {
				case 'none':
					$tagList = strip_tags($tagList);
					break;
				case 'filter':
					$tags = strip_tags($tagList);
					$tags = explode($seperator, $tags);

					$text = '';
					if (!empty($tags)) {
						foreach ($tags as $key => $tag) {
							if ($key > 0) $text .= $seperator;
							$text .= '<a href="#" class="eg-triggerfilter" data-filter="filter-' . $tag . '">' . sanitize_title($tag) . '</a>';
						}
					}
					$tagList = $text;
					break;
				case 'link':
					//return tagList as it is
					break;
			}
		}

		return apply_filters('essgrid_get_tags_html_list', $tagList, $postID, $seperator, $do_type);
	}

	/**
	 * check if text has a certain tag in it
	 */
	public function text_has_certain_tag($string, $tag)
	{
		$r = apply_filters('essgrid_text_has_certain_tag', array('string' => $string, 'tag' => $tag));
		if (!is_array($r) || !isset($r['string']) || is_array($r['string'])) return "";
		return preg_match("/<" . $r['tag'] . "[^<]+>/", $r['string'], $m) != 0;
	}

	/**
	 * output the demo skin html
	 */
	public static function output_demo_skin_html($data)
	{
		$data = apply_filters('essgrid_output_demo_skin_html_pre', $data);
		$grid = new Essential_Grid();
		$base = new Essential_Grid_Base();
		$item_skin = new Essential_Grid_Item_Skin();

		$preview = '';
		$preview_type = ($data['postparams']['source-type'] == 'custom') ? 'custom' : 'preview';
		$grid_id = (isset($data['id']) && intval($data['id']) > 0) ? intval($data['id']) : '-1';

		ob_start();
		$grid->output_essential_grid($grid_id, $data, $preview_type);
		$html = ob_get_contents();
		ob_clean();
		ob_end_clean();

		$skin = $base->getVar($data, array('params', 'entry-skin'), 0, 'i');
		if ($skin > 0) {
			ob_start();
			$item_skin->init_by_id($skin);
			$item_skin->output_item_skin('custom');
			$preview = ob_get_contents();
			ob_clean();
			ob_end_clean();
		}

		return apply_filters('essgrid_output_demo_skin_html_post', array('html' => $html, 'preview' => $preview), $grid);
	}

	/**
	 * return all custom element fields
	 */
	public function get_custom_elements_for_javascript()
	{
		$meta = new Essential_Grid_Meta();
		$item_elements = new Essential_Grid_Item_Element();

		$elements = array(
			array('name' => 'custom-soundcloud', 'type' => 'input'),
			array('name' => 'custom-vimeo', 'type' => 'input'),
			array('name' => 'custom-youtube', 'type' => 'input'),
			array('name' => 'custom-wistia', 'type' => 'input'),
			array('name' => 'custom-html5-mp4', 'type' => 'input'),
			array('name' => 'custom-html5-ogv', 'type' => 'input'),
			array('name' => 'custom-html5-webm', 'type' => 'input'),
			array('name' => 'custom-image', 'type' => 'image'),
			array('name' => 'custom-text', 'type' => 'textarea'),
			array('name' => 'custom-ratio', 'type' => 'select'),
			array('name' => 'post-link', 'type' => 'input'),
			array('name' => 'custom-filter', 'type' => 'input')
		);

		$custom_meta = $meta->get_all_meta(false);
		if (!empty($custom_meta)) {
			foreach ($custom_meta as $cmeta) {
				if ($cmeta['type'] == 'text') $cmeta['type'] = 'input';
				$elements[] = array('name' => 'eg-cm-' . $cmeta['handle'], 'type' => $cmeta['type'], 'default' => @$cmeta['default']);
			}
		}

		$def_ele = $item_elements->getElementsForDropdown();
		foreach ($def_ele as $element) {
			foreach ($element as $handle => $name) {
				$elements[] = array('name' => $handle, 'type' => 'input');
			}
		}

		return apply_filters('essgrid_get_custom_elements_for_javascript', $elements);
	}

	/**
	 * return all media data of post that we may need
	 * 
	 * @param int $post_id
	 * @param string $image_type
	 * @param array $media_sources
	 * @param array $image_size
	 * @return array
	 */
	public function get_post_media_source_data($post_id, $image_type, $media_sources, $image_size = array(), $postdata = []) //PLURA
	{
		$sources = apply_filters('essgrid_post_media_sources', $media_sources);
		
		$ret = array();
		$io = Essential_Grid_Image_Optimization::get_instance();
		$c_post = get_post($post_id);
		$attachment_id = get_post_thumbnail_id($post_id);
		
		$ret['featured-image']  = '';
		if (in_array('featured-image', $sources)) {
			if (!empty($image_size)) $io->generate_thumbnails($attachment_id, $image_size);
			
			$media = $io->get_media_source_src($attachment_id, $image_type, $image_size);

			$ret['featured-image'] = ($media['x1'] !== false) ? $media['x1']['0'] : '';
			$ret['featured-image-' . $io->get_retina_ext()] = ($media['x2'] !== false) ? $media['x2']['0'] : '';
			$ret['featured-image-width'] = ($media['x1'] !== false) ? $media['x1']['1'] : '';
			$ret['featured-image-height'] = ($media['x1'] !== false) ? $media['x1']['2'] : '';
			$ret['featured-image-alt'] = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);

			$feat_img_full = wp_get_attachment_image_src($attachment_id, 'full');
			$ret['featured-image-full'] = ($feat_img_full !== false) ? $feat_img_full['0'] : '';
			$ret['featured-image-full-width'] = ($feat_img_full !== false) ? $feat_img_full['1'] : '';
			$ret['featured-image-full-height'] = ($feat_img_full !== false) ? $feat_img_full['2'] : '';
		}

		$ret['content-image'] = '';
		$ret['content-image-alt'] = '';
		if (in_array('content-image', $sources)) {
			$content_image = $this->get_first_content_image(-1, $c_post);
			$content_id = $this->get_image_id_by_url($content_image);
			if (!empty($content_id)) {
				if (!empty($image_size)) $io->generate_thumbnails($attachment_id, $image_size);

				$media = $io->get_media_source_src($content_id, $image_type, $image_size);
				$ret['content-image'] = ($media['x1'] !== false) ? $media['x1']['0'] : '';
				$ret['content-image-' . $io->get_retina_ext()] = ($media['x2'] !== false) ? $media['x2']['0'] : '';
				$ret['content-image-alt'] = get_post_meta($content_id, '_wp_attachment_image_alt', true);
			}
		}

		$ret['content-iframe'] = '';
		if (in_array('content-iframe', $sources)) {
			$ret['content-iframe'] = $this->get_first_content_iframe(-1, $c_post);
		}

		//get Post Metas
		$values = get_post_custom($post_id);

		$ret['youtube'] = '';
		if (in_array('youtube', $sources)) {
			$ret['youtube'] = isset($values['eg_sources_youtube']) ? esc_attr($values['eg_sources_youtube'][0]) : '';
		}
		$ret['content-youtube'] = '';
		if (in_array('content-youtube', $sources)) {
			$ret['content-youtube'] = $this->get_first_content_youtube(-1, $c_post);
		}
		
		$ret['vimeo'] = '';
		if (in_array('vimeo', $sources)) {
			$ret['vimeo'] = isset($values['eg_sources_vimeo']) ? esc_attr($values['eg_sources_vimeo'][0]) : '';
		}
		$ret['content-vimeo'] = '';
		if (in_array('content-vimeo', $sources)) {
			$ret['content-vimeo'] = $this->get_first_content_vimeo(-1, $c_post);
		}
		
		$ret['wistia'] = '';
		if (in_array('wistia', $sources)) {
			$ret['wistia'] = isset($values['eg_sources_wistia']) ? esc_attr($values['eg_sources_wistia'][0]) : '';
		}
		$ret['content-wistia'] = '';
		if (in_array('content-wistia', $sources)) {
			$ret['content-wistia'] = $this->get_first_content_wistia(-1, $c_post);
		}

		$ret['alternate-image'] = '';
		$ret['alternate-image-alt'] = '';
		if (in_array('alternate-image', $sources) && isset($values['eg_sources_image'])) {
			if (!empty($image_size)) $io->generate_thumbnails($values['eg_sources_image'][0], $image_size);

			$media = $io->get_media_source_src($values['eg_sources_image'][0], $image_type, $image_size);
			
			$ret['alternate-image'] = ($media['x1'] !== false) ? $media['x1']['0'] : '';
			$ret['alternate-image-' . $io->get_retina_ext()] = ($media['x2'] !== false) ? $media['x2']['0'] : '';
			$ret['alternate-image-width'] = ($media['x1'] !== false) ? $media['x1']['1'] : '';
			$ret['alternate-image-height'] = ($media['x1'] !== false) ? $media['x1']['2'] : '';
			$ret['alternate-image-alt'] = get_post_meta(esc_attr($values['eg_sources_image'][0]), '_wp_attachment_image_alt', true);

			$alt_img_full = wp_get_attachment_image_src(esc_attr($values['eg_sources_image'][0]), 'full');
			$ret['alternate-image-full'] = ($alt_img_full !== false) ? $alt_img_full['0'] : '';
			$ret['alternate-image-full-width'] = ($alt_img_full !== false) ? $alt_img_full['1'] : '';
			$ret['alternate-image-full-height'] = ($alt_img_full !== false) ? $alt_img_full['2'] : '';
		}

		$ret['iframe'] = isset($values['eg_sources_iframe']) ? esc_attr($values['eg_sources_iframe'][0]) : '';

		$ret['soundcloud'] = '';
		if (in_array('soundcloud', $sources)) {
			$ret['soundcloud'] = isset($values['eg_sources_soundcloud']) ? esc_attr($values['eg_sources_soundcloud'][0]) : '';
		}
		$ret['content-soundcloud'] = '';
		if (in_array('content-soundcloud', $sources)) {
			$ret['content-soundcloud'] = $this->get_first_content_soundcloud(-1, $c_post);
		}

		$ret['html5']['mp4'] = isset($values['eg_sources_html5_mp4']) ? esc_attr($values['eg_sources_html5_mp4'][0]) : '';
		$ret['html5']['ogv'] = isset($values['eg_sources_html5_ogv']) ? esc_attr($values['eg_sources_html5_ogv'][0]) : '';
		$ret['html5']['webm'] = isset($values['eg_sources_html5_webm']) ? esc_attr($values['eg_sources_html5_webm'][0]) : '';

		$ret['image-fit'] = isset($values['eg_image_fit']) && $values['eg_image_fit'][0] != '-1' ? esc_attr($values['eg_image_fit'][0]) : '';
		$ret['image-repeat'] = isset($values['eg_image_repeat']) && $values['eg_image_repeat'][0] != '-1' ? esc_attr($values['eg_image_repeat'][0]) : '';
		$ret['image-align-horizontal'] = isset($values['eg_image_align_h']) && $values['eg_image_align_h'][0] != '-1' ? esc_attr($values['eg_image_align_h'][0]) : '';
		$ret['image-align-vertical'] = isset($values['eg_image_align_v']) && $values['eg_image_align_v'][0] != '-1' ? esc_attr($values['eg_image_align_v'][0]) : '';

		$ret['content-html5']['mp4'] = '';
		$ret['content-html5']['ogv'] = '';
		$ret['content-html5']['webm'] = '';
		$content_video = $this->get_first_content_video(-1, $c_post);
		if ($content_video !== false) {
			$ret['content-html5']['mp4'] = @$content_video['mp4'];
			$ret['content-html5']['ogv'] = @$content_video['ogv'];
			$ret['content-html5']['webm'] = @$content_video['webm'];
		}

		$ret['revslider'] = isset($values['eg_sources_revslider']) ? esc_attr($values['eg_sources_revslider'][0]) : '';
		$ret['essgrid'] = isset($values['eg_sources_essgrid']) ? esc_attr($values['eg_sources_essgrid'][0]) : '';

		return apply_filters('essgrid_modify_media_sources', $ret, $post_id, $postdata); //PLURA
	}

	/**
	 * return all media data of custom element that we may need
	 * 
	 * @param array $values
	 * @param string $image_type
	 * @param array $image_size
	 * @return array
	 */
	public function get_custom_media_source_data($values, $image_type, $image_size = array())
	{
		$ret = array();
		$io = Essential_Grid_Image_Optimization::get_instance();
		
		if (!empty($values['custom-image']) || !empty($values['custom-image-url'])) {
			if (!empty($values['custom-image'])) {
				if (!empty($image_size)) $io->generate_thumbnails($values['custom-image'], $image_size);
				
				$media = $io->get_media_source_src($values['custom-image'], $image_type, $image_size);
				
				$alt_img = $media['x1'];
				$alt_img_retina = $media['x2'];
				$alt_img_full = wp_get_attachment_image_src(esc_attr($values['custom-image']), 'full');
				$alt_text = get_post_meta(esc_attr($values['custom-image']), '_wp_attachment_image_alt', true);
			} else {
				$alt_img = $values['custom-image-url'];
				if (!empty($values['custom-image-url-full']))
					$alt_img_full = $values['custom-image-url-full'];
				else
					$alt_img_full = $values['custom-image-url'];
				$alt_text = '';
			}
			
			$ret['featured-image'] = ($alt_img !== false && isset($alt_img['0'])) ? $alt_img['0'] : '';
			$ret['featured-image-' . $io->get_retina_ext()] = (!empty($alt_img_retina)) ? $alt_img_retina['0'] : '';
			$ret['featured-image-width'] = ($alt_img !== false && isset($alt_img['1'])) ? $alt_img['1'] : '';
			$ret['featured-image-height'] = ($alt_img !== false && isset($alt_img['2'])) ? $alt_img['2'] : '';
			$ret['featured-image-alt'] = ($alt_text !== '') ? $alt_text : '';
			
			$ret['featured-image-full'] = ($alt_img_full !== false && isset($alt_img_full['0'])) ? $alt_img_full['0'] : '';
			$ret['featured-image-full-width'] = ($alt_img_full !== false && isset($alt_img_full['1'])) ? $alt_img_full['1'] : '';
			$ret['featured-image-full-height'] = ($alt_img_full !== false && isset($alt_img_full['2'])) ? $alt_img_full['2'] : '';
			
			$ret['alternate-image-preload-url'] = (isset($values['custom-preload-image-url'])) ? $values['custom-preload-image-url'] : '';
		}

		if (isset($values['eg-alternate-image']) && $values['eg-alternate-image'] !== '') {
			if (!empty($image_size)) $io->generate_thumbnails(esc_attr($values['eg-alternate-image']), $image_size);

			$media = $io->get_media_source_src(esc_attr($values['eg-alternate-image']), $image_type, $image_size);
			
			$ret['alternate-image'] = ($media['x1'] !== false) ? $media['x1']['0'] : '';
			$ret['alternate-image-' . $io->get_retina_ext()] = ($media['x2'] !== false) ? $media['x2']['0'] : '';
			$ret['alternate-image-width'] = ($media['x1'] !== false) ? $media['x1']['1'] : '';
			$ret['alternate-image-height'] = ($media['x1'] !== false) ? $media['x1']['2'] : '';
			$ret['alternate-image-alt'] = get_post_meta(esc_attr($values['eg-alternate-image']), '_wp_attachment_image_alt', true);

			$alt_img_full = wp_get_attachment_image_src(esc_attr($values['eg-alternate-image']), 'full');
			$ret['alternate-image-full'] = ($alt_img_full !== false && isset($alt_img_full['0'])) ? $alt_img_full['0'] : '';
			$ret['alternate-image-full-width'] = ($alt_img_full !== false) ? @$alt_img_full['1'] : '';
			$ret['alternate-image-full-height'] = ($alt_img_full !== false) ? @$alt_img_full['2'] : '';

		}

		$ret['image-fit'] = isset($values['image-fit']) && $values['image-fit'] != '-1' ? esc_attr($values['image-fit']) : '';
		$ret['image-repeat'] = isset($values['image-repeat']) && $values['image-repeat'] != '-1' ? esc_attr($values['image-repeat']) : '';
		$ret['image-align-horizontal'] = isset($values['image-align-horizontal']) && $values['image-align-horizontal'] != '-1' ? esc_attr($values['image-align-horizontal']) : '';
		$ret['image-align-vertical'] = isset($values['image-align-vertical']) && $values['image-align-vertical'] != '-1' ? esc_attr($values['image-align-vertical']) : '';

		$ret['youtube'] = isset($values['custom-youtube']) ? esc_attr($values['custom-youtube']) : '';
		$ret['vimeo'] = isset($values['custom-vimeo']) ? esc_attr($values['custom-vimeo']) : '';
		$ret['wistia'] = isset($values['wistia']) ? esc_attr($values['wistia']) : '';

		$ret['soundcloud'] = isset($values['custom-soundcloud']) ? esc_attr($values['custom-soundcloud']) : '';

		$ret['html5']['mp4'] = isset($values['custom-html5-mp4']) ? esc_attr($values['custom-html5-mp4']) : '';
		$ret['html5']['ogv'] = isset($values['custom-html5-ogv']) ? esc_attr($values['custom-html5-ogv']) : '';
		$ret['html5']['webm'] = isset($values['custom-html5-webm']) ? esc_attr($values['custom-html5-webm']) : '';

		$ret['iframe'] = isset($values['iframe']) ? esc_attr($values['iframe']) : '';
		$ret['revslider'] = isset($values['revslider']) ? esc_attr($values['revslider']) : '';
		$ret['essgrid'] = isset($values['essgrid']) ? esc_attr($values['essgrid']) : '';

		return apply_filters('essgrid_get_custom_media_source_data', $ret);
	}

	/**
	 * set basic Order List for Main Media Source
	 */
	public static function get_media_source_order()
	{
		$media = array('featured-image' => array('name' => esc_attr__('Featured Image', ESG_TEXTDOMAIN), 'type' => 'picture'),
			'youtube' => array('name' => esc_attr__('YouTube Video', ESG_TEXTDOMAIN), 'type' => 'video'),
			'vimeo' => array('name' => esc_attr__('Vimeo Video', ESG_TEXTDOMAIN), 'type' => 'video'),
			'wistia' => array('name' => esc_attr__('Wistia Video', ESG_TEXTDOMAIN), 'type' => 'video'),
			'html5' => array('name' => esc_attr__('HTML5 Video', ESG_TEXTDOMAIN), 'type' => 'video'),
			'soundcloud' => array('name' => esc_attr__('SoundCloud', ESG_TEXTDOMAIN), 'type' => 'play-circled'),
			'alternate-image' => array('name' => esc_attr__('Alternate Image', ESG_TEXTDOMAIN), 'type' => 'picture'),
			'iframe' => array('name' => esc_attr__('iFrame Markup', ESG_TEXTDOMAIN), 'type' => 'align-justify'),
			'content-image' => array('name' => esc_attr__('First Content Image', ESG_TEXTDOMAIN), 'type' => 'picture'),
			'content-iframe' => array('name' => esc_attr__('First Content iFrame', ESG_TEXTDOMAIN), 'type' => 'align-justify'),
			'content-html5' => array('name' => esc_attr__('First Content HTML5 Video', ESG_TEXTDOMAIN), 'type' => 'video'),
			'content-youtube' => array('name' => esc_attr__('First Content YouTube Video', ESG_TEXTDOMAIN), 'type' => 'video'),
			'content-vimeo' => array('name' => esc_attr__('First Content Vimeo Video', ESG_TEXTDOMAIN), 'type' => 'video'),
			'content-wistia' => array('name' => esc_attr__('First Content Wistia Video', ESG_TEXTDOMAIN), 'type' => 'video'),
			'content-soundcloud' => array('name' => esc_attr__('First Content SoundCloud', ESG_TEXTDOMAIN), 'type' => 'play-circled')
		);

		return apply_filters('essgrid_set_media_source_order', apply_filters('essgrid_get_media_source_order', $media));
	}

	/**
	 * set basic Order List for Lightbox Source
	 */
	public static function get_lb_source_order()
	{
		$media = array('featured-image' => array('name' => esc_attr__('Featured Image', ESG_TEXTDOMAIN), 'type' => 'picture'),
			'youtube' => array('name' => esc_attr__('YouTube Video', ESG_TEXTDOMAIN), 'type' => 'video'),
			'vimeo' => array('name' => esc_attr__('Vimeo Video', ESG_TEXTDOMAIN), 'type' => 'video'),
			'wistia' => array('name' => esc_attr__('Wistia Video', ESG_TEXTDOMAIN), 'type' => 'video'),
			'html5' => array('name' => esc_attr__('HTML5 Video', ESG_TEXTDOMAIN), 'type' => 'video'),
			'alternate-image' => array('name' => esc_attr__('Alternate Image', ESG_TEXTDOMAIN), 'type' => 'picture'),
			'content-image' => array('name' => esc_attr__('First Content Image', ESG_TEXTDOMAIN), 'type' => 'picture'),
			'post-content' => array('name' => esc_attr__('Post Content', ESG_TEXTDOMAIN), 'type' => 'doc-inv'),
			'revslider' => array('name' => esc_attr__('Slider Revolution', ESG_TEXTDOMAIN), 'type' => 'arrows-ccw'),
			'essgrid' => array('name' => esc_attr__('Essential Grid', ESG_TEXTDOMAIN), 'type' => 'th-large'),
			'soundcloud' => array('name' => esc_attr__('SoundCloud', ESG_TEXTDOMAIN), 'type' => 'soundcloud'),
			'iframe' => array('name' => esc_attr__('iFrame', ESG_TEXTDOMAIN), 'type' => 'link')
		);

		return apply_filters('essgrid_set_lb_source_order', apply_filters('essgrid_get_lb_source_order', $media));
	}

	/**
	 * set basic Order List for Lightbox Source
	 */
	public static function get_lb_button_order()
	{
		$buttons = array('share' => array('name' => esc_attr__('Social Share', ESG_TEXTDOMAIN), 'type' => 'forward'),
			'slideShow' => array('name' => esc_attr__('Play / Pause', ESG_TEXTDOMAIN), 'type' => 'play'),
			'thumbs' => array('name' => esc_attr__('Thumbnails', ESG_TEXTDOMAIN), 'type' => 'th'),
			'zoom' => array('name' => esc_attr__('Zoom/Pan', ESG_TEXTDOMAIN), 'type' => 'search'),
			'download' => array('name' => esc_attr__('Download Image', ESG_TEXTDOMAIN), 'type' => 'download'),
			'arrowLeft' => array('name' => esc_attr__('Left Arrow', ESG_TEXTDOMAIN), 'type' => 'left'),
			'arrowRight' => array('name' => esc_attr__('Right Arrow', ESG_TEXTDOMAIN), 'type' => 'right'),
			'close' => array('name' => esc_attr__('Close Button', ESG_TEXTDOMAIN), 'type' => 'cancel')
		);

		return apply_filters('essgrid_set_lb_button_order', apply_filters('essgrid_get_lb_button_order', $buttons));
	}

	/**
	 * set basic Order List for Ajax loading
	 * @since: 1.5.0
	 */
	public static function get_aj_source_order()
	{
		$media = array('post-content' => array('name' => esc_attr__('Post Content', ESG_TEXTDOMAIN), 'type' => 'doc-text'),
			'youtube' => array('name' => esc_attr__('YouTube Video', ESG_TEXTDOMAIN), 'type' => 'video'),
			'vimeo' => array('name' => esc_attr__('Vimeo Video', ESG_TEXTDOMAIN), 'type' => 'video'),
			'wistia' => array('name' => esc_attr__('Wistia Video', ESG_TEXTDOMAIN), 'type' => 'video'),
			'html5' => array('name' => esc_attr__('HTML5 Video', ESG_TEXTDOMAIN), 'type' => 'video'),
			'soundcloud' => array('name' => esc_attr__('SoundCloud', ESG_TEXTDOMAIN), 'type' => 'video'),
			'featured-image' => array('name' => esc_attr__('Featured Image', ESG_TEXTDOMAIN), 'type' => 'picture'),
			'alternate-image' => array('name' => esc_attr__('Alternate Image', ESG_TEXTDOMAIN), 'type' => 'picture'),
			'content-image' => array('name' => esc_attr__('First Content Image', ESG_TEXTDOMAIN), 'type' => 'picture')
		);

		return apply_filters('essgrid_set_ajax_source_order', apply_filters('essgrid_get_ajax_source_order', $media));
	}

	/**
	 * set basic Order List for Poster Orders
	 */
	public static function get_poster_source_order()
	{
		$media = array('featured-image' => array('name' => esc_attr__('Featured Image', ESG_TEXTDOMAIN), 'type' => 'picture'),
			'alternate-image' => array('name' => esc_attr__('Alternate Image', ESG_TEXTDOMAIN), 'type' => 'picture'),
			'content-image' => array('name' => esc_attr__('First Content Image', ESG_TEXTDOMAIN), 'type' => 'picture'),
			'youtube-image' => array('name' => esc_attr__('YouTube Thumbnail', ESG_TEXTDOMAIN), 'type' => 'picture'),
			'default-youtube-image' => array('name' => esc_attr__('YouTube Default Image', ESG_TEXTDOMAIN), 'type' => 'picture'),
			'vimeo-image' => array('name' => esc_attr__('Vimeo Thumbnail', ESG_TEXTDOMAIN), 'type' => 'picture'),
			'default-vimeo-image' => array('name' => esc_attr__('Vimeo Default Image', ESG_TEXTDOMAIN), 'type' => 'picture'),
			'default-html-image' => array('name' => esc_attr__('HTML5 Default Image', ESG_TEXTDOMAIN), 'type' => 'picture'),
			'no-image' => array('name' => esc_attr__('No Image', ESG_TEXTDOMAIN), 'type' => 'align-justify')
		);

		return apply_filters('essgrid_set_poster_source_order', apply_filters('essgrid_get_poster_source_order', $media));
	}

	/**
	 * remove essential grid shortcode from text
	 * @since: 2.0
	 */
	public function strip_essential_shortcode($content)
	{
		if (has_shortcode($content, 'ess_grid')) {
			global $shortcode_tags;
			$stack = $shortcode_tags;
			$shortcode_tags = array('ess_grid' => 1);
			$content = strip_shortcodes($content);
			$shortcode_tags = $stack;
		}

		return apply_filters('essgrid_strip_essential_shortcode', $content);
	}

	/**
	 * retrieve all content gallery images in post text
	 * @since: 1.5.4
	 * @original: in Essential_Grid->check_for_shortcodes()
	 */
	public function get_all_gallery_images($content, $url = false, $source = 'full')
	{
		$ret = array();
		if (empty($content)) return apply_filters('essgrid_get_all_gallery_images', $ret, $content, $url, $source);
		
		//classic editor shortcode
		if (has_shortcode($content, 'gallery')) {
			preg_match('/\[gallery.*ids=.(.*).\]/', $content, $img_ids);
			if (isset($img_ids[1])) {
				if (!$url) {
					if ($img_ids[1] !== '') $ret = explode(',', $img_ids[1]);
				} else { //get URL instead of ID
					$images = array();
					$imgs = explode(',', $img_ids[1]);
					foreach ($imgs as $img) {
						$t_img = wp_get_attachment_image_src($img, $source);
						if ($t_img !== false) {
							$images[] = $t_img[0];
						}
					}
					$ret = $images;
				}
			}
		}
		
		//gutenberg block
		if (empty($ret) && function_exists('parse_blocks')) {
			
			$blocks = parse_blocks($content);
			foreach ($blocks as $block) {
				if ( 'core/gallery' !== $block['blockName'] ) continue;
				foreach ( $block['innerBlocks'] as $inner_block ) {
					if ( 'core/image' === $inner_block['blockName'] && isset( $inner_block['attrs']['id'] ) ) {
						if (!$url) {
							$ret[] = $inner_block['attrs']['id'];
						} else {
							$t_img = wp_get_attachment_image_src($inner_block['attrs']['id'], $source);
							if ($t_img !== false) {
								$ret[] = $t_img[0];
							}
						}
					}
				}
			}
			
		}

		return apply_filters('essgrid_get_all_gallery_images', $ret, $content, $url, $source);
	}

	/**
	 * retrieve the first content image in post text
	 */
	public function get_first_content_image($post_id, $post = false)
	{
		if ($post_id != -1)
			$post = get_post($post_id);

		$first_img = '';
		ob_start();
		ob_end_clean();
		preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $matches);

		if (isset($matches[1][0]))
			$first_img = $matches[1][0];

		if (empty($first_img)) {
			$first_img = '';
		}

		return apply_filters('essgrid_get_first_content_image', $first_img, $post_id, $post);
	}

	/**
	 * retrieve all content images in post text
	 * @since: 1.5.4
	 */
	public function get_all_content_images($post_id, $post = false, $source = 'full')
	{
		if ($post_id != -1)
			$post = get_post($post_id);

		$images = array();
		ob_start();
		ob_end_clean();
		preg_match_all('/<img[^>]*src\s?=\s?([\'"])((?:(?!\1).)*)[^>]*>/i', $post->post_content, $matches);

		if (isset($matches[2][0]))
			$images = $matches[2];

		if (empty($images)) {
			$images = array();
		} else {
			if ($source !== 'full') {
				foreach ($images as $i => $img) {
					$img_id = $this->get_image_id_by_url($img);
					$_img = wp_get_attachment_image_src($img_id, $source);
					$images[$i] = (!empty($_img)) ? $_img[0] : $img;
				}
			}
		}

		return apply_filters('essgrid_get_all_content_images', $images, $post_id, $post);
	}

	/**
	 * retrieve the first iframe in the post text
	 * @since: 1.2.0
	 */
	public function get_first_content_iframe($post_id, $post = false)
	{
		if ($post_id != -1)
			$post = get_post($post_id);

		$first_iframe = '';
		ob_start();
		ob_end_clean();
		preg_match_all('/<iframe.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $matches);

		if (isset($matches[0][0]))
			$first_iframe = $matches[0][0];

		if (empty($first_iframe)) {
			$first_iframe = '';
		}

		return apply_filters('essgrid_get_first_content_iframe', $first_iframe, $post_id, $post);
	}

	/**
	 * retrieve the first YouTube video in the post text
	 * @since: 1.2.0
	 */
	public function get_first_content_youtube($post_id, $post = false)
	{
		if ($post_id != -1)
			$post = get_post($post_id);

		$first_yt = '';
		ob_start();
		ob_end_clean();
		preg_match_all('/(http:|https:|:)?\/\/(?:[0-9A-Z-]+\.)?(?:youtu\.be\/|youtube\.com(?:\/embed\/|\/v\/|\/watch\?v=|\/ytscreeningroom\?v=|\/feeds\/api\/videos\/|\/user\S*[^\w\-\s]|\S*[^\w\-\s]))([\w\-]{11})[?=&+%\w-]*/i', $post->post_content, $matches);

		if (isset($matches[2][0]))
			$first_yt = $matches[2][0];

		if (empty($first_yt)) {
			$first_yt = '';
		}

		return apply_filters('essgrid_get_first_content_youtube', $first_yt, $post_id, $post);
	}

	/**
	 * retrieve the first vimeo video in the post text
	 * @since: 1.2.0
	 */
	public function get_first_content_vimeo($post_id, $post = false)
	{
		if ($post_id != -1)
			$post = get_post($post_id);

		$first_vim = '';
		ob_start();
		ob_end_clean();
		preg_match_all('/(http:|https:|:)?\/\/?vimeo\.com\/([0-9a-z\/]+)\??|player\.vimeo\.com\/video\/([0-9a-z\/]+)\??/i', $post->post_content, $matches);

		if (!empty($matches[2][0]))
			$first_vim = $matches[2][0];
		if (!empty($matches[3][0]))
			$first_vim = $matches[3][0];

		return apply_filters('essgrid_get_first_content_vimeo', $first_vim, $post_id, $post);
	}

	/**
	 * retrieve the first wistia video in the post text
	 * @since: 2.0.6
	 */
	public function get_first_content_wistia($post_id, $post = false)
	{
		if ($post_id != -1)
			$post = get_post($post_id);

		$first_ws = '';
		ob_start();
		ob_end_clean();
		
		preg_match_all('/(http:|https:|:)?\/\/?wistia\.net\/([0-9]+)\??|player\.wistia\.net\/video\/([0-9]+)\??/i', $post->post_content, $matches);
		if (isset($matches[2][0]))
			$first_ws = $matches[2][0];

		if (empty($first_ws)) {
			preg_match_all("/wistia\.com\/(medias|embed)\/([0-9a-z]+)/i", $post->post_content, $matches);
			if (isset($matches[2][0]))
				$first_ws = $matches[2][0];
			if (empty($first_ws)) {
				$first_ws = '';
			}
		}

		return apply_filters('essgrid_get_first_content_wistia', $first_ws, $post_id, $post);
	}

	/**
	 * retrieve the first video in the post text
	 * @since: 1.2.0
	 */
	public function get_first_content_video($post_id, $post = false)
	{
		if ($post_id != -1)
			$post = get_post($post_id);

		$video = false;
		ob_start();
		ob_end_clean();
		preg_match_all("'<video>(.*?)</video>'si", $post->post_content, $matches);

		if (isset($matches[0][0])) {
			preg_match_all('/<source.+src=[\'"]([^\'"]+)[\'"].*>/i', $matches[0][0], $video_match);
			if (isset($video_match[1]) && is_array($video_match[1])) {
				foreach ($video_match[1] as $video_source) {
					$vid = explode('.', $video_source);
					switch (end($vid)) {
						case 'ogv':
							$video['ogv'] = $video_source;
							break;
						case 'webm':
							$video['webm'] = $video_source;
							break;
						case 'mp4':
							$video['mp4'] = $video_source;
							break;
					}
				}
			}
		}

		if (empty($video)) {
			$video = false;
		}

		return apply_filters('essgrid_get_first_content_video', $video, $post_id, $post);
	}

	/**
	 * retrieve the first soundcloud in the post text
	 * @since: 1.2.0
	 */
	public function get_first_content_soundcloud($post_id, $post = false)
	{
		if ($post_id != -1)
			$post = get_post($post_id);

		$first_sc = '';
		ob_start();
		ob_end_clean();
		preg_match_all('/\/\/api.soundcloud.com\/tracks\/(.[0-9]*)/i', $post->post_content, $matches);

		if (isset($matches[1][0]))
			$first_sc = $matches[1][0];
		if (empty($first_sc)) {
			$first_sc = '';
		}

		return apply_filters('essgrid_get_first_content_soundcloud', $first_sc, $post_id, $post);
	}

	/**
	 * retrieve the image id from the given image url
	 * @since: 1.1.0
	 */
	public function get_image_id_by_url($image_url)
	{
		global $wpdb;
		$attachment_id = false;

		// If there is no url, return.
		if ('' != $image_url) {
			$attachment_id = (function_exists('attachment_url_to_postid')) ? attachment_url_to_postid($image_url) : 0;
			if (0 == $attachment_id) {
				// Get the upload directory paths
				$upload_dir_paths = wp_upload_dir();
				// Make sure the upload path base directory exists in the attachment URL, to verify that we're working with a media library image
				if (false !== strpos($image_url, $upload_dir_paths['baseurl'])) {
					// If this is the URL of an auto-generated thumbnail, get the URL of the original image
					$image_url = preg_replace('/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $image_url);
					// Remove the upload path base directory from the attachment URL
					$image_url = str_replace($upload_dir_paths['baseurl'] . '/', '', $image_url);
					// Finally, run a custom database query to get the attachment ID from the modified attachment URL
					$attachment_id = $wpdb->get_var($wpdb->prepare("SELECT wposts.ID FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta WHERE wposts.ID = wpostmeta.post_id AND wpostmeta.meta_key = '_wp_attached_file' AND wpostmeta.meta_value = '%s' AND wposts.post_type = 'attachment'", $image_url));
				}
			}
		}
		return apply_filters('essgrid_get_image_id_by_url', $attachment_id, $image_url);
	}

	/**
	 * check if in the content exists a certain essential grid
	 * @since 1.0.6
	 */
	public function is_shortcode_with_handle_exist($grid_handle)
	{
		$content = get_the_content();
		$pattern = get_shortcode_regex();
		preg_match_all('/' . $pattern . '/s', $content, $matches);
		$found = false;
		if (is_array($matches[2]) && !empty($matches[2])) {
			foreach ($matches[2] as $key => $sc) {
				if ($sc == 'ess_grid') {
					$attr = shortcode_parse_atts($matches[3][$key]);
					if (isset($attr['alias'])) {
						if ($grid_handle == $attr['alias']) {
							$found = true;
							break;
						}
					}
				}
			}
		}

		return apply_filters('essgrid_is_shortcode_with_handle_exist', $found, $grid_handle);
	}

	/**
	 * minimize CSS styles
	 * @since 1.1.0
	 */
	public function compress_css($buffer)
	{
		$buffer = apply_filters('essgrid_compress_css_pre', $buffer);

		/* remove comments */
		$buffer = preg_replace("!/\*[^*]*\*+([^/][^*]*\*+)*/!", "", $buffer);
		/* remove tabs, spaces, newlines, etc. */
		$buffer = str_replace("	", " ", $buffer); //replace tab with space
		$arr = array("\r\n", "\r", "\n", "\t", "  ", "    ", "    ");
		$rep = array("", "", "", "", " ", " ", " ");
		$buffer = str_replace($arr, $rep, $buffer);
		/* remove whitespaces around {}:, */
		$buffer = preg_replace("/\s*([\{\}:,])\s*/", "$1", $buffer);
		/* remove last ; */
		$buffer = str_replace(';}', "}", $buffer);

		return apply_filters('essgrid_compress_css_post', $buffer);
	}

	/**
	 * shuffle by preserving the key
	 * @since 1.5.1
	 */
	public function shuffle_assoc($list)
	{
		if (!is_array($list)) return $list;

		$keys = array_keys($list);
		shuffle($keys);
		$random = array();
		foreach ($keys as $key) {
			$random[$key] = $list[$key];
		}

		return apply_filters('essgrid_shuffle_assoc', $random);
	}

	/**
	 * prints out debug text if constant TP_DEBUG is defined and true
	 * @since: 2.1.0
	 */
	public static function debug($value, $message, $where = "console")
	{
		return false;
	}

	/**
	 * prints out numbers in YouTube format
	 * @since: 2.1.0
	 */
	public static function thousandsViewFormat($num)
	{
		if ($num > 999) {
			$x = round($num);
			$x_number_format = number_format($x);
			$x_array = explode(',', $x_number_format);
			$x_parts = array('K', 'M', 'B', 'T');
			$x_count_parts = count($x_array) - 1;
			$x_display = $x_array[0] . ((int)$x_array[1][0] !== 0 ? '.' . $x_array[1][0] : '');
			$x_display .= $x_parts[$x_count_parts - 1];
		} else $x_display = $num;

		return $x_display;
	}

	/**
	 * sanitizes utf8 characters to unicode
	 * @since: 3.0.9
	 */
	public static function sanitize_utf8_to_unicode($string)
	{
		return sanitize_key(json_encode($string));
	}
	
	/**
	 * get attachment info
	 * @since: 3.0.14
	 */
	public static function get_attachment_info( $attachment_id )
	{
		$attachment = get_post( $attachment_id );
		return array(
			'alt' => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
			'caption' => $attachment->post_excerpt,
			'description' => $attachment->post_content,
			'href' => get_permalink( $attachment->ID ),
			'src' => $attachment->guid,
			'title' => $attachment->post_title
		);
	}

	/**
	 * detect device type
	 * @since: 3.0.14
	 */
	public static function detect_device()
	{
		$detect = new \Esg\Mobile_Detect();
		$isMobile = $detect->isMobile();
		$isTablet = $detect->isTablet();
		return ($isMobile ? ($isTablet ? 'tablet' : 'mobile') : 'desktop');
	}
	
	/**
	 * get all device types along with column keys to get device width
	 * keys can be checked in get_basic_devices()
	 * default width can be checked in set_basic_colums_width()
	 * 
	 * @since: 3.0.14
	 */
	public static function get_device_columns()
	{
		return array(
			array(
				'device' => 'desktop',
				'columns' => array(0, 1, 2, 3),
			),
			array(
				'device' => 'tablet',
				'columns' => array(4, 5),
			),
			array(
				'device' => 'mobile',
				'columns' => array(6, 7),
			),
		);
	}

	/**
	 * clear transients by pattern
	 * 
	 * @param string $pattern
	 * @return void
	 */
	public static function clear_transients($pattern)
	{
		global $wpdb;

		$transients = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT REPLACE(option_name, '_transient_', '') as option_name FROM $wpdb->options WHERE `option_name` LIKE '%%%s%%'",
				$wpdb->esc_like($pattern)
			),
			ARRAY_A
		);
		foreach ($transients as $t) {
			delete_transient($t['option_name']);
		}
	}

}

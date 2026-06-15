<?php
/**
 * @author    ThemePunch <info@themepunch.com>
 * @link      http://www.themepunch.com/
 * @copyright 2023 ThemePunch
 * @version   1.0.1
 */
 
if( !defined( 'ABSPATH') ) exit();

if(!class_exists('ThemePunch_Fonts')) {
	 
	class ThemePunch_Fonts {
		
		/**
		 * register all fonts
		 */
		public function register_icon_fonts($focus){
			$enable_fontello = get_option('tp_eg_global_enable_fontello', 'backfront');
			$enable_font_awesome = get_option('tp_eg_global_enable_font_awesome', 'false');
			$enable_pe7 = get_option('tp_eg_global_enable_pe7', 'false');
			
			if ($focus=="admin") {
				if ($enable_pe7!="false") wp_enqueue_style('tp-stroke-7', ESG_PLUGIN_URL . 'public/assets/font/pe-icon-7-stroke/css/pe-icon-7-stroke.css', array(), ESG_REVISION );	
				if ($enable_font_awesome!="false") wp_enqueue_style('tp-font-awesome', ESG_PLUGIN_URL . 'public/assets/font/font-awesome/css/font-awesome.css', array(), ESG_REVISION );
			} else {
				if ($enable_fontello=="backfront") wp_enqueue_style('tp-fontello', ESG_PLUGIN_URL . 'public/assets/font/fontello/css/fontello.css', array(), ESG_REVISION );
				if ($enable_font_awesome=="backfront") wp_enqueue_style('tp-font-awesome', ESG_PLUGIN_URL . 'public/assets/font/font-awesome/css/font-awesome.css', array(), ESG_REVISION );
				if ($enable_pe7=="backfront") wp_enqueue_style('tp-stroke-7', ESG_PLUGIN_URL . 'public/assets/font/pe-icon-7-stroke/css/pe-icon-7-stroke.css', array(), ESG_REVISION );
			}
		}
		
		/**
		 * register all fonts
		 */
		public static function propagate_default_fonts($networkwide = false){
			global $wpdb;
			
			$default = array ();
			$default = apply_filters('essgrid_add_default_fonts', $default); // will be obsolete soon, use tp_add_default_fonts instead
			$default = apply_filters('tp_add_default_fonts', $default);
			
			if (function_exists('is_multisite') && is_multisite() && $networkwide) { 
				// do for each existing site
				// Get all blog ids and create tables
				$blogids = $wpdb->get_col("SELECT blog_id FROM ".$wpdb->blogs);
				foreach ($blogids as $blog_id) {
					switch_to_blog($blog_id);
					self::_propagate_default_fonts($default);
					// 2.2.5
					restore_current_blog();
				}
			} else {
				self::_propagate_default_fonts($default);
			}
		}
		
		/**
		 * register all fonts modified for multisite
		 * @since: 1.5.0
		 */
		public static function _propagate_default_fonts($default){
			$fonts = get_option('tp-google-fonts', array());
			if (empty($fonts)) {
				update_option('tp-google-fonts', $default);
				self::invalidate_privacy();
			}
		}

		/**
		 * real cookie banner: invalidate presets cache so Google Fonts gets shown in scanner
		 */
		protected static function invalidate_privacy() {
			if (function_exists('wp_rcb_invalidate_presets_cache')) {
				wp_rcb_invalidate_presets_cache();
			}
		}
		
	}
}

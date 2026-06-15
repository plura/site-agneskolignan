<?php
/**
 * @package   Essential_Grid
 * @author    ThemePunch <info@themepunch.com>
 * @link      http://www.themepunch.com/essential/
 * @copyright 2023 ThemePunch
 */

if (!defined('ABSPATH')) exit();

class Essential_Grid_Addons
{
	/**
	 * option to keep addons list retrieved from TP server
	 */
	CONST OPTION_ADDONS = 'tp_eg-addons';
	/**
	 * option to keep new addons counter
	 */
	CONST OPTION_ADDONS_COUNTER = 'tp_eg-addons-counter';

	/**
	 * Holds the class instance.
	 * @var Essential_Grid_Addons
	 */
	private static $instance = null;
	
	/**
	 * @var string 
	 */
	private $url_download = 'essential-grid/addons/download.php';
	/**
	 * @var string
	 */
	private $path_download = '/essential-grid/templates';
	
	/**
	 * @var Essential_Grid_Base
	 */
	private $base;
	
	protected $_required_addons = array(
		'esg-fonts-addon' => '1.1.2',
		'esg-nextgen-addon' => '1.0.2',
		'esg-rml-addon' => '1.0.2',
		'esg-socialmedia-addon' => '1.0.4',
		'esg-videoplaylists-addon' => '1.0.4',
	);

	/**
	 * get class instance
	 *
	 * @return Essential_Grid_Addons  An instance of the class.
	 */
	public static function instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct()
	{
		include_once(ABSPATH . 'wp-admin/includes/plugin.php');
		$this->base = new Essential_Grid_Base();
	}

	/**
	 * @param mixed $default
	 * @return array
	 */
	public function get_addons($default = array())
	{
		$return = get_option(self::OPTION_ADDONS, $default);
		return (array)$return;
	}

	/**
	 * @param array $addons
	 * @return bool
	 */
	public function set_addons($addons)
	{
		return update_option(self::OPTION_ADDONS, $addons);
	}

	/**
	 * @param array $new_addons
	 * @return bool
	 */
	public function update_addons($new_addons)
	{
		$addons = $this->get_addons();
		foreach ($new_addons as $slug => $a) {
			if (!isset($addons[$slug])) continue;
			if (isset($addons[$slug]->enabled)) {
				$new_addons[$slug]->enabled = $addons[$slug]->enabled;
			}
		}
		
		$cur_addons_count = count($addons);
		$new_addons_count = count($new_addons);
		if($cur_addons_count < $new_addons_count){
			$this->set_addons_counter($new_addons_count - $cur_addons_count);
		}
		
		return $this->set_addons($new_addons);
	}

	/**
	 * @param mixed $default
	 * @return false|int
	 */
	public function get_addons_counter($default = 0)
	{
		return get_option(self::OPTION_ADDONS_COUNTER, $default);
	}

	/**
	 * @param int $count
	 * @return void
	 */
	public function set_addons_counter($count)
	{
		update_option(self::OPTION_ADDONS_COUNTER, $count);
	}

	/**
	 * get addons list loaded from Themepunch server
	 *
	 * @return array
	 **/
	public function get_addons_list()
	{
		$addons = $this->get_addons();
		$addons = array_reverse($addons, true);
		$plugins = get_plugins();

		if (!empty($addons)) {
			foreach ($addons as $k => $addon) {
				if (!is_object($addon)) continue;
				if (array_key_exists($addon->slug . '/' . $addon->slug . '.php', $plugins)) {
					$addons[$k]->full_title = $plugins[$addon->slug . '/' . $addon->slug . '.php']['Name'];
					$addons[$k]->active = is_plugin_active($addon->slug . '/' . $addon->slug . '.php');
					$addons[$k]->installed = $plugins[$addon->slug . '/' . $addon->slug . '.php']['Version'];
				} else {
					$addons[$k]->active = false;
					$addons[$k]->installed = false;
				}
				//only global addons store enabled value in addons option
				//other addons disabled by default
				if (!$addons[$k]->active || !$addons[$k]->global || !isset($addons[$k]->enabled)) {
					$addons[$k]->enabled = false;
				}
			}
		}

		return $addons;
	}
	
	/**
	 * get addons list for certain grid
	 * 
	 * @param int $grid_id
	 * @param null|array $grid_addons  addons could be passed explicitly to omit additional database call
	 * @return array
	 **/
	public function get_grid_addons_list($grid_id, $grid_addons = null)
	{
		$addons = $this->get_addons_list();
		
		if (is_null($grid_addons)) {
			$grid = Essential_Grid::get_essential_grid_by_id(intval($grid_id));
			if (empty($grid) || empty($grid['params']['addons'])) {
				return $addons;
			}
			
			$grid_addons = $grid['params']['addons'];
		}
		
		foreach ($grid_addons as $handle => $v) {
			if (empty($addons[$handle]) || !$addons[$handle]->active) continue;
			$addons[$handle]->enabled = true;
		}

		return $addons;
	}

	/**
	 * get a specific addon version
	 *
	 * @param string $handle  i.e. 'esg-test-addon'
	 * @return bool|string
	 **/
	public function get_addon_version($handle)
	{
		$addon_path = $handle . '/' . $handle . '.php';
		$plugins = get_plugins();
		if (!array_key_exists($addon_path, $plugins)) return false;
		
		return $this->base->getVar($plugins, array($addon_path, 'Version'), false);
	}

	/**
	 * @param string $handle  i.e. 'esg-test-addon'
	 */
	public function get_addon_attr($handle, $attr, $default = false)
	{
		$addons = $this->get_addons();
		return $this->base->getVar($addons, array($handle, $attr), $default);
	}

	/**
	 * @param string $handle  i.e. 'esg-test-addon'
	 * @param bool $force
	 * @return bool
	 */
	public function install_addon($handle, $force = false)
	{
		//check if downloaded already && download if nessecary
		$addon_path = $handle . '/' . $handle . '.php';
		$plugins = get_plugins();
		if ($force || !array_key_exists($addon_path, $plugins) || !file_exists(WP_PLUGIN_DIR . '/' . $addon_path)) {
			return $this->download_addon($handle);
		}

		/* translators: %s: Addon Name. */
		return esc_attr(sprintf(__('"%s" Add-On already Installed', ESG_TEXTDOMAIN), $handle));
	}

	/**
	 * @param string $addon  i.e. 'esg-test-addon'
	 * @return bool
	 */
	public function download_addon($addon)
	{
		global $esg_loadbalancer;
		
		$plugin_slug = basename($addon);
		if (0 !== strpos($plugin_slug, 'esg-')) die('-1');

		$code = Essential_Grid_Base::getCode();
		$data = array(
			'code' => urlencode($code),
			'version' => urlencode(ESG_REVISION),
			'product' => urlencode(ESG_PLUGIN_SLUG),
			'type' => urlencode($plugin_slug)
		);
		$request = $esg_loadbalancer->call_url($this->url_download, $data);
		if (is_wp_error($request)) return false;

		$response = wp_remote_retrieve_body($request);
		if (empty($response) || $response == 'invalid') return false;

		$upload_dir = wp_upload_dir();
		$dir = $upload_dir['basedir'] . $this->path_download;
		$file = $dir . '/' . $plugin_slug . '.zip';
		if (!file_exists( $dir ) || !is_dir( $dir)) {
			mkdir($dir, 0777, true);
		}
		
		$ret = @file_put_contents($file, $response);

		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php');
		require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php');
		$fsd = new WP_Filesystem_Direct(false);
		WP_Filesystem();

		global $wp_filesystem;

		//remove the addon folder if exists
		$fsd->rmdir(WP_PLUGIN_DIR . '/' . $plugin_slug, true);

		$unzipfile = unzip_file($file, WP_PLUGIN_DIR);
		if (is_wp_error($unzipfile)) {
			//lets try direct.
			define('FS_METHOD', 'direct'); 
			//WP_Filesystem() needs to be called again since now we use direct !
			WP_Filesystem();  

			$unzipfile = unzip_file($file, WP_PLUGIN_DIR);
		}

		@unlink($file);

		wp_clean_plugins_cache();
		
		return !is_wp_error($unzipfile);
	}

	/**
	 * @param string $handle  i.e. 'esg-test-addon'
	 * @return bool
	 */
	public function activate_addon($handle)
	{
		$addon_path = $handle . '/' . $handle . '.php';
		$return = activate_plugin($addon_path);
		wp_clean_plugins_cache();
		return !is_wp_error($return);
	}

	/**
	 * @param string $handle  i.e. 'esg-test-addon'
	 * @return void
	 */
	public function deactivate_addon($handle)
	{
		$addon_path = $handle . '/' . $handle . '.php';
		deactivate_plugins($addon_path);
		wp_clean_plugins_cache();
	}
	
	/**
	 * @param string $handle  i.e. 'esg-test-addon'
	 * @param string $key
	 * @param mixed $value
	 * @return bool
	 */
	protected function _update_addon($handle, $key, $value)
	{
		$addons = $this->get_addons();
		if (empty($addons[$handle])) return false;

		$addons[$handle]->{$key} = $value;
		return $this->set_addons($addons);
	}
	
	/**
	 * @param string $handle  i.e. 'esg-test-addon'
	 * @return bool
	 */
	public function enable_addon($handle)
	{
		return $this->_update_addon($handle, 'enabled', true);
	}

	/**
	 * @param string $handle  i.e. 'esg-test-addon'
	 * @return bool
	 */
	public function disable_addon($handle)
	{
		return $this->_update_addon($handle, 'enabled', false);
	}

	/**
	 * update grid params and clear cache
	 * 
	 * @param array $grid
	 * @return bool
	 */
	protected function _update_grid($grid)
	{
		$return = Essential_Grid_Admin::update_create_grid($grid);
		if (true !== $return) return $return;

		//clear cache
		Essential_Grid_Base::clear_transients('ess_grid_trans_query_' . $grid['id']);
		Essential_Grid_Base::clear_transients('ess_grid_trans_full_grid_' . $grid['id']);

		return true;
	}
	
	/**
	 * @param string $handle  i.e. 'esg-test-addon'
	 * @param int $grid_id
	 * @return string|bool
	 */
	public function enable_grid_addon($handle, $grid_id)
	{
		$grid = Essential_Grid::get_essential_grid_by_id(intval($grid_id));
		if (empty($grid)) {
			return esc_attr__('Grid could not be loaded', ESG_TEXTDOMAIN);
		}

		if (!isset($grid['params']['addons']) || !is_array($grid['params']['addons'])) {
			$grid['params']['addons'] = array();
		}
		$grid['params']['addons'][$handle] = true;

		return $this->_update_grid($grid);
	}
	
	/**
	 * @param string $handle  i.e. 'esg-test-addon'
	 * @param int $grid_id
	 * @return string|bool
	 */
	public function disable_grid_addon($handle, $grid_id)
	{
		$grid = Essential_Grid::get_essential_grid_by_id(intval($grid_id));
		if (empty($grid)) {
			return esc_attr__('Grid could not be loaded', ESG_TEXTDOMAIN);
		}

		unset($grid['params']['addons'][$handle]);

		return $this->_update_grid($grid);
	}

	/**
	 * get list of addons that enabled in global options or grids and not installed / not activated
	 * 
	 * @return array
	 */
	public function get_missing_addons()
	{
		$addons = array();

		$dir = ESG_PLUGIN_PATH . 'includes/addons';
		$files = array_diff(scandir($dir), array('..', '.', 'index.php'));
		foreach ($files as $f) {
			require_once($dir . '/' . $f);
			$class = sprintf('Essential_Grid_%s_Addon', ucfirst(strtok($f, '.')));
			if (!class_exists($class)) continue;
			
			$addon = new $class;
			if ($addon->is_missing()) {
				$addons[] = $addon->get_handle();
			}
		}
		
		return $addons;
	}

	/**
	 * get list of addons that require update
	 *
	 * @return array
	 */
	public function get_require_update_addons()
	{
		$addons = array();

		$addons_list = $this->get_addons_list();
		foreach ($addons_list as $_addon) {
			if (!$_addon->active || empty($this->_required_addons[$_addon->slug])) continue;

			if (version_compare($_addon->installed, $this->_required_addons[$_addon->slug], '<')) {
				$addons[] = $_addon->slug;
			}
		}

		return $addons;
	}

	/**
	 * check if import key can be processed by addon
	 *
	 * @param array $keys
	 * @return array
	 */
	public function check_import_keys($keys)
	{
		$missing_addons = array();

		$dir = ESG_PLUGIN_PATH . 'includes/addons';
		$files = array_diff(scandir($dir), array('..', '.', 'index.php'));
		foreach ($files as $f) {
			require_once($dir . '/' . $f);
			$class = sprintf('Essential_Grid_%s_Addon', ucfirst(strtok($f, '.')));
			if (!class_exists($class)) continue;

			$addon = new $class;
			if ($addon->check_import_keys($keys)) {
				$missing_addons[$addon->get_handle()] = $addon->get_import_keys();
			}
		}

		return $missing_addons;
	}
	
}

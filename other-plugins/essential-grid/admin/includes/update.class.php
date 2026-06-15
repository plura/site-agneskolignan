<?php
/**
 * Update Class For Essential Grid
 * Enables automatic updates on the Plugin
 *
 * @package Essential_Grid_Admin
 * @author  ThemePunch <info@themepunch.com>
 * @link      http://www.themepunch.com/essential/
 * @copyright 2023 ThemePunch
 */

if (!defined('ABSPATH')) exit();

class Essential_Grid_Update
{

	/**
	 * @var string 
	 */
	private $plugin_url = 'https://codecanyon.net/item/essential-grid-wordpress-plugin/7563340';
	/**
	 * @var string 
	 */
	private $remote_url = 'check_for_updates.php';
	/**
	 * @var string 
	 */
	private $remote_url_info = 'essential-grid/essential-grid.php';
	/**
	 * @var string 
	 */
	private $plugin_path = 'essential-grid/essential-grid.php';
	/**
	 * @var string 
	 */
	private $version;
	/**
	 * @var string 
	 */
	private $option;
	/**
	 * @var stdClass 
	 */
	private $data;

	/**
	 * @param string $version
	 */
	public function __construct($version)
	{
		$this->option = ESG_PLUGIN_SLUG . '_update_info';
		$this->data = new stdClass;
		$this->_retrieve_version_info();
		$this->version = (empty($version)) ? ESG_REVISION : $version;
	}

	public function add_update_checks()
	{
		if (isset($_GET['checkforupdates']) && $_GET['checkforupdates'] == 'true'){
			// an update can follow, so set the execution time high for the runtime
			ini_set('max_execution_time', 300); 
			$transient = get_site_transient('update_plugins');
			$esg_t = $this->set_update_transient($transient);
			
			if(!empty($esg_t)){
				set_site_transient('update_plugins', $esg_t);
			}
		}

		add_filter('pre_set_site_transient_update_plugins', array(&$this, 'set_update_transient'));
		add_filter('plugins_api', array(&$this, 'set_updates_api_results'), 10, 3);
	}

	/**
	 * @param stdClass $transient
	 * @return stdClass
	 */
	public function set_update_transient($transient)
	{
		$this->_check_updates();
		
		if (!is_object($transient)) {
			$transient = new stdClass();
		}

		if (!isset($transient->response)) {
			$transient->response = array();
		}

		if (!empty($this->data->basic) && is_object($this->data->basic)) {
			$version = (isset($this->data->basic->version)) ? $this->data->basic->version : $this->data->basic->new_version;
			if (version_compare($this->version, $version, '<')) {
				$this->data->basic->new_version = $version;
				if(isset($this->data->basic->version)){
					unset($this->data->basic->version);
				}
				$transient->response[$this->plugin_path] = $this->data->basic;
			}
		}

		return $transient;
	}

	/**
	 * @param mixed $result
	 * @param string $action
	 * @param stdClass $args
	 * @return mixed
	 */
	public function set_updates_api_results($result, $action, $args)
	{
		$this->_check_updates();

		if (isset($args->slug) && $args->slug == ESG_PLUGIN_SLUG && $action == 'plugin_information') {
			if (is_object($this->data->full) && !empty($this->data->full)) {
				$result = $this->data->full;
			}
		}

		return $result;
	}
	
	protected function _check_updates()
	{
		$force = false;
		if (isset($_GET['checkforupdates']) && $_GET['checkforupdates'] == 'true') $force = true;

		// Get data
		if (empty($this->data)) {
			$data = get_option($this->option, false);
			$data = $data ? $data : new stdClass;
			$this->data = is_object($data) ? $data : maybe_unserialize($data);
		}

		// If there is no option in the database, boolean `false` is returned
		$last_check = get_option('tp_eg_update-check');
		if (!$last_check) {
			$last_check = time();
			update_option('tp_eg_update-check', $last_check);
		}

		// Check for updates
		if (time() - $last_check > 172800 || $force || empty($this->data)) {
			$data = $this->_retrieve_update_info();
			if (isset($data->basic)) {
				update_option('tp_eg_update-check', time());
				$this->data->checked = time();
				$this->data->basic = $data->basic;
				$this->data->full = $data->full;
				update_option('tp_eg_latest-version', $data->full->version);
			}
		}

		// Save results
		update_option($this->option, $this->data);
	}

	public function _retrieve_update_info()
	{
		global $esg_loadbalancer;
		
		// Build request
		$code = Essential_Grid_Base::getCode();
		$data = array(
			'code' => urlencode($code),
			'product' => urlencode(ESG_PLUGIN_SLUG),
			'version' => urlencode(ESG_REVISION)
		);
		$request = $esg_loadbalancer->call_url($this->remote_url_info, $data);

		$data = new stdClass;
		if (!is_wp_error($request)) {
			if ($response = maybe_unserialize($request['body'])) {
				if (is_object($response)) {
					$data = $response;
					$data->basic->url = $this->plugin_url;
					$data->full->url = $this->plugin_url;
					$data->full->external = 1;
				}
			}
		}
		
		return $data;
	}

	/**
	 * @param bool $force
	 * @return bool|string
	 */
	public function _retrieve_version_info($force = false)
	{
		global $esg_loadbalancer;

		// If there is no option in the database, boolean `false` is returned
		$last_check = get_option('tp_eg_update-check-short');

		// Check for updates
		if (!$last_check || time() - $last_check > 172800 || $force) {
			update_option('tp_eg_update-check-short', time());

			$hash = $force ? '' : get_option('tp_eg-update-hash', '');
			$data = array(
				'item' => urlencode(ESG_PLUGIN_SLUG),
				'version' => urlencode(ESG_REVISION),
				'hash' => urlencode($hash),
			);
			$request = $esg_loadbalancer->call_url($this->remote_url, $data);
			if (is_wp_error($request)) {
				update_option('essential-connection', false);
				return false;
			} 
			update_option('essential-connection', true);

			$response = wp_remote_retrieve_body($request);
			if ('actual' != $response) {
				$version_info = json_decode($response);

				if (isset($version_info->hash)) update_option('tp_eg-update-hash', $version_info->hash);
				if (isset($version_info->stable)) update_option('essential-stable-version', $version_info->stable);
				if (isset($version_info->notices)) update_option('essential-notices', $version_info->notices);
				if (isset($version_info->dashboard)) update_option('essential-dashboard', $version_info->dashboard);
				if (isset($version_info->version)) update_option('tp_eg_latest-version', $version_info->version);
				if (isset($version_info->addons)) {
					$esg_addons = Essential_Grid_Addons::instance();
					$esg_addons->update_addons((array)$version_info->addons);
				}
			}
		}

		return get_option('tp_eg_latest-version', ESG_REVISION);
	}

}

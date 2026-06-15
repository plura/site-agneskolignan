<?php
/**
 * @package   Essential_Grid
 * @author    ThemePunch <info@themepunch.com>
 * @link      https://www.themepunch.com/essential/
 * @copyright 2023 ThemePunch
 */

if (!defined('ABSPATH')) exit();

class Essential_Grid_License
{

	/**
	 * @var string 
	 */
	private $url_activate = 'activate.php';
	/**
	 * @var string 
	 */
	private $url_deactivate = 'deactivate.php';

	/**
	 * @param string $code
	 * @return bool|string
	 */
	public function activate_plugin($code)
	{
		global $esg_loadbalancer;

		$data = array(
			'code' => urlencode($code),
			'product' => urlencode(ESG_PLUGIN_SLUG),
		);
		$request = $esg_loadbalancer->call_url($this->url_activate, $data);
		if (is_wp_error($request)) return false;

		$response = wp_remote_retrieve_body($request);
		switch ($response) {
			case 'valid':
				Essential_Grid_Base::setValid('true');
				Essential_Grid_Base::setCode($code);
				return true;
				
			case 'exist':
				return esc_attr__('Purchase Code already registered!', ESG_TEXTDOMAIN);
				
			default:
				return esc_attr__('Purchase Code is not valid!', ESG_TEXTDOMAIN);
		}
	}

	/**
	 * @return bool
	 */
	public function deactivate_plugin()
	{
		global $esg_loadbalancer;

		$code = Essential_Grid_Base::getCode();
		$data = array(
			'code' => urlencode($code),
			'product' => urlencode(ESG_PLUGIN_SLUG),
		);
		$request = $esg_loadbalancer->call_url($this->url_deactivate, $data);
		if (is_wp_error($request)) return false;

		$response = wp_remote_retrieve_body($request);
		if ($response == 'valid') {
			Essential_Grid_Base::setValid('false');
			Essential_Grid_Base::setCode('');
			return true;
		}
		
		return false;
	}
}

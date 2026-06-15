<?php
/**
 * @package   Essential_Grid
 * @author    ThemePunch <info@themepunch.com>
 * @link      http://www.themepunch.com/essential/
 * @copyright 2023 ThemePunch
 */

if( !defined( 'ABSPATH') ) exit();

class Essential_Grid_Gallery_Addon
{
	protected $_handle = 'esg-gallery-addon';
	
	public function __construct()
	{
	}

	public function get_handle()
	{
		return $this->_handle;
	}
	
	/**
	 * addon is missing if original gallery option still in database
	 *
	 * @return bool
	 */
	public function is_missing()
	{
		$esg_addons = Essential_Grid_Addons::instance();
		$addons = $esg_addons->get_addons_list();
		$option = get_option('tp_eg_overwrite_gallery', 'off');

		if (empty($option) || 'off' == $option) return false;

		if (empty($addons[$this->_handle]) || !$addons[$this->_handle]->installed || !$addons[$this->_handle]->active) return true;

		return false;
	}

	/**
	 * check if import key can be processed by addon
	 *
	 * @param array $keys
	 * @return bool
	 */
	public function check_import_keys($keys)
	{
		return false;
	}
}

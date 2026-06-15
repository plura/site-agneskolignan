<?php
/**
 * @author    ThemePunch <info@themepunch.com>
 * @link      https://www.themepunch.com/
 * @copyright 2023 ThemePunch
 */

if(!defined('ABSPATH')) exit();

class Essential_Grid_Favorite {
	/**
	 * option to keep favorite list
	 */
	CONST OPTION = 'tp_eg-favorite';

	/**
	 * @param mixed $default
	 * @return false|mixed|void
	 */
	public function get_favorites($default = array())
	{
		return get_option(self::OPTION, $default);
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function set_favorites($data)
	{
		update_option(self::OPTION, $data);
	}

	/**
	 * get a certain favorite type
	 * 
	 * @param string $type
	 * @return array
	 **/
	public function get_favorite_type($type){
		$fav = $this->get_favorites();
		return Essential_Grid_Base::getVar($fav, $type, array());
	}

	/**
	 * check if certain element is in favorites
	 * 
	 * @param string $type
	 * @param mixed $id
	 * @return bool
	 **/
	public function is_favorite($type, $id){
		$favs = $this->get_favorite_type($type);
		return array_search($id, $favs) !== false;
	}
	
	/**
	 * change the setting of a favorization
	 * 
	 * @param string $action
	 * @param string $type
	 * @param mixed $id
	 * @return array
	 **/
	public function update_favorites($action, $type, $id){
		$fav = $this->get_favorites();
		$id	 = esc_attr($id);

		if (!isset($fav[$type])) $fav[$type] = array();
		$key = array_search($id, $fav[$type]);
		
		switch ($action) {
			case 'add':
				if ($key === false) $fav[$type][] = $id;
				break;
			case 'remove':
				unset($fav[$type][$key]);
				break;
			case 'replace':
				$fav[$type] = $id;
				break;
			default:
		}
		
		$this->set_favorites($fav);

		return $fav;
	}
}

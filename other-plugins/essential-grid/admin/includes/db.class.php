<?php
/**
 * @package   Essential_Grid
 * @author    ThemePunch <info@themepunch.com>
 * @link      http://www.themepunch.com/essential/
 * @copyright 2023 ThemePunch
 */

if (!defined('ABSPATH')) exit();

class Essential_Grid_Db
{
	const OPTION_VERSION = 'tp_eg_grids_version';
	
	const TABLE_GRID = 'eg_grids';
	const TABLE_ITEM_SKIN = 'eg_item_skins';
	const TABLE_ITEM_ELEMENTS = 'eg_item_elements';
	const TABLE_NAVIGATION_SKINS = 'eg_navigation_skins';

	/**
	 * get table with wp prefix
	 * @param string $table
	 * @return string
	 * @throws Exception
	 */
	public static function get_table($table)
	{
		global $wpdb;
		
		$t = '';
		
		switch ($table) {
			case 'grids':
				$t = self::TABLE_GRID;
				break;
			case 'skins':
				$t = self::TABLE_ITEM_SKIN;
				break;
			case 'elements':
				$t = self::TABLE_ITEM_ELEMENTS;
				break;
			case 'nav_skins':
				$t = self::TABLE_NAVIGATION_SKINS;
				break;
			default:
				Essential_Grid_Base::throw_error(esc_attr__('Unknown table name!', ESG_TEXTDOMAIN));
		}
		
		return $wpdb->prefix . $t;
	}
	
	public static function is_table_exists($table)
	{
		global $wpdb;

		return strtolower($wpdb->get_var("SHOW TABLES LIKE '" . $table . "'")) === strtolower($table);
	}
	
	/**
	 * get db version
	 * @return string
	 */
	public static function get_version()
	{
		return get_option(self::OPTION_VERSION, '0.99');
	}
	
	/**
	 * update db version
	 * @param string $new_version
	 * @return void
	 */
	public static function update_version($new_version)
	{
		update_option(self::OPTION_VERSION, $new_version);
	}

	/**
	 * Check if the tables could be properly created, by checking if TABLE_GRID exists AND table version is latest!
	 * @return bool
	 */
	public static function check_table_exist_and_version(){
		if (version_compare(ESG_REVISION, self::get_version(), '>')) return false;
		return self::is_table_exists(self::get_table('grids'));
	}

	/**
	 * Create/Update Database Tables
	 * @return bool
	 */
	public static function create_tables($networkwide = false)
	{
		global $wpdb;

		$created = false;

		if (function_exists('is_multisite') && is_multisite() && $networkwide) { //do for each existing site
			// Get all blog ids and create tables
			$blogids = $wpdb->get_col("SELECT blog_id FROM " . $wpdb->blogs);

			foreach ($blogids as $blog_id) {
				switch_to_blog($blog_id);
				$created = self::_create_tables();
				if ($created === false) {
					return false;
				}
				// 2.2.5
				restore_current_blog();
			}
		} else { //no multisite, do normal installation
			$created = self::_create_tables();
		}

		if ($created === false) {
			return false;
		}
	}

	/**
	 * Create Tables
	 * @return bool
	 */
	public static function _create_tables()
	{
		global $wpdb;

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		$charset_collate = $wpdb->get_charset_collate();
		//Create/Update Grids Database
		$force = isset($_GET['esg_recreate_database']) && wp_verify_nonce($_GET['esg_recreate_database'], 'Essential_Grid_recreate_db');
		$grid_ver = ($force === true) ? '0.99' : self::get_version();
		
		if (version_compare($grid_ver, '1', '<')) {
			$table_name = self::get_table('grids');
			if ($wpdb->get_var("SHOW TABLES LIKE '".$table_name."'") != $table_name) {
				$sql = "CREATE TABLE $table_name (
					id mediumint(6) NOT NULL AUTO_INCREMENT,
					name VARCHAR(191) NOT NULL,
					handle VARCHAR(191) NOT NULL,
					postparams TEXT NOT NULL,
					params TEXT NOT NULL,
					layers TEXT NOT NULL,
					UNIQUE KEY id (id),
					UNIQUE (handle)
					) $charset_collate;";

				dbDelta($sql);
			}

			$table_name = self::get_table('skins');
			if ($wpdb->get_var("SHOW TABLES LIKE '".$table_name."'") != $table_name) {
				$sql = "CREATE TABLE $table_name (
					id mediumint(6) NOT NULL AUTO_INCREMENT,
					name VARCHAR(191) NOT NULL,
					handle VARCHAR(191) NOT NULL,
					params TEXT NOT NULL,
					layers TEXT NOT NULL,
					settings TEXT,
					UNIQUE KEY id (id),
					UNIQUE (name),
					UNIQUE (handle)
					) $charset_collate;";

				dbDelta($sql);
			}

			$table_name = self::get_table('elements');
			if ($wpdb->get_var("SHOW TABLES LIKE '".$table_name."'") != $table_name) {
				$sql = "CREATE TABLE $table_name (
					id mediumint(6) NOT NULL AUTO_INCREMENT,
					name VARCHAR(191) NOT NULL,
					handle VARCHAR(191) NOT NULL,
					settings TEXT NOT NULL,
					UNIQUE KEY id (id),
					UNIQUE (handle)
					) $charset_collate;";

				dbDelta($sql);
			}

			$table_name = self::get_table('nav_skins');
			if ($wpdb->get_var("SHOW TABLES LIKE '".$table_name."'") != $table_name) {
				$sql = "CREATE TABLE $table_name (
					id mediumint(6) NOT NULL AUTO_INCREMENT,
					name VARCHAR(191) NOT NULL,
					handle VARCHAR(191) NOT NULL,
					css TEXT NOT NULL,
					UNIQUE KEY id (id),
					UNIQUE (handle)
					) $charset_collate;";

				dbDelta($sql);
			}

			//check if a table was created, if not return false and return an error
			$table_name = self::get_table('grids');
			if (!self::is_table_exists($table_name)) {
				return false;
			}

			if($force === false) self::update_version('1');
			$grid_ver = '1';
		}

		//Change database on certain release? No Problem, use the following:
		//change layers to MEDIUMTEXT from TEXT so that more layers can be added (fix for limited entries on custom grids)
		if (version_compare($grid_ver, '1.02', '<')) {
			$table_name = self::get_table('grids');
			$sql = "CREATE TABLE $table_name (
				layers MEDIUMTEXT NOT NULL
				) $charset_collate;";

			dbDelta($sql);

			//check if a table was created, if not return false and return an error
			if (!self::is_table_exists($table_name)) {
				return false;
			}

			if($force === false) self::update_version('1.02');
			$grid_ver = '1.02';
		}

		//change more entries to MEDIUMTEXT so that can be stored to prevent loss of data/errors
		if (version_compare($grid_ver, '1.03', '<')) {
			$table_name = self::get_table('skins');
			$sql = "CREATE TABLE $table_name (
				layers MEDIUMTEXT NOT NULL
				) $charset_collate;";

			dbDelta($sql);

			$table_name = self::get_table('nav_skins');
			$sql = "CREATE TABLE $table_name (
				css MEDIUMTEXT NOT NULL
				) $charset_collate;";

			dbDelta($sql);

			$table_name = self::get_table('elements');
			$sql = "CREATE TABLE $table_name (
				settings MEDIUMTEXT NOT NULL
				) $charset_collate;";

			dbDelta($sql);

			//check if a table was created, if not return false and return an error
			$table_name = self::get_table('skins');
			if (!self::is_table_exists($table_name)) {
				return false;
			}

			if($force === false) self::update_version('1.03');
			$grid_ver = '1.03';
		}

		//Add new column settings, as for 2.0 you can add favorite grids
		if (version_compare($grid_ver, '2.1', '<')) {
			$table_name = self::get_table('grids');
			$sql = "CREATE TABLE $table_name (
				settings TEXT NULL
				last_modified DATETIME
				) $charset_collate;";

			dbDelta($sql);

			//check if a table was created, if not return false and return an error
			if (!self::is_table_exists($table_name)) {
				return false;
			}

			if($force === false) self::update_version('2.1');
			$grid_ver = '2.1';
		}

		if (version_compare($grid_ver, '2.2', '<')) {
			$table_name = self::get_table('nav_skins');
			$sql = "CREATE TABLE $table_name (
				navversion VARCHAR(191)
				) $charset_collate;";

			dbDelta($sql);

			//check if a table was created, if not return false and return an error
			if (!self::is_table_exists($table_name)) {
				return false;
			}

			if($force === false) self::update_version('2.2');
			$grid_ver = '2.2';
		}

		do_action('essgrid__create_tables', $grid_ver);

		return true;
	}

}

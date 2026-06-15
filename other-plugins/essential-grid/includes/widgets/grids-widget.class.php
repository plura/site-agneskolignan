<?php
/**
 * @package   Essential_Grid
 * @author    ThemePunch <info@themepunch.com>
 * @link      http://www.themepunch.com/essential/
 * @copyright 2023 ThemePunch
 */

if (!defined('ABSPATH')) exit();

class Essential_Grids_Widget extends WP_Widget
{

	public function __construct()
	{
		// widget actual processes
		$widget_ops = array('classname' => 'widget_ess_grid', 'description' => esc_attr__('Displays certain Essential Grid on the page', ESG_TEXTDOMAIN));
		parent::__construct('ess-grid-widget', esc_attr__('Essential Grid', ESG_TEXTDOMAIN), $widget_ops);
	}

	/**
	 * the form
	 */
	public function form($instance)
	{
		$arrGrids = Essential_Grid::get_grids_short();
		if (empty($arrGrids)) {
			echo esc_attr__("No Essential Grids found, Please create at least one!", ESG_TEXTDOMAIN);
		} else {
			$field = "ess_grid";
			$fieldPages = "ess_grid_pages";
			$fieldCheck = "ess_grid_homepage";
			$fieldTitle = "ess_grid_title";

			$gridID = @$instance[$field];
			$homepage = @$instance[$fieldCheck];
			$pagesValue = @$instance[$fieldPages];
			$title = @$instance[$fieldTitle];

			$fieldID = $this->get_field_id($field);
			$fieldName = $this->get_field_name($field);

			$fieldID_check = $this->get_field_id($fieldCheck);
			$fieldName_check = $this->get_field_name($fieldCheck);
			$checked = "";
			if ($homepage == "on")
				$checked = "checked='checked'";

			$fieldPages_ID = $this->get_field_id($fieldPages);
			$fieldPages_Name = $this->get_field_name($fieldPages);

			$fieldTitle_ID = $this->get_field_id($fieldTitle);
			$fieldTitle_Name = $this->get_field_name($fieldTitle);

			?>
			<div class="div13"></div>
			<label for="<?php echo $fieldTitle_ID; ?>"><?php esc_html_e('Title', ESG_TEXTDOMAIN); ?>:</label>
			<input type="text" name="<?php echo $fieldTitle_Name; ?>" id="<?php echo $fieldTitle_ID; ?>" value="<?php echo $title; ?>" class="widefat">
			<div class="div13"></div>

			<?php esc_html_e('Choose Essential Grid', ESG_TEXTDOMAIN); ?>:
			<select name="<?php echo $fieldName; ?>" id="<?php echo $fieldID; ?>">
				<?php foreach ($arrGrids as $id => $name) { ?>
					<option value="<?php echo $id; ?>"<?php echo ($gridID == $id) ? ' selected="selected"' : ''; ?>><?php echo $name; ?></option>
				<?php } ?>
			</select>

			<div class="div13"></div>

			<label for="<?php echo $fieldID_check; ?>"><?php esc_html_e('Home Page Only', ESG_TEXTDOMAIN); ?>:</label>
			<input type="checkbox" class="esg-widget-checkbox" name="<?php echo $fieldName_check; ?>" id="<?php echo $fieldID_check; ?>" <?php echo $checked; ?> >
			<div class="div13"></div>
			<label for="<?php echo $fieldPages_ID; ?>"><?php esc_html_e('Pages: (example: 3,8,15)', ESG_TEXTDOMAIN); ?></label>
			<input type="text" name="<?php echo $fieldPages_Name; ?>" id="<?php echo $fieldPages_ID; ?>" value="<?php echo $pagesValue; ?>">
			<div class="div13"></div>
			<?php
		}
	}

	/**
	 * update
	 */
	public function update($new_instance, $old_instance)
	{
		return ($new_instance);
	}

	/**
	 * widget output
	 */
	public function widget($args, $instance)
	{
		$grid_id = $instance["ess_grid"];
		$title = apply_filters('widget_title', empty($instance['ess_grid_title']) ? '' : $instance['ess_grid_title'], $instance); //needed for WPML translation

		$homepageCheck = @$instance["ess_grid_homepage"];
		$homepage = "";
		if ($homepageCheck == "on") $homepage = "homepage";

		$pages = $instance["ess_grid_pages"];
		if (!empty($pages)) {
			if (!empty($homepage)) $homepage .= ",";
			$homepage .= $pages;
		}

		if (empty($grid_id)) return (false);

		//widget output
		$beforeWidget = $args["before_widget"];
		$afterWidget = $args["after_widget"];
		$beforeTitle = $args["before_title"];
		$afterTitle = $args["after_title"];

		echo $beforeWidget;

		if (!empty($title)) echo $beforeTitle . $title . $afterTitle;

		$caching = get_option('tp_eg_use_cache', 'false');
		$use_cache = $caching == 'true';

		// Enqueue Scripts
		wp_enqueue_script('tp-tools');
		wp_enqueue_script('esg-essential-grid-script');

		// Enqueue Lightbox Style/Script
		if ($use_cache) {
			wp_enqueue_script('esg-tp-boxext');
		}

		$grid = new Essential_Grid();
		$grid->output_essential_grid($grid_id, $homepage);

		echo $afterWidget;
	}
}

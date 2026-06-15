<?php
/**
 * @package   Essential_Grid
 * @author    ThemePunch <info@themepunch.com>
 * @link      http://www.themepunch.com/essential/
 * @copyright 2023 ThemePunch
 */

if (!defined('ABSPATH')) exit();

/**
 * Adds Filter Widgets
 * @since 1.0.6
 */
class Essential_Grids_Widget_Filter extends WP_Widget
{

	public function __construct()
	{
		// widget actual processes
		$widget_ops = array(
			'classname' => 'widget_ess_grid_filter', 
			'description' => esc_attr__('Display the filter of a certain Grid (Grid Navigation Settings in Navigations tab of the Grid has to be set to Widget)', ESG_TEXTDOMAIN)
		);
		parent::__construct('ess-grid-widget-filter', esc_attr__('Essential Grid Filter', ESG_TEXTDOMAIN), $widget_ops);
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
			$fieldTitle = "ess_grid_title";

			$gridID = @$instance[$field];
			$title = @$instance[$fieldTitle];

			$fieldID = $this->get_field_id($field);
			$fieldName = $this->get_field_name($field);

			$fieldTitle_ID = $this->get_field_id($fieldTitle);
			$fieldTitle_Name = $this->get_field_name($fieldTitle);

			?>
			<label for="<?php echo $fieldTitle_ID; ?>"><?php esc_html_e('Title', ESG_TEXTDOMAIN); ?>:</label>
			<input type="text" name="<?php echo $fieldTitle_Name; ?>" id="<?php echo $fieldTitle_ID; ?>" value="<?php echo $title; ?>" class="widefat">
			<br><br>

			<?php esc_html_e('Choose Essential Grid', ESG_TEXTDOMAIN); ?>:
			<select name="<?php echo $fieldName; ?>" id="<?php echo $fieldID; ?>">
				<?php foreach ($arrGrids as $id => $name) { ?>
					<option value="<?php echo $id; ?>"<?php echo ($gridID == $id) ? ' selected="selected"' : ''; ?>><?php echo $name; ?></option>
				<?php } ?>
			</select>
			<div class="esg-widget-separator"></div>
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
		$title = @$instance["ess_grid_title"];
		if (empty($grid_id))
			return (false);

		$base = new Essential_Grid_Base();
		$grid = new Essential_Grid();

		$grids = $grid->get_grids_short_widgets();
		if (!isset($grids[$grid_id]))
			return false;

		$grid_handle = $grids[$grid_id];

		//widget output
		$beforeWidget = $args["before_widget"];
		$afterWidget = $args["after_widget"];
		$beforeTitle = $args["before_title"];
		$afterTitle = $args["after_title"];

		echo $beforeWidget;

		if (!empty($title))
			echo $beforeTitle . $title . $afterTitle;

		if ($base->is_shortcode_with_handle_exist($grid_handle)) {
			$my_grid = $grid->init_by_id($grid_id);
			if (!$my_grid) return false; //be silent
			$grid->output_grid_filter();
		}
		echo $afterWidget;
	}
}

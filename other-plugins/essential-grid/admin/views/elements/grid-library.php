<?php
/**
 * @package   Essential_Grid
 * @author    ThemePunch <info@themepunch.com>
 * @link      https://www.themepunch.com/essential/
 * @copyright 2023 ThemePunch
 */

if( !defined( 'ABSPATH') ) exit();

$library = new Essential_Grid_Library();
$filters = $library->get_tp_template_filters();
?>
<div id="esg-libary-wrapper">
	<div class="esg-lib-row">
		<div class="esg-lib-col-left">
			<img class="esg-lib-logo" src="<?php echo ESG_PLUGIN_URL; ?>admin/assets/images/logo.png" alt="<?php esc_attr_e('Essential Grid', ESG_TEXTDOMAIN); ?>" />
			<div class="esg-lib-filters-wrapper">
				<ul class="esg-lib-filters">
					<li><a class="esg-lib-selected" href="javascript:void(0)" data-type="temp_all"><?php esc_html_e('All Grids', ESG_TEXTDOMAIN); ?></a></li>
					<?php foreach ($filters as $f) : if ('newupdate' == $f['slug']) continue; ?>
					<li><a href="javascript:void(0)" data-type="temp_<?php echo esc_attr($f['slug']); ?>"><?php esc_html_e($f['name']); ?></a></li>
					<?php endforeach; ?>
					<li><a class="esg-lib-new-updated" href="javascript:void(0)" data-type="temp_newupdate"><?php esc_html_e('New / Updated', ESG_TEXTDOMAIN); ?></a></li>
				</ul>
			</div>
		</div>
		<div class="esg-lib-col-right">
			<div class="esg-lib-toolbar">
				<div class="esg-lib-search-wrapper">
					<div id="esg-lib-search-clear" class="esg-lib-search-clear"><i class="material-icons">close</i></div>
					<input id="esg-lib-search" type="text" placeholder="<?php esc_attr_e('Search Templates ...', ESG_TEXTDOMAIN); ?>" />
				</div>
				<div class="esg-lib-toolbar-actions">
					<div id="esg-close-template" class="esg-close-template"><i class="material-icons">close</i></div>
					<div id="esg-reload-shop" class="esg-btn esg-red esg-reload-shop"><i class="eg-icon-arrows-ccw"></i><?php esc_html_e('Update Library', ESG_TEXTDOMAIN); ?></div>
					<div class="esg-lib-sort">
						<select id="esg-lib-sort" data-theme="autowidth esg-lib-sort">
							<option value="date-desc"><?php esc_html_e('Sort by Creation', ESG_TEXTDOMAIN); ?> &darr;</option>
							<option value="date-asc"><?php esc_html_e('Sort by Creation', ESG_TEXTDOMAIN); ?> &uarr;</option>
							<option value="title-desc"><?php esc_html_e('Sort by Title', ESG_TEXTDOMAIN); ?> &darr;</option>
							<option value="title-asc"><?php esc_html_e('Sort by Title', ESG_TEXTDOMAIN); ?> &uarr;</option>
						</select>
					</div>
					<div id="esg-lib-favorite" class="esg-lib-favorite"><i class="material-icons">star</i><?php esc_html_e('Favorites', ESG_TEXTDOMAIN); ?></div>
					<div class="esg-clearfix"></div>
				</div>
				<div class="esg-clearfix"></div>
			</div>
			<!-- THE GRID BASE TEMPLATES -->
			<div id="esg-library-grids" class="esg-library-groups">
				<!-- TEMPLATES WILL BE ADDED OVER AJAX -->
			</div>
		</div>
	</div>
</div>


<div id="dialog_import_library_grid_from" title="<?php esc_html_e('Import Library Grid', ESG_TEXTDOMAIN); ?>" class="dialog_import_library_grid_from esg-display-none">
	<form action="<?php //echo RevSliderBase::$url_ajax; ?>" enctype="multipart/form-data" method="post" name="esg-import-template-from-server" id="esg-import-template-from-server">
		<input type="hidden" name="action" value="revslider_ajax_action">
		<input type="hidden" name="client_action" value="import_slider_online_template_slidersview">
		<input type="hidden" name="nonce" value="<?php echo wp_create_nonce("Essential_Grid_actions"); ?>">
		<input type="hidden" name="uid" class="esg-uid" value="">
		<input type="hidden" name="page-creation" class="esg-page-creation" value="false">
	</form>
</div>

<div id="dialog_import_library_grid_info" title="<?php esc_html_e('Importing Status', ESG_TEXTDOMAIN); ?>" class="dialog_import_library_grid_info esg-display-none">
	<!-- ADD INFOS HERE ON DEMAND -->
	<div class="esg_logo_rotating">
		<div class="import-spinner">
			<div>
				<span></span>
				<span></span>
				<span></span>
				<span></span>
			</div>
		</div>
	</div>
	<div id="nowinstalling_label"><?php esc_html_e('Now Installing', ESG_TEXTDOMAIN); ?></div>
	<div id="import_grid_title"></div>
</div>

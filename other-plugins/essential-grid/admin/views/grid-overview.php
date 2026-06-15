<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   Essential_Grid
 * @author    ThemePunch <info@themepunch.com>
 * @link      http://www.themepunch.com/essential/
 * @copyright 2023 ThemePunch
 */

if (!defined('ABSPATH')) exit();
?>

<div class="esg-dashboard-wrapper">

<?php
include('elements/notice-table-exists.php');

$library = new Essential_Grid_Library();
$new_templates_counter = $library->get_templates_counter();

$esg_addons = Essential_Grid_Addons::instance();
$new_addon_counter = $esg_addons->get_addons_counter();

$current_user = wp_get_current_user();
$time = date('H');
$hi = esc_html__('Good Evening ', ESG_TEXTDOMAIN);
if ($time < '12') {
	$hi = esc_html__('Good Morning ', ESG_TEXTDOMAIN);
} elseif($time >= '12' && $time < '17') {
	$hi = esc_html__('Good Afternoon ', ESG_TEXTDOMAIN);
}
?>

	<!--WELCOME MSG-->
	<div class="esg-welcome-wrapper">
		<div class="logo"></div>
		<h2 class="title"><?php echo $hi; echo $current_user->display_name; echo '!'; ?></h2>
		<h3 class="subtitle"><?php esc_html_e('You are running Essential Grid ', ESG_TEXTDOMAIN); echo ESG_REVISION; ?></h3>
	</div>

	<!--BIG BUTTONS-->
	<div class="esg-big-buttons-wrapper">
		<a class="esg-bb-empty-grid" href="<?php echo $this->getViewUrl(Essential_Grid_Admin::VIEW_GRID_CREATE, 'create=true'); ?>">
			<i class="material-icons">apps</i><?php esc_html_e('Create Empty Grid', ESG_TEXTDOMAIN); ?>
		</a>
		<a class="esg-bb-template-grid" id="esg-library-open" href="javascript:void(0);">
			<i class="material-icons esg-color-green">photo_library</i><?php esc_html_e('Create Grid from Template', ESG_TEXTDOMAIN); ?>
			<?php if ( $new_templates_counter ) : ?>
				<span id="esg-new-templates-counter" class="esg-new-templates-counter"><?php echo $new_templates_counter; ?></span>
			<?php endif; ?>
		</a>
		<a class="esg-bb-addons" id="esg-addons-open" href="javascript:void(0);">
			<i class="material-icons esg-color-blue">extension</i><?php esc_html_e('AddOns', ESG_TEXTDOMAIN); ?>
			<?php if ( $new_addon_counter ) : ?>
				<span id="esg-new-addons-counter" class="esg-new-addons-counter"><?php echo $new_addon_counter; ?></span>
			<?php endif; ?>
		</a>
		<a class="esg-bb-help" target="_blank" href="https://www.essential-grid.com/help-center">
			<i class="material-icons esg-color-red">help</i><?php esc_html_e('Help Center', ESG_TEXTDOMAIN); ?>
		</a>
	</div>

	<!--GRIDS LIST-->
	<div id="esg_gl" class="esg-grid-list-wrapper">
		
		<div id="esg_gl_header" class="esg-grid-list-header esg-hidden">
			<div class="esg-gl-left">
				<input class="flat_input" id="esg_gl_search" type="text" placeholder="<?php esc_attr_e('Search Grids...', ESG_TEXTDOMAIN);?>"/>
			</div>
			<div class="esg-gl-right">
				<span class="esg-grid-list-header-item">
					<span id="esg_gl_favorite" class="esg-gl-favorite"><i class="material-icons">star</i>Favorites</span>
				</span>
				
				<span class="esg-grid-list-header-item">
					<i class="material-icons esg-grid-list-reset-item" id="esg_gl_sorting_reset">replay</i>
					<select id="esg_gl_sorting" data-theme="autowidth esg-lib-sort">
						<option value="id-desc"><?php esc_html_e('Sort by ID', ESG_TEXTDOMAIN);?></option>
						<option value="id-asc"><?php esc_html_e('ID Ascending', ESG_TEXTDOMAIN);?></option>
						<option value="name-asc"><?php esc_html_e('Sort by Title', ESG_TEXTDOMAIN);?></option>
						<option value="name-desc"><?php esc_html_e('Title Descending', ESG_TEXTDOMAIN);?></option>
					</select>
				</span>

				<span class="esg-grid-list-header-item">
					<i class="material-icons esg-grid-list-reset-item" id="esg_gl_filtering_reset">replay</i>
					<select id="esg_gl_filtering" data-theme="autowidth esg-lib-sort">
						<option value="all"><?php _e('Show all Modules', ESG_TEXTDOMAIN);?></option>
					</select>
				</span>
			</div>
			<div class="esg-clearfix"></div>
		</div>
		
		<div class="div15"></div>
		<div id="esg_gl_list" class="esg-grid-list"><div class="esg-grid-list-overlay"></div></div>
		
		<div id="esg_gl_footer" class="esg-grid-list-footer esg-hidden">
			<div class="esg-gl-right">
				<div class="esg-gl-pagination-wrapper"></div>
				<select id="esg_gl_pagination" data-theme="autowidth esg-lib-sort">
					<option id="page_per_page_0" value="4"></option>
					<option id="page_per_page_1" value="8"></option>
					<option id="page_per_page_2" value="16"></option>
					<option id="page_per_page_3" value="32"></option>
					<option id="page_per_page_4" value="64"></option>
					<option id="page_per_page_5" value="all"><?php _e('Show All', ESG_TEXTDOMAIN);?></option>
				</select>
			</div>
			<div class="esg-clearfix"></div>
		</div>
		
	</div>
	
	<div class="div75"></div>

	<?php include('elements/grid-info.php'); ?>
	
</div>

<script type="text/html" id="tmpl-esg_gl_item">
	<div class="esg-grid-list-item" data-id="{{ data.id }}">
		
		<# if (ESG.F._truefalse(data?.settings?.favorite)) { #>
		<div class="grid-favorite selected"><i class="material-icons">star</i></div>
		<# } else { #>
		<div class="grid-favorite"><i class="material-icons">star_outline</i></div>
		<# } #>
		
		<div class="grid-tags">
			<# if (ESG.F._truefalse(data.params?.pg)) { #>
			<div class="premium"><span>premium</span></div>
			<# } #>
			<# for (let i in data.tags) { #>
			<# if (!ESG.F.hop(data.tags, i)) continue; #>
			<div><span>{{ data.tags[i] }}</span></div>
			<# } #>
		</div>
		
		<div class="grid-hover">
			<a class="link-edit" data-title="{{ data.name }}" data-info="<?php esc_attr_e('Open in Editor', ESG_TEXTDOMAIN); ?>" href="<?php echo Essential_Grid_Base::getViewUrl(Essential_Grid_Admin::VIEW_GRID_CREATE, 'create='); ?>{{ data.id }}"><i class="material-icons">edit</i></a>
		</div>
		
		<div class="esg-grid-list-item-img" style="background-image: url('{{ data.bg }}');"></div>
		<div class="esg-grid-list-item-title">
			<input data-id="{{ data.id }}" class="input-title" value="{{ data.name }}" />
			<i class="show_toolbar material-icons">arrow_drop_down</i>
		</div>
		<div class="esg-gl-toolbar">
			<div class="esg-gl-tool embedgrid"><i class="material-icons">add_to_queue</i><span>Embed</span></div>
			<div class="esg-gl-tool exportgrid"><i class="material-icons">file_download</i><span>Export</span></div>
			<div class="esg-gl-tool renamegrid"><i class="material-icons">title</i><span>Rename</span></div>
			<div class="esg-gl-tool favoritegrid"><i class="material-icons">star_outline</i><span>Favorite</span></div>
			<div class="esg-gl-tool editgridskin" data-href="<?php echo Essential_Grid_Base::getViewUrl(Essential_Grid_Admin::VIEW_ITEM_SKIN_EDITOR, 'create='); ?>{{ data.params['entry-skin'] }}"><i class="material-icons">water_drop</i><span>Edit Skin</span></div>
			<div class="esg-gl-tool duplicategrid"><i class="material-icons">content_copy</i><span>Duplicate</span></div>
			<div class="esg-gl-tool deletegrid"><i class="material-icons">delete</i><span>Delete</span></div>
		</div>
	</div>
</script>

<?php
require_once('elements/grid-library.php');
require_once('elements/grid-addons.php');
Essential_Grid_Dialogs::open_imported_grid();
Essential_Grid_Dialogs::error_import_grid();
?>

<script type="text/javascript">
	window.ESG ??= {};
	ESG.ENV ??= {};
	ESG.ENV.overviewMode = true;
	ESG.ENV.missingAddons = <?php echo json_encode($esg_addons->get_missing_addons()); ?>;
	ESG.ENV.requireUpdateAddons = <?php echo json_encode($esg_addons->get_require_update_addons()); ?>;
	ESG.ENV.newTemplatesCounter = document.getElementById('esg-new-templates-counter');
	ESG.ENV.newTemplatesAmount = <?php echo $new_templates_counter; ?>;
	ESG.ENV.newAddonsCounter = document.getElementById('esg-new-addons-counter');
	ESG.ENV.newAddonsAmount = <?php echo $new_addon_counter; ?>;

	ESG.LIB ??= {};
	ESG.LIB.grids = <?php echo wp_json_encode(Essential_Grid::get_essential_grids(false, false)); ?>;
	
	try {
		jQuery('.mce-notification-error').remove();
		jQuery('#wpbody-content >.notice').remove();
	} catch (e) {
	}

	jQuery(function(){
		AdminEssentials.Addons.init({
			afterInit: function() {
				AdminEssentials.Library.init();
				AdminEssentials.Overview.init();
			}
		});
	});
</script>

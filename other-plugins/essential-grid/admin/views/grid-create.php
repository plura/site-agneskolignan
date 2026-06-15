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

if( !defined( 'ABSPATH') ) exit();

$grid = false;

$base = new Essential_Grid_Base();
$nav_skin = new Essential_Grid_Navigation();
$wa = new Essential_Grid_Widget_Areas();
$meta = new Essential_Grid_Meta();

$isCreate = $base->getGetVar('create', 'true');

$esg_color_picker_presets = ESGColorpicker::get_color_presets();

$title = esc_attr__('Create New Grid', ESG_TEXTDOMAIN);
$save = esc_attr__('Save Grid', ESG_TEXTDOMAIN);

$layers = false;
$settings = array('bg' => '');

if (intval($isCreate) > 0) {
	//currently editing
	$grid = Essential_Grid::get_essential_grid_by_id(intval($isCreate));
	if (!empty($grid)) {
		$title = esc_attr__('Settings', ESG_TEXTDOMAIN);
		$layers = $grid['layers'];
		$settings = !is_null($grid['settings']) ? $grid['settings'] : $settings;
	}
} else {
	$editAlias = $base->getGetVar('alias', false);
	if ($editAlias) {
		$grid = Essential_Grid::get_essential_grid_by_handle($editAlias);
		if (!empty($grid)) {
			$title = esc_attr__('Settings', ESG_TEXTDOMAIN);
			$layers = $grid['layers'];
			$settings = !is_null($grid['settings']) ? $grid['settings'] : $settings;
		}
	}
}

$postTypesWithCats = $base->getPostTypesWithCatsForClient();
$jsonTaxWithCats = $base->jsonEncodeForClientSide($postTypesWithCats);

$base = new Essential_Grid_Base();

$pages = get_pages(array('sort_column' => 'post_name'));

$post_elements = $base->getPostTypesAssoc();

$postTypes = $base->getVar($grid, array('postparams', 'post_types'), 'post');
$categories = $base->setCategoryByPostTypes($postTypes, $postTypesWithCats);

$selected_pages = explode(',', $base->getVar($grid, array('postparams', 'selected_pages'), '-1', 's'));

$columns = $base->getVar($grid, array('params', 'columns'));
$columns = $base->set_basic_colums($columns);

$mascontent_height = $base->getVar($grid, array('params', 'mascontent-height'));
$mascontent_height = $base->set_basic_mascontent_height($mascontent_height);

$columns_width = $base->getVar($grid, array('params', 'columns-width'));
$columns_width = $base->set_basic_colums_width($columns_width);

$columns_height = $base->getVar($grid, array('params', 'columns-height'));
$columns_height = $base->set_basic_colums_height($columns_height);

$columns_advanced = array();
if (!empty($grid)) $columns_advanced = $base->get_advanced_colums($grid['params']);

$nav_skin_choosen = $base->getVar($grid, array('params', 'navigation-skin'), 'minimal-light');
$navigation_skins = $nav_skin->get_essential_navigation_skins();
$navigation_skin_css = $base->jsonEncodeForClientSide($navigation_skins);

$entry_skins = Essential_Grid_Item_Skin::get_essential_item_skins();
$entry_skin_choosen = $base->getVar($grid, array('params', 'entry-skin'), '0');

$grid_animations = $base->get_grid_animations();
$start_animations = $base->get_start_animations();
$grid_item_animations = $base->get_grid_item_animations();
$hover_animations = $base->get_hover_animations();
$grid_animation_choosen = $base->getVar($grid, array('params', 'grid-animation'), 'fade');
$grid_start_animation_choosen = $base->getVar($grid, array('params', 'grid-start-animation'), 'reveal');
$grid_item_animation_choosen = $base->getVar($grid, array('params', 'grid-item-animation'), 'none');
$grid_item_animation_other = $base->getVar($grid, array('params', 'grid-item-animation-other'), 'none');
$hover_animation_choosen = $base->getVar($grid, array('params', 'hover-animation'), 'fade');

if(intval($isCreate) > 0) //currently editing, so default can be empty
	$media_source_order = $base->getVar($grid, array('postparams', 'media-source-order'));
else
	$media_source_order = $base->getVar($grid, array('postparams', 'media-source-order'), array('featured-image'));

$media_source_list = $base->get_media_source_order();

$custom_elements = $base->get_custom_elements_for_javascript();

$all_image_sizes = $base->get_all_image_sizes();

$meta_keys = $meta->get_all_meta_handle();

// INIT POSTER IMAGE SOURCE ORDERS
if (intval($isCreate) > 0) {
	//currently editing, so default can be empty
	$poster_source_order = $base->getVar($grid, array('params', 'poster-source-order'));
	if ($poster_source_order == '') { //since 2.1.0
		$poster_source_order = $base->getVar($grid, array('postparams', 'poster-source-order'));
	}
} else {
	$poster_source_order = $base->getVar($grid, array('postparams', 'poster-source-order'), array('featured-image'));
}

$poster_source_list = $base->get_poster_source_order();
$esg_default_skins = $nav_skin->get_default_navigation_skins();

$esg_addons = Essential_Grid_Addons::instance();
$new_addon_counter = $esg_addons->get_addons_counter();
$grid_addons = array();
if ($grid !== false) {
	$grid_addons = $esg_addons->get_grid_addons_list($grid['id'], $base->getVar($grid, array('params', 'addons'), array()));
}
?>

<!-- LEFT SETTINGS -->
<h2 class="topheader">
	<?php echo $title; ?>
	<span class="topheader-buttons esg-f-right">
		<?php if (!intval($isCreate)) { ?>
		<span class="eg-tooltip-wrap esg-display-inline-block" title="<?php esc_attr_e('Save new grid to get access to addons!', ESG_TEXTDOMAIN); ?>">
		<?php } ?>
		<a class="esg-btn esg-blue esg-btn-addons <?php if (!intval($isCreate)) { echo 'notavailable'; } ?>" id="esg-addons-open" href="javascript:void(0);">
			<i class="material-icons">extension</i><?php esc_html_e('AddOns', ESG_TEXTDOMAIN); ?>
				<?php if ( $new_addon_counter ) : ?>
					<span id="esg-new-addons-counter" class="esg-new-addons-counter"><?php echo $new_addon_counter; ?></span>
				<?php endif; ?>
		</a>
		<?php if (!intval($isCreate)) { ?>
		</span>
		<?php } ?>
		<a target="_blank" class="esg-btn esg-red" href="https://www.essential-grid.com/help-center">
			<i class="material-icons">help</i>
			<?php esc_html_e('Help Center', ESG_TEXTDOMAIN); ?>
		</a>
	</span>
	<div class="esg-clearfix"></div>
</h2>
<div class="eg-pbox esg-box esg-box-min-width">
	<div class="esg-box-inside esg-box-inside-layout">
		
		<!-- MENU -->
		<div id="eg-create-settings-menu">
			<ul>
				<li id="esg-naming-tab" class="selected-esg-setting" data-toshow="eg-create-settings"><i class="eg-icon-cog"></i><p><?php esc_html_e('Naming', ESG_TEXTDOMAIN); ?></p></li>
				<li id="esg-source-tab" class="selected-source-setting" data-toshow="esg-settings-posts-settings"><i class="eg-icon-folder"></i><p><?php esc_html_e('Source', ESG_TEXTDOMAIN); ?></p></li>
				<li id="esg-grid-settings-tab" data-toshow="esg-settings-grid-settings"><i class="eg-icon-menu"></i><p><?php esc_html_e('Grid Settings', ESG_TEXTDOMAIN); ?></p></li>
				<li id="esg-filterandco-tab" data-toshow="esg-settings-filterandco-settings"><i class="eg-icon-shuffle"></i><p><?php esc_html_e('Nav-Filter-Sort', ESG_TEXTDOMAIN); ?></p></li>
				<li id="esg-skins-tab" data-toshow="esg-settings-skins-settings"><i class="eg-icon-droplet"></i><p><?php esc_html_e('Skins', ESG_TEXTDOMAIN); ?></p></li>
				<li data-toshow="esg-settings-animations-settings"><i class="eg-icon-tools"></i><p><?php esc_html_e('Animations', ESG_TEXTDOMAIN); ?></p></li>
				<li data-toshow="esg-settings-lightbox-settings"><i class="eg-icon-search"></i><p><?php esc_html_e('Lightbox', ESG_TEXTDOMAIN); ?></p></li>
				<li data-toshow="esg-settings-ajax-settings"><i class="eg-icon-ccw-1"></i><p><?php esc_html_e('Ajax', ESG_TEXTDOMAIN); ?></p></li>
				<li data-toshow="esg-settings-spinner-settings"><i class="eg-icon-back-in-time"></i><p><?php esc_html_e('Spinner', ESG_TEXTDOMAIN); ?></p></li>
				<li data-toshow="esg-settings-api-settings"><i class="eg-icon-magic"></i><p><?php esc_html_e('API/JavaScript', ESG_TEXTDOMAIN); ?></p></li>
				<li data-toshow="esg-settings-cookie-settings"><i class="eg-icon-eye"></i><p><?php esc_html_e('Cookies', ESG_TEXTDOMAIN); ?></p></li>
				<?php echo apply_filters('essgrid_grid_create_menu', '', $grid); ?>
			</ul>
		 </div>

		<!-- NAMING -->
		<div id="eg-create-settings" class="esg-settings-container active-esc">
			<div>
				<div class="eg-cs-tbc-left">
					<esg-llabel><span><?php esc_html_e('Naming', ESG_TEXTDOMAIN); ?></span></esg-llabel>
				</div>
				<div class="eg-cs-tbc">
					<input type="hidden" name="eg-settings" value="<?php echo htmlspecialchars(json_encode($settings)); ?>" />
					
					<?php $pg = 'false';?>
					<?php if ($grid !== false) { 
						$pg = $base->getVar($grid, array('params', 'pg'), 'false');
						?>
						<input type="hidden" name="eg-id" value="<?php echo $grid['id']; ?>" />
					<?php } ?>
					
					<input type="hidden" name="eg-pg" value="<?php echo $pg; ?>" />
					<?php if ($pg === 'true') { ?>
						<div class="esg-pg-wrap">
							<div class="esg-pg-title"><?php esc_html_e('PREMIUM TEMPLATE', ESG_TEXTDOMAIN); ?></div>
							<div class="esg-pg-content">
								<?php 
								echo sprintf(
									/* translators: link to ESG templates library */
									esc_html__( 'This is a Premium template from the Essential Grid %s.', ESG_TEXTDOMAIN ),
									'<a target="_blank" rel="noopener" href="https://www.essential-grid.com/grids/">'
									. esc_html__('template library', ESG_TEXTDOMAIN)
									. '</a>'
								); 
								
								echo sprintf(
									/* translators: link to ESG manual */
									esc_html__( 'It can only be used on this website with a %s.', ESG_TEXTDOMAIN ),
									'<a target="_blank" rel="noopener" href="https://www.essential-grid.com/manual/installing-activating-and-registering-essential-grid/">'
									. esc_html__('registered license key', ESG_TEXTDOMAIN)
									. '</a>'
								); 
								?>
							</div>
						</div>
					<?php } ?>
					
					<div><label for="name" class="eg-tooltip-wrap" title="<?php esc_attr_e('Name of the grid', ESG_TEXTDOMAIN); ?>"><?php esc_html_e('Title', ESG_TEXTDOMAIN); ?></label> <input type="text" name="name" value="<?php echo $base->getVar($grid, 'name', '', 's'); ?>" /> *</div>
					<div class="div13"></div>
					<div><label for="handle" class="eg-tooltip-wrap" title="<?php esc_attr_e('Technical alias without special chars and white spaces', ESG_TEXTDOMAIN); ?>"><?php esc_html_e('Alias', ESG_TEXTDOMAIN); ?></label> <input type="text" name="handle" value="<?php echo $base->getVar($grid, 'handle', '', 's'); ?>" /> *</div>
					<div class="div13"></div>
					<div><label for="shortcode" class="eg-tooltip-wrap" title="<?php esc_attr_e('Copy this shortcode to paste it to your pages or posts content', ESG_TEXTDOMAIN); ?>" ><?php esc_html_e('Shortcode', ESG_TEXTDOMAIN); ?></label> <input type="text" name="shortcode" value="" readonly="readonly" /></div>
					<div class="div13"></div>
					<div><label for="id" class="eg-tooltip-wrap" title="<?php esc_attr_e('Add a unique ID to be able to add CSS to certain Grids', ESG_TEXTDOMAIN); ?>"><?php esc_html_e('CSS ID', ESG_TEXTDOMAIN); ?></label> <input type="text" name="css-id" id="esg-id-value" value="<?php echo $base->getVar($grid, array('params', 'css-id'), '', 's'); ?>" /></div>
				</div>
			</div>
		</div>

		<!-- SOURCE -->
		<div id="esg-settings-posts-settings" class="esg-settings-container">
			<div>
				<form id="eg-form-create-posts">
					<div id="esg-source-type-wrap">
						<div class="eg-cs-tbc-left"><esg-llabel><span><?php esc_html_e('Source', ESG_TEXTDOMAIN); ?></span></esg-llabel></div>
						<div class="eg-cs-tbc ">
							<label for="shortcode" class="eg-tooltip-wrap" title="<?php esc_attr_e('Choose source of grid items', ESG_TEXTDOMAIN); ?>"><?php esc_html_e('Based on', ESG_TEXTDOMAIN); ?></label><!--
							--><div class="esg-staytog"><input type="radio" name="source-type" value="post" class="esg-source-choose-wrapper" <?php checked($base->getVar($grid, array('postparams', 'source-type'), 'post'), 'post'); ?>><span class="eg-tooltip-wrap" title="<?php esc_attr_e('Items from Posts, Custom Posts', ESG_TEXTDOMAIN); ?>"><?php esc_html_e('Post, Pages, Custom Posts', ESG_TEXTDOMAIN); ?></span><div class="space18"></div></div><!--
							--><div class="esg-staytog"><input type="radio" name="source-type" value="custom" class="esg-source-choose-wrapper" <?php echo checked($base->getVar($grid, array('postparams', 'source-type'), 'post'), 'custom'); ?> ><span class="eg-tooltip-wrap" title="<?php esc_attr_e('Items from the Media Gallery (Bulk Selection, Upload Possible)', ESG_TEXTDOMAIN); ?>"><?php esc_html_e('Custom Grid (Editor Below)', ESG_TEXTDOMAIN); ?></span><div class="space18"></div></div>
							<?php do_action('essgrid_grid_source', $base, $grid); ?>
						</div>
					</div>

					<div id="custom-sorting-wrap" class="esg-display-none">
						<ul id="esg-custom-li-sorter" class="esg-margin-0">
						</ul>
					</div>
					<div id="post-pages-wrap">
						<div>
							<div class="eg-cs-tbc-left"><esg-llabel><span><?php esc_html_e('Type and Category', ESG_TEXTDOMAIN); ?></span></esg-llabel></div>
							<div class="eg-cs-tbc">
								<label for="post_types" class="eg-tooltip-wrap" title="<?php esc_attr_e('Select Post Types (multiple selection possible)', ESG_TEXTDOMAIN); ?>"><?php esc_html_e('Post Types', ESG_TEXTDOMAIN); ?></label><!--
								--><select name="post_types" size="5" multiple="multiple">
									<?php
									$selectedPostTypes = array();
									$post_types = $base->getVar($grid, array('postparams', 'post_types'), 'post');
									if(!empty($post_types))
										$selectedPostTypes = explode(',',$post_types);
									else
										$selectedPostTypes = array('post');

									if(!empty($post_elements)){
										// 3.0.12
										$foundOne = false;
										foreach($post_elements as $handle => $name){
											if(!$foundOne && in_array($handle, $selectedPostTypes)) {
												$foundOne = true;
											}
										}
										$postTypeCount = 0;
										foreach($post_elements as $handle => $name){
											if($postTypeCount === 0 && !$foundOne) {
												$selected = ' selected';
											} else {
												$selected = in_array($handle, $selectedPostTypes) ? ' selected' : '';
											}
											?>
											<option value="<?php echo $handle; ?>"<?php echo $selected; ?>><?php echo $name; ?></option>
											<?php
											$postTypeCount++;
										}
									}
									?>
								</select>

								<div id="eg-post-cat-wrap">
									<div class="div13"></div>
									<label for="post_category" class="eg-tooltip-wrap" title="<?php esc_attr_e('Select Categories and Tags (multiple selection possible)', ESG_TEXTDOMAIN); ?>"><?php esc_html_e('Post Categories', ESG_TEXTDOMAIN); ?></label><!--
									--><select id="post_category" name="post_category" size="7" multiple="multiple" >
										<?php
										$selectedCats = array();
										$post_cats = $base->getVar($grid, array('postparams', 'post_category'));
										if(!empty($post_cats))
											$selectedCats = explode(',',$post_cats);
										else
											$selectedCats = array();
										
										foreach ($categories as $handle => $cat) {
											$isDisabled = strpos($handle, 'option_disabled_') !== false;
											if(!$isDisabled) {
												$selected = in_array($handle, $selectedCats) ? ' data-selected="true"' : '';
											} else {
												$selected = '';
											}
											?>
											<option value="<?php echo $handle; ?>"<?php echo $selected; ?><?php echo $isDisabled ? ' disabled="disabled"' : ''; ?>><?php echo $cat; ?></option>
											<?php
										}
										?>
									</select>

									<div class="div15"></div>
									<label>&nbsp;</label>
									<a class="esg-btn esg-purple eg-clear-taxonomies" href="javascript:void(0);"><?php esc_html_e('Clear', ESG_TEXTDOMAIN); ?></a>
									<a class="esg-btn esg-purple eg-categories-taxonomies" href="javascript:void(0);"><?php esc_html_e('All Categories', ESG_TEXTDOMAIN); ?></a>
									<a class="esg-btn esg-purple eg-tags-taxonomies" href="javascript:void(0);"><?php esc_html_e('All Tags', ESG_TEXTDOMAIN); ?></a>
									<a class="esg-btn esg-purple eg-all-taxonomies" href="javascript:void(0);"><?php esc_html_e('All Items', ESG_TEXTDOMAIN); ?></a>
									
									<div class="div5"></div>
									<label for="category-relation"><?php esc_html_e('Category Relation', ESG_TEXTDOMAIN); ?></label><!--
								--><span class="esg-display-inline-block"><input type="radio" value="OR" name="category-relation" <?php checked($base->getVar($grid, array('postparams', 'category-relation'), 'OR'), 'OR'); ?> ><span class="eg-tooltip-wrap" title="<?php esc_attr_e('Post need to be in one of the selected categories/tags', ESG_TEXTDOMAIN); ?>"><?php esc_html_e('OR', ESG_TEXTDOMAIN); ?></span></span><div class="space18"></div><!--
								--><span><input type="radio" value="AND" name="category-relation" <?php checked($base->getVar($grid, array('postparams', 'category-relation'), 'OR'), 'AND'); ?>><span class="eg-tooltip-wrap" title="<?php esc_attr_e('Post need to be in all categories/tags selected', ESG_TEXTDOMAIN); ?>"><?php esc_html_e('AND', ESG_TEXTDOMAIN); ?></span></span>
									
								</div>
								
								<div id="eg-additional-post">
									<div class="div13"></div>
									<label for="additional-query" class="eg-tooltip-wrap" title="<?php esc_attr_e('Please use it like \'year=2012&monthnum=12\'', ESG_TEXTDOMAIN); ?>"><?php esc_html_e('Additional Parameters', ESG_TEXTDOMAIN); ?></label><!--
									--><input type="text" name="additional-query" class="eg-additional-parameters esg-w-305" value="<?php echo $base->getVar($grid, array('postparams', 'additional-query')); ?>" />
									<div><label></label><?php esc_html_e('Please use it like \'year=2012&monthnum=12\' or \'post__in=array(1,2,5)&post__not_in=array(25,10)\'', ESG_TEXTDOMAIN); ?>&nbsp;-&nbsp;
									<?php _e('For a full list of parameters, please visit <a href="https://codex.wordpress.org/Class_Reference/WP_Query" target="_blank">this</a> link', ESG_TEXTDOMAIN); ?></div>
								</div>
							</div>
						</div>
					</div>

					<div id="set-pages-wrap">
						<div>
							<div class="eg-cs-tbc-left">
								<esg-llabel><span><?php esc_html_e('Pages', ESG_TEXTDOMAIN); ?></span></esg-llabel>
							</div>
							<div class="eg-cs-tbc">
								<label for="pages" class="eg-tooltip-wrap" title="<?php esc_attr_e('Additional filtering on pages,Start to type a page title for pre selection', ESG_TEXTDOMAIN); ?>"><?php esc_html_e('Select Pages', ESG_TEXTDOMAIN); ?></label><!--
								--><input type="text" id="pages" value="" name="search_pages"> <a class="esg-btn esg-purple" id="button-add-pages" href="javascript:void(0);"><i class="material-icons">add</i></a>
								<div id="pages-wrap">
									<?php
									if(!empty($pages)){
										foreach($pages as $page){
											if(in_array($page->ID, $selected_pages)){
												?>
												<div class="esg-page-list-element-wrap"><div class="esg-page-list-element" data-id="<?php echo $page->ID; ?>"><?php echo str_replace('"', '', $page->post_title).' (ID: '.$page->ID.')'; ?></div><div class="esg-btn esg-red del-page-entry"><i class="eg-icon-trash"></i></div></div>
												<?php
											}
										}
									}
									?>
								</div>
								<select name="selected_pages" multiple="multiple" class="esg-display-none">
									<?php
									if (!empty($pages)) {
										foreach ($pages as $page) { ?>
											<option value="<?php echo $page->ID; ?>"<?php echo (in_array($page->ID, $selected_pages)) ? ' selected' : ''; ?>><?php echo str_replace('"', '', $page->post_title).' (ID: '.$page->ID.')'; ?></option>
										<?php
										}
									}
									?>
								</select>
							</div>
						</div>

					</div>

					<div id="aditional-pages-wrap">
						<div>
							<div class="eg-cs-tbc-left">
								<esg-llabel><span><?php esc_html_e('Options', ESG_TEXTDOMAIN); ?></span></esg-llabel>
							</div>
							<div class="eg-cs-tbc">
								<?php
								$max_entries = intval($base->getVar($grid, array('postparams', 'max_entries'), '-1'));
								?>
								<label for="pages" class="eg-tooltip-wrap" title="<?php esc_attr_e('Defines a posts limit, use only numbers, -1 will disable this option, use only numbers', ESG_TEXTDOMAIN); ?>"><?php esc_html_e('Maximum Posts', ESG_TEXTDOMAIN); ?></label><!--
								--><input type="number" value="<?php echo $max_entries; ?>" name="max_entries">
								<div class="div13"></div>
								<?php
								$max_entries_preview = intval($base->getVar($grid, array('postparams', 'max_entries_preview'), '20'));
								?>

								<label for="pages" class="eg-tooltip-wrap" title="<?php esc_attr_e('Defines a posts limit, use only numbers, -1 will disable this option, use only numbers', ESG_TEXTDOMAIN); ?>"><?php esc_html_e('Maximum Posts Preview', ESG_TEXTDOMAIN); ?></label><!--
								--><input type="number" value="<?php echo $max_entries_preview; ?>" name="max_entries_preview">

							</div>
						</div>

					</div>

					<div id="all-stream-wrap">
						<div id="external-stream-wrap">
							<div>
								<div class="eg-cs-tbc-left"><esg-llabel><span><?php esc_html_e('Service', ESG_TEXTDOMAIN); ?></span></esg-llabel></div>
								<div class="eg-cs-tbc esg-stream-source-type-container">
									<label for="shortcode" class="eg-tooltip-wrap" title="<?php esc_attr_e('Choose source of grid items', ESG_TEXTDOMAIN); ?>"><?php esc_html_e('Provider', ESG_TEXTDOMAIN); ?></label>
								</div>
							</div>
						</div>
					</div>
					<?php do_action('essgrid_grid_source_options',$base,$grid); ?>
					
					<div id="media-source-order-wrap">
						<div>
							<div class="eg-cs-tbc-left"><esg-llabel><span><?php esc_html_e('Media Source', ESG_TEXTDOMAIN); ?></span></esg-llabel></div>
							<div class="eg-cs-tbc">
								<div class="esg-msow-inner">
									<div class="esg-msow-inner-container">
										<div class="eg-tooltip-wrap" title="<?php esc_attr_e('Set default order of used media', ESG_TEXTDOMAIN); ?>"><?php esc_html_e('Item Media Source Order', ESG_TEXTDOMAIN); ?></div>
										<div id="imso-list" class="eg-media-source-order-wrap eg-media-source-order-wrap-additional">
											<?php
											if(!empty($media_source_order)){
												foreach($media_source_order as $media_handle){
													if(!isset($media_source_list[$media_handle])) continue;
													?>
													<div id="imso-<?php echo $media_handle; ?>" class="eg-media-source-order esg-blue esg-btn"><i class="eg-icon-<?php echo $media_source_list[$media_handle]['type']; ?>"></i><span><?php echo $media_source_list[$media_handle]['name']; ?></span><input class="eg-get-val" type="checkbox" name="media-source-order[]" checked="checked" value="<?php echo $media_handle; ?>" /></div>
													<?php
													unset($media_source_list[$media_handle]);
												}
											}
	
											if(!empty($media_source_list)){
												foreach($media_source_list as $media_handle => $media_set){
													?>
													<div id="imso-<?php echo $media_handle; ?>" class="eg-media-source-order esg-purple esg-btn"><i class="eg-icon-<?php echo $media_set['type']; ?>"></i><span><?php echo $media_set['name']; ?></span><input class="eg-get-val" type="checkbox" name="media-source-order[]" value="<?php echo $media_handle; ?>" /></div>
													<?php
												}
											}
											?>
										</div>
									</div>
									<div id="poster-media-source-container" class="eg-poster-media-source-container">
										<div class="eg-tooltip-wrap" title="<?php esc_attr_e('Set the default order of Poster Image Source', ESG_TEXTDOMAIN); ?>"><?php esc_html_e('Optional Audio/Video Image Order', ESG_TEXTDOMAIN); ?></div>
										<div id="pso-list" class="eg-media-source-order-wrap eg-media-source-order-wrap-additional">
											<?php
											if(!empty($poster_source_order)){
												foreach($poster_source_order as $poster_handle){
													if(!isset($poster_source_list[$poster_handle])) continue;
													?>
													<div id="pso-<?php echo $poster_handle; ?>" class="eg-media-source-order esg-purple esg-btn"><i class="eg-icon-<?php echo $poster_source_list[$poster_handle]['type']; ?>"></i><span><?php echo $poster_source_list[$poster_handle]['name']; ?></span><input class="eg-get-val" type="checkbox" name="poster-source-order[]" checked="checked" value="<?php echo $poster_handle; ?>" /></div>
													<?php
													unset($poster_source_list[$poster_handle]);
												}
											}
	
											if(!empty($poster_source_list)){
												foreach($poster_source_list as $poster_handle => $poster_set){
													?>
													<div id="pso-<?php echo $poster_handle; ?>" class="eg-media-source-order esg-purple esg-btn"><i class="eg-icon-<?php echo $poster_set['type']; ?>"></i><span><?php echo $poster_set['name']; ?></span><input class="eg-get-val" type="checkbox" name="poster-source-order[]" value="<?php echo $poster_handle; ?>" /></div>
													<?php
												}
											}
											?>
										</div>
									</div>
									<div><?php esc_html_e('First Media Source will be loaded as default. In case one source does not exist, next available media source in this order will be used', ESG_TEXTDOMAIN); ?></div>
								</div>
							</div>
						</div>
					</div>

				<div id="media-source-sizes">
					<div>
						<div class="eg-cs-tbc-left">
							<esg-llabel><span><?php esc_html_e('Source Size', ESG_TEXTDOMAIN); ?></span></esg-llabel>
						</div>
						<div class="eg-cs-tbc eg-cs-tbc-padding-top">
							
							<?php $image_source_smart = $base->getVar($grid, array('postparams', 'image-source-smart'), 'off');?>
							<label for="image-source-smart" class="eg-tooltip-wrap" title="<?php esc_attr_e('Grid will try to detect user device and use optimized image sizes', ESG_TEXTDOMAIN); ?>"><?php esc_html_e('Enable Smart Image Size', ESG_TEXTDOMAIN); ?></label><!--
							--><span><input type="radio" name="image-source-smart" value="on" <?php checked($image_source_smart, 'on'); ?> /><?php esc_html_e('On', ESG_TEXTDOMAIN); ?></span><div class="space18"></div><!--
							--><span><input type="radio" name="image-source-smart" value="off" <?php checked($image_source_smart, 'off'); ?> /><?php esc_html_e('Off', ESG_TEXTDOMAIN); ?></span>
							<div class="div13"></div>

							<div>
								<!-- DEFAULT IMAGE SOURCE -->
								<label class="eg-tooltip-wrap" title="<?php esc_attr_e('Desktop Grid Image Source Size', ESG_TEXTDOMAIN); ?>"><?php esc_html_e('Desktop Image Source Type', ESG_TEXTDOMAIN); ?></label><!--
								--><?php $image_source_type = $base->getVar($grid, array('postparams', 'image-source-type'), 'full');?><select name="image-source-type">
									<?php
									foreach($all_image_sizes as $handle => $name){
										?>
										<option <?php selected($image_source_type, $handle); ?> value="<?php echo $handle; ?>"><?php echo $name; ?></option>
										<?php
									}
									?>
								</select>
							</div>
							<div class="div13"></div>

							<!-- DEFAULT IMAGE SOURCE -->
							<label class="eg-tooltip-wrap" title="<?php esc_attr_e('Mobile Grid Image Source Size', ESG_TEXTDOMAIN); ?>"><?php esc_html_e('Mobile Image Source Type', ESG_TEXTDOMAIN); ?></label><!--
							--><?php $image_source_type = $base->getVar($grid, array('postparams', 'image-source-type-mobile'), $image_source_type);?><select name="image-source-type-mobile">
								<?php
								foreach($all_image_sizes as $handle => $name){
									?>
									<option <?php selected($image_source_type, $handle); ?> value="<?php echo $handle; ?>"><?php echo $name; ?></option>
									<?php
								}
								?>
							</select>

						</div>

					</div>
				</div>
				
				<div id="media-source-default-templates">
					<div>
						<div class="eg-cs-tbc-left"><esg-llabel><span><?php esc_html_e('Default Source', ESG_TEXTDOMAIN); ?></span></esg-llabel></div>
						<?php
						$default_img = $base->getVar($grid, array('postparams', 'default-image'), 0, 'i');
						$var_src = '';
						if($default_img > 0){
							$img = wp_get_attachment_image_src($default_img, 'full');
							if($img !== false){
								$var_src = $img[0];
							}
						}
						?>
						<div class="eg-cs-tbc">
							<label class="eg-tooltip-wrap" title="<?php esc_attr_e('Image will be used if no criteria are matching so a default image will be shown', ESG_TEXTDOMAIN); ?>"><?php esc_html_e('Default Image', ESG_TEXTDOMAIN); ?></label><!--
							--><div class="esg-btn esg-purple eg-default-image-add" data-setto="eg-default-image"><?php esc_html_e('Choose Image', ESG_TEXTDOMAIN); ?></div><!--
							--><div class="esg-btn  esg-red  eg-default-image-clear" data-setto="eg-default-image"><?php esc_html_e('Remove Image', ESG_TEXTDOMAIN); ?></div><!--
							--><input type="hidden" name="default-image" value="<?php echo !empty($default_img) ? $default_img : ""; ?>" id="eg-default-image" /><!--
							--><div class="eg-default-image-container"><img id="eg-default-image-img" class="image-holder-wrap-div<?php echo ($var_src == '') ? ' esg-display-none' : ''; ?>" src="<?php echo $var_src; ?>"  alt=""/></div>
						</div>
					</div>
				</div>

				<div class=" default-posters notavailable" id="eg-youtube-default-poster">
					<div class="eg-cs-tbc-left"><esg-llabel><span><?php esc_html_e('YouTube Poster', ESG_TEXTDOMAIN); ?></span></esg-llabel></div>
					<div class="eg-cs-tbc">
						<?php
						$youtube_default_img = $base->getVar($grid, array('postparams', 'youtube-default-image'), 0, 'i');
						$var_src = '';
						if($youtube_default_img > 0){
							$youtube_img = wp_get_attachment_image_src($youtube_default_img, 'full');
							if($youtube_img !== false){
								$var_src = $youtube_img[0];
							}
						}
						?>
						<label class="eg-tooltip-wrap" title="<?php esc_attr_e('Set the default posters for the different video sources', ESG_TEXTDOMAIN); ?>"><?php esc_html_e('Default Poster', ESG_TEXTDOMAIN); ?></label><!--
						--><div class="esg-btn esg-purple eg-youtube-default-image-add" data-setto="eg-youtube-default-image"><?php esc_html_e('Choose Image', ESG_TEXTDOMAIN); ?></div><!--
						--><div class="esg-btn esg-red eg-youtube-default-image-clear" data-setto="eg-youtube-default-image"><?php esc_html_e('Remove Image', ESG_TEXTDOMAIN); ?></div>
						<input type="hidden" name="youtube-default-image" value="<?php echo !empty($youtube_default_img) ? $youtube_default_img : '' ; ?>" id="eg-youtube-default-image" /><!--
						--><div class="eg-default-image-container"><img id="eg-youtube-default-image-img" class="image-holder-wrap-div<?php echo ($var_src == '') ? ' esg-display-none' : ''; ?>" src="<?php echo $var_src; ?>"  alt=""/></div>
					</div>
				</div>

				<div class=" default-posters notavailable" id="eg-vimeo-default-poster">
					<div class="eg-cs-tbc-left"><esg-llabel><span><?php esc_html_e('Vimeo Poster', ESG_TEXTDOMAIN); ?></span></esg-llabel></div>
					<div class="eg-cs-tbc">
						<?php
						$vimeo_default_img = $base->getVar($grid, array('postparams', 'vimeo-default-image'), 0, 'i');
						$var_src = '';
						if($vimeo_default_img > 0){
							$vimeo_img = wp_get_attachment_image_src($vimeo_default_img, 'full');
							if($vimeo_img !== false){
								$var_src = $vimeo_img[0];
							}
						}
						?>
						<label class="eg-tooltip-wrap" title="<?php esc_attr_e('Set the default posters for the different video sources', ESG_TEXTDOMAIN); ?>"><?php esc_html_e('Default Poster', ESG_TEXTDOMAIN); ?></label><!--
						--><div class="esg-btn esg-purple eg-vimeo-default-image-add"  data-setto="eg-vimeo-default-image"><?php esc_html_e('Choose Image', ESG_TEXTDOMAIN); ?></div><!--
						--><div class="esg-btn esg-red eg-vimeo-default-image-clear"  data-setto="eg-vimeo-default-image"><?php esc_html_e('Remove Image', ESG_TEXTDOMAIN); ?></div>
						<input type="hidden" name="vimeo-default-image" value="<?php echo !empty($vimeo_default_img) ? $vimeo_default_img : ''; ?>" id="eg-vimeo-default-image" /><!--
						--><div class="eg-default-image-container"><img id="eg-vimeo-default-image-img" class="image-holder-wrap-div<?php echo ($var_src == '') ? ' esg-display-none' : ''; ?>" src="<?php echo $var_src; ?>"  alt=""/></div>
					</div>
				</div>

				<div class=" default-posters notavailable" id="eg-html5-default-poster">

					<div class="eg-cs-tbc-left"><esg-llabel><span><?php esc_html_e('HTML5 Poster', ESG_TEXTDOMAIN); ?></span></esg-llabel></div>
					<div class="eg-cs-tbc">
						<?php
						$html_default_img = $base->getVar($grid, array('postparams', 'html-default-image'), 0, 'i');
						$var_src = '';
						if($html_default_img > 0){
							$html_img = wp_get_attachment_image_src($html_default_img, 'full');
							if($html_img !== false){
								$var_src = $html_img[0];
							}
						}
						?>
						<label class="eg-tooltip-wrap" title="<?php esc_attr_e('Set the default posters for the different video sources', ESG_TEXTDOMAIN); ?>"><?php esc_html_e('Default Poster', ESG_TEXTDOMAIN); ?></label><!--
						--><div class="esg-btn esg-purple eg-html-default-image-add"  data-setto="eg-html-default-image"><?php esc_html_e('Choose Image', ESG_TEXTDOMAIN); ?></div><!--
						--><div class="esg-btn esg-red eg-html-default-image-clear"  data-setto="eg-html-default-image"><?php esc_html_e('Remove Image', ESG_TEXTDOMAIN); ?></div>
						<input type="hidden" name="html-default-image" value="<?php echo !empty($html_default_img) ? $html_default_img : ''; ?>" id="eg-html-default-image" /><!--
						--><div class="eg-default-image-container"><img id="eg-html-default-image-img" class="image-holder-wrap-div<?php echo ($var_src == '') ? ' esg-display-none' : ''; ?>" src="<?php echo $var_src; ?>"  alt=""/></div>
					</div>
				</div>
				<div id="gallery-wrap"></div>

				<?php echo apply_filters('essgrid_grid_form_create_posts', '', $grid); ?>
					
				</form>
			</div>
		</div>
		<?php
			require_once('elements/grid-settings.php');
		?>


		<div id="custom-element-add-elements-wrapper">
			<div>
				<div class="eg-cs-tbc-left">
					<esg-llabel><span><?php esc_html_e('Add Items', ESG_TEXTDOMAIN); ?></span></esg-llabel>
				</div>
				<div class="eg-cs-tbc">
					<label class="eg-tooltip-wrap" title="<?php esc_attr_e('Add element to Custom Grid', ESG_TEXTDOMAIN); ?>"><?php esc_html_e('Add', ESG_TEXTDOMAIN); ?></label><!--
					--><div class="esg-btn esg-purple esg-open-edit-dialog" id="esg-add-new-custom-youtube-top"><i class="eg-icon-youtube-squared"></i><?php esc_html_e('You Tube', ESG_TEXTDOMAIN); ?></div><!--
					--><div class="esg-btn esg-purple esg-open-edit-dialog" id="esg-add-new-custom-vimeo-top"><i class="eg-icon-vimeo-squared"></i><?php esc_html_e('Vimeo', ESG_TEXTDOMAIN); ?></div><!--
					--><div class="esg-btn esg-purple esg-open-edit-dialog" id="esg-add-new-custom-html5-top"><i class="eg-icon-video"></i><?php esc_html_e('Self Hosted Media', ESG_TEXTDOMAIN); ?></div><!--
					--><div class="esg-btn esg-purple esg-open-edit-dialog" id="esg-add-new-custom-image-top"><i class="eg-icon-picture-1"></i><?php esc_html_e('Image(s)', ESG_TEXTDOMAIN); ?></div><!--
					--><div class="esg-btn esg-purple esg-open-edit-dialog" id="esg-add-new-custom-soundcloud-top"><i class="eg-icon-soundcloud"></i><?php esc_html_e('Sound Cloud', ESG_TEXTDOMAIN); ?></div><!--
					--><div class="esg-btn esg-purple esg-open-edit-dialog" id="esg-add-new-custom-text-top"><i class="eg-icon-font"></i><?php esc_html_e('Simple Content', ESG_TEXTDOMAIN); ?></div><!--
					--><div class="esg-btn esg-purple esg-open-edit-dialog" id="esg-add-new-custom-blank-top"><i class="eg-icon-cancel"></i><?php esc_html_e('Blank Item', ESG_TEXTDOMAIN); ?></div>
				</div>
			</div>

		</div>

		<div class="save-wrap-settings">
			<div class="sws-toolbar-button"><a class="esg-btn esg-green" href="javascript:void(0);" id="eg-btn-save-grid"><i class="rs-icon-save-light"></i><?php echo $save; ?></a></div>
			<div class="sws-toolbar-button"><a class="esg-btn esg-purple esg-refresh-preview-button"><i class="eg-icon-arrows-ccw"></i><?php esc_html_e('Refresh Preview', ESG_TEXTDOMAIN); ?></a></div>
			<div class="sws-toolbar-button"><a class="esg-btn esg-blue" href="<?php echo Essential_Grid_Base::getViewUrl(Essential_Grid_Admin::VIEW_OVERVIEW); ?>"><i class="eg-icon-cancel"></i><?php esc_html_e('Close', ESG_TEXTDOMAIN); ?></a></div>
			<div class="sws-toolbar-button"><?php if($grid !== false){ ?> <a class="esg-btn esg-red" href="javascript:void(0);" id="eg-btn-delete-grid"><i class="eg-icon-trash"></i><?php esc_html_e('Delete Grid', ESG_TEXTDOMAIN); ?></a><?php } ?></div>
		</div>
		<script>
			jQuery('document').ready(function() {
				punchgs.TweenLite.fromTo(jQuery('.save-wrap-settings'),1,{autoAlpha:0,x:50},{autoAlpha:1,x:0,ease:punchgs.Power3.easeInOut,delay:2});
				jQuery.each(jQuery('.sws-toolbar-button'),function(ind,elem) {
					punchgs.TweenLite.fromTo(elem,0.7,{x:50},{x:0,ease:punchgs.Power3.easeInOut,delay:2.2+(ind*0.15)});
				})

				jQuery('.sws-toolbar-button')
					.on('mouseenter', function () {
						punchgs.TweenLite.to(jQuery(this),0.3,{x:-150,ease:punchgs.Power3.easeInOut});
					})
					.on('mouseleave', function () {
						punchgs.TweenLite.to(jQuery(this),0.3,{x:0,ease:punchgs.Power3.easeInOut});
					});
			});
		</script>
	</div>
</div>

<div class="clear"></div>

<?php
if(intval($isCreate) == 0){ 
	//currently editing
	echo '<div id="eg-create-step-3">';
}
?>

<div class="esg-editor-space"></div>
<h2><?php esc_html_e('Editor / Preview', ESG_TEXTDOMAIN); ?></h2>
<form id="eg-custom-elements-form-wrap">
	<div id="eg-live-preview-wrap">
		<?php
		Essential_Grid_Global_Css::output_global_css_styles_wrapped();
		?>
		<div id="esg-preview-wrapping-wrapper">
			<?php
			if($base->getVar($grid, array('postparams', 'source-type'), 'post') == 'custom'){
				$layers = @$grid['layers']; //no stripslashes used here

				if(!empty($layers)){
					foreach($layers as $layer){
						?>
						<input class="eg-remove-on-reload" type="hidden" name="layers[]" value="<?php echo htmlentities($layer); ?>" />
						<?php
					}
				}
			}
			?>
		</div>
	</div>
</form>
<?php
if(intval($isCreate) == 0){ 
	//currently editing
	echo '</div>';
}

Essential_Grid_Dialogs::post_meta_dialog(); // to change post meta information
Essential_Grid_Dialogs::edit_custom_element_dialog(); // to change post meta information
Essential_Grid_Dialogs::custom_element_image_dialog(); // to change post meta information

require_once('elements/grid-addons.php');
?>
<script type="text/javascript">
	try{
		jQuery('.mce-notification-error').remove();
		jQuery('#wpbody-content >.notice').remove();
	} catch(e) {

	}

	window.ESG = window.ESG === undefined ? {F:{}, C:{}, ENV:{}, LIB:{}, V:{}, S:{}, DOC:jQuery(document), WIN:jQuery(window)} : window.ESG;
	ESG.LIB.COLOR_PRESETS	= <?php echo (!empty($esg_color_picker_presets)) ? 'JSON.parse('. $base->jsonEncodeForClientSide($esg_color_picker_presets) .')' : '{}'; ?>;
	ESG.ENV.overviewMode = false;
	ESG.ENV.newAddonsCounter = document.getElementById('esg-new-addons-counter');
	ESG.ENV.newAddonsAmount = <?php echo $new_addon_counter; ?>;
	ESG.ENV.grid = {
		id: <?php echo ($grid !== false ? esc_attr($grid['id']) : 'false'); ?>,
		addons: <?php echo (!empty($grid['params']['addons']) ? json_encode($grid['params']['addons']) : '{}'); ?>,
		params : <?php echo (!empty($grid['params']) ? json_encode($grid['params']) : '{}'); ?>,
		postparams : <?php echo (!empty($grid['postparams']) ? json_encode($grid['postparams']) : '{}'); ?>,
	};

	// EARLY ACCESS TO SELECTED SOURE TYPE
	ESG.C.sourceType = jQuery('input[name="source-type"]');
	ESG.S.STYPE = jQuery('input[name="source-type"]:checked').val();

	var eg_jsonTaxWithCats = <?php echo $jsonTaxWithCats; ?>;
	var pages = [
		<?php
		if(!empty($pages)){
			$first = true;
			foreach($pages as $page){
				echo (!$first) ? ",\n" : "\n";
				echo '{ value: '.$page->ID.', label: "'.str_replace('"', '', $page->post_title).' (ID: '.$page->ID.')" }';
				$first = false;
			}
		}
		?>
	];

	function esg_grid_create_ready_function() {
		jQuery('#eg-create-settings-menu ul').esgScrollTabs();
		
		AdminEssentials.set_basic_columns(<?php echo $base->jsonEncodeForClientSide($base->set_basic_colums(array())); ?>);
		AdminEssentials.set_basic_columns_width(<?php echo $base->jsonEncodeForClientSide($base->set_basic_colums_width(array())); ?>);
		AdminEssentials.set_basic_masonry_content_height(<?php echo $base->jsonEncodeForClientSide($base->set_basic_masonry_content_height(array())); ?>);
		AdminEssentials.setInitMetaKeysJson(<?php echo $base->jsonEncodeForClientSide($meta_keys); ?>);
		
		AdminEssentials.Addons.setAddons(<?php echo (!empty($grid_addons) ? json_encode($grid_addons) : '{}'); ?>);
		AdminEssentials.Addons.init({
			afterInit: function() {
				AdminEssentials.initCreateGrid(<?php echo ($grid !== false) ? '"update_grid"' : ''; ?>);
				AdminEssentials.set_default_nav_skin(<?php echo $navigation_skin_css; ?>);
				AdminEssentials.get_default_nav_originals(<?php echo $base->jsonEncodeForClientSide($esg_default_skins); ?>);
				AdminEssentials.initSlider();
				AdminEssentials.initAutocomplete();
				AdminEssentials.initTabSizes();
				AdminEssentials.set_navigation_layout();
				AdminEssentials.checkDepricatedSkins();

				AdminEssentials.initSpinnerAdmin();
				AdminEssentials.setInitCustomJson(<?php echo $base->jsonEncodeForClientSide($custom_elements); ?>);
				
				AdminEssentials.createPreviewGrid();

				<?php if (!intval($isCreate)) { ?>
				AdminEssentials.Tip.create('new_grid', '<?php esc_attr_e('Add-ons cannot be assigned to the grid without a valid ID. Please fill the grid title and alias fields, and save the new grid to gain access to the add-on functionality!', ESG_TEXTDOMAIN); ?>');
				<?php } ?>
			}
		});

		ESG.DOC.trigger('esggrid_init_create_form');
	}
	
	var esg_grid_create_ready_function_once = false
	if (document.readyState === "loading") 
		document.addEventListener('readystatechange',function(){
			if ((document.readyState === "interactive" || document.readyState === "complete") && !esg_grid_create_ready_function_once) {
				esg_grid_create_ready_function_once = true;
				esg_grid_create_ready_function();
			}
		});
	else {
		esg_grid_create_ready_function_once = true;
		esg_grid_create_ready_function();
	}
	
</script>

<?php

echo '<div id="navigation-styling-css-wrapper">'."\n";
$skins = Essential_Grid_Navigation::output_navigation_skins();
echo $skins;
echo '</div>';

?>

<div id="esg-template-wrapper" class="esg-display-none">

</div>

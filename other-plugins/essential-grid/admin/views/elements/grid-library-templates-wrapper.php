<div id="library_bigoverlay"></div>
<?php
if (!empty($tp_grids)) {
	$favorites = new Essential_Grid_Favorite();
	$fav_grids = $favorites->get_favorite_type('grid');
	foreach ($tp_grids as $grid) {
		$isnew = false;
		$classes = array('esg_group_wrappers', 'not-imported-wrapper', 'template_premium');

		if ($favorites->is_favorite('grid', $grid['id'])) $classes[] = 'esg-lib-favorite-grid';

		if (!empty($grid['filter']) && is_array($grid['filter'])) {
			foreach ($grid['filter'] as $f => $v) {
				if ($v === 'newupdate') {
					$isnew = true;
				}
				$classes[] = $grid['filter'][$f] = 'temp_' . $v;
			}
		}
		?>
		<div class="<?php echo implode(' ', $classes); ?>"
		     data-date="<?php esc_attr_e($grid['id']); ?>"
		     data-title="<?php echo Essential_Grid_Base::sanitize_utf8_to_unicode($grid['title']); ?>">
			<?php if ($isnew) { ?>
				<span class="library_new"><?php esc_html_e("New", ESG_TEXTDOMAIN); ?></span>
			<?php } ?>
			<?php echo $this->write_import_template_markup($grid); ?>
			<div class="library_thumb_title">
				<?php echo $grid['title']; ?>
				<a href="javascript:void(0)" data-id="<?php esc_attr_e($grid['id']); ?>"><i class="material-icons">star</i></a>
			</div>
		</div>
		<?php
	}
} else {
	echo '<span class="esg_library_notice">';
	esc_html_e('No data could be retrieved from the servers. Please make sure that your website can connect to the themepunch servers.', ESG_TEXTDOMAIN);
	echo '</span>';
}
?>
<div class="esg-clearfix"></div>

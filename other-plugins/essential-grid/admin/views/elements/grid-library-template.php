<div class="library_item_import library_item"
	 data-src="<?php echo $grid['img'] . "?time=" . time(); ?>"
	 data-zipname="<?php echo $grid['zip']; ?>"
	 data-uid="<?php echo $grid['uid']; ?>"
	 data-title="<?php echo esc_html($grid['title']); ?>"
	 data-versionneed="<?php echo $grid['required']; ?>"
	 data-addons="<?php echo esc_attr(!empty($grid['addons']) ? json_encode($grid['addons']) : ''); ?>"
>
	<div class="library_thumb_overview"></div>
	<div class="library_preview_add_wrapper">
		<?php if (isset($grid['preview']) && $grid['preview'] !== '') { ?>
			<a class="preview_library_grid" href="<?php echo esc_attr($grid['preview']); ?>" target="_blank"><i class="eg-icon-search"></i></a>
		<?php } ?>
		<span class="show_more_library_grid"><i class="eg-icon-plus"></i></span>
		<span class="library_group_opener"><i class="fa-icon-folder"></i></span>
	</div>
</div>

<div class="library_thumb_more">
	<span class="ttm_label"><?php echo $grid['title']; ?></span>
	<?php
	if (isset($grid['description'])) {
		echo $grid['description'];
	}
	if (!empty($grid['setup_notes'])) {
		?>
		<span class="ttm_space"></span>
		<span class="ttm_label"><?php esc_html_e('Setup Notes', ESG_TEXTDOMAIN); ?></span>
		<?php
		echo $grid['setup_notes'];
		?>
		<?php
	}
	?>
	<span class="ttm_space"></span>
	<span class="ttm_label"><?php esc_html_e('Requirements', ESG_TEXTDOMAIN); ?></span>
	<ul class="ttm_requirements">
		<li><?php
			if (version_compare(ESG_REVISION, $grid['required'], '>=')) {
				?><i class="eg-icon-check"></i><?php
			} else {
				?><i class="eg-icon-cancel"></i><?php
				$allow_install = false;
			}
			esc_html_e('Essential Grid Version', ESG_TEXTDOMAIN);
			echo ' ' . $grid['required'];
			?></li>
	</ul>
	<span class="ttm_space"></span>
	<span class="ttm_label_direct"><?php esc_html_e('Available Version', ESG_TEXTDOMAIN); ?></span>
	<span class="ttm_label_half ttm_available"><?php echo $grid['version']; ?></span>
	<span class="ttm_space"></span>
	<?php if ($deny == '' && $allow_install) { ?>
		<div class="install_library_grid<?php echo $deny; ?>" data-zipname="<?php echo $grid['zip']; ?>"
		     data-uid="<?php echo $grid['uid']; ?>" data-title="<?php echo esc_html($grid['title']); ?>">
			<i class="eg-icon-download"></i>
			<?php esc_html_e('Install Grid', ESG_TEXTDOMAIN); ?>
		</div>
	<?php } else { ?>
		<div class="dontadd_library_grid_item"><i class="icon-not-registered"></i><?php esc_html_e('Requirements not met', ESG_TEXTDOMAIN); ?></div>
	<?php } ?>
	<span class="esg-clearfix esg-margin-b-5"></span>
</div>

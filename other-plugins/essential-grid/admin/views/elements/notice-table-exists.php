<?php if (!Essential_Grid_Db::check_table_exist_and_version()) { ?>
	<div id="tp-validation-box" class="esg_info_box">
		<div class="esg-red esg_info_box_decor"><i class="eg-icon-cancel"></i></div>
		<div id="rs-validation-wrapper">
			<div class="validation-label">
				<?php esc_html_e('The Essential Grid tables could not be updated/created.', ESG_TEXTDOMAIN); ?><br /><?php esc_html_e('Please check that the database user is able to create and modify tables.', ESG_TEXTDOMAIN); ?>
				<a class="esg-btn esg-green" href="?page=essential-grid&esg_recreate_database=<?php echo wp_create_nonce("Essential_Grid_recreate_db"); ?>"><?php esc_html_e('Create Again'); ?></a>
			</div>
			<div class="clear"></div>
			
		</div>
	</div>
	<div class="div50"></div>
<?php
}

<!-- DECISION MODAL -->
<div class="_ESG_AM_ esg-modal-wrapper" data-modal="esg_m_decisionModal">
	<div class="esg-modal-inner">
		<div class="esg-modal-content">
			<div id="esg_m_decisionModal" class="esg_modal form_inner">
				<div class="esg_m_header">
					<i id="esg_m_icon" class="esg_m_symbol material-icons">info</i>
					<span id="esg_m_title" class="esg_m_title"><?php _e('Decision Modal Title', ESG_TEXTDOMAIN);?></span>
				</div>
				<div class="esg_m_content">
					<div id="esg_m_maintxt" class="esg_m_main_txt"></div>
					<div id="esg_m_subtxt" class="esg_m_sub_txt"></div>
					<div class="div75"></div>
					<div id="esg_m_do_btn" class="esg_m_darkhalfbutton mr10">
						<i id="esg_m_do_icon" class="material-icons">add_circle_outline</i>
						<span id="esg_m_do_txt"><?php _e('Do It', 'revslider');?></span>
					</div>
					<div id="esg_m_dont_btn" class="esg_m_darkhalfbutton">
						<i id="esg_m_dont_icon" class="material-icons">add_circle_outline</i>
						<span id="esg_m_dont_txt"><?php _e('Dont Do It', 'revslider');?></span>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- PREMIUM BENEFITS MODAL -->
<div id="esg-premium-benefits-dialog" class="esg-display-none">
	<div class="esg-premium-benefits-dialogtitles" id="esg-wrong-purchase-code-title">
		<span class="material-icons">error</span>
		<span class="benefits-title-right">
			<span class="esg-premium-benefits-dialogtitle"><?php esc_html_e('Ooops... Wrong Purchase Code!', ESG_TEXTDOMAIN); ?></span>
			<span class="esg-premium-benefits-dialogsubtitle"><?php _e('Maybe just a typo? (Click <a target="_blank" href="https://www.essential-grid.com/manual/installing-activating-and-registering-essential-grid/">here</a> to find out how to locate your Essential Grid purchase code.)', ESG_TEXTDOMAIN); ?></span>
		</span>
	</div>
	<div class="esg-premium-benefits-dialogtitles esg-display-none" id="esg-plugin-update-feedback-title">
		<span class="material-icons">error</span>
		<span class="benefits-title-right">
			<span class="esg-premium-benefits-dialogtitle"><?php esc_html_e('Plugin Activation Required'); ?></span>
			<span class="esg-premium-benefits-dialogsubtitle"><?php _e('In order to download the <a target="_blank" href="https://account.essential-grid.com/licenses/pricing/">latest update</a> instantly', ESG_TEXTDOMAIN); ?></span>
		</span>
	</div>
	<div class="esg-premium-benefits-dialogtitles esg-display-none" id="esg-plugin-download-template-feedback-title">
		<span class="material-icons">error</span>
		<span class="benefits-title-right">
			<span class="esg-premium-benefits-dialogtitle"><?php esc_html_e('Plugin Activation Required'); ?></span>
			<span class="esg-premium-benefits-dialogsubtitle"><?php _e('In order to gain instant access to the entire <a target="_blank" href="https://www.essential-grid.com/grids">Grid Library</a>', ESG_TEXTDOMAIN); ?></span>
		</span>
	</div>

	<div id="basic_premium_benefits_block">
		<div class="esg-premium-benefits-block">
			<h3><?php esc_html_e('If you purchased a theme that bundled Essential Grid', ESG_TEXTDOMAIN); ?></h3>
			<ul>
				<li><?php esc_html_e('No activation needed to use / create grids with Essential Grid', ESG_TEXTDOMAIN); ?></li>
				<li><?php esc_html_e('Update manually through your theme', ESG_TEXTDOMAIN); ?></li>
				<li><?php _e('Access our <a target="_blank" href="https://www.essential-grid.com/help-center">FAQ database</a> and <a target="_blank" class="rspb_darklink" href="https://www.essential-grid.com/video-tutorials">video tutorials</a> for help', ESG_TEXTDOMAIN); ?></li>
			</ul>
		</div>
		<div class="esg-premium-benefits-block esg-premium-benefits-block-instant-access">
			<h3><?php esc_html_e('Activate Essential Grid for', ESG_TEXTDOMAIN); ?> <span class="instant_access"><?php esc_html_e('instant access', ESG_TEXTDOMAIN); ?></span> <?php esc_html_e('to', ESG_TEXTDOMAIN); ?></h3>
			<div class="instant-access-wrapper instant-access-update">
				<span class="material-icons">check_circle</span>
				<?php esc_html_e('Update to the latest version directly from your dashboard', ESG_TEXTDOMAIN); ?>
				<a target="_blank" class="instant-access-btn" href="https://www.essential-grid.com/manual/installing-activating-and-registering-essential-grid/"><?php esc_html_e('Update', ESG_TEXTDOMAIN); ?></a>
			</div>
			<div class="instant-access-wrapper instant-access-support">
				<span class="material-icons">support</span>
				<?php esc_html_e('Support ticket desk', ESG_TEXTDOMAIN); ?>
				<a target="_blank" class="instant-access-btn" href="https://support.essential-grid.com/"><?php esc_html_e('Support', ESG_TEXTDOMAIN); ?></a>
			</div>
			<div class="instant-access-wrapper instant-access-library">
				<span class="material-icons">photo_library</span>
				<?php esc_html_e('Library with tons of premium grids & addons', ESG_TEXTDOMAIN); ?>
				<a target="_blank" class="instant-access-btn" href="https://www.essential-grid.com/grids/"><?php esc_html_e('Library', ESG_TEXTDOMAIN); ?></a>
			</div>
		</div>
		<a target="_blank" class="get_purchase_code" href="https://account.essential-grid.com/licenses/pricing/"><?php esc_html_e('GET A PURCHASE CODE', ESG_TEXTDOMAIN); ?></a>
	</div>
</div>

<!--ADDONS INSTALLATION MODAL-->
<div class="_ESG_AM_ esg-modal-wrapper" data-modal="esg_m_addons">
	<div class="esg-modal-inner">
		<div class="esg-modal-content">
			<div id="esg_m_addons" class="esg_modal form_inner">
				<div class="esg_m_header">
					<i class="esg_m_symbol material-icons">extension</i><span class="esg_m_title"><?php esc_html_e('Addons', ESG_TEXTDOMAIN);?></span>
					<i class="esg_m_close material-icons">close</i>
					<div id="esg_check_addon_updates_wrap">
						<div id="esg_check_addon_updates" class="basic_action_button autosize">
							<i class="material-icons">refresh</i><?php esc_html_e('Check for Updates', ESG_TEXTDOMAIN);?>
						</div>
						<div id="esg_process_all_addon_updates" class="esg_ale_i_allupdateaddon  basic_action_coloredbutton autosize basic_action_button autosize">
							<i class="material-icons">get_app</i><?php esc_html_e('Update All', ESG_TEXTDOMAIN);?>
						</div>
					</div>
				</div>
				<div id="esg_addon_overviewheader_wrap">
					<div id="esg_addon_overviewheader" class="esg_addon_overview_header">
						<div class="esg_fh_left">
							<input class="flat_input" id="esg_search_addons" type="text" placeholder="<?php esc_attr_e('Search Addons...', ESG_TEXTDOMAIN);?>"/>
						</div>
						<div class="esg_fh_right">
							<select id="esg_sort_addons" data-theme="autowidthinmodal esg-lib-sort esg-addon-sort">
								<option value="datedesc"><?php esc_html_e('Sort by Date', ESG_TEXTDOMAIN);?></option>
								<option value="pop"><?php esc_html_e('Sort by Popularity', ESG_TEXTDOMAIN);?></option>
								<option value="title"><?php esc_html_e('Sort by Title', ESG_TEXTDOMAIN);?></option>
							</select>
							<select id="esg_filter_addons" data-theme="autowidthinmodal esg-lib-sort esg-addon-sort">
								<option value="all"><?php esc_html_e('Show all Addons', ESG_TEXTDOMAIN);?></option>
								<option value="action"><?php esc_html_e('Action Needed', ESG_TEXTDOMAIN);?></option>
								<option value="installed"><?php esc_html_e('Installed Addons', ESG_TEXTDOMAIN);?></option>
								<option value="notinstalled"><?php esc_html_e('Not Installed Addons', ESG_TEXTDOMAIN);?></option>
								<option value="activated"><?php esc_html_e('Activated Addons', ESG_TEXTDOMAIN);?></option>
							</select>
						</div>
						<div class="esg-clearfix"></div>
					</div>
				</div>
				<div id="esg_m_addonlist" class="esg_m_content"></div>
				<div id="esg_m_addon_details">
					<div class="esg_m_addon_details_inner">
						<div class="div20"></div>
						<div class="esg_ale_i_title"><?php esc_html_e('Essential Grid Addons', ESG_TEXTDOMAIN);?></div>
						<div class="esg_ale_i_content"><?php esc_html_e('Please select an Addon to start with.', ESG_TEXTDOMAIN);?></div>
						<div class="div20"></div>
					</div>
				</div>
				<div id="esg_m_configpanel_savebtn"><i class="material-icons mr10">save</i><span class="esg_m_cp_save_text"><?php esc_html_e('Save Configuration', ESG_TEXTDOMAIN);?></span></div>
			</div>
		</div>
	</div>
</div>

<script type="text/html" id="tmpl-esg-addon-item">
	<div id="esg_ale_{{ data.slug }}" class="esg_ale <# if (data.showinlist !== undefined && !data.showinlist) { #>esg-display-none<# } #>" data-ref="{{ data.slug }}">
		<div class="esg_alethumb" >
			<# if ('premium' == data.premium) { #>
			<div class="esg_ale_pf esg_ale_premium"><?php esc_html_e('Premium', ESG_TEXTDOMAIN); ?></div>
			<# } #>
			<div class="esg_alecbg" <# if (data.logo.color !== undefined && data.logo.color !== "" && data.installed !== false && data.active !== false) { #>style="background-color:{{ data.logo.color }}"<# } #>>
				<# if ("" == data.logo.img) { #>
				<div class="esg_alethumb_title">{{ data.logo.text }}</div>
				<# } #>
			</div>
			<# if ("" !== data.logo.img) { #>
			<div class="esg_alethumb_img" style="background-image:url('{{ data.logo.img }}')"></div>
			<# } #>
			<# if (!data.installed || !data.active) { #>
				<# if (!data.installed) { #>
				<div class="esg_ale_notinstalled"><?php esc_html_e('Not Installed', ESG_TEXTDOMAIN); ?></div>
				<# } #>
				<# if ("" !== data.logo.img) { #>
				<div class="esg_alethumb_notinstalledimg" style="background-image:url('{{ data.logo.img }}')"></div>
				<# } #>
			<# } else if (data.installed < data.available) { #>
			<div class="esg_ale_actionneeded"><?php esc_html_e('Action Needed', ESG_TEXTDOMAIN); ?></div>
			<# } else if (data.active && data.enabled) { #>
			<div class="esg_ale_enabled"><?php esc_html_e('Enabled', ESG_TEXTDOMAIN); ?></div>
			<# } #>
		</div>
		<div class="esg_ale_title">{{ data.title }}</div>
	</div>
</script>

<script type="text/html" id="tmpl-esg-addon-item-info">
	<div class="esg_m_addon_details_inner">
		<div class="div20"></div>
		<div class="esg_ale_i_title">{{ data.title }}</div>
		<div class="esg_ale_i_content">{{ data.line_1 + ' ' + data.line_2 }}</div>
		<div class="div20"></div>
		
		<# if (data.version_from.localeCompare(ESG.ENV.revision, undefined, { numeric: true }) > 0) { #>
		<div class="esg_ale_i_errorbutton basic_action_button autosize">
			<i class="material-icons">error_outline</i><?php esc_html_e('Check Requirements', ESG_TEXTDOMAIN); ?>
		</div>
		<# } else if (!data.installed) { #>
		<div class="esg_ale_i_installaddon basic_action_coloredbutton autosize basic_action_button" data-slug="{{ data.slug }}" data-global="{{ data.global }}">
			<i class="material-icons">get_app</i><?php esc_html_e('Install Add-On', ESG_TEXTDOMAIN); ?>
		</div>
		<# } else if (!data.active) { #>
		<div class="esg_ale_i_activateaddon basic_action_coloredbutton autosize basic_action_button" data-slug="{{ data.slug }}" data-global="{{ data.global }}">
			<i class="material-icons">power_settings_new</i>
			<# if (data.global) { #>
			<?php esc_html_e('Activate Global Add-On', ESG_TEXTDOMAIN); ?>
			<# } else { #>
			<?php esc_html_e('Activate Add-On', ESG_TEXTDOMAIN); ?>
			<# } #>
		</div>
		<# } else if (!data.enabled) { #>
			<# if (data.cannotEnable) { #>
			<div class="basic_action_button_inactive autosize basic_action_button" data-global="{{ data.global }}" data-slug="{{ data.slug }}">
				<i class="material-icons">error_outline</i>
				<?php esc_html_e('Add-On cannot be enabled!', ESG_TEXTDOMAIN); ?>
			</div>
			<# } else if (data.global) { #>
			<div class="esg_ale_i_enableaddon basic_action_coloredbutton autosize basic_action_button" data-global="{{ data.global }}" data-slug="{{ data.slug }}">
				<i class="material-icons">power_settings_new</i>
				<?php esc_html_e('Enable Global Add-On', ESG_TEXTDOMAIN); ?>
			</div>
			<# } else if (!ESG.ENV.overviewMode) { #>
			<div class="esg_ale_i_enableaddon basic_action_coloredbutton autosize basic_action_button" data-global="{{ data.global }}" data-slug="{{ data.slug }}">
				<i class="material-icons">power_settings_new</i>
				<?php esc_html_e('Enable Add-On', ESG_TEXTDOMAIN); ?>
			</div>
			<# } else { #>
			<div class="basic_action_button_inactive autosize basic_action_button" data-global="{{ data.global }}" data-slug="{{ data.slug }}">
				<i class="material-icons">error_outline</i>
				<?php esc_html_e('Enable/Disable Add-On on Grid', ESG_TEXTDOMAIN); ?>
			</div>
			<# } #>
		<# } else { #>
		<div class="esg_ale_i_disableaddon basic_action_coloredbutton autosize basic_action_button" data-global="{{ data.global }}" data-slug="{{ data.slug }}">
			<i class="material-icons">remove_circle_outline</i>
			<# if (data.global) { #>
			<?php esc_html_e('Disable Global Add-On', ESG_TEXTDOMAIN); ?>
			<# } else { #>
			<?php esc_html_e('Disable Add-On', ESG_TEXTDOMAIN); ?>
			<# } #>
		</div>
		<# } #>
		</div>
	<div class="esg_ale_i_line"></div>
	<div class="esg_m_addon_details_inner">

		<!-- VERSION DETAILS -->
		<div class="esg-addon-row">
			<div class="esg-addon-onehalf">
				<div class="esg_ale_i_title"><?php esc_html_e('Installed Version', ESG_TEXTDOMAIN); ?></div>
				<# if (data.installed === false) { #>
				<div class="esg_ale_i_content"><?php esc_html_e('Not Installed', ESG_TEXTDOMAIN); ?></div>
				<# } else { #>
				<div class="esg_ale_i_content">{{ data.installed }}</div>
				<# } #>
			</div>
			<div class="esg-addon-onehalf">
				<div class="esg_ale_i_title"><?php esc_html_e('Available Version', ESG_TEXTDOMAIN); ?></div>
				<div class="esg_ale_i_content">{{ data.available }}</div>
			</div >
		</div>

		<!-- REQUIREMENT -->
		<div class="div20"></div>
		<div class="esg_ale_i_title"><?php esc_html_e('Requirements', ESG_TEXTDOMAIN); ?></div>
		<# if (data.version_from.localeCompare(ESG.ENV.revision, undefined, { numeric: true }) > 0) { #>
		<div class="esg_ale_i_content esg_ale_yellow">
			<i class="material-icons">error_outline</i><?php esc_html_e('Essential Grid Version', ESG_TEXTDOMAIN); ?> {{ data.version_from }}
		</div>
		<# } else { #>
		<div class="esg_ale_i_content">
			<i class="material-icons">check</i><?php esc_html_e('Essential Grid Version', ESG_TEXTDOMAIN); ?> {{ data.version_from }}
		</div>
		<# } #>

		<!-- UPDATE AVAILABLE, UPDATE ADDON -->
		<# if (data.available.localeCompare(data.installed, undefined, { numeric: true }) > 0) { #>
		<div class="div20"></div>
		<div class="esg_ale_i_updateaddon basic_action_coloredbutton autosize basic_action_button" 
			 data-global="{{ data.global }}" data-slug="{{ data.slug }}">
			<i class="material-icons">get_app</i><?php esc_html_e('Update Now', ESG_TEXTDOMAIN); ?>
		</div>
		<# } #>

	</div>
	<div class="esg_ale_i_line"></div>
	<div class="esg_m_addon_details_inner" id="esg-addon-info-panel"></div>
</script>

<!-- FIX ADDONS DIALOG -->
<div id="esg-fix-addons-dialog" class="esg-display-none">
	<div class="esg-fix-addons-title-wrapper">
		<div class="oppps-icon"></div>
		<div class="esg-fix-addons-title"><?php esc_html_e('There is a problem with some of Essential Grid addons', ESG_TEXTDOMAIN); ?></div>
		<div class="esg-fix-addons-desc"></div>
	</div>
	<div id="esg-fix-addons-content">
		<div class="esg-fix-addons-content-block">
			<div id="esg-fix-addons-list" class="esg-fix-addons-list"></div>
		</div>
		<div class="esg-fix-addons-btn-block">
			<div class="esg-fix-addons-desc"></div>
			<a id="esg-fix-addons-fix" class="esg-fix-addons-fix" href="javascript:void(0);"><?php esc_html_e('Fix All Addons', ESG_TEXTDOMAIN); ?></a>
		</div>
	</div>
</div>

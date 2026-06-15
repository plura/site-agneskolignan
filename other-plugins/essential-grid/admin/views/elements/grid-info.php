<?php
/**
 * @package   Essential_Grid
 * @author    ThemePunch <info@themepunch.com>
 * @link      http://www.themepunch.com/essential/
 * @copyright 2023 ThemePunch
 */

if( !defined( 'ABSPATH') ) exit();

global $EssentialAsTheme;

$dir = plugin_dir_path(__FILE__).'../../../';

$validated = Essential_Grid_Base::getValid();
$code = Essential_Grid_Base::getCode();
$latest_version = get_option('tp_eg_latest-version', ESG_REVISION);
?>

<div class="flex-grid">
	
	<div class="col">
		<div id="esg-version-information" class="esg_info_box">
			<div class="esg-blue esg_info_box_decor"><i class="eg-icon-th-large"></i></div>
			<div class="view_title"><?php esc_html_e("Version Information", ESG_TEXTDOMAIN); ?></div>
			<div><?php esc_html_e("Installed Version", ESG_TEXTDOMAIN); ?>: <span><?php echo ESG_REVISION; ?></span></div>
			<div><?php esc_html_e("Available Version", ESG_TEXTDOMAIN); ?>: <span><?php echo $latest_version; ?></span></div>
			<div class="div10"></div>
			<div><a class="esg-btn esg-purple" href="?page=essential-grid&checkforupdates=true#esg-version-information"><?php esc_html_e('Check Version', ESG_TEXTDOMAIN); ?></a></div>
		</div>
	</div>

	<div class="col">
		<div class="esg_info_box">
			<div class="esg-purple esg_info_box_decor"><i class="eg-icon-info-circled"></i></div>
			<div class="view_title"><?php esc_html_e("How To Use Essential Grid", ESG_TEXTDOMAIN); ?></div>
			<div><?php _e('<a href="https://www.essential-grid.com/manual/installing-activating-and-registering-essential-grid/#register" target="_blank">Register</a> your Essential Grid for the full premium power!', ESG_TEXTDOMAIN); ?></div>
			<div><?php _e('Read the <a href="https://www.essential-grid.com/manual/grid-demo-in-under-3-minutes/" target="_blank">manual</a> for the fundamentals of how to create a grid.', ESG_TEXTDOMAIN); ?></div>
			<div><?php _e('Check out the premium <a href="https://www.essential-grid.com/grids/" target="_blank">grid templates</a> available for registered plugins.', ESG_TEXTDOMAIN); ?></div>
		</div>
	</div>
	
	<div class="col esg-w-100p esg-display-<?php echo ($validated === 'true' ? 'none' : 'block'); ?>">
		<div id="benefitscontent" class="esg_info_box">
			<div class="esg-blue esg_info_box_decor" ><i class="eg-icon-doc"></i></div>
			<div class="view_title"><?php esc_html_e("Registration Benefits", ESG_TEXTDOMAIN); ?>:</div>
			<div><strong><a href="https://www.essential-grid.com/grids/" target="_blank"><?php esc_html_e("Premium Grid Templates", ESG_TEXTDOMAIN); ?></a></strong><?php esc_html_e(" - Select from dozens of plug and play grid designs to kickstart your project", ESG_TEXTDOMAIN); ?></div>
			<div><strong><a href="https://account.essential-grid.com/licenses/pricing/" target="_blank"><?php esc_html_e("Premium AddOns", ESG_TEXTDOMAIN); ?></a></strong><?php esc_html_e(" - Get access to Addons with any of our Essential Grid license plans", ESG_TEXTDOMAIN); ?></div>
			<div><strong><a href="https://support.essential-grid.com/" target="_blank"><?php esc_html_e("Get Premium 1on1 Support", ESG_TEXTDOMAIN); ?></a></strong><?php esc_html_e(" - We help you in case of issues, installation problems and conflicts with other plugins or themes ", ESG_TEXTDOMAIN); ?></div>
			<div><strong><a href="https://account.essential-grid.com/licenses/pricing/" target="_blank"><?php esc_html_e("Auto Updates", ESG_TEXTDOMAIN); ?></a></strong><?php esc_html_e(" - Always receive the latest version of our plugin.  New features and bug fixes are available regularly ", ESG_TEXTDOMAIN); ?></div>
		</div>
	</div>
	
	<div class="col">
		<!-- ACTIVATE THIS PRODUCT -->
		<a id="activateplugin"></a>
		<div id="tp-validation-box" class="esg_info_box">
			<?php if ($validated === 'true') { ?>
				<div class="esg-green esg_info_box_decor"><i class="eg-icon-check"></i></div>
			<?php } else { ?>
				<div class="esg-red esg_info_box_decor"><i class="eg-icon-cancel"></i></div>
			<?php } ?>
			<div id="rs-validation-wrapper">
				<div class="view_title"><?php esc_html_e('Purchase code:', ESG_TEXTDOMAIN); ?></div>
				<div class="validation-input">
					<input class="esg-w-250 esg-margin-r-10" type="text" name="eg-validation-token" value="<?php echo $code; ?>" <?php echo ($validated === 'true') ? ' readonly="readonly"' : ''; ?> />
					<a href="javascript:void(0);" id="eg-validation-activate" class="esg-btn esg-green esg-margin-r-10 <?php echo ($validated !== 'true') ? '' : 'esg-display-none'; ?>"><?php esc_html_e('Activate', ESG_TEXTDOMAIN); ?></a><a href="javascript:void(0);" id="eg-validation-deactivate" class="esg-btn esg-red <?php echo ($validated === 'true') ? '' : 'esg-display-none'; ?>"><?php esc_html_e('Deactivate', ESG_TEXTDOMAIN); ?></a>
					<div class="validation-description"><?php esc_html_e('Please enter your ', ESG_TEXTDOMAIN); ?><strong class="esg-color-black"><?php esc_html_e('Essential Grid purchase code / license key.', ESG_TEXTDOMAIN); ?></strong><br/><?php esc_html_e('You can find your key by following the instructions on', ESG_TEXTDOMAIN); ?><a target="_blank" href="https://www.essential-grid.com/manual/installing-activating-and-registering-essential-grid/"><?php esc_html_e(' this page.', ESG_TEXTDOMAIN); ?></a><br><?php _e('Have no regular license for this installation? <a target="_blank" href="https://account.essential-grid.com/licenses/pricing/">Grab a fresh one</a>!', ESG_TEXTDOMAIN); ?></div>
				</div>
				<div class="clear"></div>
				<span id="rs_purchase_validation" class="esg-display-none loader_round"><?php esc_html_e('Please Wait...', ESG_TEXTDOMAIN); ?></span>
			</div>

			<?php if($validated === 'true') { ?>
				<div class="validation-label"> <?php esc_html_e("How to get Support ?", ESG_TEXTDOMAIN); ?></div>
				<div><?php esc_html_e("Visit our ", ESG_TEXTDOMAIN); ?><a href='https://www.essential-grid.com/help-center' target="_blank"><?php esc_html_e("Help Center ", ESG_TEXTDOMAIN); ?></a><?php esc_html_e("for the latest FAQs, Documentation and Ticket Support.", ESG_TEXTDOMAIN); ?></div>
			<?php } else { ?>
				<div id="tp-before-validation"><a href="https://account.essential-grid.com/licenses/pricing/" target="_blank"><?php esc_html_e("Click here to get ", ESG_TEXTDOMAIN); ?><strong><?php esc_html_e("Premium Support, Templates, AddOns and Auto Updates", ESG_TEXTDOMAIN); ?></strong></a></div>
			<?php } ?>
		</div>
	</div>
	
	<div class="col">
		<!-- NEWSLETTER PART -->
		<div id="eg-newsletter-wrapper" class="esg_info_box">
			<div class="esg-red esg_info_box_decor" ><i class="eg-icon-mail"></i></div>
			<div class="view_title"><?php esc_html_e('Newsletter', ESG_TEXTDOMAIN); ?></div>
			<input type="text" value="" placeholder="<?php esc_html_e('Enter your E-Mail here', ESG_TEXTDOMAIN); ?>" name="eg-email" class="esg-w-250 esg-margin-r-10" />
			<span class="subscribe-newsletter-wrap"><a href="javascript:void(0);" class="esg-btn esg-purple" id="subscribe-to-newsletter"><?php esc_html_e('Subscribe', ESG_TEXTDOMAIN); ?></a></span>
			<span class="unsubscribe-newsletter-wrap esg-display-none">
				<a href="javascript:void(0);" class="esg-btn esg-red" id="unsubscribe-to-newsletter"><?php esc_html_e('Unsubscribe', ESG_TEXTDOMAIN); ?></a>
				<a href="javascript:void(0);" class="esg-btn esg-green" id="cancel-unsubscribe"><?php esc_html_e('Cancel', ESG_TEXTDOMAIN); ?></a>
			</span>
			<div><a href="javascript:void(0);" id="activate-unsubscribe" class="esg-info-box-unsubscribe"><?php esc_html_e('unsubscibe from newsletter', ESG_TEXTDOMAIN); ?></a></div>
			<div id="why-subscribe-wrapper">
				<div class="star_red"><strong class="esg-font-w-700"><?php esc_html_e('Perks of subscribing to our Newsletter', ESG_TEXTDOMAIN); ?></strong></div>
				<ul>
					<li><?php esc_html_e('Receive info on the latest ThemePunch product updates', ESG_TEXTDOMAIN); ?></li>
					<li><?php esc_html_e('Be the first to know about new products by ThemePunch and their partners', ESG_TEXTDOMAIN); ?></li>
					<li><?php esc_html_e('Participate in polls and customer surveys that help us increase the quality of our products and services', ESG_TEXTDOMAIN); ?></li>
				</ul>
			</div>
		</div>
	</div>

	<div class="col esg-w-100p">
		<div class="esg_info_box">
			<div class="esg-purple esg_info_box_decor" ><i class="eg-icon-back-in-time"></i></div>
			<div class="view_title"><?php esc_html_e("Update History", ESG_TEXTDOMAIN); ?></div>
			<div class="esg-release-log-container"><?php
				$log = realpath($dir."release_log.html");
				if (!empty($log)) {
					echo file_get_contents($log);
				} else {
					echo 'Release log not found.';
				}
				?></div>
		</div>
	</div>
	
</div>

<script type="text/javascript">
function esg_grid_info_ready_function() {
	jQuery('#tp-validation-box').on('click',function() {
		jQuery(this).css({cursor:"default"});
		if (jQuery('#rs-validation-wrapper').css('display')=="none") {
			jQuery('#tp-before-validation').hide();
			jQuery('#rs-validation-wrapper').slideDown(200);
		}
	});
	AdminEssentials.initUpdateRoutine();
	AdminEssentials.initNewsletterRoutine();
}

var esg_grid_info_ready_once = false;
if (document.readyState === "loading") 
	document.addEventListener('readystatechange',function(){
		if ((document.readyState === "interactive" || document.readyState === "complete") && !esg_grid_info_ready_once) {
			esg_grid_info_ready_once = true;
			esg_grid_info_ready_function() ;
		}
	});
else {
	esg_grid_info_ready_once = true;
	esg_grid_info_ready_function() ;
}
</script>

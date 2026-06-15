<?php
/**
 * Essential Grid.
 *
 * @package   Essential_Grid
 * @author    ThemePunch <info@themepunch.com>
 * @link      http://www.themepunch.com/essential/
 * @copyright 2023 ThemePunch
 */

/**
 * @package Essential_Grid_Admin
 * @author  ThemePunch <info@themepunch.com>
 */

if (!defined('ABSPATH')) exit();

class Essential_Grid_Admin extends Essential_Grid_Base
{

	const ROLE_ADMIN = "admin";
	const ROLE_EDITOR = "editor";
	const ROLE_AUTHOR = "author";

	const VIEW_START = "grid";
	const VIEW_OVERVIEW = "grid-overview";
	const VIEW_GRID_CREATE = "grid-create";
	const VIEW_GRID = "grid-details";
	const VIEW_META_BOX = "grid-meta-box";
	const VIEW_ITEM_SKIN_EDITOR = "grid-item-skin-editor";
	const VIEW_GOOGLE_FONTS = "global-settings";
	const VIEW_IMPORT_EXPORT = "grid-import-export";

	const VIEW_WIDGET_AREAS = "grid-widget-areas";

	const VIEW_SEARCH = "global-settings";
	const VIEW_SUB_ITEM_SKIN_OVERVIEW = "grid-item-skin";
	const VIEW_SUB_CUSTOM_META = "global-settings";

	const VIEW_GLOBAL_SETTINGS = "grid-global-settings";
	const VIEW_SUB_CUSTOM_META_AJAX = "global-settings";

	const VIEW_SUB_WIDGET_AREA_AJAX = "widget-areas";

	/**
	 * @var string 
	 */
	private $plugin_slug;

	/**
	 * @var string
	 */
	protected static $view;

	/**
	 * Instance of this class.
	 * @var null|object
	 */
	protected static $instance = null;

	/**
	 * ESG pages slugs
	 * @var array
	 */
	protected $plugin_screen_hook_suffix = array();

	/**
	 * @var string 
	 */
	private static $menuRole = self::ROLE_ADMIN;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 */
	public function __construct()
	{
		global $wp_version, $pagenow;

		$library = new Essential_Grid_Library();
		$plugin = Essential_Grid::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		$role = get_option('tp_eg_role', self::ROLE_ADMIN);
		if (empty($role)) $role = self::ROLE_ADMIN;
		self::setMenuRole($role);

		// Add the options page and menu item.
		add_action('admin_menu', array($this, 'add_plugin_admin_menu'));
		add_action('admin_init', array($this, 'display_external_redirects'));
		add_action('admin_head', array($this, 'add_js_menu_open_blank'));

		// Load admin style sheet and JavaScript.
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts_language'));

		// Add the meta box to post/pages
		$enable_post_meta = get_option('tp_eg_enable_post_meta', 'true');
		if ('true' === $enable_post_meta) {
			add_action('registered_post_type', array($this, 'prepare_add_plugin_meta_box'), 10, 1);
			add_action('save_post', array($this, 'add_plugin_meta_box_save'));
		}
		
		add_action('wp_ajax_Essential_Grid_request_ajax', array($this, 'on_ajax_action'));

		$validated = Essential_Grid_Base::getValid();
		$notice = Essential_Grid_Base::getValidNotice();
		if ($validated === 'false' && $notice === 'true') {
			add_action('admin_notices', array($this, 'add_activate_notification'));
		}

		// Plugin page extra go premium button
		if(isset($pagenow) && $pagenow == 'plugins.php'){
			add_filter('admin_notices', array($this, 'add_plugins_page_notices'));
			if($validated == 'false'){
				add_filter('plugin_action_links_' . ESG_PLUGIN_SLUG_PATH, array($this, 'add_plugin_action_links'));
			}
		}

		$upgrade		= new Essential_Grid_Update(ESG_REVISION);
		$force_upgrade	= isset($_GET['checkforupdates']) && $_GET['checkforupdates'] == 'true';
		$shop_upgrade	= isset($_GET['update_shop']);

		$upgrade->_retrieve_version_info($force_upgrade);
		$upgrade->add_update_checks();

		$library->_get_template_list($shop_upgrade);
		
		add_action('admin_notices', array($this, 'add_notices'));

		//add calls to delete transient if needed
		add_action('save_post', array($this, 'check_for_transient_deletion'));
		add_action('future_to_publish', array($this, 'check_for_transient_deletion'));
		add_action('publish_post', array($this, 'check_for_transient_deletion'));
		add_action('publish_future_post', array($this, 'check_for_transient_deletion'));

		add_action('admin_head', array($this, 'add_tinymce_editor'));
		add_action('admin_head', array($this, 'add_header_data'));

		// Gutenberg
		add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));

		// add ThemePunch block category
		if (version_compare($wp_version, '5.8', '>=')) {
			add_filter('block_categories_all', array($this, 'create_block_category'), 10, 2);
		} else { 
			// block_categories is deprecated since 5.8.0
			add_filter('block_categories', array($this, 'create_block_category'), 10, 2);
		}

		// Privacy
		add_action('admin_init', array($this, 'add_suggested_privacy_content'), 15);
		
		//body class
		add_filter('admin_body_class', array($this, 'add_body_class'), 10, 1);
	}

	/**
	 * @param string $classes
	 * @return string
	 */
	public function add_body_class($classes)
	{
		// we are not on esg page
		if (!isset($_GET['page']) || $_GET['page'] != ESG_PLUGIN_SLUG) return $classes;

		self::$view = self::getGetVar("view");
		if (empty(self::$view))
			self::$view = self::VIEW_OVERVIEW;
		
		return $classes . ' esg-' . preg_replace('/[^a-zA-Z-]/', '', self::$view);
	}

	/**
	 * Return the default suggested privacy policy content.
	 *
	 * @return string  The default policy content.
	 */
	public function get_default_privacy_content()
	{
		return __('<h2>Essential Grid core itself does not collect any data from website visitors. In case you’re using things like Google Web Fonts (default) or connect to external sources in your Essential Grid please add the corresponding text phrase to your privacy police:</h2>
    <h3>Google Web Fonts</h3> <p>For uniform representation of fonts, this page uses web fonts provided by Google. When you open a page, your browser loads the required web fonts into your browser cache to display texts and fonts correctly.</p> <p>For this purpose your browser has to establish a direct connection to Google servers. Google thus becomes aware that our web page was accessed via your IP address. The use of Google Web fonts is done in the interest of a uniform and attractive presentation of our plugin. This constitutes a justified interest pursuant to Art. 6 (1) (f) DSGVO.</p> <p>If your browser does not support web fonts, a standard font is used by your computer.</p> <p>Further information about handling user data, can be found at <a href="https://developers.google.com/fonts/faq" target="_blank">https://developers.google.com/fonts/faq</a> and in Google\'s privacy policy at <a href="https://www.google.com/policies/privacy/" target="_blank">https://www.google.com/policies/privacy/</a>.</p>
    <h3>YouTube</h3> <p>Our website uses plugins from YouTube, which is operated by Google. The operator of the pages is YouTube LLC, 901 Cherry Ave., San Bruno, CA 94066, USA.</p> <p>If you visit one of our pages featuring a YouTube plugin, a connection to the YouTube servers is established. Here the YouTube server is informed about which of our pages you have visited.</p> <p>If you\'re logged in to your YouTube account, YouTube allows you to associate your browsing behavior directly with your personal profile. You can prevent this by logging out of your YouTube account.</p> <p>YouTube is used to help make our plugin appealing. This constitutes a justified interest pursuant to Art. 6 (1) (f) DSGVO.</p> <p>Further information about handling user data, can be found in the data protection declaration of YouTube under <a href="https://www.google.de/intl/de/policies/privacy" target="_blank">https://www.google.de/intl/de/policies/privacy</a>.</p>
    <h3>Vimeo</h3> <p>Our website uses features provided by the Vimeo video portal. This service is provided by Vimeo Inc., 555 West 18th Street, New York, New York 10011, USA.</p> <p>If you visit one of our pages featuring a Vimeo plugin, a connection to the Vimeo servers is established. Here the Vimeo server is informed about which of our pages you have visited. In addition, Vimeo will receive your IP address. This also applies if you are not logged in to Vimeo when you visit our plugin or do not have a Vimeo account. The information is transmitted to a Vimeo server in the US, where it is stored.</p> <p>If you are logged in to your Vimeo account, Vimeo allows you to associate your browsing behavior directly with your personal profile. You can prevent this by logging out of your Vimeo account.</p> <p>For more information on how to handle user data, please refer to the Vimeo Privacy Policy at <a href="https://vimeo.com/privacy" target="_blank">https://vimeo.com/privacy</a>.</p>
    <h3>SoundCloud</h3><p>On our pages, plugins of the SoundCloud social network (SoundCloud Limited, Berners House, 47-48 Berners Street, London W1T 3NF, UK) may be integrated. The SoundCloud plugins can be recognized by the SoundCloud logo on our site.</p>
      <p>When you visit our site, a direct connection between your browser and the SoundCloud server is established via the plugin. This enables SoundCloud to receive information that you have visited our site from your IP address. If you click on the “Like” or “Share” buttons while you are logged into your SoundCloud account, you can link the content of our pages to your SoundCloud profile. This means that SoundCloud can associate visits to our pages with your user account. We would like to point out that, as the provider of these pages, we have no knowledge of the content of the data transmitted or how it will be used by SoundCloud. For more information on SoundCloud’s privacy policy, please go to https://soundcloud.com/pages/privacy.</p><p>If you do not want SoundCloud to associate your visit to our site with your SoundCloud account, please log out of your SoundCloud account.</p>
    <h3>Facebook</h3>
      <p>Our website includes plugins for the social network Facebook, Facebook Inc., 1 Hacker Way, Menlo Park, California 94025, USA. For an overview of Facebook plugins, see <a href="https://developers.facebook.com/docs/plugins/" target="_blank" rel="noopener">https://developers.facebook.com/docs/plugins/</a>.</p><p>When you visit our site, a direct connection between your browser and the Facebook server is established via the plugin. This enables Facebook to receive information that you have visited our site from your IP address. If you click on the Facebook &#8220;Like button&#8221; while you are logged into your Facebook account, you can link the content of our site to your Facebook profile. This allows Facebook to associate visits to our site with your user account. Please note that, as the operator of this site, we have no knowledge of the content of the data transmitted to Facebook or of how Facebook uses these data. For more information, please see Facebook&#8217;s privacy policy at <a href="https://de-de.facebook.com/policy.php" target="_blank" rel="noopener">https://de-de.facebook.com/policy.php</a>.</p><p>If you do not want Facebook to associate your visit to our site with your Facebook account, please log out of your Facebook account.</p>
    <h3>Twitter</h3>
      <p>Functions of the Twitter service have been integrated into our website and app. These features are offered by Twitter Inc., 1355 Market Street, Suite 900, San Francisco, CA 94103, USA. When you use Twitter and the “Retweet” function, the websites you visit are connected to your Twitter account and made known to other users. In doing so, data will also be transferred to Twitter. We would like to point out that, as the provider of these pages, we have no knowledge of the content of the data transmitted or how it will be used by Twitter. For more information on Twitter&#8217;s privacy policy, please go to <a href="https://twitter.com/privacy" target="_blank" rel="noopener">https://twitter.com/privacy</a>.</p><p>Your privacy preferences with Twitter can be modified in your account settings at <a href="https://twitter.com/account/settings" target="_blank" rel="noopener">https://twitter.com/account/settings</a>.</p>
    <h3>Instagram</h3>
      <p>Our website contains functions of the Instagram service. These functions are offered by Instagram Inc., 1601 Willow Road, Menlo Park, CA 94025, USA.</p><p>If you are logged into your Instagram account, you can click the Instagram button to link the content of our pages with your Instagram profile. This means that Instagram can associate visits to our pages with your user account. As the provider of this website, we expressly point out that we receive no information on the content of the transmitted data or its use by Instagram.</p><p>For more information, see the Instagram Privacy Policy: <a href="https://instagram.com/about/legal/privacy/" target="_blank" rel="noopener">https://instagram.com/about/legal/privacy/</a>.</p>', ESG_TEXTDOMAIN);
	}

	/**
	 * Add the suggested privacy policy text to the policy eg-pbox.
	 */
	public function add_suggested_privacy_content()
	{
		if (function_exists("wp_add_privacy_policy_content")) {
			$content = $this->get_default_privacy_content();
			wp_add_privacy_policy_content(esc_attr__('Essential Grid'), $content);
		}
	}

	/**
	 * add notices from ThemePunch
	 * @since: 2.1.0
	 * @return void
	 */
	public function add_notices()
	{
		//check permissions here
		if (!current_user_can('administrator')) return;

		$enable_newschannel = apply_filters('essgrid_set_notifications', 'on');
		if ($enable_newschannel == 'on') {
			$nonce = wp_create_nonce("Essential_Grid_actions");
			$notices = get_option('essential-notices', false);
			if (!empty($notices) && is_array($notices)) {
				$notices_discarded = get_option('essential-notices-dc', array());
				$screen = get_current_screen();
				foreach ($notices as $notice) {
					if ($notice->is_global !== true && !in_array($screen->id, $this->plugin_screen_hook_suffix)) continue; //check if global or just on plugin related pages

					if (!in_array($notice->code, $notices_discarded) && version_compare($notice->version, ESG_REVISION, '>=')) {
						$text = '<div class="esg-notices-button-container"><a href="javascript:void(0);"  class="esg-notices-button esg-notice-' . esc_attr($notice->code) . '">' . esc_attr__('Close & don\'t show again', ESG_TEXTDOMAIN) . '<b>X</b></a></div>';
						if ($notice->disable) $text = '';
						?>
						<div class="<?php echo $notice->color; ?> below-h2 esg-update-notice-wrap" id="message">
							<div class="esg-update-notice-table">
								<div class="esg-update-notice-table-cell"><?php echo $notice->text; ?></div><?php echo $text; ?>
							</div>
						</div>
						<?php
					}
				}
				?>
				<script type="text/javascript">
					jQuery('.esg-notices-button').on('click', function () {

						let notice_id = jQuery(this).attr('class').replace('esg-notices-button', '').replace('esg-notice-', '');

						let objData = {
							action: "Essential_Grid_request_ajax",
							client_action: 'dismiss_dynamic_notice',
							token: '<?php echo $nonce; ?>',
							data: {'id': notice_id}
						};

						jQuery.ajax({
							type: "post",
							url: ajaxurl,
							dataType: 'json',
							data: objData
						});

						jQuery(this).closest('.esg-update-notice-wrap').slideUp(200);
					});
				</script>
				<?php
			}
		}
	}

	/**
	 * show notification message if plugin is not activated
	 */
	public function add_activate_notification()
	{
		$token = wp_create_nonce('Essential_Grid_actions');
		
		$n  = '<div class="updated below-h2 eg-update-notice-wrap esg-margin-l-0" id="message"><a href="javascript:void(0);" class="esg-f-right" id="eg-dismiss-notice">×</a><p>' . esc_attr__('Hi! Please activate your copy of the Essential Grid to receive live updates, premium support and the template library.', ESG_TEXTDOMAIN) . '</p></div>' . "\n";
		$n .= '<script type="text/javascript">' . "\n";
		$n .= ' jQuery(\'#eg-dismiss-notice\').on(\'click\',function(){' . "\n";
		$n .= '   var objData = {' . "\n";
		$n .= '     action: \'Essential_Grid_request_ajax\',' . "\n";
		$n .= '     client_action: \'dismiss_notice\',' . "\n";
		$n .= '     token: \'' . $token . '\',' . "\n";
		$n .= '     data: \'\'' . "\n";
		$n .= '   };' . "\n";
		$n .= '   ' . "\n";
		$n .= '   jQuery.ajax({' . "\n";
		$n .= '     type:\'post\',' . "\n";
		$n .= '     url:ajaxurl,' . "\n";
		$n .= '     dataType:\'json\',' . "\n";
		$n .= '     data:objData' . "\n";
		$n .= '   });' . "\n";
		$n .= '   ' . "\n";
		$n .= '   jQuery(\'.eg-update-notice-wrap\').hide();' . "\n";
		$n .= ' });' . "\n";
		$n .= '</script>' . "\n";

		echo apply_filters('essgrid_add_activate_notification', $n);
	}

	/**
	 * Return an instance of this class.
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance()
	{
		// If the single instance hasn't been set, set it now.
		if (null == self::$instance) {
			self::$instance = new self;
		}

		return apply_filters('essgrid_get_instance', self::$instance);
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 * @return void  Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles()
	{
		if (!isset($this->plugin_screen_hook_suffix)) {
			return;
		}

		wp_register_style('esg-plugin-settings', ESG_PLUGIN_URL . 'public/assets/css/settings.css', array(), ESG_REVISION);
		wp_enqueue_style('esg-plugin-settings');
		
		$screen = get_current_screen();
		if (in_array($screen->id, $this->plugin_screen_hook_suffix)) {
			wp_enqueue_style(array('wp-jquery-ui', 'wp-jquery-ui-core', 'wp-jquery-ui-dialog', 'wp-color-picker'));
			wp_enqueue_style('esg-admin-styles', ESG_PLUGIN_URL . 'admin/assets/css/esg-admin.css', array(), ESG_REVISION);
			wp_enqueue_style('esg-dialog-styles', ESG_PLUGIN_URL . 'admin/assets/css/esg-dialog.css', array(), ESG_REVISION);
			wp_enqueue_style('esg-mirror-styles', ESG_PLUGIN_URL . 'admin/assets/css/esg-mirror.css', array(), ESG_REVISION);
			wp_enqueue_style('esg-tooltipser-styles', ESG_PLUGIN_URL . 'admin/assets/css/tooltipster.css', array(), ESG_REVISION);
			wp_enqueue_style('esg-plugin-settings');
			wp_register_style('esg-tp-boxextcss', ESG_PLUGIN_URL . 'public/assets/css/jquery.esgbox.min.css', array(), ESG_REVISION);
			$font = new ThemePunch_Fonts();
			$font->register_icon_fonts("admin");
		}
		
		$google_fonts_admin = array(
			'Roboto' => array(
				'css' => ESG_PLUGIN_URL . 'public/assets/font/roboto/roboto.css',
				'url' => 'Roboto:300,400,500,700,900',
			),
			'Open Sans' => array(
				'css' => ESG_PLUGIN_URL . 'public/assets/font/opensans/opensans.css',
				'url' => 'Open+Sans:300,400,600,700,800',
			),
			'Material Icons' => array(
				'css' => ESG_PLUGIN_URL . 'public/assets/font/material/material-icons.css',
				'url' => 'Material+Icons',
			),
		);
		$google_fonts_admin = apply_filters('essgrid_enqueue_admin_styles_google_fonts', $google_fonts_admin);
		foreach ($google_fonts_admin as $f => $data) {
			wp_enqueue_style('tp-' . sanitize_title($f), $data['css']);
		}
		
		wp_enqueue_style('esg-global-styles', ESG_PLUGIN_URL . 'admin/assets/css/esg-global.css', array(), ESG_REVISION);
		wp_enqueue_style('tp-fontello', ESG_PLUGIN_URL . 'public/assets/font/fontello/css/fontello.css', array(), ESG_REVISION);

		//enqueue in all pages / posts in backend
		$post_types = get_post_types('');
		$post_types[] = 'comment';

		foreach ($post_types as $post_type) {
			if ($post_type == $screen->id) {
				wp_enqueue_style('wp-jquery-ui-dialog');
				wp_enqueue_style('wp-color-picker');
				wp_enqueue_style('tp-' . sanitize_title('Material Icons'), ESG_PLUGIN_URL . 'public/assets/font/material/material-icons.css');
			}
		}

		do_action('essgrid_enqueue_admin_styles', $this->plugin_screen_hook_suffix);
	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 * @return void  Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts()
	{
		global $esg_dev_mode, $wp_version, $pagenow;
		
		if (!isset($this->plugin_screen_hook_suffix)) {
			return;
		}

		if (strpos($wp_version, '-') !== false) {
			$_wpver = explode('-', $wp_version);
			$wp_version = $_wpver[0];
		}

		$screen = get_current_screen();
		if (in_array($screen->id, $this->plugin_screen_hook_suffix)) {
			wp_enqueue_script(
				array(
					'jquery',
					'jquery-ui-core',
					'jquery-ui-dialog',
					'jquery-ui-slider',
					'jquery-ui-autocomplete',
					'jquery-ui-droppable',
					'jquery-ui-draggable',
					'jquery-ui-resizable',
					'jquery-ui-sortable',
					'jquery-ui-tabs',
					'wp-color-picker'
				)
			);

			if (version_compare($wp_version, '5.6', '<')) {
				wp_enqueue_script('jquery-ui-sortable', 'jquery-ui-draggable');
			}

			wp_enqueue_style('esg-color-picker-css', plugins_url('assets/css/tp-color-picker.css', __FILE__), array(), ESG_REVISION);
			wp_enqueue_style('esg-ddtp-css', plugins_url('assets/css/ddTP.css', __FILE__), array(), ESG_REVISION);

			/**
			 * dequeue tp-tools to make sure that always the latest is loaded
			 **/
			wp_enqueue_script('tp-tools', ESG_PLUGIN_URL . 'public/assets/js/rbtools.min.js', array('jquery'), ESG_TP_TOOLS);

			if ($esg_dev_mode) { 
				// DEV VERSION
				wp_enqueue_script('esg-tp-boxext', ESG_PLUGIN_URL . 'public/assets/js/dev/esgbox.js', array('jquery'), ESG_REVISION);
				wp_enqueue_script('esg-admin-script', plugins_url('assets/js/modules/dev/admin.js', __FILE__), array('jquery', 'wp-color-picker'), ESG_REVISION);
				wp_enqueue_script('esg-admin-sortable-script', plugins_url('assets/js/modules/dev/sortable.js', __FILE__), array('jquery'), ESG_REVISION);
				wp_enqueue_script('esg-admin-dialog-script', plugins_url('assets/js/modules/dev/dialog.js', __FILE__), array('jquery'), ESG_REVISION);
				wp_enqueue_script('esg-admin-addons-script', plugins_url('assets/js/modules/dev/addons.js', __FILE__), array('jquery'), ESG_REVISION);
				wp_enqueue_script('esg-admin-templates-script', plugins_url('assets/js/modules/dev/templates.js', __FILE__), array('jquery'), ESG_REVISION);
				wp_enqueue_script('esg-admin-overview-script', plugins_url('assets/js/modules/dev/overview.js', __FILE__), array('jquery'), ESG_REVISION);
				wp_enqueue_script('esg-admin-tip-script', plugins_url('assets/js/modules/dev/tip.js', __FILE__), array('jquery'), ESG_REVISION);
				wp_enqueue_script('esg-admin-scroll-tabs-script', plugins_url('assets/js/modules/dev/scroll-tabs.js', __FILE__), array('jquery'), ESG_REVISION);

				//UTILS
				wp_enqueue_script('esg-perfect-scrollbar-script', plugins_url('assets/js/plugins/dev/esg-perfect-scrollbar.js', __FILE__), array('jquery'), ESG_REVISION);
				wp_enqueue_script('esg-tooltipser-script', plugins_url('assets/js/plugins/dev/tooltipster.js', __FILE__), array('jquery'), ESG_REVISION);
				wp_enqueue_script('esg-esgmirror-script', plugins_url('assets/js/plugins/dev/esgmirror.js', __FILE__), array('jquery'), ESG_REVISION);
				wp_enqueue_script('esg-color-picker-js', plugins_url('assets/js/plugins/dev/tp-color-picker.js', __FILE__), array('jquery'), ESG_REVISION);
				wp_enqueue_script('esg-ddtp-js', plugins_url('assets/js/plugins/dev/ddTP.js', __FILE__), array('jquery'), ESG_REVISION);

				//ESG GRID
				wp_enqueue_script('esg-script', plugins_url('../public/assets/js/dev/esg.js', __FILE__), array('jquery'), ESG_REVISION);
			} else { 
				// PROD VERSION

				//perfect-scrollbar + ToolTipser + Admin.js
				wp_enqueue_script('esg-admin-script', plugins_url('assets/js/modules/admin.min.js', __FILE__), array('jquery', 'wp-color-picker'), ESG_REVISION);
				//ESGMirror + ColorPicker JS + ddTP
				wp_enqueue_script('esg-utils', plugins_url('assets/js/plugins/utils.min.js', __FILE__), array('jquery', 'jquery-ui-dialog'), ESG_REVISION);

				//ESG Box
				wp_enqueue_script('esg-tp-boxext', ESG_PLUGIN_URL . 'public/assets/js/esgbox.min.js', array('jquery'), ESG_REVISION);

				//ESG GRID
				wp_enqueue_script('esg-script', plugins_url('../public/assets/js/esg.min.js', __FILE__), array('jquery'), ESG_REVISION);
			}

			wp_enqueue_media();
		}

		//enqueue in all pages / posts in backend
		$post_types = get_post_types('');
		$post_types[] = 'comment';

		foreach ($post_types as $post_type) {
			if ($post_type == $screen->id) {
				$this->_enqueue_admin_scripts_and_shortcode();
			}
		}
		
		if ( in_array($pagenow, array('widgets.php', 'customize.php')) ) {
			$this->_enqueue_admin_scripts_and_shortcode();
			add_action('in_admin_footer', array('Essential_Grid_Dialogs', 'essgrid_add_shortcode_builder'));
			add_action('customize_controls_print_footer_scripts', array('Essential_Grid_Dialogs', 'essgrid_add_shortcode_builder'));
			wp_localize_script('esg-admin-script', 'ESG_LANG', self::get_javascript_multilanguage());
		}

		do_action('essgrid_enqueue_admin_scripts');
	}
	
	protected function _enqueue_admin_scripts_and_shortcode()
	{
		global $esg_dev_mode, $wp_version;

		wp_enqueue_style('wp-jquery-ui-dialog');
		
		wp_enqueue_script(array('wpdialogs', 'jquery', 'jquery-ui-core', 'wp-color-picker'));
		if (version_compare($wp_version, '5.6', '<')) {
			wp_enqueue_script('jquery-ui-sortable');
		}
		wp_enqueue_style('esg-color-picker-css', plugins_url('assets/css/tp-color-picker.css', __FILE__), array(), ESG_REVISION);
		wp_enqueue_style('esg-ddtp-css', plugins_url('assets/css/ddTP.css', __FILE__), array(), ESG_REVISION);

		/**
		 * dequeue tp-tools to make sure that always the latest is loaded
		 **/
		wp_enqueue_script('tp-tools', ESG_PLUGIN_URL . 'public/assets/js/rbtools.min.js', array('jquery'), ESG_TP_TOOLS);

		if ($esg_dev_mode) {
			// DEV VERSION
			wp_enqueue_script('esg-admin-script', plugins_url('assets/js/modules/dev/admin.js', __FILE__), array('jquery', 'wp-color-picker'), ESG_REVISION);
			wp_enqueue_script('esg-admin-sortable-script', plugins_url('assets/js/modules/dev/sortable.js', __FILE__), array('jquery'), ESG_REVISION);
			wp_enqueue_script('esg-admin-dialog-script', plugins_url('assets/js/modules/dev/dialog.js', __FILE__), array('jquery'), ESG_REVISION);
			wp_enqueue_script('esg-admin-addons-script', plugins_url('assets/js/modules/dev/addons.js', __FILE__), array('jquery'), ESG_REVISION);
			wp_enqueue_script('esg-admin-templates-script', plugins_url('assets/js/modules/dev/templates.js', __FILE__), array('jquery'), ESG_REVISION);
			wp_enqueue_script('esg-admin-overview-script', plugins_url('assets/js/modules/dev/overview.js', __FILE__), array('jquery'), ESG_REVISION);
			wp_enqueue_script('esg-admin-tip-script', plugins_url('assets/js/modules/dev/tip.js', __FILE__), array('jquery'), ESG_REVISION);
			wp_enqueue_script('esg-admin-scroll-tabs-script', plugins_url('assets/js/modules/dev/scroll-tabs.js', __FILE__), array('jquery'), ESG_REVISION);

			wp_enqueue_script('esg-perfect-scrollbar-script', plugins_url('assets/js/plugins/dev/esg-perfect-scrollbar.js', __FILE__), array('jquery'), ESG_REVISION);
			wp_enqueue_script('esg-tooltipser-script', plugins_url('assets/js/plugins/dev/tooltipster.js', __FILE__), array('jquery'), ESG_REVISION);
			wp_enqueue_script('esg-color-picker-js', plugins_url('assets/js/plugins/dev/tp-color-picker.js', __FILE__), array('jquery'), ESG_REVISION);
			wp_enqueue_script('esg-ddtp-js', plugins_url('assets/js/plugins/dev/ddTP.js', __FILE__), array('jquery'), ESG_REVISION);
			wp_enqueue_script('esg-shortcode', plugins_url('assets/js/modules/dev/shortcode.js', __FILE__), array('jquery'), ESG_REVISION);
			wp_enqueue_media();
		} else {
			// PROD VERSION
			wp_enqueue_script('esg-admin-script', plugins_url('assets/js/modules/admin.min.js', __FILE__), array('jquery', 'wp-color-picker'), ESG_REVISION);
			wp_enqueue_script('esg-shortcode', plugins_url('assets/js/modules/shortcode.min.js', __FILE__), array('jquery'), ESG_REVISION);
			wp_enqueue_script('esg-utils', plugins_url('assets/js/plugins/utils.min.js', __FILE__), array('jquery', 'jquery-ui-dialog'), ESG_REVISION);
		}
	}

	/**
	 * Register and enqueue admin-specific JavaScript Language.
	 * @return void    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts_language()
	{
		if (!isset($this->plugin_screen_hook_suffix)) {
			return;
		}

		$screen = get_current_screen();
		if (in_array($screen->id, $this->plugin_screen_hook_suffix)) {
			wp_localize_script('esg-admin-script', 'ESG_LANG', self::get_javascript_multilanguage()); //Load multilanguage for JavaScript
		}

		//enqueue in all pages / posts in backend
		$post_types = get_post_types('');
		foreach ($post_types as $post_type)
			if ($post_type == $screen->id) wp_localize_script('esg-admin-script', 'ESG_LANG', self::get_javascript_multilanguage()); //Load multilanguage for JavaScript

		do_action('essgrid_enqueue_admin_scripts_language');
	}

	public function add_header_data()
	{
		global $typenow, $pagenow;

		$post_types = get_post_types();
		if (!is_array($post_types)) $post_types = array('post', 'page');
		// verify the post type and check if we on widget page
		if ( !in_array($typenow, $post_types) || !in_array($pagenow, array('widgets.php', 'customize.php')) ) return;

		$esg_color_picker_presets = ESGColorpicker::get_color_presets();
		?>
		<script type="text/javascript">
			window.ESG = window.ESG === undefined ? {
				F: {},
				C: {},
				ENV: {},
				LIB: {},
				V: {},
				S: {},
				DOC: jQuery(document),
				WIN: jQuery(window)
			} : window.ESG;
			ESG.LIB.COLOR_PRESETS = <?php echo (!empty($esg_color_picker_presets)) ? 'JSON.parse(' . $this->jsonEncodeForClientSide($esg_color_picker_presets) . ')' : '{}'; ?>;
		</script>
		<?php
	}

	/**
	 * Add interface for custom shortcodes to tinymce
	 * @since: 1.2.0
	 */
	public function add_tinymce_editor()
	{
		global $typenow;

		do_action('essgrid_add_tinymce_editor');

		// check user permissions
		if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) return;

		$post_types = get_post_types();
		if (!is_array($post_types)) $post_types = array('post', 'page');
		if (!in_array($typenow, $post_types)) return;

		// check if WYSIWYG is enabled
		if (get_user_option('rich_editing') == 'true') {
			add_action('before_wp_tiny_mce', array($this, 'add_tinymce_lang'));
			add_filter('mce_external_plugins', array($this, 'add_tinymce_shortcode_editor_plugin'));
			add_filter('mce_buttons', array($this, 'add_tinymce_shortcode_editor_button'));
		}

		add_action('in_admin_footer', array('Essential_Grid_Dialogs', 'essgrid_add_shortcode_builder'));
	}

	/**
	 * add ESG_LANG before tinyMCE js init 
	 * @return void
	 */
	public static function add_tinymce_lang()
	{
		echo '<script type="text/javascript">';
		echo 'var ESG_LANG = ' . json_encode(self::get_javascript_multilanguage());
		echo '</script>';
	}

	/**
	 * add script tinymce shortcode script
	 * @since: 1.2.0
	 */
	public static function add_tinymce_shortcode_editor_plugin($plugin_array)
	{
		global $esg_dev_mode;

		if ($esg_dev_mode) {
			$plugin_array['essgrid_sc_button'] = plugins_url('assets/js/modules/dev/shortcode.js', __FILE__);
		} else {
			$plugin_array['essgrid_sc_button'] = plugins_url('assets/js/modules/shortcode.min.js', __FILE__);
		}
		
		return apply_filters('essgrid_add_tinymce_shortcode_editor_plugin', $plugin_array);
	}

	/**
	 * Add button to tinymce
	 * @since: 1.2.0
	 */
	public static function add_tinymce_shortcode_editor_button($buttons)
	{
		$buttons[] = "essgrid_sc_button";
		return apply_filters('essgrid_add_tinymce_shortcode_editor_button', $buttons);
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 */
	public function add_plugin_admin_menu()
	{
		$role = self::getPluginPermission();

		$this->plugin_screen_hook_suffix[] = add_menu_page(esc_attr__('Essential Grid', ESG_TEXTDOMAIN), esc_attr__('Essential Grid', ESG_TEXTDOMAIN), $role, $this->plugin_slug, array($this, 'display_plugin_admin_page'), 'dashicons-screenoptions');
		$this->plugin_screen_hook_suffix[] = add_submenu_page($this->plugin_slug, esc_attr__('Item Skin Editor', ESG_TEXTDOMAIN), esc_attr__('Item Skin Editor', ESG_TEXTDOMAIN), $role, $this->plugin_slug . '-item-skin', array($this, 'display_plugin_submenu_page_item_skin'));
		$this->plugin_screen_hook_suffix[] = add_submenu_page($this->plugin_slug, esc_attr__('Global Settings', ESG_TEXTDOMAIN), esc_attr__('Global Settings', ESG_TEXTDOMAIN), $role, $this->plugin_slug . '-global-settings', array($this, 'display_plugin_submenu_page_global_settings'));
		$this->plugin_screen_hook_suffix[] = add_submenu_page($this->plugin_slug, esc_attr__('Import/Export', ESG_TEXTDOMAIN), esc_attr__('Import/Export', ESG_TEXTDOMAIN), $role, $this->plugin_slug . '-import-export', array($this, 'display_plugin_submenu_page_import_export'));
		//since 3.0.14
		$this->plugin_screen_hook_suffix[] = add_submenu_page($this->plugin_slug, esc_attr__('Getting Started', ESG_TEXTDOMAIN), '<div id="essgrid_manual_link" style="margin-top:15px">Getting Started</div>', $role, 'essgrid-documentation', array($this, 'display_external_redirects'));
		$this->plugin_screen_hook_suffix[] = add_submenu_page($this->plugin_slug, esc_attr__('Help Center', ESG_TEXTDOMAIN), '<div id="essgrid_helpcenter_link">Help Center</div>', $role, 'essgrid-help-center', array($this, 'display_external_redirects'));
		$this->plugin_screen_hook_suffix[] = add_submenu_page($this->plugin_slug, esc_attr__('Grid Library', ESG_TEXTDOMAIN), '<div id="essgrid_templates_link">Grid Library</div>', $role, 'essgrid-templates', array($this, 'display_external_redirects'));
		$this->plugin_screen_hook_suffix[] = add_submenu_page($this->plugin_slug, esc_attr__('Premium Support', ESG_TEXTDOMAIN), '<div id="essgrid_ticket_link">Premium Support</div>', $role, 'essgrid-ticket', array($this, 'display_external_redirects'));
		
		if (!Essential_Grid_Base::isValid()){
			$this->plugin_screen_hook_suffix[] = add_submenu_page($this->plugin_slug, esc_attr__('Go Premium', ESG_TEXTDOMAIN), '<div id="essgrid_premium_link" style="color:#f7345e;margin-top:15px"><span class="dashicons dashicons-star-filled" style="font-size: 17px"></span> '.__('Go Premium', ESG_TEXTDOMAIN)."</div>", $role, 'essgrid-buy-license', array($this, 'display_external_redirects'));
		}

		// Full Site Editing
		$this->plugin_screen_hook_suffix[] = 'widgets';

		do_action('essgrid_add_plugin_admin_menu', $role, $this->plugin_slug, $this);
	}

	/**
	 * redirect to external URLs
	 * @since 3.0.14
	 */
	public function display_external_redirects() {
		if ( empty( $_GET['page'] ) ) return;

		$eg_premium = Essential_Grid_Base::getValid();

		switch ( $_GET['page'] ) {
			case 'essgrid-buy-license':
				wp_redirect('https://account.essential-grid.com/licenses/pricing//?utm_source=admin&utm_medium=button&utm_campaign=egusers&utm_content=buykey');
				exit;
			case 'essgrid-documentation':
				wp_redirect('https://www.essential-grid.com/manual/installing-activating-and-registering-essential-grid/?utm_source=admin&utm_medium=button&utm_campaign=egusers&utm_content=usedocumentation&premium='.$eg_premium);
				exit;
			case 'essgrid-help-center':
				wp_redirect('https://www.essential-grid.com/help-center/?utm_source=admin&utm_medium=button&utm_campaign=egusers&utm_content=help&premium='.$eg_premium);
				exit;
			case 'essgrid-templates':
				wp_redirect('https://www.essential-grid.com/grids/?utm_source=admin&utm_medium=button&utm_campaign=egusers&utm_content=templates&premium='.$eg_premium);
				exit;
			case 'essgrid-ticket':
				wp_redirect('https://support.essential-grid.com?utm_source=admin&utm_medium=button&utm_campaign=egusers&utm_content=support&premium='.$eg_premium);
				exit;
			default:
		}
	}

	/**
	 * opens the external EssGrid URLs in a blank tab
	 * @since 3.0.15
	 */
	public function add_js_menu_open_blank() {
		echo '<script>
				jQuery(document).ready(function(){
					jQuery("#essgrid_manual_link, #essgrid_helpcenter_link, #essgrid_templates_link, #essgrid_ticket_link, #essgrid_premium_link").parent().attr("target","_blank");
				});
			</script>';
	}

	/**
	 * prepare the meta box inclusion if right post_type (includes all custom post types
	 */
	public static function prepare_add_plugin_meta_box($post_type)
	{
		$skip_post_types = apply_filters('essgrid_prepare_add_plugin_meta_box_skip_post_types', array (
				'attachment',
				'revision',
				'nav_menu_item',
				'custom_css',
				'customize_changeset',
				'oembed_cache',
				'user_request',
				'wp_block',
				'wp_template',
				'wp_template_part',
				'wp_global_styles',
				'wp_navigation',
		));
		if (in_array($post_type, $skip_post_types)) return;
		
		add_action('add_meta_boxes', array(self::$instance, 'add_plugin_meta_box'), 10, 2);

		do_action('essgrid_prepare_add_plugin_meta_box', $post_type);
	}

	/**
	 * Register the meta box in post / pages
	 */
	public function add_plugin_meta_box($post_type, $post)
	{
		add_meta_box(
			'eg-meta-box', 
			esc_attr__('Essential Grid', ESG_TEXTDOMAIN), 
			array(self::$instance, 'display_plugin_meta_box'), 
			$post_type, 
			'normal', 
			'high', 
			array($post)
		);
		do_action('essgrid_add_plugin_meta_box', $post_type, $post);
	}

	/**
	 * Display the meta box
	 */
	public static function display_plugin_meta_box($post)
	{
		require_once(ESG_PLUGIN_ADMIN_PATH . '/views/elements/' . self::VIEW_META_BOX . '.php');
		do_action('essgrid_display_plugin_meta_box', $post);
	}

	/**
	 * Register the meta box save in post / pages
	 */
	public function add_plugin_meta_box_save($post_id)
	{
		// Bail if we're doing an auto save
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
		self::custom_meta_box_save($post_id, $_POST);
		do_action('essgrid_add_plugin_meta_box_save', $post_id);
	}

	/**
	 * This function deletes transient of certain grids where the Post is included in
	 * @since: 1.2.0
	 */
	public static function check_for_transient_deletion($post_id)
	{
		$categories = self::get_custom_taxonomies_by_post_id($post_id);
		$tags = get_the_tags($post_id);

		$cat = array();
		if (!empty($categories) || !empty($tags)) {
			if (!empty($categories)) {
				foreach ($categories as $c) {
					$cat[$c->taxonomy][$c->term_id] = true;
				}
			}
			if (!empty($tags)) {
				foreach ($categories as $c) {
					$cat[$c->taxonomy][$c->term_id] = true;
				}
			}

			//get all grids, then check all grids
			$grids = Essential_Grid::get_essential_grids();
			if (!empty($grids)) {
				foreach ($grids as $grid) {
					$selected = json_decode($grid->postparams, true);
					$post_category = self::getVar($selected, 'post_category');
					$cat_tax = self::getCatAndTaxData($post_category);
					$cats = array();
					if (!empty($cat_tax['cats']))
						$cats = explode(',', $cat_tax['cats']);

					$taxes = array('post_tag');
					if (!empty($cat_tax['tax']))
						$taxes = explode(',', $cat_tax['tax']);

					$cont = false;
					if (!empty($cats)) {
						foreach ($taxes as $tax) {
							foreach ($cats as $c) {
								if (isset($cat[$tax][$c])) { 
									//if set, cache of grid needs to be killed
									self::clear_transients('ess_grid_trans_query_' . $grid->id);
									self::clear_transients('ess_grid_trans_full_grid_' . $grid->id);
									
									$cont = true;
								}
								if ($cont) break;
							}
							if ($cont) break;
						}
					}
				}
			}
		}

		do_action('essgrid_check_for_transient_deletion', $post_id);
	}

	/**
	 * Adds functionality to do certain things on an upgrade
	 * @since: 1.1.0
	 */
	public static function do_update_checks()
	{
		$grid_ver = Essential_Grid_Db::get_version();
		$updates = new Essential_Grid_Plugin_Update($grid_ver);
		$updates->do_update_process();
		do_action('essgrid_do_update_checks', $grid_ver);
	}

	/**
	 * Include wanted page
	 * 
	 * @return bool
	 */
	public static function custom_meta_box_save($post_id, $metas, $ajax = false)
	{
		$metas = apply_filters('essgrid_custom_meta_box_save', $metas, $post_id, $ajax);

		// if our nonce isn't there, or we can't verify it, bail
		if (!isset($metas['essential_grid_meta_box_nonce']) || !wp_verify_nonce($metas['essential_grid_meta_box_nonce'], 'eg_meta_box_nonce')) return false;

		if (isset($metas['eg_sources_html5_mp4']))
			update_post_meta($post_id, 'eg_sources_html5_mp4', esc_attr($metas['eg_sources_html5_mp4']));

		if (isset($metas['eg_sources_html5_ogv']))
			update_post_meta($post_id, 'eg_sources_html5_ogv', esc_attr($metas['eg_sources_html5_ogv']));

		if (isset($metas['eg_sources_html5_webm']))
			update_post_meta($post_id, 'eg_sources_html5_webm', esc_attr($metas['eg_sources_html5_webm']));

		if (isset($metas['eg_sources_youtube']))
			update_post_meta($post_id, 'eg_sources_youtube', esc_attr($metas['eg_sources_youtube']));

		if (isset($metas['eg_sources_vimeo']))
			update_post_meta($post_id, 'eg_sources_vimeo', esc_attr($metas['eg_sources_vimeo']));

		if (isset($metas['eg_sources_wistia']))
			update_post_meta($post_id, 'eg_sources_wistia', esc_attr($metas['eg_sources_wistia']));

		if (isset($metas['eg_sources_image']))
			update_post_meta($post_id, 'eg_sources_image', esc_attr($metas['eg_sources_image']));

		if (isset($metas['eg_sources_iframe']))
			update_post_meta($post_id, 'eg_sources_iframe', esc_attr($metas['eg_sources_iframe']));

		if (isset($metas['eg_sources_soundcloud']))
			update_post_meta($post_id, 'eg_sources_soundcloud', esc_attr($metas['eg_sources_soundcloud']));

		if (isset($metas['eg_settings_type']))
			update_post_meta($post_id, 'eg_settings_type', esc_attr($metas['eg_settings_type']));

		if (isset($metas['eg_settings_custom_display']))
			update_post_meta($post_id, 'eg_settings_custom_display', esc_attr($metas['eg_settings_custom_display']));

		if (isset($metas['eg_vimeo_ratio']))
			update_post_meta($post_id, 'eg_vimeo_ratio', esc_attr($metas['eg_vimeo_ratio']));

		if (isset($metas['eg_youtube_ratio']))
			update_post_meta($post_id, 'eg_youtube_ratio', esc_attr($metas['eg_youtube_ratio']));

		if (isset($metas['eg_wistia_ratio']))
			update_post_meta($post_id, 'eg_wistia_ratio', esc_attr($metas['eg_wistia_ratio']));

		if (isset($metas['eg_html5_ratio']))
			update_post_meta($post_id, 'eg_html5_ratio', esc_attr($metas['eg_html5_ratio']));

		if (isset($metas['eg_soundcloud_ratio']))
			update_post_meta($post_id, 'eg_soundcloud_ratio', esc_attr($metas['eg_soundcloud_ratio']));

		if (isset($metas['eg_image_fit']))
			update_post_meta($post_id, 'eg_image_fit', esc_attr($metas['eg_image_fit']));

		if (isset($metas['eg_image_repeat']))
			update_post_meta($post_id, 'eg_image_repeat', esc_attr($metas['eg_image_repeat']));

		if (isset($metas['eg_image_align_h']))
			update_post_meta($post_id, 'eg_image_align_h', esc_attr($metas['eg_image_align_h']));

		if (isset($metas['eg_image_align_v']))
			update_post_meta($post_id, 'eg_image_align_v', esc_attr($metas['eg_image_align_v']));

		/* 2.2 ?? */
		if (isset($metas['eg_sources_revslider'])) {
			update_post_meta($post_id, 'eg_sources_revslider', esc_attr($metas['eg_sources_revslider']));
		}

		if (isset($metas['eg_sources_essgrid']))
			update_post_meta($post_id, 'eg_sources_essgrid', esc_attr($metas['eg_sources_essgrid']));

		if (isset($metas['eg_featured_grid']))
			update_post_meta($post_id, 'eg_featured_grid', esc_attr($metas['eg_featured_grid']));

		/**
		 * Save Custom Meta Things that Modify Skins
		 **/
		if (isset($metas['eg-custom-meta-skin']))
			update_post_meta($post_id, 'eg_settings_custom_meta_skin', $metas['eg-custom-meta-skin']);
		else
			update_post_meta($post_id, 'eg_settings_custom_meta_skin', '');

		if (isset($metas['eg-custom-meta-element']))
			update_post_meta($post_id, 'eg_settings_custom_meta_element', $metas['eg-custom-meta-element']);
		else
			update_post_meta($post_id, 'eg_settings_custom_meta_element', '');

		if (isset($metas['eg-custom-meta-setting']))
			update_post_meta($post_id, 'eg_settings_custom_meta_setting', $metas['eg-custom-meta-setting']);
		else
			update_post_meta($post_id, 'eg_settings_custom_meta_setting', '');

		if (isset($metas['eg-custom-meta-style']))
			update_post_meta($post_id, 'eg_settings_custom_meta_style', $metas['eg-custom-meta-style']);
		else
			update_post_meta($post_id, 'eg_settings_custom_meta_style', '');

		if (isset($metas['eg_custom_meta_216']))
			update_post_meta($post_id, 'eg_custom_meta_216', $metas['eg_custom_meta_216']);

		if (!is_numeric(get_post_meta($post_id, 'eg_votes_count', true))) {
			update_post_meta($post_id, 'eg_votes_count', 0);
		}

		/**
		 * Save Custom Meta from Custom Meta Submenu
		 */
		$m = new Essential_Grid_Meta();
		$cmetas = $m->get_all_meta(false);
		if (!empty($cmetas)) {
			foreach ($cmetas as $meta) {
				if (isset($metas['eg-' . $meta['handle']])) {
					if ($meta['type'] == 'multi-select') {
						// multi select values come in two formats:
						// from post / page - array of values
						// from esg editor - string of values, separated by comma, located in first array item, we need to convert it to array
						if ($ajax !== false) {
							$metas['eg-' . $meta['handle']] = explode(',', $metas['eg-' . $meta['handle']][0]);
						}
					}
					if (is_array($metas['eg-' . $meta['handle']])) $metas['eg-' . $meta['handle']] = json_encode($metas['eg-' . $meta['handle']], JSON_UNESCAPED_UNICODE);
					update_post_meta($post_id, 'eg-' . $meta['handle'], $metas['eg-' . $meta['handle']]);
				}
			}
		}

		do_action('essgrid_custom_meta_box_save', $metas, $post_id, $ajax);

		if ($ajax !== false) return true;
	}

	/**
	 * Include wanted page
	 */
	public function display_plugin_admin_page()
	{
		//set view
		self::$view = self::getGetVar("view");
		if (empty(self::$view))
			self::$view = self::VIEW_OVERVIEW;

		$add_folder = '';
		//require styles by view
		switch (self::$view) {
			case self::VIEW_OVERVIEW:
			case self::VIEW_GRID_CREATE:
			case self::VIEW_GRID:
				break;
			case self::VIEW_ITEM_SKIN_EDITOR:
				$add_folder = 'elements/';
				break;
			default: //go back to default
				self::$view = self::VIEW_OVERVIEW;
		}

		try {
			require_once(ESG_PLUGIN_ADMIN_PATH . '/views/header.php');
			$r = apply_filters('essgrid_display_plugin_admin_page_pre', array('add_folder' => $add_folder, 'view' => self::$view));
			require_once(ESG_PLUGIN_ADMIN_PATH . '/views/' . $r['add_folder'] . $r['view'] . '.php');
			$r = apply_filters('essgrid_display_plugin_admin_page_post', array('add_folder' => $add_folder, 'view' => self::$view));
			require_once(ESG_PLUGIN_ADMIN_PATH . '/views/footer.php');
		} catch (Exception $e) {
			echo "<br><br>View (" . self::$view . ") Error: <b>" . $e->getMessage() . "</b>";
		}
	}

	/**
	 * Include wanted submenu page
	 */
	public function display_plugin_submenu_page_item_skin()
	{
		do_action('essgrid_display_plugin_submenu_page_item_skin_pre');
		self::display_plugin_submenu('grid-item-skin');
		do_action('essgrid_display_plugin_submenu_page_item_skin_post');
	}

	/**
	 * Include wanted submenu page
	 */
	public function display_plugin_submenu_page_import_export()
	{
		do_action('essgrid_display_plugin_submenu_page_import_export_pre');
		self::display_plugin_submenu('grid-import-export');
		do_action('essgrid_display_plugin_submenu_page_import_export_post');
	}

	/**
	 * Include wanted submenu page
	 * Since 1.0.6
	 */
	public function display_plugin_submenu_page_widget_areas()
	{
		do_action('essgrid_display_plugin_submenu_page_widget_areas_pre');
		self::display_plugin_submenu('grid-widget-areas');
		do_action('essgrid_display_plugin_submenu_page_widget_areas_post');
	}

	/**
	 * Include wanted submenu page
	 * Since 2.1.0
	 */
	public function display_plugin_submenu_page_global_settings()
	{
		do_action('essgrid_display_plugin_submenu_page_global_settings_pre');
		self::display_plugin_submenu('grid-global-settings');
		do_action('essgrid_display_plugin_submenu_page_global_settings_post');
	}

	/**
	 * Include wanted submenu page
	 */
	public function display_plugin_submenu($subMenu)
	{
		if (empty($subMenu))
			$subMenu = self::VIEW_SUB_ITEM_SKIN_OVERVIEW;

		//require styles by view
		switch ($subMenu) {
			case self::VIEW_SUB_ITEM_SKIN_OVERVIEW:
			case self::VIEW_SUB_CUSTOM_META:
			case self::VIEW_GOOGLE_FONTS:
			case self::VIEW_IMPORT_EXPORT:
			case self::VIEW_GLOBAL_SETTINGS:
			case self::VIEW_WIDGET_AREAS:
			case self::VIEW_SEARCH:
				break;
			default: 
				//go back to default
				$subMenu = self::VIEW_SUB_ITEM_SKIN_OVERVIEW;
		}

		try {
			require_once(ESG_PLUGIN_ADMIN_PATH . '/views/header.php');
			$subMenu = apply_filters('essgrid_display_plugin_submenu_pre', $subMenu);
			require_once(ESG_PLUGIN_ADMIN_PATH . '/views/' . $subMenu . '.php');
			$subMenu = apply_filters('essgrid_display_plugin_submenu_post', $subMenu);
			require_once(ESG_PLUGIN_ADMIN_PATH . '/views/footer.php');
		} catch (Exception $e) {
			echo "<br><br>View ($subMenu) Error: <b>" . $e->getMessage() . "</b>";
		}
	}

	/**
	 * Set Menu Role
	 * @param string $role  administrator | author etc...
	 */
	private function setMenuRole($role)
	{
		self::$menuRole = apply_filters('essgrid_setMenuRole', $role);
	}

	/**
	 * Get Menu Role
	 * @return string  the current role
	 */
	public static function getPluginPermission()
	{
		switch (self::$menuRole) {
			case self::ROLE_AUTHOR:
				$role = "edit_published_posts";
				break;
			case self::ROLE_EDITOR:
				$role = "edit_pages";
				break;
			default:
			case self::ROLE_ADMIN:
				$role = "manage_options";
				break;
		}

		return apply_filters('essgrid_getPluginPermission', $role);
	}

	/**
	 * Get Menu Role
	 * @return    string    $role    the current role
	 */
	public static function getPluginPermissionValue()
	{
		$role = self::$menuRole;
		switch (self::$menuRole) {
			case self::ROLE_AUTHOR:
			case self::ROLE_EDITOR:
			case self::ROLE_ADMIN:
				break;
			default:
				$role = self::ROLE_ADMIN;
				break;
		}

		return apply_filters('essgrid_getPluginPermissionValue', $role);
	}

	/**
	 * Save Menu Role
	 * @return    boolean true
	 */
	private static function savePluginPermission($newPermission)
	{
		$return = true;

		switch ($newPermission) {
			case self::ROLE_AUTHOR:
			case self::ROLE_EDITOR:
			case self::ROLE_ADMIN:
				break;
			default:
				$return = false;
				break;
		}

		$r = apply_filters('essgrid_getPluginPermissionValue', array('return' => $return, 'newPermission' => $newPermission));
		if ($r['return'] === true) {
			update_option('tp_eg_role', $r['newPermission']);
		}

		return $r['return'];
	}

	/**
	 * Allow for VC to use this plugin
	 * 
	 * @return void
	 */
	public static function visual_composer_include()
	{
		if (!function_exists('vc_map')) return;
		add_action('init', array('Essential_Grid_Admin', 'add_to_VC'));
		do_action('essgrid_visual_composer_include');
	}

	public static function add_to_VC()
	{
		global $esg_dev_mode, $wp_version;

		if (strpos($wp_version, '-') !== false) {
			$_wpver = explode('-', $wp_version);
			$wp_version = $_wpver[0];
		}
		if ($esg_dev_mode) {
			wp_enqueue_script('esg-perfect-scrollbar-script', plugins_url('assets/js/plugins/dev/esg-perfect-scrollbar.js', __FILE__), array('jquery'), ESG_REVISION);
			wp_enqueue_script('esg-tooltipser-script', plugins_url('assets/js/plugins/dev/tooltipster.js', __FILE__), array('jquery'), ESG_REVISION);

			wp_enqueue_script('esg-admin-script', plugins_url('assets/js/modules/dev/admin.js', __FILE__), array('jquery'), ESG_REVISION);
			wp_enqueue_script('esg-admin-sortable-script', plugins_url('assets/js/modules/dev/sortable.js', __FILE__), array('jquery'), ESG_REVISION);
			wp_enqueue_script('esg-admin-dialog-script', plugins_url('assets/js/modules/dev/dialog.js', __FILE__), array('jquery'), ESG_REVISION);
			wp_enqueue_script('esg-admin-addons-script', plugins_url('assets/js/modules/dev/addons.js', __FILE__), array('jquery'), ESG_REVISION);
			wp_enqueue_script('esg-admin-templates-script', plugins_url('assets/js/modules/dev/templates.js', __FILE__), array('jquery'), ESG_REVISION);
			wp_enqueue_script('esg-admin-overview-script', plugins_url('assets/js/modules/dev/overview.js', __FILE__), array('jquery'), ESG_REVISION);
			wp_enqueue_script('esg-admin-tip-script', plugins_url('assets/js/modules/dev/tip.js', __FILE__), array('jquery'), ESG_REVISION);
			wp_enqueue_script('esg-admin-scroll-tabs-script', plugins_url('assets/js/modules/dev/scroll-tabs.js', __FILE__), array('jquery'), ESG_REVISION);
		} else {
			wp_enqueue_script('esg-admin-script', plugins_url('assets/js/modules/admin.min.js', __FILE__), array('jquery'), ESG_REVISION);
		}
		wp_enqueue_script('wpdialogs', 'jquery-ui-dialog');
		if (version_compare($wp_version, '5.6', '<')) {
			wp_enqueue_script('jquery-ui-sortable');
		}
		wp_enqueue_style('wp-jquery-ui-dialog');

		vc_map(apply_filters('essgrid_add_to_VC', array(
				'name' => esc_attr__('Essential Grid', ESG_TEXTDOMAIN),
				'base' => 'ess_grid',
				'icon' => 'icon-wpb-ess-grid',
				'category' => esc_attr__('Content', ESG_TEXTDOMAIN),
				'show_settings_on_create' => false,
				'js_view' => 'VcEssentialGrid',
				'params' => array(
					array(
						'type' => 'ess_grid_shortcode',
						'heading' => esc_attr__('Alias', ESG_TEXTDOMAIN),
						'param_name' => 'alias',
						'admin_label' => true,
						'value' => ''
					),
					array(
						'type' => 'ess_grid_shortcode',
						'heading' => esc_attr__('Settings', ESG_TEXTDOMAIN),
						'param_name' => 'settings',
						'admin_label' => true,
						'value' => ''
					),
					array(
						'type' => 'ess_grid_shortcode',
						'heading' => esc_attr__('Layers', ESG_TEXTDOMAIN),
						'param_name' => 'layers',
						'admin_label' => true,
						'value' => ''
					),
					array(
						'type' => 'ess_grid_shortcode',
						'heading' => esc_attr__('Special', ESG_TEXTDOMAIN),
						'param_name' => 'special',
						'admin_label' => true,
						'value' => ''
					)
				)
		)));

		if (version_compare(WPB_VC_VERSION, '4.4', '>=')) { 
			//use if 4.4 or newer
			vc_add_shortcode_param('ess_grid_shortcode', array('Essential_Grid_Admin', 'ess_grid_shortcode_settings_field'));
		} else { 
			//use if older than 4.4
			add_shortcode_param('ess_grid_shortcode', array('Essential_Grid_Admin', 'ess_grid_shortcode_settings_field'));
		}

		do_action('essgrid_add_to_VC');
	}

	/**
	 * The Dialog for Visual Composer
	 * @since: 1.2.0
	 */
	public static function ess_grid_shortcode_settings_field($settings, $value)
	{
		$dependency = vc_generate_dependencies_attributes($settings);

		return apply_filters('essgrid_ess_grid_shortcode_settings_field', '<div class="ess_grid_shortcode_block">'
				. '<input id="esg-vc-input-' . $settings['param_name'] . '" name="' . $settings['param_name']
				. '" class="wpb_vc_param_value wpb-textinput '
				. $settings['param_name'] . ' ' . $settings['type'] . '_field" type="text" value="'
				. $value . '" ' . $dependency . '/>'
				. '</div>', $settings, $value);
	}

	/**
	 * update grid name
	 * 
	 * @param array $data
	 * @return string|bool
	 */
	public static function update_grid_name($data)
	{
		global $wpdb;

		$data = apply_filters('essgrid_update_grid_name', $data);
		if (!isset($data['id']) || intval($data['id']) == 0) return esc_attr__('Invalid ID', ESG_TEXTDOMAIN);
		if (!isset($data['name']) || strlen($data['name']) < 2) return esc_attr__('Title needs to have at least 2 characters', ESG_TEXTDOMAIN);
		
		$table_name = Essential_Grid_Db::get_table('grids');
		$response = $wpdb->update($table_name, $data, array('id' => $data['id']));
		if ($response === false) return esc_attr__('Grid could not be changed', ESG_TEXTDOMAIN);

		return true;
	}

	/**
	 * get first post for post-based grid
	 * 
	 * @param array $grid
	 * @return array
	 */
	public static function get_grid_first_post($grid)
	{
		$start_sortby = Essential_Grid_Base::getVar($grid['params'], 'sorting-order-by-start', 'none');
		$start_sortby_type = Essential_Grid_Base::getVar($grid['params'], 'sorting-order-type', 'ASC');
		$page_ids = explode(',', Essential_Grid_Base::getVar($grid['postparams'], 'selected_pages', '-1'));
		$post_types = Essential_Grid_Base::getVar($grid['postparams'], 'post_types');
		$post_category = Essential_Grid_Base::getVar($grid['postparams'], 'post_category');
		$cat_relation = Essential_Grid_Base::getVar($grid['postparams'], 'category-relation', 'OR');
		$cat_tax = Essential_Grid_Base::getCatAndTaxData($post_category);
		$additional_query = Essential_Grid_Base::getVar($grid['postparams'], 'additional-query');
		if ($additional_query !== '')
			$additional_query = wp_parse_args($additional_query);

		return Essential_Grid_Base::getPostsByCategory(0, $cat_tax['cats'], $post_types, $cat_tax['tax'], $page_ids, $start_sortby, $start_sortby_type, 1, $additional_query, false, $cat_relation);
	}

	/**
	 * Update/Create Grid
	 * 
	 * @param array $data
	 * @return boolean
	 */
	public static function update_create_grid($data)
	{
		global $wpdb;

		$data = apply_filters('essgrid_update_create_grid', $data);
		if (!isset($data['name']) || strlen($data['name']) < 2) return esc_attr__('Title needs to have at least 2 characters', ESG_TEXTDOMAIN);
		if (!isset($data['handle']) || strlen($data['handle']) < 2) return esc_attr__('Alias needs to have at least 2 characters', ESG_TEXTDOMAIN);
		if (preg_replace('/[^a-zA-Z0-9 \-_]/', '', $data['handle']) != $data['handle']) return esc_attr__('Alias contain forbidden characters!', ESG_TEXTDOMAIN);
		if (empty($data['params'])) return esc_attr__('No setting informations received!', ESG_TEXTDOMAIN);

		if ($data['postparams']['source-type'] == 'custom') {
			if (empty($data['layers'])) return esc_attr__('Please add at least one element in Custom Grid mode', ESG_TEXTDOMAIN);
		} elseif ($data['postparams']['source-type'] == 'post') {
			if (empty($data['postparams']['post_types'])) return esc_attr__('Please select a Post Type', ESG_TEXTDOMAIN);
		} elseif (!isset($data['postparams']['source-type'])) {
			return esc_attr__('Invalid data received, this could be the cause of server limitations. If you use a custom grid, please lower the number of entries.', ESG_TEXTDOMAIN);
		}

		// layers used for source-type = custom
		if (empty($data['layers'])) $data['layers'] = array();
		
		// add grid thumb
		if (empty($data['settings'])) {
			$data['settings'] = array();
		} else if (!is_array($data['settings'])) {
			$data['settings'] = json_decode($data['settings'], true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				$data['settings'] = array();
			}
		}
		
		switch ($data['postparams']['source-type']) {
			case 'custom':
				// get first layer image
				if (empty($data['layers'][0])) break;
				
				$layer = json_decode($data['layers'][0], true);
				if (json_last_error() !== JSON_ERROR_NONE || empty($layer['custom-image'])) break;

				$img = wp_get_attachment_image_src($layer['custom-image'], 'full');
				if (!empty($img[0])) $data['settings']['bg'] = $img[0];
				break;
				
			case 'post':
				// get first post image
				$posts = self::get_grid_first_post($data);
				if (empty($posts[0]['ID'])) break;
				
				$attach_id = get_post_thumbnail_id($posts[0]['ID']);
				if ($attach_id) {
					$img = wp_get_attachment_image_src($attach_id, 'full');
					if (!empty($img[0])) $data['settings']['bg'] = $img[0];
				}
				break;
		}

		$data = apply_filters('essgrid_before_update_create_grid', $data);
		$data_db = array(
			'name' => $data['name'],
			'handle' => $data['handle'],
			'postparams' => json_encode($data['postparams']),
			'params' => json_encode($data['params']),
			'layers' => json_encode($data['layers']),
			'settings' => json_encode($data['settings']),
			'last_modified' => date('Y-m-d H:i:s')
		);
		
		$table_name = Essential_Grid_Db::get_table('grids');
		if (isset($data['id']) && intval($data['id']) > 0) { //update
			//check if entry with handle exists, because this is unique
			$grid = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE handle = %s AND id != %s ", $data['handle'], $data['id']), ARRAY_A);
			if (!empty($grid)) {
				return esc_attr__('Ess. Grid with chosen alias already exists, please choose a different alias', ESG_TEXTDOMAIN);
			}

			//check if exists, if yes, update
			$entry = Essential_Grid::get_essential_grid_by_id($data['id']);
			if ($entry !== false) {
				$response = $wpdb->update($table_name, $data_db, array('id' => $data['id']));
				if ($response === false) return esc_attr__('Ess. Grid could not be changed', ESG_TEXTDOMAIN);

				return true;
			}
		}

		//check if entry with handle exists, because this is unique
		$grid = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE handle = %s", $data['handle']), ARRAY_A);
		if (!empty($grid)) {
			return esc_attr__('Ess. Grid with chosen alias already exists, please choose a different alias', ESG_TEXTDOMAIN);
		}

		//insert if function did not return yet
		$response = $wpdb->insert($table_name, $data_db);
		if ($response === false) return false;

		return true;
	}

	/**
	 * Delete Grid
	 * @return    boolean true
	 */
	private static function delete_grid_by_id($data)
	{
		global $wpdb;

		$data = apply_filters('essgrid_delete_grid_by_id', $data);
		if (!isset($data['id']) || intval($data['id']) == 0) return esc_attr__('Invalid ID', ESG_TEXTDOMAIN);

		$table_name = Essential_Grid_Db::get_table('grids');
		$response = $wpdb->delete($table_name, array('id' => $data['id']));
		do_action('essgrid_on_delete_grid_by_id', $response, $data);
		if ($response === false) return esc_attr__('Ess. Grid could not be deleted', ESG_TEXTDOMAIN);

		return true;
	}

	/**
	 * Duplicate Grid
	 * 
	 * @param array $data
	 * @return  string|array
	 */
	private static function duplicate_grid_by_id($data)
	{
		global $wpdb;

		$table_name = Essential_Grid_Db::get_table('grids');
		$data = apply_filters('essgrid_duplicate_grid_by_id', $data);
		if (!isset($data['id']) || intval($data['id']) == 0) return esc_attr__('Invalid ID', ESG_TEXTDOMAIN);

		//check if ID exists
		$duplicate = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %s", $data['id']), ARRAY_A);
		if (empty($duplicate)) return esc_attr__('Grid could not be duplicated', ESG_TEXTDOMAIN);

		//check if handle 'grid-n' does exist
		$i = $data['id'] - 1;
		do {
			$i++;
			$result = $wpdb->get_row($wpdb->prepare("SELECT id FROM $table_name WHERE handle = %s", 'grid-' . $i), ARRAY_A);
		} while (!empty($result));

		//now add new Entry
		unset($duplicate['id']);
		$duplicate['name'] = 'Copy of ' . $duplicate['name'] . ' ' . $i;
		$duplicate['handle'] = 'grid-' . $i;

		$response = $wpdb->insert($table_name, $duplicate);
		if ($response === false) return esc_attr__('Grid could not be duplicated', ESG_TEXTDOMAIN);

		$duplicate['id'] = $wpdb->insert_id;
		/**
		 * @param array $duplicate  duplicated grid data with new ID
		 * @param int|false  $wpdb->insert result, The number of rows inserted, or false on error
		 */
		do_action('essgrid_duplicate_grid_by_id', $duplicate, $response);

		return $duplicate;
	}

	/**
	 * get grid settings
	 * 
	 * @param int $id
	 * @return false|array
	 * @throws Exception
	 */
	public static function get_grid_settings($id)
	{
		global $wpdb;

		$id = apply_filters('essgrid_get_grid_settings', $id);
		$id = intval($id);
		if ($id === 0) return false;

		$table_name = Essential_Grid_Db::get_table('grids');
		$row = $wpdb->get_row($wpdb->prepare("SELECT settings FROM $table_name WHERE id = %s", $id), ARRAY_A);
		
		$settings = json_decode($row['settings'], true);
		if (json_last_error() !== JSON_ERROR_NONE || !is_array($settings)) {
			$settings = array();
		}
		
		return $settings;
	}

	/**
	 * @param array $data
	 * @param int $id
	 * @return int|false  The number of rows updated, or false on error.
	 * @throws Exception
	 */
	public static function set_grid_settings($data, $id)
	{
		global $wpdb;

		$table_name = Essential_Grid_Db::get_table('grids');
		return $wpdb->update($table_name, array('settings' => json_encode($data)), array('id' => $id));
	}

	/**
	 * Toggle Favorite State of Grid
	 */
	public static function toggle_favorite_by_id($id)
	{
		global $wpdb;
		
		$return = array(
			'error' => esc_attr__('Grid not found', ESG_TEXTDOMAIN),
			'data' => '', 
		);

		$settings = self::get_grid_settings($id);
		if ($settings === false) return $return;

		if (!isset($settings['favorite']) || $settings['favorite'] == 'false') {
			$settings['favorite'] = 'true';
		} else {
			$settings['favorite'] = 'false';
		}

		$result = self::set_grid_settings($settings, $id);
		if (!$result) {
			$return['error'] = esc_attr__('Grid could not be changed!', ESG_TEXTDOMAIN);
			return $return;
		}

		return array(
			'error' => false,
			'data' => array('settings' => $settings),
		);
	}

	/**
	 * Handle Ajax Requests
	 */
	public static function on_ajax_action()
	{
		$clientAction = self::getPostVar("client_action", false);
		$action_allowed = false;
		$no_permission_actions = array(
			'load_more_items',
			'load_more_content',
			'get_grid_search_ids',
			'load_post_content',
		);
		
		$current_user = wp_get_current_user();
		if ( 0 != $current_user->ID ) {
			switch (self::$menuRole) {
				case self::ROLE_AUTHOR:
					$roles = array('administrator', 'editor', 'author');
					break;
				case self::ROLE_EDITOR:
					$roles = array('administrator', 'editor');
					break;
				case self::ROLE_ADMIN:
					$roles = array('administrator');
					break;
				default:
					$roles = array();
			}
			if (!empty(array_intersect($roles, $current_user->roles))) $action_allowed = true;
		}
		
		//allow no_permission_actions for all
		if (in_array($clientAction, $no_permission_actions)) $action_allowed = true;
		
		try {
			$token = self::getPostVar('token', false);

			//verify the token
			$isVerified = wp_verify_nonce($token, 'Essential_Grid_actions');

			$error = false;
			if ($action_allowed && $isVerified) {
				$data = apply_filters('essgrid_on_ajax_action_data', self::getPostVar("data", false));
				
				switch ($clientAction) {
						
					case 'add_widget_area':
						$wa = new Essential_Grid_Widget_Areas();
						$result = $wa->add_new_sidebar($data);
						if ($result === true) {
							Essential_Grid::ajaxResponseSuccess(esc_attr__("Widget Area successfully created!", ESG_TEXTDOMAIN), array('data' => true, 'is_redirect' => true, 'redirect_url' => self::getSubViewUrl(Essential_Grid_Admin::VIEW_SUB_WIDGET_AREA_AJAX)));
						} else {
							$error = is_string($result) ? $result : esc_attr__('Failed to create Widget Area', ESG_TEXTDOMAIN);
						}
						break;
						
					case 'edit_widget_area':
						$wa = new Essential_Grid_Widget_Areas();
						$result = $wa->edit_widget_area_by_handle($data);
						if ($result === true) {
							Essential_Grid::ajaxResponseSuccess(esc_attr__("Widget Area successfully changed!", ESG_TEXTDOMAIN), array('data' => true));
						} else {
							$error = is_string($result) ? $result : esc_attr__('Failed to change Widget Area', ESG_TEXTDOMAIN);
						}
						break;
						
					case 'remove_widget_area':
						$wa = new Essential_Grid_Widget_Areas();
						$result = $wa->remove_widget_area_by_handle($data['handle']);
						if ($result === true) {
							Essential_Grid::ajaxResponseSuccess(esc_attr__("Widget Area successfully removed!", ESG_TEXTDOMAIN), array('data' => true));
						} else {
							$error = is_string($result) ? $result : esc_attr__('Failed to change Widget Area', ESG_TEXTDOMAIN);
						}
						break;
						
					case 'get_preview_html_markup':
						if (isset($data['id'])) {
							self::clear_transients('ess_grid_trans_query_' . $data['id']);
						}

						$result = self::output_demo_skin_html($data);
						if (isset($result['error'])) {
							Essential_Grid::ajaxResponseData($result);
						} else {
							$html_result = $result['html'];
							if (empty($html_result)) {
								$html_result = Essential_Grid_Admin::empty_grid_markup();
							}
							Essential_Grid::ajaxResponseData(array("data" => array('html' => $html_result, 'preview' => self::getVar($result, 'preview'))));
						}
						break;
						
					case 'save_color_preset':
						$presets = (isset($data['presets'])) ? $data['presets'] : array();
						$color_presets = ESGColorpicker::save_color_presets($presets);
						Essential_Grid::ajaxResponseData(array('presets' => $color_presets));
						break;
						
					case 'save_search_settings':
						if (!empty($data)) {
							update_option('esg-search-settings', $data);
						}
						Essential_Grid::ajaxResponseSuccess(esc_attr__("Search Settings succesfully saved!", ESG_TEXTDOMAIN));
						break;
						
					case 'update_general_settings':
						$result = self::savePluginPermission($data['permission']);
						if ($result !== true) {
							$error = esc_attr__("Global Settings has not changed!", ESG_TEXTDOMAIN);
							break;
						}

						$cur_query = get_option('tp_eg_query_type', 'wp_query');
						if ($cur_query !== $data['query_type']) {
							//delete cache
							self::clear_transients('ess_grid_trans_');
						}

						update_option('tp_eg_output_protection', self::getVar($data, 'protection'));
						update_option('tp_eg_tooltips', self::getVar($data, 'tooltips'));
						update_option('tp_eg_js_to_footer', self::getVar($data, 'js_to_footer'));
						update_option('tp_eg_use_cache', self::getVar($data, 'use_cache'));
						update_option('tp_eg_query_type', self::getVar($data, 'query_type'));
						update_option('tp_eg_use_lightbox', self::getVar($data, 'use_lightbox'));
						update_option('tp_eg_enable_log', self::getVar($data, 'enable_log'));
						update_option('tp_eg_show_stream_failure_msg', self::getVar($data, 'show_stream_failure_msg'));
						update_option('tp_eg_stream_failure_custom_msg', self::getVar($data, 'stream_failure_custom_msg'));
						update_option('tp_eg_enable_post_meta', self::getVar($data, 'enable_post_meta'));
						update_option('tp_eg_enable_custom_post_type', self::getVar($data, 'enable_custom_post_type'));
						update_option('tp_eg_enable_extended_search', self::getVar($data, 'enable_extended_search'));
						update_option('tp_eg_global_default_img', self::getVar($data, 'global_default_img'));
						update_option('tp_eg_no_filter_match_message', self::getVar($data, 'no_filter_match_message'));
						update_option('tp_eg_global_enable_pe7', self::getVar($data, 'enable_pe7'));
						update_option('tp_eg_global_enable_fontello', self::getVar($data, 'enable_fontello'));
						update_option('tp_eg_global_enable_font_awesome', self::getVar($data, 'enable_font_awesome'));
						update_option('tp_eg_enable_youtube_nocookie', self::getVar($data, 'enable_youtube_nocookie'));
						Essential_Grid_Base::setExcludePostTypes(self::getVar($data, 'exclude_post_types'));

						Essential_Grid::ajaxResponseSuccess(esc_attr__("Global Settings succesfully saved!", ESG_TEXTDOMAIN), true);
						break;
						
					case 'dismiss_dynamic_notice':
						if (trim($data['id']) !== 'DISCARD') {
							$notices_discarded = get_option('essential-notices-dc', array());
							$notices_discarded[] = esc_attr(trim($data['id']));
							update_option('essential-notices-dc', $notices_discarded);
						} else {
							update_option('essential-deact-notice', false);
						}
						Essential_Grid::ajaxResponseSuccess(esc_attr__(".", ESG_TEXTDOMAIN));
						break;
						
					case 'update_create_grid':
						$result = self::update_create_grid($data);
						if ($result !== true) {
							$error = $result;
						} else {
							if (isset($data['id']) && intval($data['id']) > 0) {
								self::clear_transients('ess_grid_trans_query_' . $data['id']);
								self::clear_transients('ess_grid_trans_full_grid_' . $data['id']);
								Essential_Grid::ajaxResponseSuccess(esc_attr__("Grid successfully saved/changed!", ESG_TEXTDOMAIN), true);
							} else {
								$grid_id = false;
								$esg_alias = $data['handle'];
								$grids = Essential_Grid::get_essential_grids();

								foreach ($grids as $grid) {

									$alias = $grid->handle;
									if ($alias === $esg_alias) {
										$grid_id = $grid->id;
										break;
									}
								}
								Essential_Grid::ajaxResponseSuccess(esc_attr__("Grid successfully saved/changed!", ESG_TEXTDOMAIN), array('data' => true, 'is_redirect' => false, 'redirect_url' => self::getViewUrl(Essential_Grid_Admin::VIEW_OVERVIEW), 'grid_id' => $grid_id));
							}
						}
						break;

					case 'update_grid_name':
						$result = self::update_grid_name($data);
						if ($result !== true) {
							$error = $result;
						} else {
							Essential_Grid::ajaxResponseSuccess(esc_attr__("Grid title updated", ESG_TEXTDOMAIN), array('name' => $data['name']));
						}
						break;
						
					case 'delete_grid':
						$result = self::delete_grid_by_id($data);
						if ($result !== true)
							$error = $result;
						else
							Essential_Grid::ajaxResponseSuccess(esc_attr__("Grid deleted", ESG_TEXTDOMAIN), array('data' => true, 'redirect_url' => self::getViewUrl(Essential_Grid_Admin::VIEW_OVERVIEW)));
						break;
						
					case 'duplicate_grid':
						$result = self::duplicate_grid_by_id($data);
						if (!is_array($result)) {
							$error = $result;
						} else {
							// duplicate return raw data
							// convert it to array
							$result['params'] = json_decode($result['params'], true);
							$result['postparams'] = json_decode($result['postparams'], true);
							$result['layers'] = json_decode($result['layers'], true);
							$result['settings'] = json_decode($result['settings'], true);
							Essential_Grid::ajaxResponseSuccess(esc_attr__("Grid duplicated", ESG_TEXTDOMAIN), array('grid' => $result));
						}
						break;
						
					case 'update_create_item_skin':
						$result = Essential_Grid_Item_Skin::update_save_item_skin($data);
						if ($result !== true) {
							$error = $result;
						} else {
							if (isset($data['id']) && intval($data['id']) > 0)
								Essential_Grid::ajaxResponseSuccess(esc_attr__("Item Skin changed", ESG_TEXTDOMAIN), array('data' => true));
							else
								Essential_Grid::ajaxResponseSuccess(esc_attr__("Item Skin created/changed", ESG_TEXTDOMAIN), array('data' => true, 'is_redirect' => true, 'redirect_url' => self::getViewUrl("", "", 'essential-' . Essential_Grid_Admin::VIEW_SUB_ITEM_SKIN_OVERVIEW)));
						}
						break;
						
					case 'update_custom_css':
						if (isset($data['global_css'])) {
							Essential_Grid_Global_Css::set_global_css_styles($data['global_css']);
							Essential_Grid::ajaxResponseSuccess(esc_attr__("CSS saved!", ESG_TEXTDOMAIN), '');
						} else {
							$error = esc_attr__("No CSS Received", ESG_TEXTDOMAIN);
						}
						break;
						
					case 'delete_item_skin':
						$result = Essential_Grid_Item_Skin::delete_item_skin_by_id($data);
						if ($result !== true)
							$error = $result;
						else
							Essential_Grid::ajaxResponseSuccess(esc_attr__("Item Skin deleted", ESG_TEXTDOMAIN), array('data' => true));
						break;
						
					case 'duplicate_item_skin':
						$result = Essential_Grid_Item_Skin::duplicate_item_skin_by_id($data);
						if ($result !== true)
							$error = $result;
						else
							Essential_Grid::ajaxResponseSuccess(esc_attr__("Item Skin duplicated", ESG_TEXTDOMAIN), array('data' => true, 'is_redirect' => true, 'redirect_url' => self::getViewUrl("", "", 'essential-' . Essential_Grid_Admin::VIEW_SUB_ITEM_SKIN_OVERVIEW)));
						break;
						
					case 'star_item_skin':
						$result = Essential_Grid_Item_Skin::star_item_skin_by_id($data);
						if ($result !== true) {
							$error = $result;
						} else {
							Essential_Grid::ajaxResponseSuccess(esc_attr__("Favorite Changed", ESG_TEXTDOMAIN), array('data' => true));
						}
						break;
						
					case 'update_create_item_element':
						$result = Essential_Grid_Item_Element::update_create_essential_item_element($data);
						if ($result !== true) {
							$error = $result;
						} else {
							Essential_Grid::ajaxResponseSuccess(esc_attr__("Item Element created/changed", ESG_TEXTDOMAIN), array('data' => true));
						}
						break;
						
					case 'check_item_element_existence':
						$result = Essential_Grid_Item_Element::check_existence_by_handle(self::getVar($data, 'name'));
						if ($result === false) {
							Essential_Grid::ajaxResponseData(array("data" => array('existence' => 'false')));
						} elseif ($result === true) {
							Essential_Grid::ajaxResponseData(array("data" => array('existence' => 'true')));
						} else {
							Essential_Grid::ajaxResponseData(array("data" => array('existence' => $result)));
						}
						break;
						
					case 'get_predefined_elements':
						$elements = Essential_Grid_Item_Element::getElementsForJavascript();
						$html_elements = Essential_Grid_Item_Element::prepareDefaultElementsForEditor();
						$html_elements .= Essential_Grid_Item_Element::prepareTextElementsForEditor();
						Essential_Grid::ajaxResponseData(array("data" => array('elements' => $elements, 'html' => $html_elements)));
						break;
						
					case 'delete_predefined_elements':
						$result = Essential_Grid_Item_Element::delete_element_by_handle($data);
						if ($result !== true) {
							$error = $result;
						} else {
							Essential_Grid::ajaxResponseSuccess(esc_attr__("Item Element successfully deleted", ESG_TEXTDOMAIN), array('data' => true));
						}
						break;
						
					case 'update_create_navigation_skin_css':
						$nav = new Essential_Grid_Navigation();
						$result = $nav->update_create_navigation_skin_css($data);
						if ($result !== true) {
							$error = $result;
						} else {
							$skin_css = Essential_Grid_Navigation::output_navigation_skins();
							$skins = Essential_Grid_Navigation::get_essential_navigation_skins();
							$select = '';
							foreach ($skins as $skin) {
								$select .= '<option value="' . $skin['handle'] . '">' . $skin['name'] . '</option>' . "\n";
							}
							if (isset($data['sid']) && intval($data['sid']) > 0)
								Essential_Grid::ajaxResponseSuccess(esc_attr__("Navigation Skin successfully changed!", ESG_TEXTDOMAIN), array('css' => $skin_css, 'select' => $select, 'default_skins' => $skins));
							else
								Essential_Grid::ajaxResponseSuccess(esc_attr__("Navigation Skin successfully created", ESG_TEXTDOMAIN), array('css' => $skin_css, 'select' => $select, 'default_skins' => $skins));
						}
						break;
						
					case 'delete_navigation_skin_css':
						$nav = new Essential_Grid_Navigation();
						$result = $nav->delete_navigation_skin_css($data);
						if ($result !== true) {
							$error = $result;
						} else {
							$skin_css = Essential_Grid_Navigation::output_navigation_skins();
							$skins = Essential_Grid_Navigation::get_essential_navigation_skins();
							$select = '';
							foreach ($skins as $skin) {
								$select .= '<option value="' . $skin['handle'] . '">' . $skin['name'] . '</option>' . "\n";
							}
							Essential_Grid::ajaxResponseSuccess(esc_attr__("Navigation Skin successfully deleted!", ESG_TEXTDOMAIN), array('css' => $skin_css, 'select' => $select, 'default_skins' => $skins));
						}
						break;
						
					case 'get_post_meta_html_for_editor':
						if (!isset($data['post_id']) || intval($data['post_id']) == 0) {
							$error = esc_attr__('No Post ID/Wrong Post ID!', ESG_TEXTDOMAIN);
							break;
						}
						if (!isset($data['grid_id']) || intval($data['grid_id']) == 0) {
							$error = esc_attr__('Please save the grid first to use this feature!', ESG_TEXTDOMAIN);
							break;
						}

						$post = get_post($data['post_id']);
						$disable_advanced = true; //nessecary, so that only normal things can be changed in preview mode
						if (!empty($post)) {
							$grid_id = $data['grid_id'];
							ob_start();
							require_once(ESG_PLUGIN_ADMIN_PATH . '/views/elements/' . self::VIEW_META_BOX . '.php');
							$content = ob_get_contents();
							ob_clean();
							ob_end_clean();

							Essential_Grid::ajaxResponseData(array("data" => array('html' => $content)));
						} else {
							$error = esc_attr__('Post not found!', ESG_TEXTDOMAIN);
						}
						break;
						
					case 'update_post_meta_through_editor':
						$result = false;

						if (!empty($data['metas'])) {
							foreach ($data['metas'] as $meta) {
								if (!isset($meta['post_id']) || intval($meta['post_id']) == 0) continue;
								if (!isset($meta['grid_id']) || intval($meta['grid_id']) == 0) continue;

								//set the cobbles setting to the post
								$cobbles = json_decode(get_post_meta($meta['post_id'], 'eg_cobbles', true), true);
								$cobbles[$meta['grid_id']]['cobbles'] = $meta['eg_cobbles_size'];
								$cobbles = json_encode($cobbles);
								update_post_meta($meta['post_id'], 'eg_cobbles', $cobbles);


								//set the use_skin setting to the post
								$use_skin = json_decode(get_post_meta($meta['post_id'], 'eg_use_skin', true), true);
								$use_skin[$meta['grid_id']]['use-skin'] = $meta['eg_use_skin'];
								$use_skin = json_encode($use_skin);
								update_post_meta($meta['post_id'], 'eg_use_skin', $use_skin);


								$result = self::custom_meta_box_save($meta['post_id'], $meta, true);
								$result = apply_filters('essgrid_after_update_post_meta_through_editor', $result, $meta);

								self::check_for_transient_deletion($meta['post_id']);
							}
						}

						if ($result === true) {
							Essential_Grid::ajaxResponseSuccess(esc_attr__("Post Meta saved!", ESG_TEXTDOMAIN), array());
						} else {
							$error = esc_attr__('Post not found!', ESG_TEXTDOMAIN);
						}
						break;
						
					case 'trigger_post_meta_visibility':
						if (!isset($data['post_id']) || intval($data['post_id']) == 0) {
							$error = esc_attr__('No Post ID/Wrong Post ID!', ESG_TEXTDOMAIN);
							break;
						}
						if (!isset($data['grid_id']) || intval($data['grid_id']) == 0) {
							$error = esc_attr__('Please save the grid first to use this feature!', ESG_TEXTDOMAIN);
							break;
						}

						$visibility = json_decode(get_post_meta($data['post_id'], 'eg_visibility', true), true);
						$found = false;
						if (!empty($visibility) && is_array($visibility)) {
							foreach ($visibility as $grid => $setting) {
								if ($grid == $data['grid_id']) {
									$visibility[$grid] = !$setting;
									$found = true;
									break;
								}
							}
						}

						if (!$found) {
							$visibility[$data['grid_id']] = false;
						}

						$visibility = json_encode($visibility);
						update_post_meta($data['post_id'], 'eg_visibility', $visibility);
						self::check_for_transient_deletion($data['post_id']);
						Essential_Grid::ajaxResponseSuccess(esc_attr__("Visibility of Post for this Grid changed!", ESG_TEXTDOMAIN), array());
						break;
						
					case 'get_image_by_id':
						if (!isset($data['img_id']) || intval($data['img_id']) == 0) {
							$error = esc_attr__('Wrong Image ID given', ESG_TEXTDOMAIN);
						} else {
							$img = wp_get_attachment_image_src($data['img_id'], 'full');
							if ($img !== false) {
								Essential_Grid::ajaxResponseSuccess('', array('url' => $img[0]));
							} else {
								$error = esc_attr__('Image with given ID does not exist', ESG_TEXTDOMAIN);
							}
						}
						break;
						
					case 'activate_purchase_code':
						$result = false;
						if (!empty($data['code'])) {
							$esg_license = new Essential_Grid_License();
							$result = $esg_license->activate_plugin($data['code']);
						} else {
							$error = esc_attr__('The API key, the Purchase Code and the Username need to be set!', ESG_TEXTDOMAIN);
						}
						if ($result === true) {
							Essential_Grid::ajaxResponseSuccess(
								esc_attr__('Purchase Code Successfully Activated', ESG_TEXTDOMAIN), 
								array(
									'data' => true, 
									'is_redirect' => true, 
									'redirect_url' => self::getViewUrl("", "", 'essential-' . Essential_Grid_Admin::VIEW_START)
								)
							);
						} else {
							if ($result !== false)
								$error = $result;
							else
								$error = esc_attr__('Purchase Code is invalid', ESG_TEXTDOMAIN);
						}
						break;
						
					case 'deactivate_purchase_code':
						$esg_license = new Essential_Grid_License();
						$result = $esg_license->deactivate_plugin();
						if ($result === true) {
							Essential_Grid::ajaxResponseSuccess(
								esc_attr__('Successfully removed validation', ESG_TEXTDOMAIN), 
								array(
									'data' => true, 
									'is_redirect' => true, 
									'redirect_url' => self::getViewUrl("", "", 'essential-' . Essential_Grid_Admin::VIEW_START)
								)
							);
						} else {
							if ($result !== false)
								$error = $result;
							else
								$error = esc_attr__('Could not remove Validation!', ESG_TEXTDOMAIN);
						}
						break;
						
					case 'dismiss_notice':
						Essential_Grid_Base::setValidNotice('false');
						Essential_Grid::ajaxResponseSuccess('.');
						break;
						
					case 'import_default_post_data':
						try {
							require(ESG_PLUGIN_PATH . 'includes/assets/default-posts.php');
							require(ESG_PLUGIN_PATH . 'includes/assets/default-grids-meta-fonts.php');

							if (isset($json_tax)) {
								$import_tax = new PunchPost;
								$import_tax->import_taxonomies($json_tax);
							}

							//insert meta, grids
							$im = new Essential_Grid_Import();
							if (isset($tp_grid_meta_fonts)) {
								$tp_grid_meta_fonts = json_decode($tp_grid_meta_fonts, true);
								$custom_metas = self::getVar($tp_grid_meta_fonts, 'custom-meta');
								if (!empty($custom_metas) && is_array($custom_metas)) {
									$im->import_custom_meta($custom_metas);
								}
							}

							if (isset($json_posts)) {
								$import = new PunchPort;
								$import->set_tp_import_posts($json_posts);
								$import->import_custom_posts();
							}
							Essential_Grid::ajaxResponseSuccess(esc_attr__('Demo data successfully imported', ESG_TEXTDOMAIN), array());
						} catch (Exception $d) {
							$error = esc_attr__('Something wrong, please contact the developer', ESG_TEXTDOMAIN);
						}
						break;
						
					case 'get_grid_export_resources':
						if (!isset($data['id'])) {
							$error = esc_attr__('No ID given', ESG_TEXTDOMAIN);
						} else {
							$grid = Essential_Grid::get_essential_grid_by_id($data['id'], true);
							if (empty($grid)) {
								$error = esc_attr__('Grid not found', ESG_TEXTDOMAIN);
							} else {
								$import = new Essential_Grid_Import();
								$export = new Essential_Grid_Export();
								$item_skin = new Essential_Grid_Item_Skin();
								$nav_skin = new Essential_Grid_Navigation();

								$skins = $item_skin->get_essential_item_skins();
								$navigation_skins = $nav_skin->get_essential_navigation_skins();
								$global_css = Essential_Grid_Global_Css::get_global_css_styles();

								$resources = $export->getGridResources(
									$import,
									array(
										'grid' => $grid,
										'skins' => $skins,
										'navigation_skins' => $navigation_skins,
										'global_css' => $global_css
									)
								);
								Essential_Grid::ajaxResponseSuccess(esc_attr__('Grid resources fetched', ESG_TEXTDOMAIN), array('resources' => $resources));
							}
						}
						break;
						
					case 'export_data':
						$export_grid_name = self::getPostVar('esg_export_grid_name', '');
						$export_grids = self::getPostVar('export-grids-id', false);
						$export_skins = self::getPostVar('export-skins-id', false);
						$export_elements = self::getPostVar('export-elements-id', false);
						$export_navigation_skins = self::getPostVar('export-navigation-skins-id', false);
						$export_global_styles = self::getPostVar('export-global-styles', false);
						$export_custom_meta = self::getPostVar('export-custom-meta-handle', false);
						
						$filename = 'ess_grid.json';
						if (!empty($export_grid_name)) {
							$filename = sanitize_file_name('esg-' . $export_grid_name . '.json');
						}
						
						$export = array();
						$ex = new Essential_Grid_Export();

						//export Grids
						if (!empty($export_grids))
							$export['grids'] = $ex->export_grids($export_grids);

						//export Skins
						if (!empty($export_skins))
							$export['skins'] = $ex->export_skins($export_skins);

						//export Elements
						if (!empty($export_elements))
							$export['elements'] = $ex->export_elements($export_elements);

						//export Navigation Skins
						if (!empty($export_navigation_skins))
							$export['navigation-skins'] = $ex->export_navigation_skins($export_navigation_skins);

						//export Custom Meta
						if (!empty($export_custom_meta))
							$export['custom-meta'] = $ex->export_custom_meta($export_custom_meta);

						//export Global Styles
						if ($export_global_styles == 'on')
							$export['global-css'] = $ex->export_global_styles($export_global_styles);

						$export = apply_filters('essgrid_export_data_before_json_encode', $export);
						
						header('Content-Type: text/json');
						header('Content-Disposition: attachment;filename=' . $filename);
						echo json_encode($export);
						exit();
						break;
						
					case 'import_data':
						if (empty($data['imports'])) {
							$error = esc_attr__('No data for import selected', ESG_TEXTDOMAIN);
							break;
						}
						
						try {
							$imported_data = array();
							
							$im = new Essential_Grid_Import();
							
							$temp_d = self::getVar($data, 'imports', array(), 'r');
							unset($temp_d['data-grids']);
							unset($temp_d['data-skins']);
							unset($temp_d['data-elements']);
							unset($temp_d['data-navigation-skins']);
							unset($temp_d['data-custom-meta']);
							unset($temp_d['data-global-css']);
							unset($temp_d['import-grids-id']);
							unset($temp_d['import-skins-id']);
							unset($temp_d['import-navigation-skins-id']);
							unset($temp_d['import-custom-meta-handle']);

							//set overwrite data global to class
							$im->set_overwrite_data($temp_d);

							$skins = self::getVar($data, array('imports', 'data-skins'), array(), 'r');
							if (!empty($skins) && is_array($skins)) {
								
								foreach ($skins as $key => $skin) {
									$skin = base64_decode($skin);
									$tskin = json_decode($skin, true);
									if (class_exists('Essential_Grid_Plugin_Update')) {
										$tskin = Essential_Grid_Plugin_Update::process_update_216($tskin, true);
									}
									$skins[$key] = $tskin;
								}
								
								if (!empty($skins)) {
									$skins_ids = self::getVar($data, array('imports', 'import-skins-id'));
									$imported_data['skins'] = $im->import_skins($skins, $skins_ids);
								}
							}

							$navigation_skins = self::getVar($data, array('imports', 'data-navigation-skins'), array(), 'r');
							if (!empty($navigation_skins) && is_array($navigation_skins)) {
								
								foreach ($navigation_skins as $key => $navigation_skin) {
									$navigation_skin = base64_decode($navigation_skin);
									$tnavigation_skin = json_decode($navigation_skin, true);
									$navigation_skins[$key] = $tnavigation_skin;
								}
								
								if (!empty($navigation_skins)) {
									$navigation_skins_ids = self::getVar($data, array('imports', 'import-navigation-skins-id'));
									$imported_data['nav_skins'] = $im->import_navigation_skins($navigation_skins, $navigation_skins_ids);
								}
							}

							$grids = self::getVar($data, array('imports', 'data-grids'), array(), 'r');
							if (!empty($grids) && is_array($grids)) {
								
								foreach ($grids as $key => $grid) {
									$grid = base64_decode($grid);
									$tgrid = json_decode($grid, true);
									$grids[$key] = $tgrid;
								}

								if (!empty($grids)) {
									$grids_ids = self::getVar($data, array('imports', 'import-grids-id'));
									$im->import_grids($grids, $grids_ids, true, $imported_data);
								}
							}

							$elements = self::getVar($data, array('imports', 'data-elements'), array(), 'r');
							if (!empty($elements) && is_array($elements)) {
								
								foreach ($elements as $key => $element) {
									$element = base64_decode($element);
									$telement = json_decode($element, true);
									$elements[$key] = $telement;
								}
								
								if (!empty($elements)) {
									$elements_ids = self::getVar($data, array('imports', 'import-elements-id'));
									$im->import_elements($elements, $elements_ids);
								}
							}

							$custom_metas = self::getVar($data, array('imports', 'data-custom-meta'), array(), 'r');
							if (!empty($custom_metas) && is_array($custom_metas)) {
								
								foreach ($custom_metas as $key => $custom_meta) {
									$custom_meta = base64_decode($custom_meta);
									$tcustom_meta = json_decode($custom_meta, true);
									$custom_metas[$key] = $tcustom_meta;
								}
								
								if (!empty($custom_metas)) {
									$custom_metas_handle = self::getVar($data, array('imports', 'import-custom-meta-handle'));
									$im->import_custom_meta($custom_metas, $custom_metas_handle);
								}
							}

							if (self::getVar($data, array('imports', 'import-global-styles')) == 'on') {
								$global_css = self::getVar($data, array('imports', 'data-global-css'));
								$global_css = base64_decode($global_css);
								$im->import_global_styles($global_css);
							}
							
							do_action('essgrid_import_data', $data);
							
							Essential_Grid::ajaxResponseSuccess(esc_attr__('Successfully imported data', ESG_TEXTDOMAIN), array('is_redirect' => true, 'redirect_url' => self::getViewUrl("", "", 'essential-' . Essential_Grid_Admin::VIEW_START)));
						} catch (Exception $d) {
							$error = $d->getMessage();
						}
						break;
						
					case 'delete_full_cache':
						self::clear_transients('ess_grid_trans_');
						self::clear_transients('essgrid_');
						Essential_Grid::ajaxResponseSuccess(esc_attr__('Successfully deleted all cache', ESG_TEXTDOMAIN), array());
						break;
						
					case "toggle_grid_favorite":
						if (isset($data['id']) && intval($data['id']) > 0) {
							$return = self::toggle_favorite_by_id($data['id']);
							if ($return['error'] === false) {
								Essential_Grid::ajaxResponseSuccess(esc_attr__('Favorite successfully updated', ESG_TEXTDOMAIN), $return['data']);
							} else {
								$error = $return['error'];
							}
						} else {
							$error = esc_attr__('No ID given', ESG_TEXTDOMAIN);
						}
						break;
						
					case "subscribe_to_newsletter":
						if (!empty($data['email'])) {
							$return = ThemePunch_Newsletter::subscribe($data['email']);
							if ($return !== false) {
								if (!isset($return['status']) || $return['status'] === 'error') {
									$error = (!empty($return['message'])) ? $return['message'] : esc_attr__('Invalid Email', ESG_TEXTDOMAIN);
								} else {
									Essential_Grid::ajaxResponseSuccess(esc_attr__("Success! Please check your Emails to finish the subscribtion", ESG_TEXTDOMAIN), $return);
								}
							} else {
								$error = esc_attr__('Invalid Email/Could not connect to the Newsletter server', ESG_TEXTDOMAIN);
							}
						} else {
							$error = esc_attr__('No Email given', ESG_TEXTDOMAIN);
						}
						break;
						
					case "unsubscribe_to_newsletter":
						if (!empty($data['email'])) {
							$return = ThemePunch_Newsletter::unsubscribe($data['email']);
							if ($return !== false) {
								if (!isset($return['status']) || $return['status'] === 'error') {
									$error = (!empty($return['message'])) ? $return['message'] : esc_attr__('Invalid Email', ESG_TEXTDOMAIN);
								} else {
									Essential_Grid::ajaxResponseSuccess(esc_attr__("Success! Please check your Emails to finish the process", ESG_TEXTDOMAIN), $return);
								}
							} else {
								$error = esc_attr__('Invalid Email/Could not connect to the Newsletter server', ESG_TEXTDOMAIN);
							}
						} else {
							$error = esc_attr__('No Email given', ESG_TEXTDOMAIN);
						}
						break;
						
					case "load_library":
						$library = new Essential_Grid_Library();
						$grids = $library->get_tp_template_grids();
						$html = $library->get_library_grids_html($grids);

						if ($html !== false) {
							//set new templates counter back to 0
							$library->set_templates_counter(0);
							
							Essential_Grid::ajaxResponseData($html);
						} else {
							$error = esc_attr__('Library could not be loaded', ESG_TEXTDOMAIN);
						}
						break;
						
					case 'import_grid_online':
						$library = new Essential_Grid_Library();
						$uid = self::getVar($data, 'uid');
						$zip = self::getVar($data, 'zip');
						$bg = self::getVar($data, 'bg');
						$addons = self::getVar($data, 'addons', array());

						$return = $library->import_grid($uid, $zip, true);
						if (is_array($return) && !empty($return)) {
							$id = (isset($return['grids_imported'])) ? current($return['grids_imported']) : 1;
							
							//check for non-global addons
							if (is_array($addons) && !empty($addons)) {
								$esg_addons = Essential_Grid_Addons::instance();
								foreach ($addons as $handle) {
									$global = $esg_addons->get_addon_attr($handle, 'global');
									if (!$global) {
										$esg_addons->enable_grid_addon($handle, $id);
									}
								}
							}

							$settings = self::get_grid_settings($id);
							$settings['bg'] = $bg;
							self::set_grid_settings($settings, $id);
							
							$grid = Essential_Grid::get_essential_grid_by_id($id);
							Essential_Grid::ajaxResponseSuccess(esc_attr__('Successfully imported Grid', ESG_TEXTDOMAIN), array('grid' => $grid));
						} else {
							$error = is_string($return) ? $return : esc_attr__('Failed to import Grid', ESG_TEXTDOMAIN);
						}
						break;
						
					case 'save_metas':
						$m = new Essential_Grid_Meta();
						$lm = new Essential_Grid_Meta_Linking();
						$metas = self::getVar($data, 'CustomMetas');
						$link_meta = self::getVar($data, 'LinkMetas');

						$result = $m->save_all_metas($metas);
						if ($result !== true) {
							$error = $result;
							break;
						}
						$result = $lm->save_all_link_metas($link_meta);
						if ($result !== true) {
							$error = $result;
							break;
						}

						Essential_Grid::ajaxResponseSuccess(esc_attr__("Meta successfully saved!", ESG_TEXTDOMAIN));
						break;

					case 'get_addons_list':
						$grid_id = self::getVar($data, 'grid_id');
						$esg_addons = Essential_Grid_Addons::instance();
						
						if (empty($grid_id)) {
							$esg_addons_list = $esg_addons->get_addons_list();
						} else {
							$esg_addons_list = $esg_addons->get_grid_addons_list($grid_id);
						}

						//set new addons counter back to 0
						$esg_addons->set_addons_counter(0);

						Essential_Grid::ajaxResponseSuccess(esc_attr__("Addons loaded", ESG_TEXTDOMAIN), array('addons' => $esg_addons_list));
						break;

					case 'install_addon':
						$handle = self::getVar($data, 'addon');
						$esg_addons = Essential_Grid_Addons::instance();
						$return = $esg_addons->install_addon($handle);
						
						if ($return === true) {
							$version = $esg_addons->get_addon_version($handle);
							$data = apply_filters('essgrid_install_addon', array(), $handle);
							Essential_Grid::ajaxResponseData(array($handle => $data, 'version' => $version));
						} else {
							$error = ($return === false) ? esc_attr__('AddOn could not be installed!', ESG_TEXTDOMAIN) : $return;
						}
						break;
						
					case 'update_addon':
						$handle = self::getVar($data, 'addon');
						$esg_addons = Essential_Grid_Addons::instance();
						$return = $esg_addons->install_addon($handle, true);

						if ($return === true) {
							$version = $esg_addons->get_addon_version($handle);
							$data = apply_filters('essgrid_update_addon', array(), $handle);
							Essential_Grid::ajaxResponseData(array($handle => $data, 'version' => $version));
						} else {
							$error = ($return === false) ? esc_attr__('AddOn could not be updated!', ESG_TEXTDOMAIN) : $return;
						}
						break;
						
					case 'activate_addon':
						$handle = self::getVar($data, 'addon');
						$enable = self::getVar($data, 'enable');
						$grid_id = self::getVar($data, 'grid_id');
						
						$esg_addons = Essential_Grid_Addons::instance();
						$return = $esg_addons->activate_addon($handle);

						if ($return === true) {
							if ($enable) {
								$global = $esg_addons->get_addon_attr($handle, 'global');
								if ($global) {
									$esg_addons->enable_addon($handle);
								} else {
									$esg_addons->enable_grid_addon($handle, $grid_id);
								}
							}
							$version = $esg_addons->get_addon_version($handle);
							$data = apply_filters('essgrid_activate_addon', array(), $handle);
							Essential_Grid::ajaxResponseData(array($handle => $data, 'version' => $version));
						} else {
							$error = ($return === false) ? esc_attr__('AddOn could not be activated!', ESG_TEXTDOMAIN) : $return;
						}
						break;
						
					case 'deactivate_addon':
						$handle = self::getVar($data, 'addon');
						$esg_addons = Essential_Grid_Addons::instance();
						$esg_addons->deactivate_addon($handle);

						do_action('essgrid_deactivate_addon', $handle);
						Essential_Grid::ajaxResponseSuccess(__('AddOn deactivated', ESG_TEXTDOMAIN));
						break;

					case 'enable_addon':
						$handle = self::getVar($data, 'addon');
						$grid_id = self::getVar($data, 'grid_id');
						$esg_addons = Essential_Grid_Addons::instance();
						
						$global = $esg_addons->get_addon_attr($handle, 'global');
						if ($global) {
							$return = $esg_addons->enable_addon($handle);
						} else {
							$return = $esg_addons->enable_grid_addon($handle, $grid_id);
						}
						
						if (true === $return) {
							do_action('essgrid_enable_addon', $handle, $grid_id);
							Essential_Grid::ajaxResponseSuccess(__('AddOn enabled', ESG_TEXTDOMAIN));
						} else {
							$error = ($return === false) ? esc_attr__('AddOn could not be activated!', ESG_TEXTDOMAIN) : $return;
						}
						break;
						
					case 'disable_addon':
						$handle = self::getVar($data, 'addon');
						$grid_id = self::getVar($data, 'grid_id');
						$esg_addons = Essential_Grid_Addons::instance();
						
						$global = $esg_addons->get_addon_attr($handle, 'global');
						if ($global) {
							$return = $esg_addons->disable_addon($handle);
						} else {
							$return = $esg_addons->disable_grid_addon($handle, $grid_id);
						}

						if ($return) {
							do_action('essgrid_disable_addon', $handle, $grid_id);
							Essential_Grid::ajaxResponseSuccess(__('AddOn disabled', ESG_TEXTDOMAIN));
						} else {
							$error = esc_attr__('AddOn could not be disabled!', ESG_TEXTDOMAIN);
						}
						break;

					case 'update_favorites':
						$action = self::getVar($data, 'action', 'add');
						$type = self::getVar($data, 'type', 'grid');
						$id = esc_attr(self::getVar($data, 'id'));

						$favorites = new Essential_Grid_Favorite();
						$favorites->update_favorites($action, $type, $id);

						Essential_Grid::ajaxResponseSuccess(esc_attr__("Favorites updated", ESG_TEXTDOMAIN));
						break;

					case 'check_for_updates':
						$update = new Essential_Grid_Update(ESG_REVISION);
						$version = $update->_retrieve_version_info(true);
						if ($version !== false) {
							Essential_Grid::ajaxResponseData(array('version' => $version));
						} else {
							$error = esc_attr__('Connection to Update Server Failed!', ESG_TEXTDOMAIN);
						}
						break;
						
					case 'check_for_updates_shop':
						$library = new Essential_Grid_Library();
						if ($library->_get_template_list(true)) {
							Essential_Grid::ajaxResponseSuccess(esc_attr__("Templates Library updated", ESG_TEXTDOMAIN));
						} else {
							$error = esc_attr__('Connection to Update Server Failed!', ESG_TEXTDOMAIN);
						}
						break;

					case 'tip_dont_show':
						$id = self::getVar($data, 'id');
						if (!empty($id)) {
							$tips = get_option('tp_eg_tips_dont_show', array());
							if (!in_array($id, $tips)) $tips[] = $id;
							update_option('tp_eg_tips_dont_show', $tips);
						}
						
						Essential_Grid::ajaxResponseSuccess(esc_attr__("Tips Data Updated", ESG_TEXTDOMAIN));
						break;

					default:
						$return = apply_filters('essgrid_on_ajax_action', false, $clientAction, $data);
						if ($return) {
							if (is_array($return)) {
								if (isset($return['error'])) {
									$error = $return['error'];
									break;
								}
								if (isset($return['message'])) {
									Essential_Grid::ajaxResponseSuccess($return['message'], $return['data']);
								}
								Essential_Grid::ajaxResponseData(array('data' => $return['data']));
							} else {
								Essential_Grid::ajaxResponseSuccess($return);
							}
						}
						
						$error = true;
						break;
				}
			} else {
				$error = true;
			}
			if ($error !== false) {
				$showError = esc_attr__("Wrong Request!", ESG_TEXTDOMAIN);
				if ($error !== true)
					$showError = esc_attr__("Ajax Error: ", ESG_TEXTDOMAIN) . $error;
				Essential_Grid::ajaxResponseError($showError, false);
			}
			exit();
		} catch (Exception $e) {
			exit();
		}
	}

	/**
	 * Enqueue Gutenberg editor blocks styles and scripts
	 */
	public function enqueue_block_editor_assets()
	{
		global $pagenow; 
		
		$block_path = 'admin/includes/gutenberg-blocks/build/index.js';
		$style_path = 'admin/includes/gutenberg-blocks/build/index.css';

		//do not include wp-editor on widgets page 
		$deps = array('wp-i18n', 'wp-element', 'wp-blocks', 'wp-components');
		if ('widgets.php' !== $pagenow ) {
			$deps[] = 'wp-editor';
		}
		// Enqueue the bundled block JS file
		wp_enqueue_script(
			'essgrid-blocks-js',
			ESG_PLUGIN_URL . $block_path,
			$deps,
			filemtime(ESG_PLUGIN_PATH . $block_path)
		);

		wp_localize_script(
			'essgrid-blocks-js',
			'EssGridOptions',
			array(
				'pluginurl' => ESG_PLUGIN_URL
			)
		);

		// Enqueue optional editor only styles
		wp_enqueue_style(
				'essgrid-blocks-editor-css',
				ESG_PLUGIN_URL . $style_path,
				'',
				filemtime(ESG_PLUGIN_PATH . $style_path)
		);
	}

	/**
	 * Add Essential Grid Gutenberg Block Category
	 * 
	 * @param array $categories
	 * @param WP_Post $post
	 * @return array
	 */
	public function create_block_category($categories, $post)
	{
		if ($this->in_array_r('essgrid', $categories)) return $categories;
		return array_merge(
			$categories,
			array(
				array(
					'slug' => 'essgrid',
					'title' => esc_attr__('Essential Grid', 'essgrid'),
				),
			)
		);
	}

	/**
	 * Check Array for Value
	 *
	 * @param mixed $needle
	 * @param array $haystack
	 * @param bool $strict
	 * @return bool
	 */
	public function in_array_r($needle, $haystack, $strict = false)
	{
		foreach ($haystack as $item) {
			if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && $this->in_array_r($needle, $item, $strict))) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Improve UX for empty grids or when social stream data isn't available
	 * @since: 3.0.12
	 * @return string
	 */
	public static function empty_grid_markup()
	{
		return
			'<div>No posts found for this Grid.<br><br>' .
			'<a id="go-to-source" class="esg-btn esg-purple" href="#">Edit Source Settings</a> ' .
			'<a class="esg-btn esg-purple" href="' . admin_url() . 'edit.php" target="_blank">Create Posts</a>' .
			'</div>';
	}

	/**
	 * add a go premium button to the plugins page
	 **/
	public function add_plugin_action_links($links){
		$links['go_premium'] = '<a href="https://account.essential-grid.com/licenses/pricing/?utm_source=admin&utm_medium=button&utm_campaign=esgusers&utm_content=buykey" target="_blank" style="color: #F7345E; font-weight: 700;">'.__('Go Premium', 'essgrid').'</a>';
		return $links;
	}

	/**
	 * add plugin notices to the Slider Essential Grid Plugin at the overview page of plugins
	 **/
	public static function add_plugins_page_notices(){
		$plugins = get_plugins();

		foreach($plugins as $plugin_id => $plugin){
			$slug = dirname($plugin_id);
			if(empty($slug) || $slug !== ESG_PLUGIN_SLUG) continue;
			if(!Essential_Grid_Base::isValid() && version_compare(get_option('tp_eg_latest-version', ESG_REVISION), $plugin['Version'], '>')){
				add_action('after_plugin_row_' . $plugin_id, array('Essential_Grid_Admin', 'show_purchase_notice'), 10, 3);
			}

			break;
		}
	}

	/**
	 * Show message for activation benefits
	 **/
	public static function show_purchase_notice($plugin_file, $plugin_data, $plugin_status)
	{
		$wp_list_table = _get_list_table('WP_Plugins_List_Table');
		$esg_latest_version = get_option('tp_eg_latest-version', ESG_REVISION);
		?>
		<tr class="plugin-update-tr active">
			<td colspan="<?php echo $wp_list_table->get_column_count(); ?>" class="plugin-update colspanchange">
				<div class="update-message notice inline notice-warning notice-alt">
					<p><?php _e('There is a new version (<a href="https://www.essential-grid.com/documentation/changelog/?utm_source=admin&utm_medium=wpplugins&utm_campaign=esgusers&utm_content=updateinfo#' . $esg_latest_version . '" target="_blank">' . $esg_latest_version . '</a>) of Essential Grid available. To update directly <a href="admin.php?page=essential-grid#activateplugin" onclick="#">register your license key now</a> or <a href="https://account.essential-grid.com/licenses/pricing/?utm_source=admin&utm_medium=wpplugins&utm_campaign=esgusers&utm_content=updateinfo" target="_blank">purchase a new license key</a> to access <a href="https://www.essential-grid.com/?utm_source=admin&utm_medium=wpplugins&utm_campaign=esgusers&utm_content=updateinfo" target="_blank">all premium features</a>.', ESG_TEXTDOMAIN); ?></p>
				</div>
			</td>
		</tr>
		<style>tr[data-slug="essential-grid"] td, tr[data-slug="essential-grid"] th { box-shadow: none !important } #essential-grid-update { display: none; }</style>
		<?php
	}
}

<?php
/*
 * Plugin Name: AgnesKolignan
 * Description: Common, site specific code changes for agneskolignan website
 * Domain Path: /languages
 * Text Domain: agneskolignan
 */
add_action('plugins_loaded', function () {
	if (!function_exists('plura_includes')) {
		add_action('admin_notices', function () {
			echo '<div class="notice notice-error"><p><strong>My Plugin:</strong> The <code>Plura</code> plugin must be active for this plugin to work properly.</p></div>';
		});
		return;
	}

	plura_includes([
		'includes/clients',
		'includes/common',
		'includes/config',
		'includes/exhibitions',
		'includes/objects',
		'includes/objects-collections'
	], __DIR__);
});





add_action( 'init', 'ak_init' );
  
/**
 * Load plugin textdomain.
 */
function ak_init() {

	load_plugin_textdomain( 'agneskolignan', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

}


add_action( 'admin_enqueue_scripts', function() {


	wp_enqueue_style( 'ak-admin', plugins_url( "/assets/css/admin.css", __FILE__ ) );

} );




/**
 * Expose a menu item's target object id as a class, so a nav item can be styled by what
 * it points at rather than by menu position.
 *
 * https://wordpress.stackexchange.com/a/237795
 *
 * @param array   $classes Classes for the menu item's <li>.
 * @param WP_Post $item    The menu item.
 * @return array
 */
function ak_nav_menu_object_id_class( array $classes, $item ): array {

	if( isset( $item->object_id ) ) {

		$classes[] = sprintf( 'menu-item-object-id-%d', $item->object_id );

	}

	return $classes;

}

add_filter( 'nav_menu_css_class', 'ak_nav_menu_object_id_class', 10, 2 );


add_action( 'wp_head', function() {

	$img = ak_config_bg_image();

	if( !$img ) return;

	$var = '--ak-config-bg:url("' . $img['url'] . '")';

	?><style type="text/css">:root {<?php echo $var; ?>}</style><?php

} );





//Body Class
add_filter('body_class', function( $classes ) {

	$c = [];

	if( ak_config_bg_image() ) {

		$c[] = 'ak-has-bg';

	}

	return array_merge($classes, $c);

} );



/**
 * Render Plura shortcodes in the block editor without wpautop.
 *
 * WordPress' core/shortcode block renders as `return wpautop( $content )`. On this site
 * the block's innerHTML already arrives with shortcodes expanded, so that wpautop() runs
 * over finished grid markup: it breaks lines around every <h3> and <div> and opens a <p>
 * before each </a>. Under link="1" the item wrapper is an anchor, so the browser then
 * splits it and the grid collapses. Classic-theme Plura sites never hit this — there
 * do_shortcode() runs at priority 11 on the_content, after wpautop at 10.
 *
 * Returning the block's own content skips the core callback entirely. do_shortcode() is
 * kept for the case where innerHTML still holds an unexpanded shortcode; it is a no-op
 * once there is nothing left to expand.
 *
 * @param string $html  Rendered block HTML.
 * @param array  $block Parsed block.
 * @return string
 */
add_filter('render_block', function( string $html, array $block ): string {

	if( ( $block['blockName'] ?? '' ) !== 'core/shortcode' ) {

		return $html;

	}

	$source = trim( $block['innerHTML'] ?? '' );

	if( ! str_contains( $source, 'plura-wp-' ) ) {

		return $html;

	}

	return do_shortcode( $source );

}, 10, 2);


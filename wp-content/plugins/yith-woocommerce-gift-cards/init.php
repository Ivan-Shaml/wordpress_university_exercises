<?php
/**
 * Plugin Name: YITH WooCommerce Gift Cards
 * Plugin URI: https://yithemes.com/themes/plugins/yith-woocommerce-gift-cards
 * Description: <code><strong>YITH Woocommerce Gift Card</strong></code> allows your users to purchase and give gift cards. In this way, you will increase the spread of your brand, your sales, and average spend, especially during the holidays. <a href="https://yithemes.com/" target="_blank">Get more plugins for your e-commerce shop on <strong>YITH</strong></a>.
 * Version: 2.0.10
 * Author: YITH
 * Author URI: https://yithemes.com/
 * Text Domain: yith-woocommerce-gift-cards
 * Domain Path: /languages/
 * WC requires at least: 4.2
 * WC tested up to: 4.9
 **/

/*  Copyright 2013-2018  Your Inspiration Themes  (email : plugins@yithemes.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

if ( ! function_exists( 'yit_deactive_free_version' ) ) {
	require_once 'plugin-fw/yit-deactive-plugin.php';
}

if ( ! function_exists( 'yith_ywgc_install_woocommerce_admin_notice' ) ) {

	function yith_ywgc_install_woocommerce_admin_notice() {
		?>
		<div class="error">
			<p><?php _e( 'YITH WooCommerce Gift Cards is enabled but not effective. It requires WooCommerce in order to work.', 'yit' ); ?></p>
		</div>
		<?php
	}
}

if ( ! function_exists ( 'yith_ywgc_install_free_admin_notice' ) ) {
	/**
	 * Unable to activate the free version while the premium version is active
	 *
	 * @author Lorenzo Giuffrida
	 * @since  1.0.0
	 */
	function yith_ywgc_install_free_admin_notice () {
		?>
		<div class="error">
			<p><?php _e ( 'You can\'t activate the free version of YITH WooCommerce Gift Cards while you are using the premium one.', 'yith-woocommerce-gift-cards' ); ?></p>
		</div>
		<?php
	}
}


if ( ! function_exists( 'yith_plugin_registration_hook' ) ) {
	require_once 'plugin-fw/yit-plugin-registration-hook.php';
}
register_activation_hook( __FILE__, 'yith_plugin_registration_hook' );

//region    ****    Define constants

defined( 'YITH_YWGC_SLUG' ) || define( 'YITH_YWGC_SLUG', 'yith-woocommerce-gift-cards' );
defined ( 'YITH_YWGC_FREE_INIT' ) || define ( 'YITH_YWGC_FREE_INIT', plugin_basename ( __FILE__ ) );
defined( 'YITH_YWGC_SECRET_KEY' ) || define( 'YITH_YWGC_SECRET_KEY', 'GcGTnx2i0Qdavxe9b9by' );
defined( 'YITH_YWGC_PLUGIN_NAME' ) || define( 'YITH_YWGC_PLUGIN_NAME', 'YITH WooCommerce Gift Cards' );
defined( 'YITH_YWGC_INIT' ) || define( 'YITH_YWGC_INIT', plugin_basename( __FILE__ ) );
defined ( 'YITH_YWGC_VERSION' ) || define ( 'YITH_YWGC_VERSION', '2.0.10' );
defined ( 'YITH_YWGC_DB_CURRENT_VERSION' ) || define ( 'YITH_YWGC_DB_CURRENT_VERSION', '1.0.1' );
defined( 'YITH_YWGC_FILE' ) || define( 'YITH_YWGC_FILE', __FILE__ );
defined( 'YITH_YWGC_DIR' ) || define( 'YITH_YWGC_DIR', plugin_dir_path( __FILE__ ) );
defined( 'YITH_YWGC_URL' ) || define( 'YITH_YWGC_URL', plugins_url( '/', __FILE__ ) );
defined( 'YITH_YWGC_ASSETS_URL' ) || define( 'YITH_YWGC_ASSETS_URL', YITH_YWGC_URL . 'assets' );
defined( 'YITH_YWGC_ASSETS_DIR' ) || define( 'YITH_YWGC_ASSETS_DIR', YITH_YWGC_DIR . 'assets' );
defined( 'YITH_YWGC_SCRIPT_URL' ) || define( 'YITH_YWGC_SCRIPT_URL', YITH_YWGC_ASSETS_URL . '/js/' );
defined( 'YITH_YWGC_TEMPLATES_DIR' ) || define( 'YITH_YWGC_TEMPLATES_DIR', YITH_YWGC_DIR . 'templates/' );
defined( 'YITH_YWGC_ASSETS_IMAGES_URL' ) || define( 'YITH_YWGC_ASSETS_IMAGES_URL', YITH_YWGC_ASSETS_URL . '/images/' );
defined( 'YITH_YWGC_VIEWS_PATH' ) || define( 'YITH_YWGC_VIEWS_PATH', YITH_YWGC_DIR . 'views/' );

//endregion

/* Plugin Framework Version Check */
if ( ! function_exists( 'yit_maybe_plugin_fw_loader' ) && file_exists( YITH_YWGC_DIR . 'plugin-fw/init.php' ) ) {
	require_once( YITH_YWGC_DIR . 'plugin-fw/init.php' );
}
yit_maybe_plugin_fw_loader( YITH_YWGC_DIR );

require_once( YITH_YWGC_DIR . 'functions.php' );

if ( ! function_exists( 'yith_ywgc_init' ) ) {
	/**
	 * Init the plugin
	 *
	 * @author Lorenzo Giuffrida
	 * @since  1.0.0
	 */
	function yith_ywgc_init() {

		/**
		 * Load text domain and start plugin
		 */
		load_plugin_textdomain( 'yith-woocommerce-gift-cards', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		require_once( YITH_YWGC_DIR . 'lib/class-ywgc-plugin-fw-loader.php' );

		//  Star the plugin
		YITH_YWGC();
	}
}
add_action( 'yith_ywgc_init', 'yith_ywgc_init' );

if ( ! function_exists( 'YITH_YWGC' ) ) {
	/**
	 * Get the main plugin class
	 *
	 * @author Lorenzo Giuffrida
	 * @since  1.0.0
	 */
	function YITH_YWGC() {
		return YITH_WooCommerce_Gift_Cards::get_instance();
	}
}

if ( ! function_exists( 'yith_ywgc_install' ) ) {
	/**
	 * Install the plugin
	 *
	 * @author Lorenzo Giuffrida
	 * @since  1.0.0
	 */
	function yith_ywgc_install() {

		if ( ! function_exists( 'WC' ) ) {
			add_action( 'admin_notices', 'yith_ywgc_install_woocommerce_admin_notice' );
		}elseif ( defined ( 'YITH_YWGC_PREMIUM' ) ){
			add_action ( 'admin_notices', 'yith_ywgc_install_free_admin_notice' );
			deactivate_plugins ( plugin_basename ( __FILE__ ) );
		}
		else {
			do_action( 'yith_ywgc_init' );
		}
	}
}

add_action( 'plugins_loaded', 'yith_ywgc_install', 11 );



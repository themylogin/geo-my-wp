<?php
/*
Plugin Name: GEO my WP
Plugin URI: http://www.geomywp.com
Description: GEO my WP is an adavanced mapping and proximity search plugin. Geotag post types and BuddyPress members and create proximity search forms to search and find locations based on address, radius, categories and more.
Version: 3.0-beta-1
Author: Eyal Fitoussi
Author URI: http://www.geomywp.com
Requires at least: 4.0
Tested up to: 4.8.1
Buddypress: 2.1.1 and up
Text Domain: GMW
Domain Path: /languages/
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GEO my WP class.
 */
class GEO_my_WP {

	/**
	 * GEO my WP version
	 * @var string
	 */
	public $version = '3.0-beta-1';

	/**
	 * Database version
	 * @var integer
	 */
	public $db_version = 3;

	/**
	 * GEO my WP & Extensions options
	 * @var [type]
	 */
	public $options;

	/**
	 * GEO my WP URL parameteres prefix
	 *
	 * This is the prefix used for the URL paramaters that GEO my WP
	 * uses with submitted form. It can modified using the filter
	 *
	 * apply_filters( 'gmw_form_url_prefix', 'gmw_' );
	 * 
	 * @var string
	 */
	public $url_prefix = 'gmw_';

	/**
	 * Showing on mobile device?
	 * 
	 * @var boolean
	 */
	public $is_mobile = false;

	/**
	 * Ajax URl
	 * @var boolean
	 */
	public $ajax_url = false;

	/**
	 * Enable disable internal caching system
	 * 
	 * @var boolean
	 */
	public $internal_cache = true;

	/**
	 * Enable disable internal caching system
	 * 
	 * @var boolean
	 */
	public $internal_cache_expiration = MONTH_IN_SECONDS;

	/**
	 * Minimum versions required for this version of GEO my WP
	 * @var array
	 */
	public $required_versions = array(
		'premium_settings' 	    => '2.0',
		'global_maps' 	        => '2.1',
		'groups_locator'   	    => '1.6',
		'gmw_kleo_geolocation'  => '1.5',
		'wp_users_geo-location' => '1.5',
		'nearby_posts'			=> '1.5',
		'geo_members_directory' => '1.5',
		'exclude_members'		=> '1.5',
		'xprofile_fields'		=> '1.5'
	);

	/**
	 * Registered Objects Types 
	 * @var array
	 */
	public $object_types = array();

	/**
	 * Loaded addons
	 * @var array
	 */
	public $loaded_addons = array();

	/**
	 * Addons Status
	 * @var array
	 */
	public $addons_status = array();

	/**
	 * array of objects which will use the database base prefix
	 * @var array
	 */
	public $global_db_objects = array();

	/**
	 * Core addons
	 * @var array
	 */
	public $core_addons = array();

	/**
	 * Addons data
	 * @var array
	 */
	public $addons = array();

	/**
	 * Current Form being loaded
	 * @var array
	 */
	public $current_form = array();

	/**
	 * @var GEO my WP
	 * 
	 * @since 2.4
	 */
	private static $instance;
	
	/**
	 * Main Instance
	 *
	 * Insures that only one instance of GEO_my_WP exists in memory at any one
	 * time.
	 *
	 * @since 2.4
	 * @static
	 * @staticvar array $instance
	 * @return GEO_my_WP
	 */
	public static function instance() {

		if ( !isset( self::$instance ) && ! ( self::$instance instanceof GEO_my_WP ) ) {

			self::$instance = new GEO_my_WP;
			self::$instance->constants();

			// load textdomain
			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );

			// run plugin installer once GEO my WP activated
			register_activation_hook( __FILE__, array( self::$instance, 'install' ) );
			
			// setup some global variables
			self::$instance->setup_globals();
			self::$instance->includes();
			self::$instance->actions();
			self::$instance->load_core_addons();	
		}

		return self::$instance;
	}

	/**
	 * A dummy constructor to prevent GEO my WP from being loaded more than once.
	 *
	 * @since 2.4
	 */
	private function __construct() {}

	/**
	 * Prevent cloning of GEO my WP.
	 *
	 * @since 3.0
	 * @return void
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin\' eh?!', 'GMW' ), '3.0' );
	}

	/**
	 * Prevent GEO my WP from being unserialized.
	 *
	 * @since 3.0
	 * @return void
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin\' eh?!', 'GMW' ), '3.0' );
	}

	/**
	 * Setup plugin constants
	 *
	 * @access private
	 * @since 2.4
	 * @return void
	 */
	private function constants() {
	
		// Define constants
		if ( ! defined( 'GMW_REMOTE_SITE_URL' ) ) {
			define( 'GMW_REMOTE_SITE_URL', 'https://geomywp.com' );
		}
		
		if ( ! defined( 'IS_ADMIN' ) ) {
			define( 'IS_ADMIN', is_admin() );
		}

		define( 'GMW_VERSION', $this->version );
		define( 'GMW_DB_VERSION', $this->db_version );
		define( 'GMW_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'GMW_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
		define( 'GMW_PLUGINS_PATH', GMW_PATH . '/plugins' );
		define( 'GMW_PLUGINS_URL', GMW_URL . '/plugins' );
		define( 'GMW_IMAGES', GMW_URL . '/assets/images' );
		define( 'GMW_FILE', __FILE__ );
		define( 'GMW_BASENAME', plugin_basename( GMW_FILE ) );	
	}

	/**
	 * Localization
	 *
	 * @access public
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'GMW', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
	
	/**
	 * Plugin installer. Execute when plugin activated
	 * 
	 * @return [type] [description]
	 */
	public function install() {

		include( 'includes/class-gmw-installer.php' );

		GMW_Installer::init();

		flush_rewrite_rules();
	}

	/**
	 * Plugin Updates
	 * 
	 * @return [type] [description]
	 */
	public function update() {

		// check if version changed
		if ( version_compare( GMW_VERSION, get_option( 'gmw_version' ) , '>' ) ) {

			include( 'includes/class-gmw-installer.php' );

			GMW_Installer::init();

			flush_rewrite_rules();
		} 
	}

	/**
	 * Setup global variables 
	 * 
	 * @return [type] [description]
	 */
	public function setup_globals() {

		global $gmw_options;

		$gmw_options   = $this->options = get_option( 'gmw_options' );
		$addons_status = get_option( 'gmw_addons_status' );
		
		if ( empty( $addons_status ) ) {
			$addons_status = array();
		}

		// we get the addons data from database only in front-end.
		// We do this to saved a bit on performance.
		if ( ! IS_ADMIN ) {

			$addons_data = get_option( 'gmw_addons_data' );

			if ( empty( $addons_data ) ) {
				$addons_data = array();
			}

			$this->addons = $addons_data;
		}

		// addons statuses: active, inactive or disabled.
		$this->addons_status = $addons_status;

		// filter url prefix
		$this->url_prefix     = apply_filters( 'gmw_form_url_prefix', $this->url_prefix );
		$this->ajax_url       = admin_url( 'admin-ajax.php', is_ssl() ? 'admin' : 'http' );
		$this->internal_cache = apply_filters( 'gmw_internal_cache_enabled', $this->internal_cache );
		$this->internal_cache_expiration = apply_filters( 'gmw_internal_cache_expiration', $this->internal_cache_expiration );
	}

	/**
	 * Include files
	 * 
	 * @since 2.4
	 * 
	 */
	public function includes() {

		// enable GMW cache helper
		if ( apply_filters( 'gmw_cache_helper_enabled', true ) ) {
			include( 'includes/class-gmw-cache-helper.php' );
		}

		// include files
		include( 'includes/class-gmw-helper.php' );
		include( 'includes/gmw-functions.php' );
		include( 'includes/class-gmw-register-addon.php' );
		include( 'includes/class-gmw-location.php' );
		include( 'includes/gmw-location-functions.php' );
		include( 'includes/gmw-user-location-functions.php' );
		include( 'includes/class-gmw-maps-api.php' );
		include( 'includes/gmw-deprecated-functions.php' );
		include( 'includes/class-gmw-cron.php' );
		include( 'includes/gmw-enqueue-scripts.php' );
		include( 'includes/location-form/includes/class-gmw-location-form.php' );
		include( 'includes/gmw-widgets.php' );
		include( 'includes/gmw-template-functions.php' );
		include( 'includes/class-gmw-form.php' );
		include( 'includes/gmw-shortcodes.php' );

		//include admin files
		if ( IS_ADMIN ) {
			include( GMW_PATH . '/includes/admin/class-gmw-admin.php' ); 	
		}
	}

	/**
	 * add actions
	 *
	 * run update on admin init.
	 * 
	 * @since 2.4
	 */
	public function actions() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'update' ) );
	}
	
	public function init() {
		$this->is_mobile = ( function_exists( 'wp_is_mobile' ) && wp_is_mobile() ) ? true : false;
	}

	/**
	 * Verify if add-on is active
	 * @param  [array] $addon
	 * @return [boolean]      
	 */
	public static function gmw_check_addon( $addon ) {
		return GMW_Helper::is_addon_active( $addon );
	}

	/**
	 * Include core add-ons
	 */
	private function load_core_addons() {
		
		include( GMW_PLUGINS_PATH . '/single-location/loader.php' );
		include( GMW_PLUGINS_PATH . '/posts-locator/loader.php' );
		include( GMW_PLUGINS_PATH . '/members-locator/loader.php' );
		include( GMW_PLUGINS_PATH . '/current-location/loader.php' );
		include( GMW_PLUGINS_PATH . '/sweetdate-geolocation/loader.php' );
	}
}

/**
 *  GMW Instance
 *
 * @since 1.1.1
 * @return GEO my WP Instance
 */
function GMW() {
	return GEO_my_WP::instance();
}

// Init GMW
$GLOBALS['geomywp'] = GMW();
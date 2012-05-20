<?php
/**
 * WordPress Plugin to store the ftp-account data in the WordPress database
 *
 * PHP version 5.3
 *
 * @category   PHP
 * @package    WordPress
 * @subpackage FTP-Account to UserMeta
 * @author     Ralf Albert <me@neun12.de>
 * @license    GPLv3 http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    0.1
 * @link       http://wordpress.com
 */

/**
 * Plugin Name:	FTP-Account to UserMeta
 * Plugin URI:	https://github.com/RalfAlbert/WordPress-FTP-Account-to-UserMeta
 * Description:	This plugin add a couple of fields to your user-profile to enter your ftp-account data. This will prevent to re-entering your ftp-account data everytime WordPress request them.
 * Version: 	0.1.1
 * Author: 		Ralf Albert
 * Author URI: 	http://yoda.neun12.de
 * Text Domain: ftp2um
 * Domain Path: /languages
 * Network:
 * License:		GPLv3
 */

/*  Copyright 2012 Author  (email : me@neun12.de)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 3, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


! defined( 'ABSPATH' ) and die( "Cheetin' uh!?" );

add_action( 'plugins_loaded', array( 'WP_FTPAcc_to_UM', 'plugin_start' ) );

register_activation_hook(	__FILE__, array( 'WP_FTPAcc_to_UM', 'activate_plugin' ) );
register_deactivation_hook(	__FILE__, array( 'WP_FTPAcc_to_UM', 'deactivate_plugin' ) );
register_uninstall_hook(	__FILE__, array( 'WP_FTPAcc_to_UM', 'uninstall_plugin' ) );

if( ! class_exists( 'WP_FTPAcc_to_UM' ) ){
	
	class WP_FTPAcc_to_UM
	{
		
		/**
		 * 
		 * The minimal role OR capability a user must have to enter the ftp-credentials
		 * 
		 * You can use a role or a capability to specify who can enter (and use) ftp-credentials.
		 * 
		 * Specifying a ROLE (e.g. administrator, contributor or reader) means,
		 * the user must have EXACT this role to use ftp-credentials. Use roles
		 * if you want to pick a small group of users.
		 * 
		 * Specifying a CAPABILITY (e.g. update_core, publish_posts or read) means,
		 * the user must have AT LEAST this capability to use ftp-credentials. Use capability
		 * if you want a larger group of users. 
		 * 
		 * @var string
		 */
		const MIN_LEVEL = 'administrator';

		/**
		 * 
		 * The metakey to save the ftp-credentials in usermeta
		 * @var string
		 */
		const METAKEY = 'FTP_Credentials';
		
		/**
		 * 
		 * Constant for textdomain
		 * @var string
		 */
		private static $lang = 'ftp2um';
		
		/**
		 * 
		 * Array for data from plugin-header
		 * @var array $plugin_data
		 */
		private $plugin_data = array();

		/**
		 * 
		 * Container for the different template-engines
		 * @var object $template_engine
		 */
		protected static $template_engine = NULL;
		
		/**
		 * 
		 * ID for ftp-hostname in formular
		 * @var string $ftp_host
		 */
		protected $ftp_host	= 'ftp_host';
		
		/**
		 * 
		 * ID for ftp-username in formular
		 * @var string $ftp_uname
		 */
		protected $ftp_uname = 'ftp_uname';
		
		/**
		 * 
		 * ID for ftp-password in formular
		 * @var string $ftp_pass
		 */
		protected $ftp_pass	= 'ftp_pass'; 
		
		/**
		 * 
		 * Initialize a instance of the main-class
		 * @access public static
		 * @since 0.1
		 */
		public static function plugin_start(){
			
			// start the plugin only in backend an d if the user have the minimum role/capability
			$user = wp_get_current_user();
			
			if( is_admin() && $user->has_cap( self::MIN_LEVEL ) )
				new self();
				
			unset( $user );
				
		}

		/**
		 * 
		 * Things to do on plugin-activation
		 * @access public static
		 * @since 0.1
		 */
		public static function activate_plugin(){

			// need the autoloader
			self::init_autoloader();
			
			// check environment
			$v = new stdClass();
			$v->wp = '3.0';
			$v->php = '5.2';
			
			new WP_Environment_Check( $v );
			
		}
		
		/**
		 * 
		 * Things to do on plugin-deactivation
		 * @access public static
		 * @since 0.1
		 */
		public static function deactivate_plugin(){}
		
		/**
		 * 
		 * Things to do on plugin-uninstall
		 * @access public static
		 * @since 0.1
		 */
		public static function uninstall_plugin(){
			
			global $wpdb;
			
			$wpdb->query( 
				$wpdb->prepare( 
					"DELETE FROM $wpdb->usermeta
					 WHERE meta_key = '%s'",
					self::METAKEY
				)
			);
			
		} 
		
		/**
		 * 
		 * Constructor
		 * Add hooks&filters
		 * @access public
		 * @since 0.1
		 */
		public function __construct(){
			
			// initialize the autoloader
			self::init_autoloader();
			
			// load the textdomain via annotation
			$this->loadtextdomain();
			
			// hook all actions for backend
			add_action( 'admin_init', array( &$this, 'add_admin_hooks' ) );
			
			// add a filter to 'request_filesystem_credentials' so we can override the 
			// request-credentials-formular
			add_filter( 'request_filesystem_credentials', array( &$this, 'ftp_credentials' ), 1, 0 );
				
		}
				
		/**
		 * 
		 * Initialize the autoloader
		 * @access protected
		 * @since 0.1
		 */
		protected static function init_autoloader( $config = array() ){
			
			// get the class if it was not already loaded
			if( ! class_exists( 'WP_Autoloader' ) )
				require_once dirname( __FILE__ ) . '/classes/class-wp_autoloader.php';
			
			// setup the default values
			$defaults = array(  
					'abspath'			=> __FILE__,
					'include_path' 		=> array( 'classes', ),
					'class_extension'	=> '.php',
					'class_prefix' 		=> array( 'class-', ),
			);
			
			$config = array_merge( $config, $defaults );
			
			new WP_Autoloader( $config );
			
		}

		/**
		 * 
		 * Load textdomain via annotation
		 * @since 1.0
		 * @access public
		 */
		protected function loadtextdomain(){
			
			load_plugin_textdomain( 
				$this->get_plugin_data( 'TextDomain' ), 
				FALSE, 
				dirname( plugin_basename( __FILE__ ) ) . $this->get_plugin_data( 'DomainPath' ) . '/'
			);
			
			self::$lang = $this->get_plugin_data( 'TextDomain' );
			
		}
		
		/**
		 * 
		 * Read plugin header
		 * @since 1.0
		 * @access public
		 * @param string $value
		 * @return string | array Value from the pluginheader or array with all values (if no $value is requested)
		 */
		protected function get_plugin_data( $value = '' ) {
			
			if( empty( $this->plugin_data ) ){
				
				if ( ! function_exists( 'get_plugin_data' ) )
					require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			
				$this->plugin_data = get_plugin_data( __FILE__ );
				
			}
			
			return empty( $value ) ? $this->plugin_data : $this->plugin_data[$value];

		}

		/**
		 * 
		 * Caching for the template-classes
		 * Create a WP-Error if the template-engine could not be initialize
		 * @param string $template_engine The requested template-engine
		 * @return object The requested template-engine if available
		 * @access protected
		 * @since 0.1
		 */
		protected function get_template_engine( $template_engine ){
			
			$template_engine = strtolower( $template_engine );
			
			// available template-engines and the template-classes
			$available_template_engines = array(
			
				'html'	=> array( 'WP_Simple_HTML', 'WP_Simple_HTML_Templates' )
			
			);
			
			/*
			 * uncomment this line if plugins have to override the template engines
			 */
			// $available_template_engines = apply_filters( 'ftp2db_available_template_engines', $available_template_engines );
			
			// check if the requested template-engine is available. if not, try to create it
			if( ! isset( self::$template_engine->$template_engine ) ){

				if( ! isset( $available_template_engines[$template_engine] ) ){
					
					return new WP_Error( 'template_engine', 'Unknown template-engine <strong>' . $template_engine . '</strong>' );
					
				} else {
						
					$tmpl_ng	= &$available_template_engines[$template_engine];
					$class		= &$tmpl_ng[0];
					$template	= &$tmpl_ng[1];
					
					self::$template_engine->$template_engine = new $class( new $template );
					
				}
				
			}
			
			return self::$template_engine->$template_engine;

		} 
		
		/**
		 * 
		 * Adding the filters for the admin-backend
		 * @access public
		 * @since 0.1
		 * @internal hooked by 'admin_init' in ::__constructor()
		 */
		public function add_admin_hooks(){
			
			add_action( 'show_user_profile', array( &$this, 'add_custom_user_profile_fields' ) );
			add_action( 'edit_user_profile', array( &$this, 'add_custom_user_profile_fields' ) );
			
			add_action( 'personal_options_update', array( &$this, 'save_custom_user_profile_fields' ) );
			add_action( 'edit_user_profile_update', array( &$this, 'save_custom_user_profile_fields' ) );

		}
		
		/**
		 * 
		 * Initialize and output the table for ftp-username and ftp-password
		 * @param object $user
		 * @return void
		 * @access public
		 * @since 0.1
		 * @internal hooked by 'show_user_profile' and 'edit_user_profile' in ::add_admin_hooks()
		 */
		public function add_custom_user_profile_fields( $user ){

			// get the ftp-credentials from database
			$creds = unserialize( get_user_meta( $user->ID, self::METAKEY, TRUE ) );
			
			// create the hostname for the formular
			// if no hostname (and no port) was found, blank hostname & port
			$hostname_attr = ! empty( $creds['hostname'] ) ? sprintf( '%s:%s', $creds['hostname'], $creds['port'] ) : '';
							
			// initialize the vars for the table
			$args = new stdClass();
			
			$args->headline			= __( 'FTP-Account', self::$lang );
			
			$args->host_id			= $this->ftp_host;
			$args->host_label		= __( 'FTP Host&Port', self::$lang );
			$args->host_value		= esc_attr( $hostname_attr );
			$args->host_desc		= __( 'Please enter your ftp hostname and port. E.g. <code>mydomain.com:21</code>', self::$lang );
			
			$args->uname_id			= $this->ftp_uname;
			$args->uname_label		= __( 'FTP Username', self::$lang );
			$args->uname_value		= esc_attr( $creds['username'] );
			$args->uname_desc		= __( 'Please enter your ftp username', self::$lang );
			
			$args->ftppass_id		= $this->ftp_pass;
			$args->ftppass_label	= __( 'FTP Password', self::$lang );
			$args->ftppass_value	= esc_attr( $creds['password'] );
			$args->ftppass_desc		= __( 'Please enter your ftp password', self::$lang );

			// using the template-engine to print the table
			$html = $this->get_template_engine( 'html' );
			
			if( is_wp_error( $html ) ){
				
				printf(
					'<div class="error"><h4>Template Error</h4>%s in line %s</div>',
					$html->get_error_message(),
					__LINE__
				);
				
			} else {
				$html->print_table_userprofile( $args );
			}

		}

		/**
		 * 
		 * Save the data in the usermeta
		 * @param integer $user_id
		 * @access public
		 * @since 0.1
		 */
		public function save_custom_user_profile_fields( $user_id ) {
			
			if( ! current_user_can( 'edit_user', $user_id ) )
				return FALSE;
			
			// retrieving the data from the POST array and filter them
			$user = filter_input( INPUT_POST, $this->ftp_uname, FILTER_SANITIZE_STRIPPED ); // only strip tags
			$pass = filter_input( INPUT_POST, $this->ftp_pass, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW ); // strip all characters below 32
			$host = filter_input( INPUT_POST, $this->ftp_host, FILTER_SANITIZE_STRIPPED ); // only strip tags

			// split host into hostname and port. if no port was given, set port to standard-port 21
			$host		= parse_url( $host );
			$hostname	= isset( $host['host'] ) ? $host['host'] : 
							( isset( $host['path'] ) ? $host['path'] : $host['scheme'] );
			$port		= isset( $host['port'] ) ? $host['port'] : '21';

			// save the ftp-credentials to database (usermeta). serialize the array to store a single value
			update_user_meta(
				$user_id,
				self::METAKEY,
				serialize(
					array(
						'hostname'	=> $hostname,
						'port'		=> $port,
						'username'	=> $user,
						'password'	=> $pass
					)
				)
			);
			
		}
		
		/**
		 * 
		 * Retrieve the ftp-credentials from the usermeta and serve them
		 * to the WordPress function "request_filesystem_credentials"
		 * If the user does not saved some ftp-account data, the formular to entering them will be displayed
		 * @access public
		 * @since 0.1
		 */
		public function ftp_credentials(){
			
			$user		= wp_get_current_user();
			$creds		= unserialize( get_user_meta( $user->ID, self::METAKEY, TRUE ) );

			$req_cred = array(
			
				'username'	=> '',
				'password'	=> '',
				'hostname'	=> '',
				'port'		=> '',
			
			);
			
			$req_cred = array_merge( $req_cred, $creds );
			
			// check if $req_cred get some empty values. if so, show the formular for ftp-credentials, else return the
			// credentials from database (usermeta)
			$empty = FALSE;
			foreach( $req_cred as $c )
				if( empty( $c ) )
					$empty = TRUE;
				
			if( TRUE === $empty )
				$req_cred = '';
				
			return $req_cred;
			
		}
		
	} // .end class WP_FTPAcc_to_UM
	
	
} //.end if-class-exists WP_FTPAcc_to_UM
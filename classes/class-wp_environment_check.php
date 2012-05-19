<?php
/**
 * 
 * Class to check the environment (WordPress-, PHP and MySQL-version)
 * Test only on minimum or equal version
 * 
 * @author Ralf Albert
 * @version 1.0
 * @link https://gist.github.com/1593410
 * @license GPL
 *  
 * @var array|object $versions (optional) Array with key=>val or object $version->wp|php|mysql; what to test => minimum version
 *
 */
class WP_Environment_Check
{
	/**
	 * 
	 * WP version
	 * @access public
	 * @var string minimum or equal version of WordPress
	 */
	public $wp	  = '3.2';
	
	/**
	 * 
	 * PHP version
	 * @access public
	 * @var string minimum or equal version of PHP
	 */
	public $php   = '5.2';
	
	/**
	 * 
	 * MySQL version
	 * @access public
	 * @var string minimum or equal version of MySQL
	 */
	public $mysql = '5.0';
	
	/**
	 * 
	 * Exit message if WordPress test failed
	 * @access public
	 * @var string
	 */
	public $exit_msg_wp = '';

	/**
	 * 
	 * Exit message if PHP test failed
	 * @access public
	 * @var string
	 */
	public $exit_msg_php = '';

	/**
	 * 
	 * Exit message if MySQL test failed
	 * @access public
	 * @var string
	 */
	public $exit_msg_mysql = '';
	
	/**
	 * 
	 * If set to true, the class will die with a message if a WP|PHP|MySQL test fail.
	 * Does not affect is_WP() or if forbidden_headers() is called withot a message 
	 * @access public static
	 * @var bool true (default)|false
	 */
	public static $die_on_fail = TRUE;
	
	/**
	 * 
	 * Constructor
	 * Run all test that are defined in $version
	 * @access public 
	 * @param array|object $versions
	 */
	public function __construct( $versions = NULL ){

		if( ! empty( $versions ) || ( is_array( $versions ) || is_object( $versions ) ) )
			$respond = $this->run_all_tests( $versions );
			
		return $respond;
	}

	/**
	 * 
	 * Set $die_on_fail
	 * @param bool $status True exits the script with a message 
	 */
	public function set_die_on_fail( $status = TRUE ){
		if( ! is_bool( $status ) )
			$status = (bool) $status;

		self::$die_on_fail = $status;
	}
	
	/**
	 * 
	 * Check if WordPress is active (if $wp is an object of class wp() )
	 * @access public static
	 * @return bool true|die with message and send forbidden-headers if WP is not active
	 */
	public static function is_WP(){
		/*
		 * ABSPATH is one of the first defined variables which are  global accessible.
		 * But this tells us only that a variable named 'ABSPATH' was defined.
		 * We don't know who has defined ABSPATH nor the database is connected to WordPress or not.
		 * Better we check if the database is connected with an instance of WordPress class wpdb.
		 */
		
		global $wpdb;
		
		if( ! ( $wpdb instanceof wpdb ) )
			self::forbidden_header();
		else
			return TRUE;
	}
	
	/**
	 * 
	 * Run all tests
	 * @access public
	 * @param array|object $versions
	 * @return bool true if all tests passed successfully
	 */
	public function run_all_tests( $versions = NULL ){
		if( empty( $versions ) || ( ! is_array( $versions ) && ! is_object( $versions ) ) )
			return FALSE;

		$tests = array( 'wp', 'php', 'mysql' );
		
		foreach( $versions as $test => $version ){
			// check if the wanted test is available (means: is the test x a method 'check_x')
			if( in_array( strtolower( $test ), $tests ) ){
				$method = strtolower( $test );
				$func = 'check_' . $test; // create the method (check_wp|check_php|check_mysql)
				$this->$method = $version; // set $this->wp|php|mysql to version x
				 
				if( ! call_user_func( array( &$this, $func ) ) )
					die( 'Test ' . __CLASS__ . '::' . $func . ' failed!' ); // this should never happen...
			}
		}
		
		return TRUE;
	}
		
	/**
	 * 
	 * Check WordPress version
	 * @access public
	 * @return bool true returns true if the test passed successfully. Die with a message if not.
	 */
	public function check_wp(){
		if( empty( $this->wp ) )
			return FALSE;
		
		if( empty( $this->exit_msg_wp ) )
			$this->exit_msg_wp = 'This plugin requires WordPress ' . $this->wp . ' or newer. <a href="http://codex.wordpress.org/Upgrading_WordPress">Please update WordPress</a> or delete the plugin.';
			
		global $wp_version;
		if( ! version_compare( $wp_version, $this->wp, '>=' ) ){
			return self::forbidden_header( $this->exit_msg_wp );
		}

		return TRUE;
	}
	
	/**
	 * 
	 * Check PHP version
	 * @access public
	 * @return bool true|die with message
	 */
	public function check_php(){
		if( empty( $this->php ) )
			return FALSE;
		
		if( empty( $this->exit_msg_php ) )
			$this->exit_msg_php = 'This plugin requires at least PHP version <strong>' . $this->php . '</strong>'; 
		
		if( ! version_compare( PHP_VERSION, $this->php, '>=' ) ){
			return self::forbidden_header( $this->exit_msg_php );
		}
		
		return TRUE;
	}
	
	/**
	 * 
	 * Check MYSQL version
	 * @access public
	 * @return bool true|die with message
	 */
	public function check_mysql(){
		if( empty( $this->mysql ) )
			return FALSE;
		
		if( empty( $this->exit_msg_mysql ) )
			$this->exit_msg_mysql = 'This plugin requires at least MySQL version <strong>' . $this->mysql . '</strong>';
			
		global $wpdb;
		if( ! version_compare( $wpdb->db_version(), $this->mysql, '>=' ) ){
			return self::forbidden_header( $this->exit_msg_mysql );
		}
		
		return TRUE;
	}
	
	/**
	 * 
	 * Send forbidden-headers (403) if no message is set. Only dies if a message is set
	 * @access public static
	 * @param string (optional) $exit_msg
	 */
	public static function forbidden_header( $exit_msg = '' ){

		if( empty( $exit_msg ) ){
			header( 'Status: 403 Forbidden' );
			header( 'HTTP/1.1 403 Forbidden' );
			die( "I'm sorry Dave, I'm afraid I can't do that." );
		} else {		
			if( FALSE === self::$die_on_fail )
				return FALSE;
			else			
				die( $exit_msg );
		}
	}	
}
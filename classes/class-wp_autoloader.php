<?php
/**
 * 
 * WP-Autoloader
 * 
 * Easy to use autoloader
 * 
 * To overwrite $class_prefix, an empty string have to be passed to the autoloader
 * 
 * @author Ralf Albert (neun12@googlemail.com)
 * @version 0.3
 */

/*
 * Usage
 * =====
 * 
 * $autoloader = new WP_Autoloader( [$config] );
 * 
 * $config is optional
 * $config could be array or object
 * 
 * $config should look like this:
 * 
 * $config = new stdClass();
 * $config->abspath = __FILE__;
 * $config->include_path = array( '/lib' );
 * $config->class_extension = '-class.php';
 * $config->class_prefix = array();
 *  
 *  OR
 *  
 * $config = array(  
 * 		'abspath'			=> __FILE__,
 * 		'include_path' 		=> array( 'models', 'views' ),
 * 		'class_extension'	=> '.php',
 * 		'class_prefix' 		=> array( 'model-', 'view-' ),
 * );
 * 
 */

class WP_Autoloader
{
	/**
	 * 
	 * Absolute path to include directory
	 * @var string
	 */
	public static $abspath = FALSE;
	
	/**
	 * 
	 * Relative path to include directory
	 * @var array
	 */
	public static $include_pathes	= array();
	
	/**
	 * 
	 * Extension for class files
	 * @var string
	 */
	public static $class_extension	= '.php';
	
	/**
	 * 
	 * Prefix for class files
	 * @var array
	 */
	public static $class_prefixes 	= array();

	/**
	 * 
	 * Constructor for one-step-autoloading
	 * 
	 * @param array|object $config
	 */
	public function __construct( $config = NULL ){
		if( NULL === $config )
			$config = array();
						
		// convert $config to an array if an object was given and merge defaults into the config-array
		$config = (array) $config;

		$defaults = array(
			'include_path' 		=> self::$include_pathes,
			'class_extension' 	=> self::$class_extension,
			'class_prefix' 		=> self::$class_prefixes 
		);

		extract( array_merge( $defaults, $config ) );
		
		if( isset( $abspath ) && is_string( $abspath ) );
			self::set_abspath( $abspath );
		
		self::autoloader_init( $include_path, $class_prefix, $class_extension );
	}
	
	/**
	 * 
	 * Initialize the spl_autoloader
	 * Overwrite class-vars, set&sanitize include-path, checks if include-path was already set
	 * 
	 * @param array $include_path
	 * @param array $class_prefix
	 * @param string $class_extension
	 */
	public static function autoloader_init( $include_pathes = array(), $class_prefixes = array(), $class_extension = FALSE ){

		if( FALSE === self::$abspath )
			self::$abspath = dirname( __FILE__ );
		
		if( ! is_array( $include_pathes ) )
			$include_pathes = (array) $include_pathes;
			
		if( ! empty( $include_pathes ) )
			self::set_includepath( $include_pathes );
			
		if( ! is_array( $class_prefixes ) )
			$class_prefixes = (array) $class_prefixes;
			
		if( ! empty( $class_prefixes ) )
			self::$class_prefixes = $class_prefixes;

		if( FALSE !== $class_extension )
			self::$class_extension = $class_extension;
  		
		/*
		 * From php.net/spl_autoload (http://de3.php.net/manual/de/function.spl-autoload.php)
		 * 
		 * 1. Add your class dir to include path
		 * 2. You can use this trick to make autoloader look for commonly used "My-class.php" type filenames
		 * 3. Use default autoload implementation or self defined autoloader 
		 */

		foreach( self::$include_pathes as $includepath ){

			$path = self::$abspath . DIRECTORY_SEPARATOR . $includepath;

			// check if the path have already been added to include_path
			$pathes = explode( PATH_SEPARATOR, get_include_path() );

			if( ! in_array( $path, $pathes ) )				
				// set our path at the first position. require, include, __autoload etc. start searching in the first path
				// with our custom path at the first, PHP does not have to search in all other pathes for our classes
				set_include_path( $path . PATH_SEPARATOR . get_include_path() );
			
		}
			
		spl_autoload_extensions( self::$class_extension );
		spl_autoload_register( array( __CLASS__, 'autoload' ) );

	}
		
	/**
	 * 
	 * Callback for spl_autoload_register
	 * 
	 * @param string $class_name
	 */
	private static function autoload( $class_name ){
		// if a class-prefix is set, add it to the class-name
		$load_error = FALSE;
		
		if( ! empty( self::$class_prefixes ) ){
			foreach( self::$class_prefixes as $prefix ){
				$test_class_name = $prefix . $class_name;
				
				try {
					spl_autoload( $test_class_name );
				} catch ( Exception $e ) {
					$load_error = TRUE;
				}
						
			}
			
			if( $load_error )
				throw new Exception( 'Class ' . $class_name . ' not found' );
				
		} else {
				try {
					spl_autoload( $class_name );
				} catch ( Exception $e ) {
					throw new Exception( 'Class ' . $class_name . ' not found' );
				}
		}
		
	}
	
	/**
	 * 
	 * Set the absolute path to file
	 * 
	 * @param string $abspath
	 * @return bool True on success (abspath is an file or directory), false on fail
	 */
	public static function set_abspath( $abspath = FALSE ){
		if( FALSE != $abspath && is_string( $abspath ) ){
			
			if( is_file( $abspath ) ){	
				self::$abspath = dirname( $abspath );
			}
			elseif( is_dir( $abspath) ){
				self::$abspath = $abspath;
			}
			else
				return FALSE;
		}
		
		return TRUE;  
	}
	
	/**
	 * 
	 * Sanitize and set the include path variable
	 * Returns the include path if it is a directory. Returns false if failed
	 * 
	 * @param array $pathes
	 * @return bool True if $path is a file or directory (siccess). False if not (fail)
	 */
	public static function set_includepath( $pathes = array() ){
		if( empty( $pathes ) )
			return FALSE;

		$sanitized_pathes = array();
		
		foreach( $pathes as $path ){
			if( is_string( $path ) ){
				// strip slashes and backslashes at the start and end of the string /classes/ -> classes; /lib/classes/ -> lib/classes
				$path = preg_replace( "#^[/|\\\]|[/|\\\]$#", '', $path );
				
				// replace slashes and backslashes with the OS specific directory seperator
				$path = preg_replace( "#[/|\\\]#", DIRECTORY_SEPARATOR, $path );
				
				// check if the directory exists
				if( ! is_dir( self::$abspath . '/' . $path ) )
					continue;
	
				// $path is a file or directory and was successfully converted
				array_push( $sanitized_pathes, $path );
			}	
		}

		self::$include_pathes = $sanitized_pathes;
		
		unset( $sanitized_pathes );
	}

}

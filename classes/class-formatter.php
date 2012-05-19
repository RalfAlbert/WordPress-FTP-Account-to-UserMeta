<?php
/**
 * Simple Template Engine
 *
 * PHP version 5.3
 *
 * @category   PHP
 * @package    Template_Engine
 * @author     Ralf Albert <me@neun12.de>
 * @license    GPLv3 http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    0.1.1
 * @link       http://neun12.de
 */

/**
 * 
 * Formtatter
 *  
 * Formatter is a class which provide a simple template engine. It uses the printf/sprintf-syntax to format
 * values.
 * 
 * Usage:
 * $values = array(
 * 	'key_1'	=> 'value_1',
 * 	'key_2'	=> 'value_2',
 * );
 * 
 * $format = 'key_1 have value %key_1%. key_2 have value %key_2%. %key_1% and %key_2%';
 * Formatter::printf( $format, $values );
 * 
 * $values = new stdClass();
 * $values->key_1 = 'value 1';
 * $values->key_2 = 'value 2';
 * $format = '>key_1< belongs to key_1, >key_2< belongs to key_2';
 * 
 * Formatter::set_delimiter( '<', '>' );
 * $e = Formatter::sprintf( $format, $values );
 * 
 * $values = array(
 * 	'number_one'	=> 3,
 *  'number_two'	=> 5,
 *  );
 *  
 * $format	 = 'Number One filled to 5 positions: %number_one[05d]%';
 * $format	.= ' and Number Two as hex (upper chars) filled to 6 positions: %number_two[06X]%';
 * Formatter::printf( $format, $values );
 *  
 *    
 * Formatter::printf( string $format, array|object $values );
 * Formatter::sprintf( string $format, array|object $values );
 * Formatter::set_delimiter( string $start_delimiter, string $end_delimiter );
 * 
 * @author Ralf Albert
 * @version 1.1.1
 * @see http://php.net/manual/function.sprintf.php
 */
 
class Formatter
{
	/**
	 * 
	 * Starting delimiter
	 * @var string
	 */
	public static $start_delimiter	= '%';
	
	/**
	 * 
	 * Ending delimiter
	 * @var string
	 */
	public static $end_delimiter	= '%';
	
	/**
	 * 
	 * Replacing values in a format-string
	 * @param string $format
	 * @param array|object $values
	 * @throws Exception
	 * @return string|bool	Returns the formated string or FALSE on failure
	 */
	public static function sprintf( $format = '', $values = NULL ){
		/*
		 * Checking arguments
		 */
		if( empty( $format ) || NULL == $values )
			return FALSE;
			
		if( ! is_string( $format ) )
			throw new Exception( 'Format must be a string' );
			
		if( ! is_array( $values ) && ! is_object( $values ) )
			throw new Exception( 'Values must be type of array or object' );
			
		/*
		 * Do the replacement
		 */	
		foreach( $values as $key => $value ){

			$matches	= array();
			$search_key	= sprintf( '%s%s%s', self::$start_delimiter, $key, self::$end_delimiter );
			$pattern	= sprintf( '/%%%s\[(.*)\]%%/iU', $key );

			// search for the values in format-string. find %key% or %key[format]%
			preg_match_all( $pattern, $format, $matches );
			
			// the '[format]' part was not found. replace only the key with the value 
			if( empty( $matches[1] ) ){
				$format = str_replace( $search_key, $value, $format );
			}
			// one or more keys with a '[format]' part was found.
			// walk over the formats and replace the key with a formated value
			else {

				foreach( $matches[1] as $match ){
					$replace = sprintf( '%' . $match, $value );
					$search = sprintf( '%s%s[%s]%s', self::$start_delimiter, $key, $match, self::$end_delimiter );
					$format = str_replace( $search, $replace, $format );
				}
			}
		}

		// return the formatted string	
		return $format;
		
	}
	
	/**
	 * 
	 * Print a formated string
	 * @param string $format
	 * @param array|object $values
	 * @uses Formatter::sprintf()
	 * @return void
	 */
	public static function printf( $format, $values ){
		echo self::sprintf( $format, $values );
	}
	
	/**
	 * 
	 * Set the start- and end-delimiter
	 * @param string $start
	 * @param string $end
	 */
	public static function set_delimiter( $start = '%', $end = '%' ){
		self::$start_delimiter	= $start;
		self::$end_delimiter	= $end;
	}
}
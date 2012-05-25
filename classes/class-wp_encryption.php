<?php
/**
 * Class to encrypt and decrypt in WordPress
 *
 * PHP version 5.3
 *
 * @category   PHP
 * @package    WordPress
 * @subpackage WP Encrypt
 * @author     Ralf Albert <me@neun12.de>
 * @license    GPLv3 http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    0.1
 * @link       http://wordpress.com
 */

class WP_Encrypt
{
	
	/**
	 * 
	 * Key to encrypt and decrypt data 
	 * @var string
	 */
	private $key = '';
	
	/**
	 * 
	 * The method to use for encryption/decryption
	 * @var string
	 */
	private $method = '';
	
	/**
	 * 
	 * Available methods to encrypt/decrypt
	 * @var array
	 */
	private $available_methods = array(
									'mysql'
								);
								
	/**
	 * 
	 * Default method for encryption/decryption
	 * @var string
	 */
	private $default_method = 'mysql';
	
	public function construct( $method = 'mysql' ){
		
		// sanitize the method to use for cryption
		if( '' != $method ){
			
			$method = strtolower( $method );
			
			if( in_array( $method, $this->available_methods ) )
				$this->method = $method;
			else
				$this->method = $this->default_method;
			
		} else {
			
			$this->method = $this->default_method;
			
		}
		
	}
	
	/**
	 * 
	 * Returns an array with available cryption-methods
	 * @return array $available_methods
	 * @access public
	 * @since 0.1
	 */
	public function get_methods(){
		
		return $this->available_methods;
		
	}
	
	public function set_key( $key = '' ){
		
		if( ! is_string( $key ) )
			return FALSE;
		else
			$this->key = $key;
			
	}
	
	public function encrypt( $string ){
		
	}
	
	public function decrypt( $string ){
		
	}
	
	
}
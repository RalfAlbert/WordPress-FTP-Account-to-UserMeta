<?php
/**
 * HTML-Elements for WordPress
 *
 * PHP version 5.3
 *
 * @category   PHP
 * @package    Template_Engine
 * @subpackage WP_Simple_HTML
 * @author     Ralf Albert <me@neun12.de>
 * @license    GPLv3 http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    0.1
 * @link       http://wordpress.com
 */

/**
 * 
 * Abstract class WP Simple Templater creates a copy of the template-class and
 * provide the templates to the template-engine
 * @author Ralf Albert
 *
 */
abstract class WP_Simple_Templater extends Formatter
{
	/**
	 * 
	 * Instance of template-class
	 * @var object $templates_object
	 */
	protected $templates_object = NULL;
	
	/**
	 * 
	 * Constructor
	 * Creates an instance of the template-class and setup the delimiters for Formatter
	 * @param WP_Simple_Templates $templates
	 */
	public function __construct( WP_Simple_Templates $templates ){
		
		$this->templates_object = &$templates;
		
		$this->set_delimiter( '%', '%' );
		
	}
	
	/**
	 * 
	 * Return a template by given type. Returns an wp-error on failure
	 * @param string $type
	 * @return string|bool Return a template-string if it was defined by the template-class. Or false if no such template was defined
	 */
	protected function get_templates( $templatetype = '', $type = '' ){
		
		// error message
		$err_msg = '';
		
		// if the sub-type is empty (e.g the template is a string not an array), use the template-type as sub-type
		if( '' == $type )
			$type = $templatetype;
		
		// first check if a template-type was set
		if( '' == $templatetype )
			$err_msg = 'Empty template-type';
		
		// check if a template-class was loaded
		elseif( NULL === $this->templates_object )
			$err_msg = 'No templates defined in <strong>' . get_class( $this ) . '</strong>';					

		// we have a template-type and a template-class. get the template(s)
		else {
			
			 $template = $this->templates_object->get_templates( $templatetype );

			 // get error-message thrown by template-class
			 if( is_wp_error( $template ) )
			 	$err_msg = $template->get_error_message();

			 // check the template.
			 // convert a string into an array. else check if the requested type is in the template-array
			 else {
			 	
				 if( ! is_array( $template ) )
				 	$template[$type] = $template;
	
				 elseif( ! in_array( $type, array_keys( $template ) ) )
				 	$err_msg = $type . ' is not defined.';

			 }
			 
		}

		// if a error occurs, return an error-object
		if( '' != $err_msg ){
			
			return new WP_Error(
					'template_error',
					sprintf(
						'<div class="error"><h4>Template Error</h4>%s</div>',
						$err_msg
					)
				);
						
		}

		// finally all checks are ok, return the template-array
		return $template;
	
	}
	
}

/**
 * 
 * The concrete class WP Simple HTML insert the data in the templates
 * @author Ralf Albert
 *
 */
class WP_Simple_HTML extends WP_Simple_Templater
{
	
	/**
	 * 
	 * Constructor overrides the delimiters in parent class
	 * @param WP_Simple_Templates $templates
	 */
	public function __construct( WP_Simple_Templates $templates ){
		
		parent::__construct( $templates );
		
		$this->set_delimiter( '{', '}' );
		
	}
	
	/**
	 * 
	 * Print the table userprofiles
	 * @param array $args
	 * @access public
	 * @since 0.1
	 */
	public function print_table_userprofile( $args ){

		echo $this->get_table_userprofile( $args );
		
	}
	
	/**
	 * 
	 * Create the table userprofile with the given arguments
	 * @param array $args
	 * @access public
	 * @since 0.1
	 */
	public function get_table_userprofile( $args ){
		
		if( ! is_array( $args ) )
			$args = (array) $args;

		$template = $this->get_templates( 'table_userprofile' );
		
		if( is_wp_error( $template ) )
			return $template->get_error_message();
				
		
		$defaults = array(
		
			'headline'		=> 'Headline',
		
			'host_id'		=> 'host_id',
			'host_label'	=> 'Label For Host',
			'host_value'	=> 'localhost:21',
			'host_desc'		=> 'Description for hostname',
		
			'uname_id'		=> 'uname_id',
			'uname_label'	=> 'Label For Uname',
			'uname_value'	=> 'UserName',
			'uname_desc'	=> 'The description for username',
		
			'ftppass_id'	=> 'ftppass_id',
			'ftppass_label'	=> 'Label For FTPPass',
			'ftppass_value'	=> 'Your ftp-password',
			'ftppass_desc'	=> 'The description for ftp-password',
		
		);
		
		$args = array_merge( $defaults, $args );
		
		return self::sprintf( $template['table'], $args );
		
	}

}


/* ------------------------------------------------------------------------------ */
/* ---------- Templates -------------------------------------------------- */
/* ------------------------------------------------------------------------------ */

/**
 * 
 * Abstract class WP Simple Templates define a method to retrive the defined templates
 * and a method to return all available templates
 * @author Ralf Albert
 *
 */
abstract class WP_Simple_Templates
{
	
	/**
	 * 
	 * Returns the template if it was defined in the template class. If the requested template was not
	 * defined in the template-class, return a wp-error.
	 * @param string $type
	 * @return array|string|object Array with template-strings or a single template-string or wp-error on failure
	 * @access public
	 * @since 0.1.2
	 */
	public function get_templates( $type = '' ){
		
		if( '' == $type )
			return NULL;
		
		// if the template-class have a method $type, return the template(s). else return a wp-error
		if( method_exists( $this, $type . '_template' ) ){
			
			$method = $type . '_template';
			return $this->$method();
			
		} else {
		
			return new WP_error(
				'template_error',
				printf(
					'Template <strong>%s</strong> does not exists in <strong>%s</strong>',
					$type,
					get_class( $this )
				)
			);

		}
			
	}
	
	/**
	 * 
	 * Return an array with all available templates from the template-class
	 * @return array $templates Array with available templates
	 * @access public
	 * @since 0.1.2
	 */
	public function get_available_templates(){

		// get all methods from the abstract class (__CLASS__) and the extended class ($this)
		$self_methods		= get_class_methods( __CLASS__ );
		$extended_methods	= get_class_methods( $this );
		
		// remove the '_template' extension and return the array with template-names
		$templates = array();
		
		foreach( array_diff( $extended_methods, $self_methods ) as $template )
			array_push( $templates, str_replace( '_template', '', $template ) );
			
		return $templates;
		
	}
	
}

/**
 * 
 * Concrete class WP Simple HTML Templates defines the templates
 * @author Ralf Albert
 *
 */
class WP_Simple_HTML_Templates extends WP_Simple_Templates
{
	
	/**
	 * 
	 * The template for table userprofile
	 */
	public function table_userprofile_template(){

		return '<h3>{headline}</h3>
				
				<table class="form-table">
					<tr>
						<th><label for="{host_id}">{host_label}</label></th>
						
						<td>
							<input type="text" name="{host_id}" id="{host_id}" value="{host_value}" class="regular-text" /><br />
							<span class="description">{host_desc}</span>
						</td>
						
					</tr>
				
					<tr>
						<th><label for="{uname_id}">{uname_label}</label></th>
						
						<td>
							<input type="text" name="{uname_id}" id="{uname_id}" value="{uname_value}" class="regular-text" /><br />
							<span class="description">{uname_desc}</span>
						</td>
						
					</tr>

					<tr>
						<th><label for="{ftppass_id}">{ftppass_label}</label></th>
						
						<td>
							<input type="password" name="{ftppass_id}" id="{ftppass_id}" value="{ftppass_value}" class="regular-text" /><br />
							<span class="description">{ftppass_desc}</span>
						</td>
					
					</tr>
				</table>';

	}
		
}
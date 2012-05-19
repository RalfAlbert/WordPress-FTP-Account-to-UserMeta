<?php
/**
 * HTML-Elements for WordPress
 *
 * PHP version 5.3
 *
 * @category   PHP
 * @package    Template_Engine
 * @subpackage WP_Simple_Forms
 * @author     Ralf Albert <me@neun12.de>
 * @license    GPLv3 http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    0.1
 * @link       http://wordpress.com
 */

class WP_Simple_HTML extends Formatter
{
	/**
	 * 
	 * Instance of class Simple_Forms_Templates
	 * @var object $templates_object
	 * @access protected
	 * @since 0.1
	 * @see class-simple_forms_templates.php
	 */
	protected $templates_object = NULL;
	
	public function __construct( WP_Simple_Templates $templates ){
		
		$this->templates_object = &$templates;
		
		$this->set_delimiter( '{', '}' );
	}
	
	protected function get_templates( $type = '' ){
		
		if( '' == $type )
			return FALSE;
		
		if( NULL === $this->templates_object ){
			
				printf(
					'<div class="error"><h4>Template Error</h4>No templates defined in <strong>%s</strong></div>',
					get_class( $this )
				);
						
		} else {
			
			 $template = $this->templates_object->get_templates( $type );

			 if( FALSE != $template )
			 	return $template;
			 	
		}
	
	}
			
	public function print_table_userprofile( $args ){

		if( ! is_array( $args ) )
			$args = (array) $args;

		echo $this->get_table_userprofile( $args );
		
	}
	
	public function get_table_userprofile( $args ){
		
		if( ! is_array( $args ) )
			$args = (array) $args;

		$template = $this->get_templates( 'table_userprofile' );
		
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

abstract class WP_Simple_Templates
{
	public function get_templates( $type = '' ){
		
		if( '' == $type )
			return NULL;
			
		if( method_exists( $this, $type . '_template' ) ){
			
			$method = $type . '_template';
			return $this->$method();
			
		} else {
				printf(
					'<div class="error"><h4>Template Error</h4>Template <strong>%s</strong> does not exists in <strong>%s</strong></div>',
					$type,
					get_class( $this )
				);
				
				return FALSE;
		}
			
	}
	
}

class WP_Simple_HTML_Templates extends WP_Simple_Templates
{
	public function table_userprofile_template(){

		return array(
				'table' => '<h3>{headline}</h3>
				
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
				</table>'

		);
		
	}
		
}
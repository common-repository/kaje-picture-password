<?php
if(!class_exists('Kaje_Picture_Password_Settings'))
{
	class Kaje_Picture_Password_Settings
	{
		//Construct the plugin object
		public function __construct()
		{
			// register actions
			add_action('admin_init', array(&$this, 'admin_init'));
			add_action('admin_menu', array(&$this, 'add_menu'));
			add_action('admin_footer', array(&$this, 'admin_footer'));
		}
		
		public function admin_footer()
		{
			echo '<p style="text-align:center"><img src="' . plugins_url( 'img/Icon_Green_White_25x25.png' , __FILE__ ) . '" >&nbsp;Kaje Picture Password™<br>';
			echo 'Copyright ' . date("Y") . '  Bright Plaza, Inc.<br>';
			echo '<a href="http://www.picturepassword.info" target="_blank">http://www.picturepassword.info</a></p>';
		}
			
		//hook into WP's admin_init action hook
		public function admin_init()
		{			
			// register your plugin's settings
			register_setting('kaje_picture_password-group', 'relying_party_id', array(&$this, 'validate_kaje_relying_party_id'));
			register_setting('kaje_picture_password-group', 'relying_party_secret', array(&$this, 'validate_kaje_relying_party_secret'));

			// add your settings section
			add_settings_section(
				'kaje_picture_password-section', 
				'Kaje Picture Password Settings', 
				array(&$this, 'settings_section_kaje_picture_password'), 
				'kaje_picture_password'
			);
				
			// add your setting's fields
			add_settings_field(
				'kaje_picture_password-relying_party_id', 
				'Relying Party ID', 
				array(&$this, 'settings_field_input_text'), 
				'kaje_picture_password', 
				'kaje_picture_password-section',
				array(
					'field' => 'relying_party_id'
				)
			);
			add_settings_field(
				'kaje_picture_password-relying_party_secret', 
				'Relying Party Secret', 
				array(&$this, 'settings_field_input_text'), 
				'kaje_picture_password', 
				'kaje_picture_password-section',
				array(
					'field' => 'relying_party_secret'
				)
			);
			
			settings_errors( 'relying_party_id' );
		}
		
		public function validate_kaje_relying_party_id($input)
		{
			if($this->verifyValidUUID($input)) {
				return $input;	
			}
			
			add_settings_error('relying_party_id', 'invalid_relying_party_id', 'You have entered an invalid Relying Party ID.' );
			return '';			
		}
		
		public function validate_kaje_relying_party_secret($input)
		{
			if($this->verifyValidUUID($input)) {
				return $input;	
			}
			
			add_settings_error('relying_party_secret', 'invalid_relying_party_secret', 'You have entered an invalid Relying Party Secret.' );
			return '';
		}
		
		public function verifyValidUUID($uuid)
		{
			if(preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/i', $uuid)) {
				return true;
			}
			return false;
		}
        
        public function settings_section_kaje_picture_password()
        {
            //help text for the section
            $kajeAdminURL = 'https://kaje.authenticator.com/index.php/admin';
			$html  = '<div class="updated"><p>';            
			$html .= '<li>You can return to your <a href="' . $kajeAdminURL . '" target="_blank">Kaje Admin Account</a> at anytime to edit information or policies regarding Picture Password logins on your site.</li>';
			$html .= '</ul></p></div>';
			
			echo $html;
        }
        
        //This function provides text inputs for settings fields
        public function settings_field_input_text($args)
        {
            // Get the field name from the $args array
            $field = $args['field'];
            // Get the value of this setting
            $value = get_option($field);
            // echo a proper input type="text"
            echo sprintf('<input type="text" size="45" maxlength="36" name="%s" id="%s" value="%s" />', $field, $field, $value);
        }
		
        //add a menu
        public function add_menu()
        {
            // Add a page to manage this plugin's settings
			add_options_page(
				'Kaje Picture Password Settings', 
				'Kaje Picture Password', 
				'manage_options', 
				'kaje_picture_password', 
				array(&$this, 'plugin_settings_page')
                );
        }
		
        //Menu Callback
        public function plugin_settings_page()
        {
			if(!current_user_can('manage_options'))
			{
					wp_die(__('You do not have sufficient permissions to access this page.'));
			}
	
			// Render the settings template
			include(sprintf("%s/templates/settings.php", dirname(__FILE__)));
        }
    }
}
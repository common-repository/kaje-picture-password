<?php
/**
 * Plugin Name: Kaje Picture Password
 * Plugin URI: http://www.picturepassword.info
 * Description: Easily integrate Kaje Picture Password™ on your WordPress site. 
 * Version: 1.2
 * Author: Bright Plaza Inc.
 * Author URI: http://www.picturepassword.info
 * License: GPL2
 */

/*  Copyright 2014  Bright Plaza, Inc.

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

if(!class_exists('Kaje_Picture_Password'))
{
	class Kaje_Picture_Password
	{
		
		public function __construct()
		{			
			//if Kaje settings page not loaded via SSL, then redirect using https
			if(isset($_GET['page']) && $_GET['page'] == 'kaje_picture_password' && empty($_SERVER['HTTPS'])) {
				$https_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				header('Location: ' . $https_url);
				exit();
			}
			
			// Initialize Settings
			require_once(sprintf("%s/settings.php", dirname(__FILE__)));
			$Kaje_Picture_Password_Settings = new Kaje_Picture_Password_Settings();
			
			// Initialize Login
			require_once(sprintf("%s/login.php", dirname(__FILE__)));
			$Kaje_Picture_Password_Login = new Kaje_Picture_Password_Login();
			
			//If RP ID and Secret are not set, display message
			if(get_option('relying_party_id') == '' || get_option('relying_party_secret') == '') {
    				add_action('admin_notices', array(&$this, 'kaje_admin_notice'));
			}
			else {
				add_action('admin_notices', array(&$Kaje_Picture_Password_Login, 'kaje_status_message'));
			}
			
			//load jquery
			add_action( 'init', array(&$this, 'load_jquery'));
		}
		
		public function kaje_admin_notice()
		{			
			$html  = '<div class="error"><p>';
			
			//kaje admin notice when not on the settings page
			if(!isset($_GET['page']) || $_GET['page'] != 'kaje_picture_password') {
				$settingsURL = admin_url('options-general.php?page=kaje_picture_password');
				$html .= 'In order to use Kaje Picture Password on your site, you\'ll need to enter your Relying Party <strong>ID</strong> and <strong>Secret</strong> on the <strong><a href="' . $settingsURL . '">Kaje Settings Page</a></strong>.';
			}
			
			//kaje admin page instructions if on the settings page
			if(isset($_GET['page']) && $_GET['page'] == 'kaje_picture_password') {
				$kajeAdminURL = 'https://kaje.authenticator.com/index.php/admin';
				$html .= '<li>To use Kaje Picture Password on your site, you\'ll need to create(or access) your <a href="' . $kajeAdminURL . '" target="_blank">Kaje Admin Account</a>.</li>';
				$html .= '<li>&nbsp;&nbsp;&nbsp;<em>NOTE</em>:&nbsp;&nbsp;When entering in the <strong>DOMAIN</strong> and <strong>REDIRECT</strong> values, use this for both:&nbsp;&nbsp;<strong>' . get_site_url() . '</strong></li>';
				$html .= '<li>Once you\'ve created your account and verified ownership of your domain, you\'ll be given an <strong>ID</strong> and <strong>SECRET</strong>.</li>';
				$html .= '<li>Enter that info below and Kaje will be enabled on your site.</li>';
			}
			
			$html .= '</p></div><!-- /.updated -->';
						
			echo $html;
	
		}
		
		public function load_jquery()
		{
			wp_enqueue_script( 'jquery' );
		}

		//Activate the plugin
		public static function activate()
		{
			//nothing to do here yet
		}
		
		//Deactivate the plugin                
		public static function deactivate()
		{
			//nothing to do here yet
		}
	}
}

if(class_exists('Kaje_Picture_Password'))
{
	// Installation and uninstallation hooks
	register_activation_hook(__FILE__, array('Kaje_Picture_Password', 'activate'));
	register_deactivation_hook(__FILE__, array('Kaje_Picture_Password', 'deactivate'));

	// instantiate the plugin class
	$kaje_picture_password = new Kaje_Picture_Password();
        
    // Add a link to the settings page onto the plugin page
    if(isset($kaje_picture_password))
    {
        // Add the settings link to the plugins page
        function plugin_settings_link($links)
        { 
            $settings_link = '<a href="options-general.php?page=kaje_picture_password">Settings</a>'; 
            array_unshift($links, $settings_link); 
            return $links; 
        }

        $plugin = plugin_basename(__FILE__); 
        add_filter("plugin_action_links_$plugin", 'plugin_settings_link');
    }
}
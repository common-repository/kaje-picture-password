<?php
if(!class_exists('Kaje_Picture_Password_Login'))
{
	class Kaje_Picture_Password_Login
	{
		public function __construct()
		{
			// register actions
			add_action('wp_loaded', array(&$this, 'handle_kaje_redirect'));				//when redirected back from Kaje
			add_action('login_head', array(&$this, 'handle_kaje_login'));				//display Kaje login when applicable
			add_action('wp_login', array(&$this, 'check_kaje_setup'), 10, 2);			//check if a Kaje account needs created or setup
			add_filter('user_row_actions', array(&$this, 'kaje_row_actions'), 10, 2);	//add extra actions on user management pages
			add_action('admin_footer', array(&$this, 'kaje_ajax_javascript'));			//add ajax code on admin pages
			add_action('wp_ajax_kaje_action', array(&$this, 'kaje_action_callback'));	//ajax callback for Kaje actions (lock, reset, etc.)
		}
		
		public function kaje_status_check()
		{			
			//get current Kaje status
			$kajeStatus = $this->call_kaje('status');
			return (!$kajeStatus) ? false : $kajeStatus;
		}
		
		public function kaje_status_message()
		{
			if(isset($_GET['page']) && $_GET['page'] == 'kaje_picture_password') {
				$kajeStatus = $this->kaje_status_check();
				$kajeStatusError = (!$kajeStatus || !(isset($kajeStatus->msg_code)) || ($kajeStatus->msg_code != '700')) ? true : false;
				
				//if kaje not available, try to get error message
				if($kajeStatusError) {
					if($kajeStatus) {
						if(isset($kajeStatus->msg_code)) {
							$errorMessage = handle_kaje_error($kajeStatus->msg_code);
						}
						else {
							$errorMessage = $kajeStatus;
						}
					}
					else {
						$errorMessage = 'Unknown';
					}
				}
				else {
					$errorMessage = false;
				}				
								
				$html  = (!$kajeStatusError) ? '<div class="updated"><p>' : '<div class="error"><p>';
				$html .= '<strong>STATUS:&nbsp;&nbsp;</strong>The Kaje Picture Password service is <strong>';
				$html .= (!$kajeStatusError) ? 'AVAILABLE' : 'UNAVAILABLE';
				$html .= '</strong>';
				$html .= ($errorMessage) ? '<br><strong>ERROR:</strong>&nbsp;&nbsp;' . $errorMessage : '';
				$html .= '</p></div>';			
				echo $html;
			}
		}
		
		public function handle_kaje_login()
		{
			//verify Kaje service is available
			$kajeStatus = $this->kaje_status_check();
			if(!$kajeStatus || !(isset($kajeStatus->msg_code)) || ($kajeStatus->msg_code != '700')) {
				return;
			}
			
			//if the RP ID and Secret are not both set, don't show Kaje login button
			if(!get_option('relying_party_id') || !get_option('relying_party_secret')) {
				return;
			}
			
			//don't show Kaje button on any of these pages
			if(isset($_GET['action'])) {
				if(
					$_GET['action'] == 'register' ||
					$_GET['action'] == 'lostpassword' ||
					$_GET['action'] == 'retrievepassword'
				)
				return;
			}
			
			$error = false;
			
			//if the Kaje login button was pressed
			if(isset($_POST['kajelogin'])) {			
				$error = $this->handle_kaje_button_press();
			}
			
			$this->kaje_login_screen_changes($error);
		}

		//if returning from a Kaje Login, set kaje_setup_account back to 'no'
		//if a successful "TEXT" login, check if a Kaje account needs created or setup.
		public function check_kaje_setup($user_login, $user)
		{			
			if(isset($_GET['auth_token'])) {											//returning from a Kaje Login
				if(get_user_meta($user->data->ID, 'kaje_setup_account', true)) {
					update_user_meta($user->data->ID, 'kaje_setup_account', 'no');		//kaje_setup_account = 'no'
				}
				
				if(get_user_meta($user->data->ID, 'kaje_reset_account', true)) {
					update_user_meta($user->data->ID, 'kaje_reset_account', 'no');		//kaje_reset_account = 'no'
				}
			} //else Kaje account needs created or setup				
			else if(isset($user->data->ID) && ( get_user_meta($user->data->ID, 'kaje_setup_account', true) == 'yes' || get_user_meta($user->data->ID, 'kaje_reset_account', true) == 'yes') ) {
				if(!get_user_meta($user->data->ID, 'kaje_ID', true)) {  //if no existing Kaje account
					$this->kaje_new_user($user->data->ID);				//create a new one
				}
				else if(get_user_meta($user->data->ID, 'kaje_setup_account', true) == 'yes') {
					$this->kaje_login_user(get_user_meta($user->data->ID, 'kaje_ID', true), $user->data->ID, true);	//force Kaje login to setup accounts in NEW or RESET status.  Last arg must be TRUE.
				}
				else if(get_user_meta($user->data->ID, 'kaje_reset_account', true) == 'yes') {
					$this->kaje_login_user(get_user_meta($user->data->ID, 'kaje_ID', true), $user->data->ID, true, true);	//force Kaje reset and login.  Last two args must be TRUE.
				}
			}
		}
		
		public function handle_kaje_button_press()
		{			
			$error = false;
			
			$username = (validate_username($_POST['log'])) ? $_POST['log'] : false;		//check for a valid username
			if($username) {																//if valid				
				$userRecord = get_user_by('login', $username);							//get user record
				if($userRecord && isset($userRecord->data->ID)) {						//if record and ID are found
					$kajeID = get_user_meta($userRecord->data->ID, 'kaje_ID', true);	//get kaje_ID		
					if($kajeID) {														//if kaje_ID exists
						if(get_user_meta($userRecord->data->ID, 'kaje_reset_account', true) == 'yes') {	//if user flagged for Kaje reset
							$error = $this->handle_kaje_user_status('reset');			//return an error based on Kaje reset flag
						}
						else {															//else attempt to login user via Kaje
							$error = $this->kaje_login_user($kajeID, $userRecord->data->ID);
						}
					}
					else {																//flag the user to have a new Kaje account set up				
						if(!get_user_meta($userRecord->data->ID, 'kaje_setup_account', true)) {
							add_user_meta($userRecord->data->ID, 'kaje_setup_account', 'yes');
						} else {
							update_user_meta($userRecord->data->ID, 'kaje_setup_account', 'yes');
						}
						$error = 'To set up a new Kaje Picture Password login, you will first need to login with your text password.';
					}
				}
				else {
					$error = 'User not found';											//user record or ID not found for the given username
				}
			}
			else {
				$error = 'Invalid username';											//username given is invalid
			}
			
			return $error;
		}
		
		public function handle_kaje_redirect()
		{
			//if redirected back from Kaje
			if(isset($_GET['auth_token'])) {			
				$kajeResponse = $this->call_kaje('id', false, $_GET['auth_token']);													//Make API call to request ID token
				if($kajeResponse && isset($kajeResponse->user_id) && $kajeResponse->user_id) {										//if a kaje user id was returned
					if(isset($kajeResponse->user_status) && $kajeResponse->user_status == 'unlocked') {								//if the kaje user status is unlocked
						$user_query = new WP_User_Query(array('meta_key' => 'kaje_ID', 'meta_value' => $kajeResponse->user_id));	//get WP user based on kaje_id
						if(count($user_query->results)) {																			//if user is found		
							$this->kaje_login_wp_user($user_query->results[0]);														//log them in
						}
					}
				}
			}
		}
		
		//log user into WP
		public function kaje_login_wp_user($user)
		{
			if (is_user_logged_in()) {
				wp_logout();
			}
			
			//$user = get_userdata($ID);
			wp_set_current_user($user->ID, $user->user_login);
			wp_set_auth_cookie($user->ID);
			do_action('wp_login', $user->user_login, $user);
		}
		
		public function kaje_login_screen_changes($error = false)
		{
			?>
				<script>
				jQuery(document).ready(function() {
					kajeButton = '<p>&nbsp;<br>&nbsp;<br><img src="<?php echo plugins_url( 'img/Icon_Green_White_25x25.png' ,  __FILE__ ); ?>"><button id="kaje-submit" class="button button-primary button-large">Log In With Kaje Picture Password</button></p>';
					jQuery('.submit').after(kajeButton);
					jQuery('#kaje-submit').click(function() {
						jQuery('#kaje-submit').after('<input type="hidden" name="kajelogin" value="1" />');
					});
					
					<?php if($error) { ?>
						jQuery('#login_error').html('<strong>KAJE: </strong><?php echo $error; ?>');
					<?php } ?>
				});
				</script>
			<?php
		}
		
		public function kaje_new_user($user_ID)
		{
			//Make API call to request a new Kaje user be created
			$kajeResponse = $this->call_kaje('newuser');

			if(!$kajeResponse || !isset($kajeResponse->user_id) || !$kajeResponse->user_id) {
				$msgCode = (isset($kajeResponse) && isset($kajeResponse->msg_code)) ? $kajeResponse->msg_code : false;
				return $this->handle_kaje_error($msgCode);	//return an error based on the message code returned
			}
			else {
				add_user_meta($user_ID, 'kaje_ID', $kajeResponse->user_id); 			//associate the user's new Kaje ID with their Wordpress ID
				
				return $this->kaje_login_user($kajeResponse->user_id, $user_ID, true);	//returns false if no errors. Last arg needs to be TRUE to ignore NEW and RESET Kaje statuses
			}
		}
		
		public function kaje_login_user($kajeID, $userID, $ignoreUserStatus = false, $resetKaje = false)
		{
			//if account flagged for reset
			if($resetKaje) {
				$kajeResponse = $this->call_kaje('reset', $kajeID);
			}
			else {	//Make API call to request login token
				$kajeResponse = $this->call_kaje('request', $kajeID);
			}
			
			//if the response from Kaje was invalid or didn't return a URL to redirect the user to
			if(!$kajeResponse || !isset($kajeResponse->url) || !$kajeResponse->url) {			
				$msgCode = (isset($kajeResponse) && isset($kajeResponse->msg_code)) ? $kajeResponse->msg_code : false;
				return $this->handle_kaje_error($msgCode);	//return an error based on the message code returned
			}
			else {
				if(!$ignoreUserStatus) {
					$userStatus = (isset($kajeResponse) && isset($kajeResponse->user_status)) ? $kajeResponse->user_status : false;					
					if(!$userStatus || $userStatus == 'new' || $userStatus == 'reset') {
						if(get_user_meta($userID, 'kaje_setup_account', true)) {	//set 'yes' so next TEXT login forces Kaje setup
							update_user_meta($userID, 'kaje_setup_account', 'yes');		
						} else {
							add_user_meta($userID, 'kaje_setup_account', 'yes');
						}
						return $this->handle_kaje_user_status($userStatus);			//return an error based on the user_status returned
					}					
				}
				
				//redirect the user to their Kaje login
				echo "<script> window.location = '" . $kajeResponse->url . "'; </script>"; exit();
			}
			
			return false;  //no errors
		}
		
		//flag user's account for a Kaje reset on next login
		public function kaje_reset_user($userID)
		{
			if(!get_user_meta($userID, 'kaje_reset_account', true)) {
				add_user_meta($userID, 'kaje_reset_account', 'yes');
			} else {
				update_user_meta($userID, 'kaje_reset_account', 'yes');
			}			
		}
		
		public function kaje_lock_unlock_user($userID, $lockAcct)
		{
			$kajeID = get_user_meta($userID, 'kaje_ID', true);
			
			if(!$kajeID) { return false; }
			
			return $this->call_kaje((($lockAcct) ? 'lock' : 'unlock'), $kajeID);			
		}
		
		public function kaje_row_actions($actions, $user_object)
		{			
			//if the user has a Kaje account, display links to reset, lock, unlock
			if(get_user_meta($user_object->ID, 'kaje_ID', true)) {		
				$resetNonce = wp_create_nonce(get_current_user_id() . 'kaje_action_' . 'reset' . $user_object->ID);	/* nonce format --> current user id + 'kaje_action_' + the action + the user id */
				$lockNonce = wp_create_nonce(get_current_user_id() . 'kaje_action_' . 'lock' . $user_object->ID);
				$unlockNonce = wp_create_nonce(get_current_user_id() . 'kaje_action_' . 'unlock' . $user_object->ID);
				
				$actions['kaje_reset']  = '<a id="kaje_reset_user_' . $user_object->ID . '" style="cursor:pointer">Kaje-Reset</a>';
				$actions['kaje_reset'] .= "<script> jQuery('#kaje_reset_user_" . $user_object->ID . "').click(function() { ";
				$actions['kaje_reset'] .= "kajeAjax(" . $user_object->ID . ", 'reset', '" . $resetNonce . "');";
				$actions['kaje_reset'] .= 'jQuery(this).after(\'<img class="ajax-wait" src="' . plugins_url( 'img/ajax_wait.gif' , __FILE__ ) . '"></img>\');';
				$actions['kaje_reset'] .= " }); </script>";
				
				$actions['kaje_lock']  = '<a id="kaje_lock_user_' . $user_object->ID . '" style="cursor:pointer">Kaje-Lock</a>';
				$actions['kaje_lock'] .= "<script> jQuery('#kaje_lock_user_" . $user_object->ID . "').click(function() { ";
				$actions['kaje_lock'] .= "kajeAjax(" . $user_object->ID . ", 'lock', '" . $lockNonce . "');";
				$actions['kaje_lock'] .= 'jQuery(this).after(\'<img class="ajax-wait" src="' . plugins_url( 'img/ajax_wait.gif' , __FILE__ ) . '"></img>\');';
				$actions['kaje_lock'] .= " }); </script>";
				
				$actions['kaje_unlock']  = '<a id="kaje_unlock_user_' . $user_object->ID . '" style="cursor:pointer">Kaje-Unlock</a>';
				$actions['kaje_unlock'] .= "<script> jQuery('#kaje_unlock_user_" . $user_object->ID . "').click(function() { ";
				$actions['kaje_unlock'] .= "kajeAjax(" . $user_object->ID . ", 'unlock', '" . $unlockNonce . "');";
				$actions['kaje_unlock'] .= 'jQuery(this).after(\'<img class="ajax-wait" src="' . plugins_url( 'img/ajax_wait.gif' , __FILE__ ) . '"></img>\');';
				$actions['kaje_unlock'] .= " }); </script>";
			}
			
			return $actions;
		}
		
		public function kaje_ajax_javascript()
		{
			?>
				<script type="text/javascript" >					
					function kajeAjax(userID, kajeAction, kajeNonce) {						
						var data = {
							action: 'kaje_action',
							id: userID,
							kaje_action: kajeAction,
							_wpnonce: kajeNonce
						};
						
						// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
						jQuery.post(ajaxurl, data, function(response) {
							jQuery('.ajax-wait').remove();
							alert(response);
						});
					}	
				</script>
			<?php
		}
		
		function kaje_action_callback() {
			check_ajax_referer(get_current_user_id() . 'kaje_action_' . $_POST['kaje_action'] . $_POST['id']);	/* note: refer to the "kaje_row_actions" function for nonce format */
			
			if(!current_user_can('edit_users')) {
				echo -1;
			}
			else {
				$user_info = get_userdata($_POST['id']);
				
				switch($_POST['kaje_action']) {
					case 'reset':
						$this->kaje_reset_user($_POST['id']);						
						echo $user_info->user_login . ' -- This user will be prompted to setup a new picture password on their next login.';
						break;
					case 'lock':	//fall through
					case 'unlock':
						$kajeResponse = $this->kaje_lock_unlock_user($_POST['id'], ( ($_POST['kaje_action'] == 'lock') ? true : false ) );
						if($kajeResponse && $kajeResponse->user_status) {
							echo $user_info->user_login . ' -- This user\'s Kaje account status is now \'' . strtoupper($kajeResponse->user_status). '\'';
						}
						else {
							echo 'There was an error performing this action.';
						}
						break;
					default:
						echo -1;
				}
			}			
			
			die(); // this is required to return a proper result
		}
		
		//make an API call to the Kaje server
		public function call_kaje($type, $kajeID = false, $authToken = false)
		{
			//Kaje server's base URL for API calls
			$url = 'https://kaje.authenticator.com/index.php/auth/';
			
			//populate some POST data
			$postData = array('rp_id' => get_option('relying_party_id'), 'rp_secret' => get_option('relying_party_secret'));
			
			//add additional URL and POST info based on the type of API call
			switch($type) {
				
				//Request a login token for the current user
				case 'request':
					$url .= 'request/';
					$postData['user_id'] = $kajeID;
					break;
				case 'newuser':
					$url .= 'newUser/';
					break;
				case 'status':
					$url .= 'status/';
					break;
				case 'id':
					$url .= 'id/';
					$postData['auth_token'] = $authToken;
					break;
				case 'reset':
					$url .= 'reset/';
					$postData['user_id'] = $kajeID;
					break;
				case 'lock':
					$url .= 'lock/';
					$postData['user_id'] = $kajeID;
					break;
				case 'unlock':
					$url .= 'unlock/';
					$postData['user_id'] = $kajeID;
					break;
				default:
					return false;
			}
			
			//add timestamp to the URL
			$url .= '?ts=' . time();
			
			////////////////////////////////
			
			///////////////////////////////
			
			///ORIG
			//curl_setopt($ch, CURLOPT_URL, $url);
			//curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
			//curl_setopt($ch, CURLOPT_POST, true);
			//curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);			
			////////////
			
			//make the call
			$ch = curl_init();
			if(!$ch) { 
				return 'Error initializing CURL:&nbsp;&nbsp;' . curl_error();
			}
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17');
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);	
			$caCertPath = plugin_dir_path( __FILE__ ) . 'ca/cacert.pem';
			curl_setopt ($ch, CURLOPT_CAINFO, $caCertPath);
			$output = curl_exec($ch);
			if(!$output) {
				$curlError = curl_error($ch);
				curl_close($ch);
				return 'CURL error:&nbsp;&nbsp' . $curlError;
			}
			curl_close($ch);
			$response = json_decode($output);
			
			/*
			 * response checks
			*/
			
			//if the proper rp_id is not returned
			if(!($response && isset($response->rp_id) && $response->rp_id == $postData['rp_id'])) {
				return false;
			}
			
			//if kajeID was provided in post data, check that it matches what's returned
			if($kajeID) {
				if(!(isset($response->user_id) && $response->user_id == $postData['user_id'])) {
				return false;
				}
			}
			
			//if authToken was provided in post data, check that it matches what's returned
			if($authToken) {
				if(!(isset($response->auth_token) && $response->auth_token == urldecode($postData['auth_token']))) {
					return false;
				}
			}
			
			return $response;			
		}
		
		public function handle_kaje_error($msgCode, $msgPrefix = '')
		{
			$errorMessage = $msgPrefix;
			
			switch($msgCode) {
				case '100':
					$errorMessage .= 'Requesting Party ID not found'; break;
				case '101':
					$errorMessage .= 'Requesting Party Secret invalid'; break;
				case '102':
					$errorMessage .= 'Requesting Party Domain Not Verified'; break;
				case '103':
					$errorMessage .= 'Requesting Party has no available POKs (proofs of knowledge)'; break;
				case '104':
					$errorMessage .= 'Requesting Party Account is locked or suspended'; break;
				case '200':
					$errorMessage .= 'Error creating user'; break;
				case '201':
					$errorMessage .= 'User ID not found'; break;
				case '202':
					$errorMessage .= 'User account locked'; break;
				case '203':
					$errorMessage .= 'User account suspended'; break;
				case '204':
					$errorMessage .= 'User account deleted'; break;
				case '205':
					$errorMessage .= 'User reached maximum authorizations per minute'; break;
				case '206':
					$errorMessage .= 'User reached maximum authorizations per hour'; break;
				case '207':
					$errorMessage .= 'User reached maximum authorizations per day'; break;
				case '300':
					$errorMessage .= 'User authenticated'; break;
				case '400':
					$errorMessage .= 'Auth token not found'; break;
				case '401':
					$errorMessage .= 'Auth token expired'; break;
				case '402':
					$errorMessage .= 'Error retrieving ID token'; break;
				case '500':
					$errorMessage .= 'Requesting Party is not an affiliate'; break;
				case '501':
					$errorMessage .= 'Discount SSL location invalid'; break;
				case '502':
					$errorMessage .= 'Discount percentage invalid'; break;
				case '503':
					$errorMessage .= 'Discount expiration invalid'; break;
				case '504':
					$errorMessage .= 'Error creating discount'; break;
				case '700':
					$errorMessage .= 'Kaje service available'; break;
				case '800':
					$errorMessage .= 'Kaje service unavailable'; break;
				case '801':
					$errorMessage .= 'Kaje service down for maintenance'; break;
				case '900':
				default:
					$errorMessage .= 'Unknown error'; break;
			}
			
			return $errorMessage;
		}
		
		public function handle_kaje_user_status($userStatus)
		{
			$statusMessage = '';
			
			switch($userStatus) {
				case 'new':
					$statusMessage .= 'Your Kaje account setup has not been completed.  You will need to login with your text password first.'; break;
				case 'reset':
					$statusMessage .= 'Your Kaje account is in a RESET status.  You will need to login with your text password first.'; break;
				case 'locked':
					$statusMessage .= 'Your Kaje account is in a LOCKED status.'; break;
				case 'suspended':
					$statusMessage .= 'Your Kaje account is in a SUSPENDED status.'; break;			
			}
			
			return $statusMessage;
		}
	}
}
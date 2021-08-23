/**
**
** Generate Random Password 
**
**/

function randomPassword() {
    $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
    $pass = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for ($i = 0; $i < 16; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass); //turn the array into a string
}

/*
*
*   Custom Ajax auth via phone Number
*   !work only for unauthorized user!
*/

add_action('wp_ajax_nopriv_customPhoneAuth', 'my_wp_customPhoneAuth');

function my_wp_customPhoneAuth(){
//   error_reporting(E_ALL); 
//   ini_set("display_errors", 1);
   require_once (ABSPATH . 'wp-includes/class-phpass.php');    
    
   global $Lang, $all_options, $front_page_id;
   $authNum = $_POST['authNum'];
   $authCode = $_POST['authCode']; 
   $user_login = str_replace(array(' ', '+'), "", $authNum);
  
   if(!isset($authCode))
    {
        $authCode = rand(1111,9999);
        $authCodeHash = wp_hash_password( $authCode ); 

          if(username_exists($user_login))
                {
                    if( get_metadata( "post", $front_page_id, "sms_auth", 1 ) !== false ){
                        $message = str_replace('%c%', $authCode, get_metadata( "post", $front_page_id, "sms_auth", 1 ));
                    }else{
                        $message = $authCode.' Pizzamiorita.md';
                    }    
                    
                   $sms = new BulkSms();
                   $sms->set_global($all_options); 
                   $sms->send($user_login, $message);
                   //echo $sms->get_data(); 
                   echo $authCode;

                    if(!isset($_COOKIE['pmacc'])) 
                        {
                            // set a cookie for 5 min year
                            setcookie('pmac', $authCodeHash, time()+60*5);
                            //echo $authCode;
                        }
                    else
                        {

                        }

                }
            else
                {
                        if( get_metadata( "post", $front_page_id, "sms_auth", 1 ) !== false ){
                            $message = str_replace('%c%', $authCode, get_metadata( "post", $front_page_id, "sms_auth", 1 ));
                        }else{
                            $message = $authCode.' Pizzamiorita.md';
                        }  
                    
                        // Create new user 
                        $user_data = array(
                        'ID' => '', // automatically created
                        'user_pass' => randomPassword(),
                        'user_login' => $user_login,
                        'user_nicename' => $user_login,
                        'user_email' => $user_login.'@accountkit.com',
                        'display_name' => $user_login,
                        'role' => get_option('default_role') // administrator, editor, subscriber, author, etc
                        );

                        $user_id = wp_insert_user( $user_data );

                        $sms = new BulkSms();
                        $sms->set_global($all_options); 
                        $sms->send($user_login, $message);

                        //echo "User ".$user_id." registered successfuly!";  
                } 
    }
   else
   {
    
  
    $wp_hasher = new PasswordHash(8, true); 
    $password_hashed = $_COOKIE['pmac'];
    $plain_password = $authCode;    
       
    if($wp_hasher->CheckPassword($plain_password, $password_hashed)) 
        {
            $user = get_user_by('login', $user_login );
            // Redirect URL //
            if ( !is_wp_error( $user ) ) 
                {
                    wp_clear_auth_cookie();
                    wp_set_current_user ( $user->ID );
                    wp_set_auth_cookie  ( $user->ID );
                    $redirect_to = user_admin_url();
					
					$billing_street = get_user_meta($user->ID, 'billing_street', 1);
					if (!$billing_street) {
						$billing_street = get_user_meta($user->ID, 'billing_address_1', 1);
					}
					$billing_street = get_user_meta($user->ID, 'billing_street', 1);
					$billing_home = get_user_meta($user->ID, 'billing_home', 1);
					$billing_housing = get_user_meta($user->ID, 'billing_housing', 1);
					$billing_apartment = get_user_meta($user->ID, 'billing_apartment', 1);
					$billing_entrance = get_user_meta($user->ID, 'billing_entrance', 1);
                    
                    $data = array( 
                        'success' => true,
                        'redirect' => $redirect_to,
						'billing_street' => $billing_street,
						'billing_home' => $billing_home,
						'billing_housing' => $billing_housing,
						'billing_apartment' => $billing_apartment,
						'billing_entrance' => $billing_entrance,
                    );

                    echo wp_send_json($data);
                    
                    wp_die(); 
                }
        } 
    else 
        {
            echo "No";
        }
       
   }
   wp_die(); // securized exit
}

/*
*
* Send SMS after order was placed
*
*
*/

if ( class_exists( 'WooCommerce' ) ) {
  // code that requires WooCommerce
  add_action('woocommerce_checkout_order_processed', 'wc_on_place_order', 10, 2);    
}

function wc_on_place_order( $order_id, $post ) {    
    global $wpdb, $Lang, $all_options, $front_page_id;;
    if(isset($post['billing_delivery_type']) && !$post['billing_delivery_type']){
        date_default_timezone_set ( 'Europe/Chisinau' );

        // get order object and order details
        $order = new WC_Order( $order_id ); 

        // get product details
        $items = $order->get_items();
        $f_name = $order->get_billing_first_name();
        $phone = $order->get_billing_phone();

        $date = date("H:i", strtotime('+1 hour'));  

        $replace_with = [$f_name, $order_id, $date];
        $key = ['%n%','%i%','%t%'];

        $message = str_replace($key, $replace_with, get_metadata( "post", $front_page_id, "sms_notification", 1 ));
        //Send sms notification via third-party API

        $sms = new BulkSms();
        $sms->set_global($all_options); 
        $sms->send($phone, $message);
    }
    

}
/*************************************************************************/

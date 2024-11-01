<?php
/**
 * Plugin Name: WooCommerce Reviso Integration (WooReviso)
 * Plugin URI: www.onlineforce.net
 * Description: WooCommerce Reviso Integration (WooReviso) synchronizes your WooCommerce Orders, Customers and Products to your Reviso account.
 * Also fetches inventory from Reviso and updates WooCommerce
 * Version: 1.1
 * Author: wooreviso
 * Text Domain: wooreviso
 * Author URI: www.onlineforce.net
 * License: GPL2
 */
 if ( ! defined( 'ABSPATH' ) ) exit;
if(!defined('TESTING')){
    define('TESTING',true);
}

if(!defined('AUTOMATED_TESTING')){
    define('AUTOMATED_TESTING', true);
}

if ( ! function_exists( 'reviso_logthis' ) ) {
    function reviso_logthis($msg) {
        if(TESTING){
            if(!file_exists(dirname(__FILE__).'/reviso_logfile.log')){
                $fileobject = fopen(dirname(__FILE__).'/reviso_logfile.log', 'a');
                chmod(dirname(__FILE__).'/reviso_logfile.log', 0666);
            }
            else{
                $fileobject = fopen(dirname(__FILE__).'/reviso_logfile.log', 'a');
            }

            if(is_array($msg) || is_object($msg)){
                fwrite($fileobject,print_r($msg, true));
            }
            else{
                fwrite($fileobject,date("Y-m-d H:i:s"). ":" . $msg . "\n");
            }
        }
        else{
            error_log($msg);
        }
    }
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    if ( ! class_exists( 'WC_Reviso' ) ) {
		
		
		//Add Reviso payment class
		include_once("reviso-payment.php");

        // in javascript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
        function reviso_enqueue(){
            wp_enqueue_script('jquery');
            wp_register_script( 'reviso-script', plugins_url( '/js/reviso.js', __FILE__ ) );
            wp_enqueue_script( 'reviso-script' );
        }

        add_action( 'admin_enqueue_scripts', 'reviso_enqueue' );
		
		
		add_action('reviso_product_sync_cron', 'reviso_sync_products_callback');
        add_action( 'wp_ajax_sync_products', 'reviso_sync_products_callback' );
        function reviso_sync_products_callback() {
            global $wpdb; // this is how you get access to the database
			include_once("class-reviso-api.php");
            $wcr_api = new WCR_API();
			$wcr = new WC_Reviso();
			if($wcr->reviso_is_license_key_valid() != "Active" || !$wcr_api->wooreviso_create_API_validation_request()){
			  reviso_logthis("reviso_sync_products_callback exiting because license key validation not passed.");
			  return false;
			}			
			$log_msg = '';		
			$sync_log = $wcr_api->sync_products();
			foreach(array_slice($sync_log, 1) as $key => $value){
				$log_msg .= __('<br>Sync status: ', 'wooreviso'). $value['status'].'<br>';
				$log_msg .= __('Product SKU: ', 'wooreviso'). $value['sku'].'<br>';
				$log_msg .= __('Product Name: ', 'wooreviso'). $value['name'].'<br>';
				$log_msg .= __('Sync message: ', 'wooreviso'). $value['msg'].'<br>';
			}
            if($sync_log[0]){
				$log = array('status' => __('Products are synchronized without problems.', 'wooreviso'), 'msg' => $log_msg);
				//reviso_logthis(json_encode($log));
				echo json_encode($log);
            }
            else{
				$log = array('status' => __('Something went wrong.', 'wooreviso'), 'msg' => $log_msg);
				echo json_encode($log);
            }
            die(); // this is required to return a proper result
        }
		
		add_action( 'wp_ajax_sync_products_rw', 'reviso_sync_products_rw_callback' );
        function reviso_sync_products_rw_callback() {
            global $wpdb; // this is how you get access to the database
			include_once("class-reviso-api.php");
            $wcr_api = new WCR_API();
			$wcr = new WC_Reviso();
			if($wcr->reviso_is_license_key_valid() != "Active" || !$wcr_api->wooreviso_create_API_validation_request()){
			  reviso_logthis("reviso_sync_products_rw_callback exiting because license key validation not passed.");
			  return false;
			}			
			$log_msg = '';		
			$sync_log = $wcr_api->sync_products_rw();
			foreach(array_slice($sync_log, 1) as $key => $value){
				$log_msg .= __('<br>Sync status: ', 'wooreviso'). $value['status'].'<br>';
				$log_msg .= __('Product SKU: ', 'wooreviso'). $value['sku'].'<br>';
				$log_msg .= __('Product Name: ', 'wooreviso'). $value['name'].'<br>';
				$log_msg .= __('Sync message: ', 'wooreviso'). $value['msg'].'<br>';
			}
            if($sync_log[0]){
				$log = array('status' => __('Products are synchronized without problems.', 'wooreviso'), 'msg' => $log_msg);
				//reviso_logthis(json_encode($log));
				echo json_encode($log);
            }
            else{
				$log = array('status' => __('Something went wrong.', 'wooreviso'), 'msg' => $log_msg);
				echo json_encode($log);
            }
            die(); // this is required to return a proper result
        }

        add_action( 'wp_ajax_sync_orders', 'reviso_sync_orders_callback' );
        function reviso_sync_orders_callback() {
            global $wpdb; // this is how you get access to the database
			include_once("class-reviso-api.php");
            $wcr_api = new WCR_API();
			$wcr = new WC_Reviso();
			if($wcr->reviso_is_license_key_valid() != "Active" || !$wcr_api->wooreviso_create_API_validation_request()){
			  reviso_logthis("reviso_sync_orders_callback existing because licensen key validation not passed.");
			  return false;
			}
			$log_msg = '';
			$sync_log = $wcr_api->sync_orders();
            foreach(array_slice($sync_log, 1) as $key => $value){
				$log_msg .= __('<br>Sync status: ', 'wooreviso'). $value['status'].'<br>';
				isset($value['order_id']) ? $log_msg .= 'Order ID: '. $value['order_id'].'<br>' : '';
				$log_msg .= __('Sync message: ', 'wooreviso'). $value['msg'].'<br>';
			}
            if($sync_log[0]){
				$log = array('status' => __('Orders are synchronized without problems.', 'wooreviso'), 'msg' => $log_msg);
				echo json_encode($log);
            }
            else{
				$log = array('status' => __('Something went wrong.', 'wooreviso'), 'msg' => $log_msg);
				echo json_encode($log);
            }
            die(); // this is required to return a proper result
        }
		

        add_action( 'wp_ajax_sync_contacts', 'reviso_sync_contacts_callback' );
        function reviso_sync_contacts_callback() {
            global $wpdb; // this is how you get access to the database
			include_once("class-reviso-api.php");
            $wcr_api = new WCR_API();
			$wcr = new WC_Reviso();
			if($wcr->reviso_is_license_key_valid() != "Active" || !$wcr_api->wooreviso_create_API_validation_request()){
			  reviso_logthis("reviso_sync_contacts_callback existing because licensen key validation not passed.");
			  return false;
			}
			$log_msg = '';
			$sync_log = $wcr_api->sync_contacts();
            foreach(array_slice($sync_log, 1) as $key => $value){
				$log_msg .= __('<br>Sync status: ', 'wooreviso'). $value['status'].'<br>';
				isset($value['user_id']) ? $log_msg .= 'Contact ID: '. $value['user_id'].'<br>' : '';
				$log_msg .= __('Sync message: ', 'wooreviso'). $value['msg'].'<br>';
			}
            if($sync_log[0]){
				$log = array('status' => __('Contacts synchronized without problems.', 'wooreviso'), 'msg' => $log_msg);
				echo json_encode($log);
            }
            else{
				$log = array('status' => __('Something went wrong.', 'wooreviso'), 'msg' => $log_msg);
				echo json_encode($log);
            }
            die(); // this is required to return a proper result
        }
		
		//Sync function added for reviso to woocommerce sync.
		
		add_action( 'wp_ajax_sync_contacts_rw', 'reviso_sync_contacts_rw_callback' );
        function reviso_sync_contacts_rw_callback() {
            global $wpdb; // this is how you get access to the database
			include_once("class-reviso-api.php");
            $wcr_api = new WCR_API();
			$wcr = new WC_Reviso();
			if($wcr->reviso_is_license_key_valid() != "Active" || !$wcr_api->wooreviso_create_API_validation_request()){
			  reviso_logthis("reviso_sync_contacts_rw_callback existing because licensen key validation not passed.");
			  return false;
			}
			$log_msg = '';
			$sync_log = $wcr_api->sync_contacts_rw();
            foreach(array_slice($sync_log, 1) as $key => $value){
				$log_msg .= __('<br>Sync status: ', 'wooreviso'). $value['status'].'<br>';
				isset($value['user_id']) ? $log_msg .= 'User ID: '. $value['user_id'].'<br>' : '';
				$log_msg .= __('Sync message: ', 'wooreviso'). $value['msg'].'<br>';
			}
            if($sync_log[0]){
				$log = array('status' => __('Contacts synchronized without problems.', 'wooreviso'), 'msg' => $log_msg);
				echo json_encode($log);
            }
            else{
				$log = array('status' => __('Something went wrong.', 'wooreviso'), 'msg' => $log_msg);
				echo json_encode($log);
            }
            die(); // this is required to return a proper result
        }
		
		
		add_action( 'wp_ajax_sync_shippings', 'reviso_sync_shippings_callback' );
        function reviso_sync_shippings_callback() {
            global $wpdb; // this is how you get access to the database
			include_once("class-reviso-api.php");
            $wcr_api = new WCR_API();
			$wcr = new WC_Reviso();
			if($wcr->reviso_is_license_key_valid() != "Active" || !$wcr_api->wooreviso_create_API_validation_request()){
			  reviso_logthis("reviso_sync_shippings_callback existing because licensen key validation not passed.");
			  return false;
			}
			$log_msg = '';
			$sync_log = $wcr_api->sync_shippings();
            foreach(array_slice($sync_log, 1) as $key => $value){
				$log_msg .= __('<br>Sync status: ', 'wooreviso'). $value['status'].'<br>';
				$log_msg .= __('Shipping type: ', 'wooreviso'). $value['name'].'<br>';
				$log_msg .= __('Sync message: ', 'wooreviso'). $value['msg'].'<br>';
			}
            if($sync_log[0]){
				$log = array('status' => __('Delivery synchronized without problems.', 'wooreviso'), 'msg' => $log_msg);
				echo json_encode($log);
            }
            else{
				$log = array('status' => __('Something went wrong.', 'wooreviso'), 'msg' => $log_msg);
				echo json_encode($log);
            }
            die(); // this is required to return a proper result
        }
		
		add_action( 'wp_ajax_sync_coupons', 'reviso_sync_coupons_callback' );
        function reviso_sync_coupons_callback() {
            global $wpdb; // this is how you get access to the database
			include_once("class-reviso-api.php");
            $wcr_api = new WCR_API();
			$wcr = new WC_Reviso();
			if($wcr->reviso_is_license_key_valid() != "Active" || !$wcr_api->wooreviso_create_API_validation_request()){
			  reviso_logthis("reviso_sync_coupons_callback existing because licensen key validation not passed.");
			  return false;
			}
			$log_msg = '';
			$sync_log = $wcr_api->sync_coupons();
            foreach(array_slice($sync_log, 1) as $key => $value){
				$log_msg .= __('<br>Sync status: ', 'wooreviso'). $value['status'].'<br>';
				$log_msg .= __('Coupon code: ', 'wooreviso'). $value['name'].'<br>';
				$log_msg .= __('Sync message: ', 'wooreviso'). $value['msg'].'<br>';
			}
            if($sync_log[0]){
				$log = array('status' => __('Coupon codes synchronized without problems.', 'wooreviso'), 'msg' => $log_msg);
				echo json_encode($log);
            }
            else{
				$log = array('status' => __('Something went wrong.', 'wooreviso'), 'msg' => $log_msg);
				echo json_encode($log);
            }
            die(); // this is required to return a proper result
        }

        add_action( 'wp_ajax_send_support_mail', 'reviso_send_support_mail_callback' );
		
		function reviso_send_support_mail_callback() {
			$message = '<html><body><table rules="all" style="border-color: #91B9F6; width:70%; font-family:Calibri, Arial, sans-serif;" cellpadding="10">';
			if(isset($_POST['supportForm']) && $_POST['supportForm'] ==  "support"){
				$message .= '<tr><td align="right">Type: </td><td align="left" colspan="1"><strong>Support</strong></td></tr>';
			}else{
				$message .= '<tr><td align="right">Type: </td><td align="left" colspan="1"><strong>Installationssupport</strong></td></tr>';
			}
			$message .= '<tr><td align="right">Företag: </td><td align="left">'.$_POST['company'].'</td></tr>';
			$message .= '<tr><td align="right">Namn: </td><td align="left">'.$_POST['name'].'</td></tr>';
			$message .= '<tr><td align="right">Telefon: </td><td align="left">'.$_POST['telephone'].'</td></tr>';
			$message .= '<tr><td align="right">Email: </td><td align="left">'.$_POST['email'].'</td></tr>';
			$message .= '<tr><td align="right">Ärende: </td><td align="left">'.$_POST['subject'].'</td></tr>';
			
			if(isset($_POST['supportForm']) && $_POST['supportForm'] ==  "support"){
				$options = get_option('woocommerce_reviso_general_settings');
				//echo array_key_exists('activate-oldordersync', $options)? 'key exist' : 'key doesnt exist';
				$order_options = get_option('woocommerce_reviso_order_settings');
				$message .= '<tr><td align="right" colspan="1"><strong>Allmänna inställningar</strong></td></tr>';
				if(array_key_exists('token', $options)){
					$message .= '<tr><td align="right">Token ID: </td><td align="left">'.$options['token'].'</td></tr>';
				}
				if(array_key_exists('license-key', $options)){
					$message .= '<tr><td align="right">License Nyckel: </td><td align="left">'.$options['license-key'].'</td></tr>';
				}
				if(array_key_exists('other-checkout', $options)){
					$message .= '<tr><td align="right">Other checkout: </td><td align="left">'.$options['other-checkout'].'</td></tr>';
				}
				if(array_key_exists('reviso-checkout', $options)){
					$message .= '<tr><td align="right">Reviso checkout: </td><td align="left">'.$options['reviso-checkout'].'</td></tr>';
				}				
				if(array_key_exists('activate-oldordersync', $options)){
					$message .= '<tr><td align="right">Activate old orders sync: </td><td align="left">'.$options['activate-oldordersync'].'</td></tr>';
				}
				if(array_key_exists('product-sync', $options)){
					$message .= '<tr><td align="right">Activate product sync: </td><td align="left">'.$options['product-sync'].'</td></tr>';
				}
				if(array_key_exists('scheduled-product-sync', $options)){
					$message .= '<tr><td align="right">Run scheduled product stock sync: </td><td align="left">'.$options['scheduled-product-sync'].'</td></tr>';
				}
				if(array_key_exists('product-group', $options)){
					$message .= '<tr><td align="right">Product group: </td><td align="left">'.$options['product-group'].'</td></tr>';
				}
				if(array_key_exists('product-prefix', $options)){
					$message .= '<tr><td align="right">Product prefix: </td><td align="left">'.$options['product-prefix'].'</td></tr>';
				}
				if(array_key_exists('customer-group', $options)){
					$message .= '<tr><td align="right">Customer group: </td><td align="left">'.$options['customer-group'].'</td></tr>';
				}
				if(array_key_exists('shipping-group', $options)){
					$message .= '<tr><td align="right">Shipping group: </td><td align="left">'.$options['shipping-group'].'</td></tr>';
				}
				if(array_key_exists('coupon-group', $options)){
					$message .= '<tr><td align="right">Coupon group: </td><td align="left">'.$options['coupon-group'].'</td></tr>';
				}
				if(array_key_exists('order-reference-prefix', $options)){
					$message .= '<tr><td align="right">Order reference prefix: </td><td align="left">'.$options['order-reference-prefix'].'</td></tr>';
				}
			}
			$message .= '</table></html></body>';			
			$headers = "MIME-Version: 1.0\r\n";
			$headers .= "Content-type: text/html; charset=utf-8 \r\n";
			
            echo wp_mail( 'wooreviso@uniwin.se', 'Reviso Support', $message , $headers) ? "success" : "error";
            die(); // this is required to return a proper result
        }
		
		add_action( 'add_meta_boxes_product', 'reviso_product_group_metabox' );
		
		function reviso_product_group_metabox(){
			include_once("class-reviso-api.php");
			$wcr_api = new WCR_API();
			$wcr = new WC_Reviso();
			if($wcr->reviso_is_license_key_valid() != "Active" || !$wcr_api->wooreviso_create_API_validation_request()){
			  reviso_logthis("reviso_product_group_metabox existing because licensen key validation not passed.");
			  return false;
			}
			add_meta_box( 'productGroup', 'reviso product group', 'reviso_product_group', 'product', 'side', 'high' );
		}
		
		function reviso_product_group( $post ) {
			include_once("class-reviso-api.php");
			// Add a nonce field so we can check for it later.
			wp_nonce_field( 'reviso_productGroup_save_meta_box_data', 'reviso_productGroup_meta_box_nonce' );
			$wcr_api = new WCR_API();
			$client = $wcr_api->wooreviso_client();	
			$options = get_option('woocommerce_reviso_general_settings');
			$productGroup = get_post_meta( $post->ID, 'productGroup', true );
			
			if($productGroup == '' || $productGroup == NULL){
				$productGroup = $options['product-group'];
			}
			$groups = $client->ProductGroup_GetAll()->ProductGroup_GetAllResult->ProductGroupHandle;
			
			echo __('Product group', 'wooreviso').': ';
			echo '<select name="productGroup">';
			if(is_array($groups)){
				foreach($groups as $group){
					$groupnames[$group->Number] = $client->ProductGroup_GetName(array('productGroupHandle' => $group))->ProductGroup_GetNameResult;
					
					if($productGroup == $group->Number){
						echo '<option selected value='.$group->Number.'>'.$group->Number.'-'.$groupnames[$group->Number].'</option>';
					}else{
						echo '<option value='.$group->Number.'>'.$group->Number.'-'.$groupnames[$group->Number].'</option>';
					}
				}
			}else{
				$groupnames[$groups->Number] = $client->ProductGroup_GetName(array('productGroupHandle' => $groups))->ProductGroup_GetNameResult;
				echo '<option selected value='.$groups->Number.'>'.$groups->Number.'-'.$groupnames[$groups->Number].'</option>';
			}
			echo '</select>';
		}
		
		/**
		 * When the post is saved, saves our custom data.
		 *
		 * @param int $post_id The ID of the post being saved.
		 */
		function reviso_productGroup_save_meta_box_data( $post_id ) {
			/*
			 * We need to verify this came from our screen and with proper authorization,
			 * because the save_post action can be triggered at other times.
			 */
		
			// Check if our nonce is set.
			if ( ! isset( $_POST['reviso_productGroup_meta_box_nonce'] ) ) {
				return;
			}
		
			// Verify that the nonce is valid.
			if ( ! wp_verify_nonce( $_POST['reviso_productGroup_meta_box_nonce'], 'reviso_productGroup_save_meta_box_data' ) ) {
				return;
			}
		
			// If this is an autosave, our form has not been submitted, so we don't want to do anything.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}
		
			// Check the user's permissions.
			if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
		
				if ( ! current_user_can( 'edit_page', $post_id ) ) {
					return;
				}
		
			} else {
		
				if ( ! current_user_can( 'edit_post', $post_id ) ) {
					return;
				}
			}
		
			/* OK, it's safe for us to save the data now. */
			
			// Make sure that it is set.
			if ( ! isset( $_POST['productGroup'] ) ) {
				return;
			}
		
			// Sanitize user input.
			$productGroup = sanitize_text_field( $_POST['productGroup'] );
		
			// Update the meta field in the database.
			reviso_logthis('reviso_productGroup_save_meta_box_data adding productGroup for product: '.$post_id);
			update_post_meta( $post_id, 'productGroup', $productGroup );
			
			$args = array(
				'post_parent' => $post_id,
				'post_type'   => 'product_variation', 
				'numberposts' => -1,
				'post_status' => 'publish' 
			);
			
			$children_array = get_children( $args, OBJECT );
			if(!empty($children_array)){
				foreach ($children_array as $id => $childProduct){
					// Update the meta field in the database.
					reviso_logthis('reviso_productGroup_save_meta_box_data adding productGroup for product: '.$childProduct->ID);
					update_post_meta( $childProduct->ID, 'productGroup', $productGroup );
				}
			}
		}
		add_action( 'save_post', 'reviso_productGroup_save_meta_box_data', 1 );
		
		
		//Test the connection
		
		function reviso_test_connection_callback() {
			include_once("class-reviso-api.php");
			$wcr = new WC_Reviso();
			$wcr_api = new WCR_API();
			if( $wcr->reviso_is_license_key_valid() != "Active" ){
				_e('License Key is Invalid!', 'wooreviso');
				die(); // this is required to return a proper result
			}else{
				$data = $wcr_api->wooreviso_create_API_validation_request();
				if( $data ){
					_e('Your integration works fine!', 'wooreviso');
					die(); // this is required to return a proper result
				}else{
					_e('Your Reviso Token ID or License Key is not valid!', 'wooreviso');
					die(); // this is required to return a proper result
				}
			}
			_e('Something went wrong, please try again later!', 'wooreviso');
			die(); // this is required to return a proper result
        }
		
		//Connection testing ends

        add_action( 'wp_ajax_test_connection', 'reviso_test_connection_callback' );
		
		
		//License key invalid warning message. todo change the license purchase link
		
		function reviso_license_key_invalid() {
			$options = get_option('woocommerce_reviso_general_settings');
			$wcr = new WC_Reviso();
			$key_status = $wcr->reviso_is_license_key_valid();
			if(!isset($options['license-key']) || $options['license-key'] == '' || $key_status!='Active'){
			?>
                <div class="error">
                    <p><?php echo __('WooCommerce Reviso Integration: License Key Invalid!', 'wooreviso'); ?> <button type="button button-primary" class="button button-primary" title="" style="margin:5px" onclick="window.open('http://whmcs.onlineforce.net/cart.php?a=add&pid=60&carttpl=flex-web20cart&language=English','_blank');"><?php echo __('Get license Key', 'wooreviso'); ?></button></p>
                </div>
			<?php
			}
		}
		
		add_action( 'admin_notices', 'reviso_license_key_invalid' );
		//License key invalid warning message ends.


		//Section for wordpress pointers
		
		function reviso_wp_pointer_hide_callback(){
			update_option('reviso-tour', false);
		}
		add_action( 'wp_ajax_wp_pointer_hide', 'reviso_wp_pointer_hide_callback' );
		
		$reviso_tour = get_option('reviso-tour');
		
		if(isset($reviso_tour) && $reviso_tour){
			// Register the pointer styles and scripts
			add_action( 'admin_enqueue_scripts', 'reviso_enqueue_scripts' );
			
			// Add pointer javascript
			add_action( 'admin_print_footer_scripts', 'reviso_add_pointer_scripts' );
		}
		
		// enqueue javascripts and styles
		function reviso_enqueue_scripts()
		{
			wp_enqueue_style( 'wp-pointer' );
			wp_enqueue_script( 'wp-pointer' );	
		}
		
		// Add the pointer javascript
		function reviso_add_pointer_scripts()
		{
			$content = __('<h3>WooCommerce Reviso Integration</h3>', 'wooreviso');
			$content .= __('<p>You’ve just installed WooCommerce Reviso Integration by wooreviso. Please use the plugin options page to setup your integration.</p>', 'wooreviso');
		
			?>
			
            <script type="text/javascript">
				jQuery(document).ready( function($) {
					$("#toplevel_page_woocommerce_reviso_options").pointer({
						content: '<?php echo $content; ?>',
						position: {
							edge: 'left',
							align: 'center'
						},
						close: function() {
							// what to do after the object is closed
							var data = {
								action: 'wp_pointer_hide'
							};
	
							jQuery.post(ajaxurl, data);
						}
					}).pointer('open');
				});
			</script>
		   
		<?php
		}
		
		//Section for wordpress pointers ends.
		
		
		/***********************************************************************************************************
		* Reviso FUNCTIONS
		***********************************************************************************************************/
		
		
		function reviso_get_current_user_role() {
			require_once(ABSPATH . 'wp-includes/functions.php');
			require_once(ABSPATH . 'wp-includes/pluggable.php');
		
			global $wp_roles;
			global $current_user;
			get_currentuserinfo();
			$roles = $current_user->roles;
			$role = array_shift($roles);
			return isset($wp_roles->role_names[$role]) ? translate_user_role($wp_roles->role_names[$role] ) : false;
		}
		
		
		//Save product to reviso from woocommerce.
		add_action('save_post', 'reviso_save_object', 2, 2);
		function reviso_save_object( $post_id, $post) {
			//reviso_logthis($post);
			global $wpdb;
			if(!get_option('reviso_save_object')){
				reviso_logthis("reviso_save_object existing because disabled!");
				return;
			}
			include_once("class-reviso-api.php");
			$wcr = new WC_Reviso();
			$wcr_api = new WCR_API();
			if($wcr->reviso_is_license_key_valid() != "Active" || !$wcr_api->wooreviso_create_API_validation_request()){
				reviso_logthis("reviso_save_object existing because licensen key validation not passed.");
				return false;
			}
			reviso_logthis("reviso_save_object called by post_id: " . $post_id . " posttype: " . $post->post_type);
			if ( !$post ) return $post_id;		  
			if ( is_int( wp_is_post_revision( $post_id ) ) ) {
				reviso_logthis('reviso_save_object exit on wp_is_post_revision'); 
				return;
			}
			
			if( is_int( wp_is_post_autosave( $post_id ) ) ) {
				reviso_logthis('reviso_save_object exit on wp_is_post_autosave'); 
				return;
			}
			if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
				reviso_logthis('reviso_save_object exit on wp_is_post_autosave'); 
				return $post_id;
			}
			
			
			if($post->post_type == 'shop_order' && $post->post_status != 'auto-draft' && $post->post_status != 'wc-cancelled'){
				$order = new WC_Order($post_id);
				$options = get_option('woocommerce_reviso_general_settings');
				if(($options['reviso-checkout'] == 'invoice' || $options['other-checkout'] == 'invoice') && $wpdb->query ("SELECT * FROM wcr_orders WHERE order_id=".$post_id." AND synced=1")){
					reviso_logthis('reviso_save_object exiting, because an already invoiced order is being saved');
					return;
				}
				if($order->billing_first_name == ''){
					reviso_logthis('reviso_save_object exiting, because order data is empty.');
					return;
				}
				if(reviso_get_current_user_role() != 'Administrator'){
					reviso_logthis('reviso_save_object exiting, because save_customer is not called by administrator.');
					return;
				}
				
				$post_date = new DateTime($post->post_date);
				$post_modified = new DateTime($post->post_modified);
				$interval = $post_date->diff($post_modified);
				$diff = (int) $interval->format('%i');
				
				if($diff == 0){
					reviso_logthis('reviso_save_object exiting, because the order is being saved just after woocommerce_checkout_order_processed actionhook');
					return;
				}
				
				do_action('woo_save_'.$post->post_type.'_to_reviso', $post_id, $post);
				return;
			}
			
			
			if ($post->post_type != 'product' || $post->post_status != 'publish') {
				reviso_logthis('reviso_save_object exit on post_type: '.$post->post_type.' and post_status: '.$post->post_status); 
				return;
			}
		  
			if($post->post_type == 'product' || $post->post_type == 'product_variation'){
				reviso_logthis("reviso_save_object calling woo_save_".$post->post_type."_to_reviso");
				do_action('woo_save_'.$post->post_type.'_to_reviso', $post_id, $post);
				return;
			}
		  
		}
				
		add_action('woo_save_product_to_reviso', 'reviso_save_product_to_reviso', 1,2);
		add_action('woo_save_product_variation_to_reviso', 'reviso_save_product_to_reviso', 1,2);
		function reviso_save_product_to_reviso($post_id, $post) {
		  include_once("class-reviso-api.php");
		  $wcr = new WC_Reviso();
		  $wcr_api = new WCR_API();
		  reviso_logthis("reviso_save_product_to_reviso product post id: " . $post_id);
		  $product = new WC_Product($post->ID);
		  $client = $wcr_api->wooreviso_client();
		  reviso_logthis("saving product: " . $product->get_title() . " id: " . $product->id . " sku: " . $product->sku);
		  $wcr_api->save_product_to_reviso($product, $client);
		}
		//Save product to reviso from woocommerce ends.
		
		
		//Save orders to reviso from woocommerce.
		/*
		* Action to create invoice/order/quotation
		* This function is broken and diabled.
		*/
		//add_action('woocommerce_order_status_completed', 'reviso_save_invoice_order_to_reviso', 10, 4);
		function reviso_save_invoice_order_to_reviso($order_id) {
			try {
				global $wpdb;
				if($wpdb->query ("SELECT * FROM wcr_orders WHERE order_id=".$order_id." AND synced=1;")){
					reviso_logthis("reviso_save_invoice_order_to_reviso: order_id: ".$order_id." is already synced during the checkout");
					return true;
				}
				include_once("class-reviso-api.php");
				$options = get_option('woocommerce_reviso_general_settings');
				$wcr = new WC_Reviso();
				$wcr_api = new WCR_API();
				if($wcr->reviso_is_license_key_valid() != "Active" || !$wcr_api->wooreviso_create_API_validation_request()){
					reviso_logthis('Exiting on API license failure!');
					if($wpdb->query ("SELECT * FROM wcr_orders WHERE order_id=".$order_id.";")){
						$wpdb->update ("wcr_orders", array('synced' => 0), array('order_id' => $order->id), array('%d'), array('%d'));
					}else{
						$wpdb->insert ("wcr_orders", array('order_id' => $order_id, 'synced' => 0), array('%d', '%d'));
					}
					return false;
				}
				reviso_logthis("reviso_save_invoice_order_to_reviso: order_id: ".$order_id);
				$order = new WC_Order($order_id);
				if($order->customer_user != 0){
					$user = new WP_User($order->customer_user);
				}else{
					$user = NULL;
				}
				if($order->payment_method != 'reviso-invoice'){
					if($options['other-checkout'] == "do nothing"){
						if($wpdb->query ("SELECT * FROM wcr_orders WHERE order_id=".$order->id." AND synced=0;")){
							return false;
						}else{
							$wpdb->insert ("wcr_orders", array('order_id' => $order->id, 'synced' => 0), array('%d', '%d'));
							return false;
						}
					}
				}else{
					if($options['reviso-checkout'] == "do nothing"){
						if($wpdb->query ("SELECT * FROM wcr_orders WHERE order_id=".$order->id." AND synced=0;")){
							return false;
						}else{
							$wpdb->insert ("wcr_orders", array('order_id' => $order->id, 'synced' => 0), array('%d', '%d'));
							return false;
						}
					}
				}
				$client = $wcr_api->wooreviso_client();
				if($options['reviso-checkout'] == 'draft invoice' || $order->payment_method == 'reviso-invoice'){
					if($wcr_api->save_invoice_to_reviso($client, $user, $order, false)){
						reviso_logthis("reviso_save_invoice_order_to_reviso order: " . $order_id . " is synced with reviso");
					}
					else{
						reviso_logthis("reviso_save_invoice_order_to_reviso order: " . $order_id . " sync failed, please try again after sometime!");
					}
				}else{
					if($wcr_api->save_order_to_reviso($client, $user, $order, false)){
						reviso_logthis("reviso_save_customer_to_reviso order: " . $order_id . " is synced with reviso");
					}
					else{
						reviso_logthis("reviso_save_customer_to_reviso order: " . $order_id . " sync failed, please try again after sometime!");
					}
				}
				/**
				* if create auto debtor payment - create it
				
				$auto_create_debtor = $options['activate-cashbook'];
				if (isset($auto_create_debtor) && $auto_create_debtor == 'on') {
					woo_reviso_create_debtor_payment($user, $order);
				}*/
			}catch (Exception $exception) {
				reviso_logthis("woocommerce_order_status_completed could not sync: " . $exception->getMessage());
				$wcr_api->debug_client($client);
				reviso_logthis($exception->getMessage);
				if($wpdb->query ("SELECT * FROM wcr_orders WHERE order_id=".$order_id." AND synced=0;")){
					return false;
				}else{
					$wpdb->insert ("wcr_orders", array('order_id' => $order_id, 'synced' => 0), array('%d', '%d'));
					return false;
				}
				return false;
			}
		}
		
		/*
		* Action to create invoice/order/quotation
		*
		add_action('woocommerce_order_status_refunded', 'woo_refund_order_to_reviso', 10, 4);
		function woo_refund_order_to_reviso($order_id) {
			include_once("class-reviso-api.php");
			$wcr_api = new WCR_API();
			reviso_logthis("woo_reviso_refund_invoice: order_id: ".$order_id);
			$order = new WC_Order($order_id);
			$user = new WP_User($order->user_id);
			$client = $wcr_api->wooreviso_client();
			$wcr_api->save_invoice_to_reviso($user, $order, $client, $order_id . " refunded", true);
		}*/
		
		//Save orders to reviso from woocommerce ends.


		
		//Save customers to reviso from woocommerce ends.
		
		/*
		 * Create new customer at reviso with minimial required data.
		 */
		add_action('woocommerce_checkout_order_processed', 'reviso_save_customer_to_reviso');
		add_action('woo_save_shop_order_to_reviso', 'reviso_save_customer_to_reviso');
		
		function reviso_save_customer_to_reviso($order_id) {
			try{
				include_once("class-reviso-api.php");
				$order = new WC_Order($order_id);
				global $wpdb;
				if($wpdb->query ("SELECT * FROM wcr_orders WHERE order_id=".$order->id." AND synced=1")){
					reviso_logthis('syncing order for update.');
				}elseif($wpdb->query ("SELECT * FROM wcr_orders WHERE order_id=".$order->id." AND synced=0")){
					reviso_logthis('syncing order failed previously');
				}else{
					$wpdb->insert ("wcr_orders", array('order_id' => $order_id, 'synced' => 0), array('%d', '%d'));
				}
				$options = get_option('woocommerce_reviso_general_settings');
				$wcr = new WC_Reviso();
				$wcr_api = new WCR_API();
				if($order->customer_user != 0){
					$user = new WP_User($order->customer_user);
				}else{
					$user = NULL;
				}
				if($wcr->reviso_is_license_key_valid() != "Active" || !$wcr_api->wooreviso_create_API_validation_request()){
					if($wpdb->query ("SELECT * FROM wcr_customers WHERE email=".$order->billing_email.";")){
						$wpdb->update ("wcr_customers", array('synced' => 0), array('email' => $order->billing_email), array('%d'), array('%s'));
					}else{
						$wpdb->insert ("wcr_customers", array('user_id' => $user->ID, 'customer_number' => 0, 'email' => $order->billing_email, 'synced' => 0), array('%d', '%s', '%s', '%d'));
					}
					return false;
				}
				reviso_logthis("reviso_save_customer_to_reviso for user: " . $order->billing_first_name);
			
				if (is_reviso_customer($user)) {
					$client = $wcr_api->wooreviso_client();
					reviso_logthis("reviso_save_customer_to_reviso user: " . $order->billing_first_name . " is being synced with reviso.");
					if($wcr_api->save_customer_to_reviso($client, $user, $order)){
						reviso_logthis("reviso_save_customer_to_reviso user: " . $order->billing_first_name . " is synced with reviso.");
						if($order->payment_method == 'reviso-invoice'){
							reviso_logthis("reviso_save_customer_to_reviso syncing WC order for Reviso payment.");
							
							if($options['reviso-checkout'] == 'order'){
								if($wcr_api->save_order_to_reviso($client, $user, $order, false)){
									reviso_logthis("reviso_save_customer_to_reviso order: " . $order_id . " is synced with reviso as draft invoice.");
								}
								else{
									reviso_logthis("reviso_save_customer_to_reviso order: " . $order_id . " draft invoice sync failed, please try again after sometime!");
								}
							}
							
							if($options['reviso-checkout'] == 'draft invoice' || $options['reviso-checkout'] == 'invoice'){
								if($wcr_api->save_invoice_to_reviso($client, $user, $order, false)){
									reviso_logthis("reviso_save_invoice_order_to_reviso order: " . $order_id . " is synced with reviso as invoice.");
								}
								else{
									reviso_logthis("reviso_save_invoice_order_to_reviso order: " . $order_id . " invoice sync failed, please try again after sometime!");
								}
								
								if($options['reviso-checkout'] == 'invoice'){
									if($wcr_api->send_invoice_reviso($client, $order)){
										reviso_logthis("reviso_save_invoice_order_to_reviso invoice for order: " . $order_id . " is sent to customer.");
									}else{
										reviso_logthis("reviso_save_invoice_order_to_reviso invoice for order: " . $order_id . " sending failed!");
									}
								}
							}
								
						}else{
							reviso_logthis("reviso_save_customer_to_reviso syncing WC order for payment method except Reviso.");
							if($options['other-checkout'] == 'do nothing'){
								reviso_logthis("reviso_save_customer_to_reviso order: " . $order_id . " is not synced synced with reviso because do nothing is selected for Reviso payment.");
							}
							
							if($options['other-checkout'] == 'order'){
								if($wcr_api->save_order_to_reviso($client, $user, $order, false)){
									reviso_logthis("reviso_save_customer_to_reviso order: " . $order_id . " is synced with reviso as draft invoice.");
								}
								else{
									reviso_logthis("reviso_save_customer_to_reviso order: " . $order_id . " draft invoice sync failed, please try again after sometime!");
								}
							}
							
							if($options['other-checkout'] == 'draft invoice' || $options['other-checkout'] == 'invoice'){
								if($wcr_api->save_invoice_to_reviso($client, $user, $order, false)){
									reviso_logthis("reviso_save_invoice_order_to_reviso order: " . $order_id . " is synced with reviso as invoice.");
								}
								else{
									reviso_logthis("reviso_save_invoice_order_to_reviso order: " . $order_id . " invoice sync failed, please try again after sometime!");
								}
								
								if($options['other-checkout'] == 'invoice'){
									if($wcr_api->send_invoice_reviso($client, $order)){
										reviso_logthis("reviso_save_invoice_order_to_reviso invoice for order: " . $order_id . " is sent to customer.");
									}else{
										reviso_logthis("reviso_save_invoice_order_to_reviso invoice for order: " . $order_id . " sending failed!");
									}
								}
							}
								
						}
						//do_action( 'woocommerce_payment_complete', $order_id );
					}
					else{
						reviso_logthis("reviso_save_customer_to_reviso user: " . $user->ID . "sync failed, please manual sync after sometime!");
					}
				}
			}catch (Exception $exception) {
				reviso_logthis("reviso_save_customer_to_reviso could not sync user/order: " . $exception->getMessage());
				$wcr_api->debug_client($client);
				reviso_logthis($exception->getMessage);
				if($wpdb->query ("SELECT * FROM wcr_orders WHERE order_id=".$order_id." AND synced=0;")){
					return false;
				}else{
					$wpdb->insert ("wcr_orders", array('order_id' => $order_id, 'synced' => 0), array('%d', '%d'));
					return false;
				}
				if($wpdb->query ("SELECT * FROM wcr_customers WHERE email=".$order->billing_email." AND synced=0;")){
					return false;
				}else{
					$wpdb->insert ("wcr_customers", array('user_id' => $user->ID, 'customer_number' => 0, 'email' => $order->billing_email, 'synced' => 0), array('%d', '%s', '%s', '%d'));
					return false;
				}
				return false;
			}
		}
		
		
		// add the action for payment completed to add note to Reviso order/invoice about the payment type and date. 
		add_action( 'woocommerce_payment_complete', 'reviso_update_order_payment_to_reviso', 10, 1 ); 
		function reviso_update_order_payment_to_reviso($order_id){
			reviso_logthis('reviso_update_order_payment_to_reviso: Called by woocommerce_payment_complete hook.');
			include_once("class-reviso-api.php");
			$order = new WC_Order($order_id);
			$wcr_api = new WCR_API();
			$client = $wcr_api->wooreviso_client();
			if($client){
				$wcr_api->wooreviso_update_order_payment_to_reviso($client, $order);
			}else{
				reviso_logthis('reviso_update_order_payment_to_reviso: failed, because client not created.');
			}
		}
		
		//add the action for show user profile and update user profile added by Alvin for 1.9.9.8 release.
		add_action( 'show_user_profile', 'reviso_add_customer_meta_fields', 10, 1 );
		add_action( 'edit_user_profile', 'reviso_add_customer_meta_fields', 10, 1 );
		add_action( 'personal_options_update', 'reviso_save_customer_meta_fields', 10, 1 );
		add_action( 'edit_user_profile_update', 'reviso_save_customer_meta_fields', 10, 1 );
		
		function reviso_add_customer_meta_fields($user){
			?>
            <h3>Reviso</h3>
			<table class="form-table">
					<tr>
						<th><label for="customerno">Reviso customer number</label></th>
						<td>
							<input type="text" name="debtor_number" id="customerno" value="<?php echo esc_attr( get_user_meta( $user->ID, 'debtor_number', true ) ); ?>" class="regular-text" />
							<br/>
							<span class="description"><?php echo wp_kses_post( 'Reviso customer number' ); ?></span>
						</td>
					</tr>
			</table>
            <?php
		}
		
		
		function reviso_save_customer_meta_fields($user_id){
			if ( isset( $_POST['debtor_number'] ) ) {
				update_user_meta( $user_id, 'debtor_number', wc_clean( $_POST['debtor_number'] ) );
			}		
		}
		
		function is_reviso_customer($user) {
		  //$is_customer = false; changed for accepting all customers.
		  $is_customer = true;
		  return $is_customer;
		  foreach ($user->roles as $role) {
			reviso_logthis("user role: " . $role);
			if ($role == 'customer') {
			  $is_customer = true;
			  break;
			}
		  }
		  return $is_customer;
		}
		
		/*
		 * Save additional user data to reviso
		 */
		add_action('update_user_meta', 'reviso_update_user_meta', 10, 4);
		function reviso_update_user_meta($meta_id, $object_id, $meta_key, $_meta_value) {
		  global $wpdb;
		  include_once("class-reviso-api.php");
		  $wcr = new WC_Reviso();
		  $wcr_api = new WCR_API();
		  $user = new WP_User($object_id);
		  if(in_array($meta_key, $wcr_api->user_fields)){
			  reviso_logthis("reviso_update_user_meta: meta_id: ".$meta_id." object_id: ".$object_id." meta_key: ".$meta_key." meta_value: ".$_meta_value);
			  if($wcr->reviso_is_license_key_valid() != "Active" || !$wcr_api->wooreviso_create_API_validation_request()){
				  if($wpdb->query ("SELECT * FROM wcr_customers WHERE user_id=".$object_id.";")){
					 $wpdb->update ("wcr_customers", array('email' => $user->get('billing_email'), 'synced' => 0), array('user_id' => $object_id), array('%s', '%d'), array('%d'));
				  }else{
					 $wpdb->insert ("wcr_customers", array('user_id' => $object_id, 'customer_number' => 0, 'email' => $user->get('billing_email'), 'synced' => 0), array('%d', '%s', '%s', '%d'));
				  }
				  return false;
			  }
			  if (is_reviso_customer($user)) {
				$client = $wcr_api->wooreviso_client();
				
				$debtorHandle = $wcr_api->wooreviso_get_debtor_handle_from_reviso($client, $user);
				$debtor_delivery_location_handle = $wcr_api->wooreviso_get_debtor_delivery_location_handles_from_reviso($client, $debtorHandle);
				
				if($wcr_api->wooreviso_save_customer_meta_data_to_reviso($client, $meta_key, $_meta_value, $debtorHandle, $debtor_delivery_location_handle, $user)){
					$wpdb->update ("wcr_customers", array('synced' => 1), array('user_id' => $user->ID), array('%d'), array('%d'));
					reviso_logthis("reviso_update_user_meta user: " . $user->ID . " additional data is synced with reviso");
				}
				else{
					$wpdb->update ("wcr_customers", array('synced' => 0), array('user_id' => $user->ID), array('%d'), array('%d'));
					reviso_logthis("reviso_update_user_meta user: " . $user->ID . " additional data sync failed, please try again after sometime!");
				}
			  }
		  }else{
			  reviso_logthis("reviso_update_user_meta: Not selected for sync, skipping meta_id: ".$meta_id." object_id: ".$object_id);
		  }
		}
		
		//Save customers to reviso from woocommerce ends.


		//Section for Plugin installation and activation
		/**
		 * Creates tables for WooCommerce reviso
		 *
		 * @access public
		 * @param void
		 * @return bool
		 */
		function reviso_install(){
			add_option('reviso-tour', true);
			global $wpdb;
			$wcr_orders = "wcr_orders";
			$wcr_customers = "wcr_customers";
			
			$sql = "CREATE TABLE IF NOT EXISTS ".$wcr_orders."( id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
					order_id MEDIUMINT(9) NOT NULL,
					synced TINYINT(1) DEFAULT FALSE NOT NULL,
					UNIQUE KEY id (id)
			);";
			
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
			
			$sql = "CREATE TABLE IF NOT EXISTS ".$wcr_customers."( id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
					user_id MEDIUMINT(9) NOT NULL,
					customer_number MEDIUMINT(9) NOT NULL,
					email VARCHAR(320) DEFAULT NULL,
					synced TINYINT(1) DEFAULT FALSE NOT NULL,
					UNIQUE KEY user_id (id)
			);";
			
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
			
			update_option('reviso_version', 1.0);
			update_option('reviso_save_object', true);
		}
		
		/**
		 * Drops tables for WooCommerce reviso
		 *
		 * @access public
		 * @param void
		 * @return bool
		 */
		function reviso_uninstall(){
			global $wpdb;				
			$wcr_orders = "wcr_orders";
			$wcr_customers = "wcr_customers";
			$wpdb->query ("DROP TABLE ".$wcr_orders.";");
			$wpdb->query ("DROP TABLE ".$wcr_customers.";");
			delete_option('reviso-tour');	
			delete_option('reviso_version');
			delete_option('woocommerce_reviso_general_settings');	
			delete_option('local_key_reviso_plugin');
			delete_option('woocommerce_reviso_order_settings');
			wp_clear_scheduled_hook('reviso_product_sync_cron');		
		}
		
		/**
		 *
		 *Functon for plugin update
		*/
		function reviso_update(){
			global $wpdb;
			$wcr_orders = "wcr_orders";
			$wcr_customers = "wcr_customers";
			$reviso_version = get_option('reviso_version');
			if(floatval($reviso_version) < 1.0 ){
				
			}
			update_option('reviso_version', 1.0);
			update_option('reviso_save_object', true);
		}
		
		add_action( 'plugins_loaded', 'reviso_update' );
		
		// install necessary tables
		register_activation_hook( __FILE__, 'reviso_install');
		register_uninstall_hook( __FILE__, 'reviso_uninstall');
		//Section for plugin installation and activation ends


        /**
         * Localisation
         **/
		 
		 /**
		 * Return the locale to en_GB
		 */ 		
		add_action('plugins_loaded', 'reviso_load_textdomain');
		function reviso_load_textdomain() {
			load_plugin_textdomain( 'wooreviso', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}
				

        class WC_Reviso {

            private $general_settings_key = 'woocommerce_reviso_general_settings';
            private $order_settings_key = 'woocommerce_reviso_order_settings';
            private $support_key = 'woocommerce_reviso_support';
            private $manual_action_key = 'woocommerce_reviso_manual_action';
            private $start_action_key = 'woocommerce_reviso_start_action';
            private $general_settings;
            private $accounting_settings;
            private $plugin_options_key = 'woocommerce_reviso_options';
            private $plugin_settings_tabs = array();
			

            public function __construct() {

                //call register settings function
                add_action( 'init', array( &$this, 'load_settings' ) );
                add_action( 'admin_init', array( &$this, 'register_woocommerce_reviso_start_action' ));
                add_action( 'admin_init', array( &$this, 'register_woocommerce_reviso_general_settings' ));
                add_action( 'admin_init', array( &$this, 'register_woocommerce_reviso_manual_action' ));
                add_action( 'admin_init', array( &$this, 'register_woocommerce_reviso_support' ));
                add_action( 'admin_menu', array( &$this, 'reviso_add_admin_menus' ) );


                // install necessary tables
                //register_activation_hook( __FILE__, array(&$this, 'install'));
                //register_deactivation_hook( __FILE__, array(&$this, 'uninstall'));
            }

            /***********************************************************************************************************
             * ADMIN SETUP
             ***********************************************************************************************************/

            /**
             * Adds admin menu
             *
             * @access public
             * @param void
             * @return void
             */
            function reviso_add_admin_menus() {
				add_menu_page( 'WooCommerce Reviso Integration', 'Reviso', 'manage_options', $this->plugin_options_key, array( &$this, 'woocommerce_reviso_options_page' ) );
            }

            /**
             * Generates html for textfield for given settings params
             *
             * @access public
             * @param void
             * @return void
             */
            function field_gateway($args) {
                $options = get_option($args['tab_key']);?>

                <input type="hidden" name="<?php echo $args['tab_key']; ?>[<?php echo $args['key']; ?>]" value="<?php echo $args['key']; ?>" />

                <select name="<?php echo $args['tab_key']; ?>[<?php echo $args['key'] . "_payment_method"; ?>]" >';
                    <option value=""<?php if(isset($options[$args['key'] . "_payment_method"]) && $options[$args['key'] . "_payment_method"] == ''){echo 'selected="selected"';}?>>Välj nedan</option>
                    <option value="CARD"<?php if(isset($options[$args['key'] . "_payment_method"]) && $options[$args['key'] . "_payment_method"] == 'CARD'){echo 'selected="selected"';}?>>Kortbetalning</option>
                    <option value="BANK"<?php if(isset($options[$args['key'] . "_payment_method"]) && $options[$args['key'] . "_payment_method"] == 'BANK'){echo 'selected="selected"';}?>>Bankgiro/Postgiro</option>
                </select>
                <?php
                $str = '';
                if(isset($options[$args['key'] . "_book_keep"])){
                    if($options[$args['key'] . "_book_keep"] == 'on'){
                        $str = 'checked = checked';
                    }
                }
                ?>
                <span>Bokför automatiskt:  </span>
                <input type="checkbox" name="<?php echo $args['tab_key']; ?>[<?php echo $args['key'] . "_book_keep"; ?>]" <?php echo $str; ?> />

            <?php
            }

            /**
             * Generates html for textfield for given settings params
             *
             * @access public
             * @param void
             * @return void
             */
            function field_option_text($args) {
                $options = get_option($args['tab_key']);
                $val = '';
                if(isset($options[$args['key']])){
                    $val = esc_attr( $options[$args['key']] );
                }
				if($args['key'] == 'token' &&  (!isset($options[$args['key']]) || $options[$args['key']]=='')){
					if(isset($_GET['token'])){
						$val = $_GET['token'];
						if((!isset($options[$args['key']]) || $options[$args['key']]=='')){
							$args['desc'] .= __(' Please save the settings before leaving this page!', 'wooreviso');
						}
					}else{
						$args['desc'] .= '<a href="https://app.reviso.com/api1/requestaccess.aspx?appId=_mEIKiB7_X9-hFqVOfVmnueQDQBshgGEAa-EgXvRBlU1&redirectUrl='.urlencode(admin_url().'admin.php?page=woocommerce_reviso_options&tab=woocommerce_reviso_general_settings').'" class="button button-primary" title="" style="margin-left:5px">'.__(' Click here to generate token access ID', 'wooreviso').'</a>';
					}
					
				}
                ?>
                <input <?php echo isset($args['id'])? 'id="'.$args['id'].'"': ''; ?> type="text" name="<?php echo $args['tab_key']; ?>[<?php echo $args['key']; ?>]" value="<?php echo $val; ?>" />
                <span><i><?php echo $args['desc']; ?></i></span>
            <?php
            }
            
            /**
             * Generates html for dropdown for given settings of sandbox params
             *
             * @access public
             * @param void
             * @return void
             */
            function field_mode_dropdown($args) {
                $options = get_option($args['tab_key']);
                $str = '';
                $str2 = '';
                if(isset($options[$args['key']])){
                    if($options[$args['key']] == 'Live'){
                        $str = 'selected';
                    }
                    else
                    {
                        $str2 = 'selected';
                    }
                }

                ?>
                <select <?php echo isset($args['id'])? 'id="'.$args['id'].'"': ''; ?> name="<?php echo $args['tab_key']; ?>[<?php echo $args['key']; ?>]">
                    <option <?php echo $str; ?>>Live</option>
                    <option <?php echo $str2; ?>>Sandbox</option>
                </select>
                <span id="sandbox-mode"><i><?php echo $args['desc']; ?></i></span>
            <?php
            }
			
			/**
             * Generates html for dropdown for given settings params
             *
             * @access public
             * @param void
             * @return void
             */
            function field_option_schedule($args) {
                $options = get_option($args['tab_key']);
                $hourly = '';
                $twicedaily = '';
				$daily = '';
				$disabled = '';
                if(isset($options[$args['key']])){
                    if($options[$args['key']] == 'hourly'){
                        $hourly = 'selected';
                    }
					elseif($options[$args['key']] == 'twicedaily'){
						$twicedaily = 'selected';
					}
					elseif($options[$args['key']] == 'twicedaily'){
						$daily = 'selected';
					}
					else{
						$disabled = 'selected';
					}
                }
				
				wp_clear_scheduled_hook('reviso_product_sync_cron');
				if($options[$args['key']] != 'disabled' && $options[$args['key']] != ''){
					wp_schedule_event(time(), $options[$args['key']], 'reviso_product_sync_cron');
				}

                ?>
                <select <?php echo isset($args['id'])? 'id="'.$args['id'].'"':''; ?> name="<?php echo $args['tab_key']; ?>[<?php echo $args['key']; ?>]">
                	<option <?php echo $disabled; ?> value='disabled'><?php _e('Disabled', 'wooreviso'); ?></option>
                    <option <?php echo $hourly; ?> value='hourly'><?php _e('Hourly', 'wooreviso'); ?></option>
                    <option <?php echo $twicedaily; ?> value='twicedaily'><?php _e('Twice Daily', 'wooreviso'); ?></option>
                    <option <?php echo $daily; ?> value='daily'><?php _e('Daily', 'wooreviso'); ?></option>
                </select>
                <span><i><?php echo $args['desc']; ?></i></span>
            <?php
            }
			
			
            
            /**
             * Generates html for dropdown for given settings params
             *
             * @access public
             * @param void
             * @return void
             */
            function field_option_dropdown($args) {
                $options = get_option($args['tab_key']);
                $str1 = '';
                $str2 = '';
				$str3 = '';
				$str4 = '';
                if(isset($options[$args['key']])){
                    if($options[$args['key']] == 'do nothing'){
                        $str1 = 'selected';
                    }
					elseif($options[$args['key']] == 'order'){
						$str2 = 'selected';
					}
					elseif($options[$args['key']] == 'draft invoice'){
						$str3 = 'selected';
					}
					elseif($options[$args['key']] == 'invoice'){
						$str4 = 'selected';
					}
                }

                ?>
                <select <?php echo isset($args['id'])? 'id="'.$args['id'].'"':''; ?> name="<?php echo $args['tab_key']; ?>[<?php echo $args['key']; ?>]">
                	<?php if($args['key'] == 'other-checkout'){ ?>
                    <option <?php echo $str1; ?> value='do nothing'><?php _e('Do nothing', 'wooreviso'); ?></option>
                    <?php } ?>
                    <option <?php echo $str2; ?> value='order'><?php _e('Create order', 'wooreviso'); ?></option>
                    <option <?php echo $str3; ?> value='draft invoice'><?php _e('Create draft invoice', 'wooreviso'); ?></option>
                    <option <?php echo $str4; ?> value='invoice'><?php _e('Create invoice', 'wooreviso'); ?></option>
                </select>
                <span><i><?php echo $args['desc']; ?></i></span>
            <?php
            }
			
			
			/**
             * Generates html for dropdown for given settings params (product and customer group)
             *
             * @access public
             * @param void
             * @return void
             */
            function field_option_group($args) {
				$options = get_option('woocommerce_reviso_general_settings');
				$wcr_api = new WCR_API();
				$client = $wcr_api->wooreviso_client();
				if(!$client){
					_e('<span><i>reviso client not loaded properly, please refresh the page to load properly.</i></span>', 'wooreviso');
					return false;
				}
				if($args['key'] == 'product-group' || $args['key'] == 'shipping-group' || $args['key'] == 'coupon-group'){
					$groups = $client->ProductGroup_GetAll()->ProductGroup_GetAllResult->ProductGroupHandle;
					if(is_array($groups)){
						foreach($groups as $group){
							$groupnames[$group->Number] = $client->ProductGroup_GetName(array('productGroupHandle' => $group))->ProductGroup_GetNameResult;
						}
					}else{
						$groupnames[$groups->Number] = $client->ProductGroup_GetName(array('productGroupHandle' => $groups))->ProductGroup_GetNameResult;
					}
				}
				if($args['key'] == 'customer-group'){
					$groups = $client->DebtorGroup_GetAll()->DebtorGroup_GetAllResult->DebtorGroupHandle;
					if(is_array($groups)){
						foreach($groups as $group){
							$groupnames[$group->Number] = $client->DebtorGroup_GetName(array('debtorGroupHandle' => $group))->DebtorGroup_GetNameResult;
						}
					}else{
						$groupnames[$groups->Number] = $client->DebtorGroup_GetName(array('debtorGroupHandle' => $groups))->DebtorGroup_GetNameResult;
					}
				}
				
				?>
                <select name="<?php echo $args['tab_key']; ?>[<?php echo $args['key']; ?>]">
                	<option <?php if(empty($groups)){ echo 'selected'; } ?> value='-1'><?php _e('Select a group', 'wooreviso') ?></option>
				<?php
				foreach($groups as $group){
                ?>
                    
					<option <?php if(isset($options[$args['key']]) && $options[$args['key']] == $group->Number) echo 'selected'; ?> value='<?php echo $group->Number; ?>'><?php echo $group->Number.'-'.$groupnames[$group->Number]; ?></option>
                 
            	<?php
				}
				?>
                </select>
                    <span><i><?php echo $args['desc']; ?></i></span>
                <?php
            }


            /**
             * Generates html for checkbox for given settings params
             *
             * @access public
             * @param void
             * @return void
             */
            function field_option_checkbox($args) {
                $options = get_option($args['tab_key']);
                $str = '';
                if(isset($options[$args['key']])){
                    if($options[$args['key']] == 'on'){
                        $str = 'checked = checked';
                    }
                }

                ?>
                <input <?php echo isset($args['id'])? 'id="'.$args['id'].'"': ''; ?> type="checkbox" name="<?php echo $args['tab_key']; ?>[<?php echo $args['key']; ?>]" <?php echo $str; ?> />
                <span><i><?php echo $args['desc']; ?></i></span>
            <?php
            }

            /**
             * WooCommerce Loads settigns
             *
             * @access public
             * @param void
             * @return void
             */
            function load_settings() {
                $this->general_settings = (array) get_option( $this->general_settings_key );
                $this->order_settings = (array) get_option( $this->order_settings_key );
            }

            /**
             * Tabs and plugin page setup
             *
             * @access public
             * @param void
             * @return void
             */
            function plugin_options_tabs() {
                $current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->start_action_key;
                $options = get_option('woocommerce_reviso_general_settings');
                echo '<div class="wrap"><h2>WooCommerce Reviso Integration</h2><div id="icon-edit" class="icon32"></div></div>';
                $key_status = $this->reviso_is_license_key_valid();
                if(!isset($options['license-key']) || $options['license-key'] == '' || $key_status!='Active'){
                    echo "<button type=\"button button-primary\" class=\"button button-primary\" title=\"\" style=\"margin:5px\" onclick=\"window.open('http://whmcs.onlineforce.net/cart.php?a=add&pid=56&carttpl=flex-web20cart&language=English','_blank');\">".__('Get license Key', 'wooreviso')."</button> <div class='key_error'>".__('License Key Invalid', 'wooreviso')."</div>";

                }

                echo '<h2 class="nav-tab-wrapper">';

                foreach ( $this->plugin_settings_tabs as $tab_key => $tab_caption ) {
                    $active = $current_tab == $tab_key ? 'nav-tab-active' : '';
                    echo '<a class="nav-tab ' . $active . '" href="?page=' . $this->plugin_options_key . '&tab=' . $tab_key . '">' . $tab_caption . '</a>';
                }
                echo '</h2>';

            }

            /**
             * WooCommerce Billogram General Settings
             *
             * @access public
             * @param void
             * @return void
             */
            function register_woocommerce_reviso_general_settings() {

                $this->plugin_settings_tabs[$this->general_settings_key] = __('General settings', 'wooreviso');
				
				$options = get_option('woocommerce_reviso_general_settings');

                register_setting( $this->general_settings_key, $this->general_settings_key );
                add_settings_section( 'section_general', __('General settings', 'wooreviso'), array( &$this, 'section_general_desc' ), $this->general_settings_key );
				
				add_settings_field( 'woocommerce-reviso-token', __('Token ID', 'wooreviso'), array( &$this, 'field_option_text'), $this->general_settings_key, 'section_general', array ( 'tab_key' => $this->general_settings_key, 'key' => 'token', 'desc' => __('Token access ID from Reviso.', 'wooreviso')) );
				
                add_settings_field( 'woocommerce-reviso-license-key', __('License key', 'wooreviso'), array( &$this, 'field_option_text' ), $this->general_settings_key, 'section_general', array ( 'id' => 'license-key', 'tab_key' => $this->general_settings_key, 'key' => 'license-key', 'desc' => __('This is the License key you received from us by mail.', 'wooreviso')) );
				
				add_settings_field( 'woocommerce-reviso-other-checkout', __('Other checkout', 'wooreviso'), array( &$this, 'field_option_dropdown' ), $this->general_settings_key, 'section_general', array ( 'tab_key' => $this->general_settings_key, 'key' => 'other-checkout', 'desc' => __('What should be created at Reviso when the checkout is made via any payment gateway but not Reviso. <br><i style="margin-left:25px; color: #F00;">Note: Reviso Orders and Draft invoices can be updated later, but Reviso Invoice are readonly.</i>', 'wooreviso')) );
				
				add_settings_field( 'woocommerce-reviso-checkout', __('Reviso checkout', 'wooreviso'), array( &$this, 'field_option_dropdown' ), $this->general_settings_key, 'section_general', array ( 'tab_key' => $this->general_settings_key, 'key' => 'reviso-checkout', 'desc' => __('What should be created at Reviso when the checkout is made via Reviso. Go to WooCommerce>Settings>Checkout to enable Reviso Invoice as payment option. <br><i style="margin-left:25px; color: #F00;">Note: Reviso Orders and Draft invoices can be updated later, but Reviso Invoice are readonly.</i>', 'wooreviso')) );
				
				add_settings_field( 'woocommerce-reviso-activate-oldordersync', __('Activate old orders sync', 'wooreviso'), array( &$this, 'field_option_checkbox' ), $this->general_settings_key, 'section_general', array ( 'tab_key' => $this->general_settings_key, 'key' => 'activate-oldordersync', 'desc' => __('Also sync orders created before WooReviso installation.', 'wooreviso')) );
				
				add_settings_field( 'woocommerce-reviso-product-sync', __('Activate product sync', 'wooreviso'), array( &$this, 'field_option_checkbox' ), $this->general_settings_key, 'section_general', array ( 'tab_key' => $this->general_settings_key, 'key' => 'product-sync', 'desc' => __('Sync product information from WooCommerce to Reviso. (Stock information is updated regardless of this setting)', 'wooreviso')) );
				
				add_settings_field( 'woocommerce-reviso-scheduled-product-sync', __('Run scheduled product stock sync', 'wooreviso'), array( &$this, 'field_option_schedule' ), $this->general_settings_key, 'section_general', array ( 'tab_key' => $this->general_settings_key, 'key' => 'scheduled-product-sync', 'desc' => __('Run scheduled product stock sync from Reviso to WooCommerce.', 'wooreviso')) );
				
				if($options['token'] != ''){
					add_settings_field( 'woocommerce-reviso-product-group', __('Product group', 'wooreviso'), array( &$this, 'field_option_group' ), $this->general_settings_key, 'section_general', array ( 'id' => 'product-group', 'tab_key' => $this->general_settings_key, 'key' => 'product-group', 'desc' => __('Reviso product group to which new products are added.', 'wooreviso')) );
					
					add_settings_field( 'woocommerce-reviso-product-prefix', __('Product prefix', 'wooreviso'), array( &$this, 'field_option_text' ), $this->general_settings_key, 'section_general', array ( 'id' => 'product-prefix', 'tab_key' => $this->general_settings_key, 'key' => 'product-prefix', 'desc' => __('Prefix added to the products stored to Reviso from woocommerce', 'wooreviso')) );
					
					add_settings_field( 'woocommerce-reviso-customer-group', __('Customer group', 'wooreviso'), array( &$this, 'field_option_group' ), $this->general_settings_key, 'section_general', array ( 'id' => 'customer-group', 'tab_key' => $this->general_settings_key, 'key' => 'customer-group', 'desc' => __('Reviso customer group to which new customers are added. <br><i style="margin-left:25px; color: #F00;">MUST be selected. Sync is not possible if not selected.</i>', 'wooreviso')) );
					
					add_settings_field( 'woocommerce-reviso-shipping-group', __('Shipping group', 'wooreviso'), array( &$this, 'field_option_group' ), $this->general_settings_key, 'section_general', array ( 'id' => 'shipping-group', 'tab_key' => $this->general_settings_key, 'key' => 'shipping-group', 'desc' => __('Reviso product group to which shipping methods are added.', 'wooreviso')) );
					
					add_settings_field( 'woocommerce-reviso-coupon-group', __('Coupon group', 'wooreviso'), array( &$this, 'field_option_group' ), $this->general_settings_key, 'section_general', array ( 'id' => 'coupon-group', 'tab_key' => $this->general_settings_key, 'key' => 'coupon-group', 'desc' => __('Reviso product group to which coupon discounts are added.', 'wooreviso')) );
					
					add_settings_field( 'woocommerce-reviso-order-reference-prefix', __('Order reference prefix', 'wooreviso'), array( &$this, 'field_option_text' ), $this->general_settings_key, 'section_general', array ( 'id' => 'order-reference-prefix', 'tab_key' => $this->general_settings_key, 'key' => 'order-reference-prefix', 'desc' => __('Prefix added to the order reference of an Order synced to Reviso from woocommerce', 'wooreviso')) );
				}
            }


            /**
             * WooCommerce Manual Actions Settings
             *
             * @access public
             * @param void
             * @return void
             */
            function register_woocommerce_reviso_manual_action() {

                $this->plugin_settings_tabs[$this->manual_action_key] = __('Manual functions', 'wooreviso');
                register_setting( $this->manual_action_key, $this->manual_action_key );
            }


            /**
             * WooCommerce Start Actions
             *
             * @access public
             * @param void
             * @return void
             */
            function register_woocommerce_reviso_start_action() {
                $this->plugin_settings_tabs[$this->start_action_key] = __('Welcome!', 'wooreviso');
                register_setting( $this->start_action_key, $this->start_action_key );
            }


            /**
             * WooCommerce Billogram Accounting Settings
             *
             * @access public
             * @param void
             * @return void
             */
            function register_woocommerce_reviso_support() {

                $this->plugin_settings_tabs[$this->support_key] = __('Support', 'wooreviso');
                register_setting( $this->support_key, $this->support_key );
            }

            /**
             * The description for the general section
             *
             * @access public
             * @param void
             * @return void
             */
            function section_general_desc() { echo __('Specifies basic settings for the Reviso integration and you can control which parts you want to sync to Reviso', 'wooreviso'); }

            /**
             * The description for the accounting section
             *
             * @access public
             * @param void
             * @return void
             */
            function section_accounting_desc() { echo __('Description Accounting settings.', 'wooreviso'); }

            /**
             * The description for the shipping section
             *
             * @access public
             * @param void
             * @return void
             */
            function section_order_desc() { echo ''; }

            /**
             * Options page
             *
             * @access public
             * @param void
             * @return void
             */
            function woocommerce_reviso_options_page() {
                $tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->start_action_key;
				$options = get_option('woocommerce_reviso_general_settings');?>
                

                <!-- CSS -->
                <style>
                    li.logo,  {
                        float: left;
                        width: 100%;
                        padding: 20px;
                    }
                    li.full {
	                    padding: 10px 0;
                        height: 50px;
                    }
                    li.full img, img.test_load{
                        float: left;
                        margin: -5px 0 0 5px;
                        display: none;
                    }
					span.test_warning{
						float: left;
						margin:25px 0px 0px 10px;
					}
                    li.col-two {
                        /*float: left;
                        width: 380px;
                        margin-left: 1%;*/
						margin-left: 5%;
						list-style-type: disc;
						font-weight: 500;
						font-size: 24px;
						color: #0073aa;
						line-height: 30px;
                    }
					li.col-two a{
						text-decoration: none;
					}
                    li.col-onethird, li.col-twothird {
	                    float: left;
                    }
                    li.col-twothird {
	                    max-width: 772px;
	                    margin-right: 20px;
                    }
                    li.col-onethird {
	                    width: 300px;
                    }
                    .mailsupport {
	                	background: #dadada;
	                	border-radius: 4px;
	                	-moz-border-radius: 4px;
	                	-webkit-border-radius: 4px;
	                	max-width: 230px;
	                	padding: 0 0 20px 20px;
	                }
	                .mailsupport > h2 {
		                font-size: 20px;
		            }
	                form#support table.form-table tbody tr td, form#installationSupport table.form-table tbody tr td {
		                padding: 4px 0 !important;
		            }
		            form#support input, form#support textarea, form#installationSupport input, form#support textarea {
			                border: 1px solid #b7b7b7;
			                border-radius: 3px;
			                -moz-border-radius: 3px;
			                -webkit-border-radius: 3px;
			                box-shadow: none;
			                width: 210px;
			        }
			        form#support textarea, form#installationSupport textarea {
				        height: 60px;
			        }
			        form#support button, form#installationSupport button {
				        float: left;
				        margin: 0 !important;
				        min-width: 100px;
				    }
				    ul.manuella li.full button.button {
					       clear: left;
					       float: left;
					       min-width: 250px;
				    }
				    ul.manuella li.full > p {
					        clear: right;
					        float: left;
					        margin: 2px 0 20px 11px;
					        max-width: 440px;
					        padding: 5px 10px;
					}
					.key_error
					{
						 background-color: white;
					    color: red;
					    display: inline;
					    font-weight: bold;
					    margin-top: 5px;
					    padding: 5px;
					    position: absolute;
					    text-align: center;
					    width: 200px;
					}
					.testConnection{
						float:left;
					}
					
					.buttonDisable {
						background: #C8C1C1 !important;  
						border-color: #8C8989 !important;  
						-webkit-box-shadow: inset 0 1px 0 rgba(114, 117, 118, 0.5),0 1px 0 rgba(0,0,0,.15) !important; 
						box-shadow: inset 0 1px 0 rgba(176, 181, 182, 0.5),0 1px 0 rgba(0,0,0,.15) !important;
					}
					
					p.submit{
						float: left;
						width: auto;
						padding: 0px;
					}
					/*li.wp-first-item{
						display:none;
					}*/
					span#sandbox-mode{
						color:#F00
					}
					span.error{
						color:#F00
					}
					#sync_direction{
						float:left;
						min-width:250px;
						color: #555;
						border-color: #ccc;
						background: #f7f7f7;
						margin-top: 60px;
						margin-left: 8px;
					}
                </style>
                <script type="text/javascript">
					jQuery(document).ready(function() {
						var element = jQuery('#cashbook-name').parent().parent();
						if(jQuery('#activate-cashbook').is(':checked')){
							element.show();
						}else{
							element.hide();
						}
						jQuery('#activate-cashbook').change(function() {
							if(this.checked) {
								element.show(300);							
							}else{
								element.hide(300);
							}
						});
						
						});
						
						jQuery("#license-key").live("keyup", function(){
							var str = jQuery("#license-key").val();
							var patt = /wrm-[a-zA-Z0-9][^\W]+/gi;
							var licenseMatch = patt.exec(str);
							if(licenseMatch){
								licenseMatch = licenseMatch.toString();
								if(licenseMatch.length == 24){
									jQuery("#license-key").next().removeClass("error");
									jQuery("#license-key").next().children("i").html("Här anges License-nyckeln du har erhållit från oss via mail.");
								}else{
									jQuery("#license-key").next().children("i").html("Ogiltigt format");
									jQuery("#license-key").next().addClass("error");
								}
							}else{
								jQuery("#license-key").next().children("i").html("Ogiltigt format");
								jQuery("#license-key").next().addClass("error");
							}
						});
				</script>
                <?php
                if($tab == $this->support_key){ ?>
                    <div class="wrap">
                        <?php $this->plugin_options_tabs(); ?>
                        <ul>
                            <li class="logo"><?php echo '<img src="' . plugins_url( 'img/logo_landscape.png', __FILE__ ) . '" > '; ?></li>
                            <li class="col-two"><a style="" href="http://wooconomics.com/category/faq/"><?php _e('Our most frequently asked questions FAQ', 'wooreviso'); ?></a></li>
                            <li class="col-two"><a href="http://wooconomics.com/"><?php _e('Support', 'wooreviso'); ?></a></li>
                        </ul>
                    </div>
                <?php
                }
                else if($tab == $this->general_settings_key){ ?>
                    <div class="wrap">
                        <?php $this->plugin_options_tabs(); ?>
                        <form method="post" action="options.php">
                            <?php wp_nonce_field( 'update-options' ); ?>
                            <?php settings_fields( $tab ); ?>
                            <?php do_settings_sections( $tab ); ?>
                            <?php submit_button(__('Save changes', 'wooreviso')); ?>
                            <?php if(!isset($options['token']) || $options['token'] == '' || !isset($options['license-key']) || $options['license-key'] ==''){ ?>
                            <button style="margin: 20px 0px 0px 10px;" type="button" name="testConnection" class="button button-primary buttonDisable testConnection" onclick="" /><?php echo __('Test connection', 'wooreviso'); ?></button>
                            <?php }else{ ?>
                            <button style="margin: 20px 0px 0px 10px;" type="button" name="testConnection" class="button button-primary testConnection" onclick="test_connection()" /><?php echo __('Test connection', 'wooreviso'); ?></button>
                            <?php } ?>
                            <span class="test_warning"><?php echo __('NOTE! Save changes before testing the connection', 'wooreviso'); ?></span>
                            <img style="margin: 10px 0px 0px 10px;" src="<?php echo plugins_url( 'img/ajax-loader.gif', __FILE__ );?>" class="test_load" >
                        </form>
                    </div>
                <?php }
                else if($tab == $this->manual_action_key){ ?>
                    <div class="wrap">
                        <?php $this->plugin_options_tabs(); ?>
                        <ul class="manuella">
                        	<li class="full">
                            	<select id="sync_direction">
                                	<option value="wr">WooCommerce to Reviso</option>
                                    <option value="rw">Reviso to WooCommerce</option>
                                </select>
                            	<p> <?php _e('Manual sync direction', 'wooreviso') ?> <br /><i><?php _e('Choose this option before using "Manual sync customers" and "Manual sync products" syncs, default will be WooCommerce to Reviso.<br>WooCommerce to Reviso: Products and Customers data from WooCommerce send to Reviso.<br>
Reviso to WooCommerce: Products and Customers data from Reviso saved at WooCommerce.', 'wooreviso'); ?></i></p>
                                
                            </li>
                            <li class="full">
                                <button type="button" class="button" title="<?php _e('Manual sync products', 'wooreviso'); ?>" style="margin:5px" onclick="sync_products('<?php _e('The synchronization can take a long time depending on how many products that will be exported. \ nA message will appear on this page when the synchronization is complete. Do not leave this page, which will suspended the import!', 'wooreviso') ?>')"><?php _e('Manual sync products', 'wooreviso'); ?></button>
                                <img src="<?php echo plugins_url( 'img/ajax-loader.gif', __FILE__ );?>" class="product_load" >
                                <p><?php _e('Send all products to your Reviso. If you have many products, it may take a while.', 'wooreviso'); ?></p>
                            </li>
                            <li class="full">
                                <button type="button" class="button" title="<?php _e('Manual sync delivery methods', 'wooreviso'); ?>" style="margin:5px" onclick="sync_shippings('<?php _e('A message will appear on this page when the synchronization is complete. Do not leave this page, which will suspend the sync!', 'wooreviso') ?>')"><?php _e('Manual sync delivery methods', 'wooreviso'); ?></button>
                                <img src="<?php echo plugins_url( 'img/ajax-loader.gif', __FILE__ );?>" class="shipping_load" >
                                <p><?php _e('Send all delivery method costs to your Reviso.', 'wooreviso'); ?></p>
                            </li>
                            <li class="full">
                                <button type="button" class="button" title="<?php _e('Manual sync coupons', 'wooreviso'); ?>" style="margin:5px" onclick="sync_coupons('<?php _e('A message will appear on this page when the synchronization is complete. Do not leave this page, which will suspend the sync!', 'wooreviso') ?>')"><?php _e('Manual sync coupons', 'wooreviso'); ?></button>
                                <img src="<?php echo plugins_url( 'img/ajax-loader.gif', __FILE__ );?>" class="coupon_load" >
                                <p><?php _e('Send all coupon codes to your Reviso.', 'wooreviso'); ?></p>
                            </li>
                            <li class="full">
                                <button type="button" class="button" title="Manuell synkning kontakter" style="margin:5px" onclick="sync_contacts('<?php _e('The synchronization can take a long time depending on how many customers to be imported. \ nA message will appear on this page when the synchronization is complete. Do not leave this page, which will suspend the sync!', 'wooreviso') ?>')"><?php _e('Manual sync customers', 'wooreviso'); ?></button>
                                <img src="<?php echo plugins_url( 'img/ajax-loader.gif', __FILE__ );?>" class="customer_load" >
                                <p><?php _e('Sync customers created manually in woocommerce dashboard.', 'wooreviso'); ?></p>
                            </li>
                            <li class="full">
                                <button type="button" class="button" title="Manuell Synkning beställningar/fakturor" style="margin:5px" onclick="sync_orders('<?php _e('The synchronization can take a long time depending on how many orders to be exported. \ nA message will appear on this page when the synchronization is complete. Do not leave this page, which will suspended the import!', 'wooreviso') ?>')"><?php _e('Manual syncing orders/invoices', 'wooreviso'); ?></button>
                                <img src="<?php echo plugins_url( 'img/ajax-loader.gif', __FILE__ );?>" class="order_load" >
                                <p><?php _e('Synchronizes all orders that failed to synchronize. (default sync is set to General Settings-> Create options)', 'wooreviso'); ?></p>
                            </li>     
                        </ul>
                        <div class="clear"></div>
                    	<div id="result"></div>
                    </div>
                <?php }
                else if($tab == $this->start_action_key){
                    $options = get_option('woocommerce_reviso_general_settings');
                    ?>
                    <div class="wrap">
                        <?php $this->plugin_options_tabs(); ?>
                        <ul>
                        	<li>
                        		<?php echo '<img src="' . plugins_url( 'img/banner-772x250.png', __FILE__ ) . '" > '; ?>
                        	</li>
                            <li class="col-twothird">
                                <iframe src="//player.vimeo.com/video/38627647" width="500" height="281" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>
                            </li>
                            <?php if(!isset($options['license-key']) || $options['license-key'] == ''){ ?>
                            <li class="col-onethird">
                            	<div class="mailsupport">
                            		<h2><?php echo __('Installation Support', 'wooreviso'); ?></h2>
                            	    <form method="post" id="installationSupport">
                            	        <input type="hidden" value="send_support_mail" name="action">
                            	        <table class="form-table">
								
                            	            <tbody>
                            	            <tr valign="top">
                            	                <td>
                            	                    <input type="text" value="" placeholder="<?php echo __('Company', 'wooreviso'); ?>" name="company">
                            	                </td>
                            	            </tr>
                            	            <tr valign="top">
                            	                <td>
                            	                    <input type="text" value="" placeholder="<?php echo __('Name', 'wooreviso'); ?>" name="name">
                            	                </td>
                            	            </tr>
                            	            <tr valign="top">
                            	                <td>
                            	                    <input type="text" value="" placeholder="<?php echo __('Phone', 'wooreviso'); ?>" name="telephone">
                            	                </td>
                            	            </tr>
                            	            <tr valign="top">
                            	                <td>
                            	                    <input type="text" value="" placeholder="<?php echo __('Email', 'wooreviso'); ?>" name="email">
                            	                </td>
                            	            </tr>
                            	            <tr valign="top">
                            	                <td>
                            	                    <textarea placeholder="<?php echo __('Subject', 'wooreviso'); ?>" name="subject"></textarea>
                            	                </td>
                            	            </tr>
                            	            <tr valign="top">
                            	                <td>
                            	                    <button type="button" class="button button-primary" title="send_support_mail" style="margin:5px" onclick="send_support_mail('installationSupport')"><?php echo __('Send', 'wooreviso'); ?></button>
                            	                </td>
                            	            </tr>
                            	            </tbody>
                            	        </table>
                            	        <!-- p class="submit">
                            	           <button type="button" class="button button-primary" title="send_support_mail" style="margin:5px" onclick="send_support_mail()">Skicka</button> 
                            	        </p -->
                            	    </form>
                            	</div>
                            </li>
                        <?php } else{ ?>
                        	<li class="col-onethird">
                            	<div class="mailsupport">
                            		<h2><?php echo __('Support', 'wooreviso'); ?></h2>
                            	    <form method="post" id="support">
                            	        <input type="hidden" value="send_support_mail" name="action">
                            	        <table class="form-table">
								
                            	            <tbody>
                            	            <tr valign="top">
                            	                <td>
                            	                    <input type="text" value="" placeholder="<?php echo __('Company', 'wooreviso'); ?>" name="company">
                            	                </td>
                            	            </tr>
                            	            <tr valign="top">
                            	                <td>
                            	                    <input type="text" value="" placeholder="<?php echo __('Name', 'wooreviso'); ?>" name="name">
                            	                </td>
                            	            </tr>
                            	            <tr valign="top">
                            	                <td>
                            	                    <input type="text" value="" placeholder="<?php echo __('Phone', 'wooreviso'); ?>" name="telephone">
                            	                </td>
                            	            </tr>
                            	            <tr valign="top">
                            	                <td>
                            	                    <input type="text" value="" placeholder="<?php echo __('Email', 'wooreviso'); ?>" name="email">
                            	                </td>
                            	            </tr>
                            	            <tr valign="top">
                            	                <td>
                            	                    <textarea placeholder="<?php echo __('Subject', 'wooreviso'); ?>" name="subject"></textarea>
                            	                </td>
                            	            </tr>
                            	            <tr valign="top">
                            	                <td>
                                                	<input type="hidden" name="supportForm" value="support" />
                            	                    <button type="button" class="button button-primary" title="send_support_mail" style="margin:5px" onclick="send_support_mail('support')"><?php echo __('Send', 'wooreviso'); ?></button>
                            	                </td>
                            	            </tr>
                            	            </tbody>
                            	        </table>
                            	        <!-- p class="submit">
                            	           <button type="button" class="button button-primary" title="send_support_mail" style="margin:5px" onclick="send_support_mail()">Skicka</button> 
                            	        </p -->
                            	    </form>
                            	</div>
                            </li>
                        <?php } ?>
                        </ul>
                    </div>
                <?php }
                else{ ?>
                    <div class="wrap">
                        <?php $this->plugin_options_tabs(); ?>
                        <form method="post" action="options.php">
                            <?php wp_nonce_field( 'update-options' ); ?>
                            <?php settings_fields( $tab ); ?>
                            <?php do_settings_sections( $tab ); ?>
                            <?php submit_button(); ?>
                        </form>
                    </div>
                <?php }
            }	

           

            /***********************************************************************************************************
             * WP-PLUGS API FUNCTIONS
             ***********************************************************************************************************/

            /**
             * Checks if license-key is valid
             *
             * @access public
             * @return void
             */
            public function reviso_is_license_key_valid() {
                include_once("class-reviso-api.php");
                $wcr_api = new WCR_API();
                $result = $wcr_api->wooreviso_create_license_validation_request();
                switch ($result['status']) {
		            case "Active":
		                // get new local key and save it somewhere
		                $localkeydata = $result['localkey'];
		                update_option( 'local_key_reviso_plugin', $localkeydata );
		                return $result['status'];
		                break;
		            case "Invalid":
		                reviso_logthis("License key is Invalid");
		            	return $result['status'];
		                break;
		            case "Expired":
		                reviso_logthis("License key is Expired");
                        return $result['status'];
		                break;
		            case "Suspended":
		                reviso_logthis("License key is Suspended");
		                return $result['status'];
		                break;
		            default:
                        reviso_logthis("Invalid Response");
		                break;
	        	}
            }
        }
        $GLOBALS['WC_Reviso'] = new WC_Reviso();
    }
}
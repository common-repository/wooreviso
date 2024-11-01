<?php
//php.ini overriding necessary for communicating with the SOAP server.
//ini_set('display_errors',1);
//ini_set('display_startup_errors',1);
//error_reporting(1);
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
ini_set("default_socket_timeout", 6000);
class WCR_API{

    /** @public String base URL */
    public $api_url;
	
	/** @public String license key */
    public $license_key;
    

    /** @public String access ID or token */
    public $token;
	
	/** @public String private access ID or appToken */
    public $appToken;
	
	/** @public String local key data */
    public $localkeydata;
	
	/** @public Number corresponding the product group */
    public $product_group;
	
	/** @public Number corresponding the shipping group */
	public $shipping_group;
	
	/** @public Number corresponding the coupon group */
	public $coupon_group;	
	
	/** @public alphanumber corresponding the product offset */
    public $product_offset;
	
	/** @public Number corresponding the customer group */
    public $customer_group;
	
	/** @public string yes/no */
    public $activate_oldordersync;
	
	public $product_sync;
	
	/** @public alphanumber corresponding the order referernce offset */
    public $order_reference_prefix;
	
	
	/** @public array including all the customer meta fiedls that are snyned */
	public $user_fields = array(
	  'billing_phone',
	  'billing_email',
	  'billing_country',
	  'billing_address_1',
	  //'billing_address_2',
	  //'billing_state',
	  'billing_postcode',
	  'billing_city',
	  'billing_country',
	  'billing_company',
	  'billing_last_name',
	  'billing_first_name',
	  'billing_ean_number',
	  'vat_number',
	
	  'shipping_phone',
	  'shipping_email',
	  'shipping_country',
	  'shipping_address_1',
	  //'shipping_address_2',
	  //'shipping_state',
	  'shipping_postcode',
	  'shipping_city',
	  'shipping_country',
	  'shipping_company',
	  'shipping_last_name',
	  'shipping_first_name'
	);
	
	public $eu = array(
		'BE' => 'Belgium',
		'BG' => 'Bulgaria',
		'CZ' => 'Czech Republic',
		'DK' => 'Denmark',
		'GE' => 'Germany',
		'EE' => 'Estonia',
		'IE' => 'Republic of Ireland',
		'EL' => 'Greece',
		'ES' => 'Spain',
		'FR' => 'France',
		'HR' => 'Croatia',
		'IT' => 'Italy',
		'CY' => 'Cyprus',
		'LV' => 'Latvia',
		'LT' => 'Lithuania',
		'LU' => 'Luxembourg',
		'HU' => 'Hungary',
		'MT' => 'Malta',
		'NL' => 'Netherlands',
		'AT' => 'Austria',
		'PL' => 'Poland',
		'PT' => 'Portugal',
		'RO' => 'Romania',
		'SI' => 'Slovenia',
		'SK' => 'Slovakia',
		'FI' => 'Finland',
		'SE' => 'Sweden',
		'GB' => 'United Kingdom'
	  );
	
	public $product_lock;
	
	//public $shipping_product_id;

    /**
     *
     */
    function __construct() {

        $options = get_option('woocommerce_reviso_general_settings');
        $this->localkeydata = get_option('local_key_reviso_plugin');
        $this->api_url = dirname(__FILE__)."/RevisoWebservice.asmx.xml";
		//$this->api_url = 'https://soap.reviso.com/api1/?wsdl';
        $this->license_key = $options['license-key'];
		$this->token = $options['token'];
		$this->appToken = 'HDxdX8cT9xPWx-n_pq9yqIth8VgKv0-fEQ5CL4BThsM1';
		$this->product_sync = isset($options['product-sync'])? $options['product-sync'] : '';
		$this->other_checkout = isset($options['other-checkout'])? $options['other-checkout'] : '';
		$this->wooreviso_checkout = isset($options['reviso-checkout'])? $options['reviso-checkout'] : '';
		$this->activate_oldordersync = isset($options['activate-oldordersync'])? $options['activate-oldordersync'] : '';
		$this->product_sync = isset($options['product-sync'])? $options['product-sync'] : '';
		$this->product_group = isset($options['product-group'])? $options['product-group']: '';
		$this->product_offset = isset($options['product-prefix'])? $options['product-prefix']: '';
		$this->customer_group = isset($options['customer-group'])? $options['customer-group']: '';
		$this->shipping_group = isset($options['shipping-group'])? $options['shipping-group']: '';
		$this->coupon_group = isset($options['coupon-group'])? $options['coupon-group']: '';
		$this->order_reference_prefix = isset($options['order-reference-prefix'])? $options['order-reference-prefix'] : '';
		$this->product_lock = false;
    }

    /**
     * Create Connection to Reviso
     *
     * @access public
     * @return object
     */
    public function wooreviso_client(){
	
	  $client = new SoapClient($this->api_url, array("trace" => 1, "exceptions" => 1));
	
	  //reviso_logthis("wooreviso_client loaded token: " . $this->token . " appToken: " . $this->appToken);
	  if (!$this->token || !$this->appToken)
		die("Reviso Access Token not defined!");
		
	  //reviso_logthis("wooreviso_client - options are OK!");
	  //reviso_logthis("wooreviso_client - creating client...");
	  	  
	  try{
		 $client->ConnectWithToken(array(
			'token' 	=> $this->token,
			'appToken'  => $this->appToken));
	  }
	  catch (Exception $exception){
		reviso_logthis("Connection to client failed: " . $exception->getMessage());
		$this->debug_client($client);
		return false;
	  }
	  
	  reviso_logthis("wooreviso_client - client created");
	  return $client;
	}
	
	/**
     * Log the client connection request headers for debugging
     *
     * @access public
     * @return void
     */
	public function debug_client($client){
	  if (is_null($client)) {
		reviso_logthis("Client is null");
	  } else {
		reviso_logthis("-----LastRequestHeaders-------");
		reviso_logthis($client->__getLastRequestHeaders());
		reviso_logthis("------LastRequest------");
		reviso_logthis($client->__getLastRequest());
		reviso_logthis("------LastResponse------");
		reviso_logthis($client->__getLastResponse());
		reviso_logthis("------Debugging ends------");
	  }
	}

    /**
     * Creates a Reviso HttpRequest
     *
     * @access public
     * @return bool
     */
    public function wooreviso_create_API_validation_request(){
        reviso_logthis("API VALIDATION");
        if(!isset($this->license_key)){
			reviso_logthis("API VALIDATION FAILED: license key not set!");
            return false;
        }
		
		if($this->wooreviso_client()){
			return true;
		}
		else{
			reviso_logthis("API VALIDATION FAILED: client not connected!");
			return false;
		}
    }

    /**
     * Creates a HttpRequest and appends the given XML to the request and sends it For license key
     *
     * @access public
     * @return bool
     */
    public function wooreviso_create_license_validation_request($localkey=''){
        reviso_logthis("LICENSE VALIDATION");
        if(!isset($this->license_key)){
            return false;
        }
        $licensekey = $this->license_key;
        // -----------------------------------
        //  -- Configuration Values --
        // -----------------------------------
        // Enter the url to your WHMCS installation here
        //$whmcsurl = 'http://176.10.250.47/whmcs/'; $whmcsurlsock = '176.10.250.47/whmcs';
        $whmcsurl = 'http://whmcs.onlineforce.net/'; $whmcsurlsock = 'whmcs.onlineforce.net';
        // Must match what is specified in the MD5 Hash Verification field
        // of the licensing product that will be used with this check.
        //$licensing_secret_key = 'itservice';
		$licensing_secret_key = 'ak4762';
        // The number of days to wait between performing remote license checks
        $localkeydays = 15;
        // The number of days to allow failover for after local key expiry
        $allowcheckfaildays = 5;

        // -----------------------------------
        //  -- Do not edit below this line --
        // -----------------------------------

        $check_token = time() . md5(mt_rand(1000000000, 9999999999) . $licensekey);
        $checkdate = date("Ymd");
        $domain = $_SERVER['SERVER_NAME'];
		$host= gethostname();
		//$usersip = gethostbyname($host);
        $usersip = gethostbyname($host) ? gethostbyname($host) : $_SERVER['SERVER_ADDR'];
        //$usersip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR'];
        $dirpath = dirname(__FILE__);
        $verifyfilepath = 'modules/servers/licensing/verify.php';
        $localkeyvalid = false;
        if ($localkey) {
            $localkey = str_replace("\n", '', $localkey); # Remove the line breaks
            $localdata = substr($localkey, 0, strlen($localkey) - 32); # Extract License Data
            $md5hash = substr($localkey, strlen($localkey) - 32); # Extract MD5 Hash
            if ($md5hash == md5($localdata . $licensing_secret_key)) {
                $localdata = strrev($localdata); # Reverse the string
                $md5hash = substr($localdata, 0, 32); # Extract MD5 Hash
                $localdata = substr($localdata, 32); # Extract License Data
                $localdata = base64_decode($localdata);
                $localkeyresults = unserialize($localdata);
                $originalcheckdate = $localkeyresults['checkdate'];
                if ($md5hash == md5($originalcheckdate . $licensing_secret_key)) {
                    $localexpiry = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - $localkeydays, date("Y")));
                    if ($originalcheckdate > $localexpiry) {
                        $localkeyvalid = true;
                        $results = $localkeyresults;
                        $validdomains = explode(',', $results['validdomain']);
                        if (!in_array($_SERVER['SERVER_NAME'], $validdomains)) {
                            $localkeyvalid = false;
                            $localkeyresults['status'] = "Invalid";
                            $results = array();
                        }
                        $validips = explode(',', $results['validip']);
                        if (!in_array($usersip, $validips)) {
                            $localkeyvalid = false;
                            $localkeyresults['status'] = "Invalid";
                            $results = array();
                        }
                        $validdirs = explode(',', $results['validdirectory']);
                        if (!in_array($dirpath, $validdirs)) {
                            $localkeyvalid = false;
                            $localkeyresults['status'] = "Invalid";
                            $results = array();
                        }
                    }
                }
            }
        }
        if (!$localkeyvalid) {
            $postfields = array(
                'licensekey' => $licensekey,
                'domain' => $domain,
                'ip' => $usersip,
                'dir' => $dirpath,
            );
            if ($check_token) $postfields['check_token'] = $check_token;
            $query_string = '';
            foreach ($postfields AS $k=>$v) {
                $query_string .= $k.'='.urlencode($v).'&';
            }
            if (function_exists('curl_exec')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $whmcsurl . $verifyfilepath);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $data = curl_exec($ch);
                curl_close($ch);
            } else {
                $fp = fsockopen($whmcsurlsock, 80, $errno, $errstr, 5);
				//reviso_logthis($errstr.':'.$errno);
                if ($fp) {
                    $newlinefeed = "\r\n";
                    $header = "POST ".$whmcsurl . $verifyfilepath . " HTTP/1.0" . $newlinefeed;
                    $header .= "Host: ".$whmcsurl . $newlinefeed;
                    $header .= "Content-type: application/x-www-form-urlencoded" . $newlinefeed;
                    $header .= "Content-length: ".@strlen($query_string) . $newlinefeed;
                    $header .= "Connection: close" . $newlinefeed . $newlinefeed;
                    $header .= $query_string;
                    $data = '';
                    @stream_set_timeout($fp, 20);
                    @fputs($fp, $header);
                    $status = @socket_get_status($fp);
                    while (!@feof($fp)&&$status) {
                        $data .= @fgets($fp, 1024);
                        $status = @socket_get_status($fp);
                    }
                    @fclose ($fp);
                }
            }
            if (!$data) {
                $localexpiry = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - ($localkeydays + $allowcheckfaildays), date("Y")));
                if ($originalcheckdate > $localexpiry) {
                    $results = $localkeyresults;
                } else {
                    $results = array();
                    $results['status'] = "Invalid";
                    $results['description'] = "Remote Check Failed";
                    return $results;
                }
            } else {
                preg_match_all('/<(.*?)>([^<]+)<\/\\1>/i', $data, $matches);
                $results = array();
                foreach ($matches[1] AS $k=>$v) {
                    $results[$v] = $matches[2][$k];
                }
            }
            if (!is_array($results)) {
                die("Invalid License Server Response");
            }
            if (isset($results['md5hash'])) {
                if ($results['md5hash'] != md5($licensing_secret_key . $check_token)) {
                    $results['status'] = "Invalid";
                    $results['description'] = "MD5 Checksum Verification Failed";
                    return $results;
                }
            }
            if ($results['status'] == "Active") {
                $results['checkdate'] = $checkdate;
                $data_encoded = serialize($results);
                $data_encoded = base64_encode($data_encoded);
                $data_encoded = md5($checkdate . $licensing_secret_key) . $data_encoded;
                $data_encoded = strrev($data_encoded);
                $data_encoded = $data_encoded . md5($data_encoded . $licensing_secret_key);
                $data_encoded = wordwrap($data_encoded, 80, "\n", true);
                $results['localkey'] = $data_encoded;
            }
            $results['remotecheck'] = true;
        }
        unset($postfields,$data,$matches,$whmcsurl,$licensing_secret_key,$checkdate,$usersip,$localkeydays,$allowcheckfaildays,$md5hash);
        return $results;
        //return true;
    }
	
	
	/**
     * Get product SKU, concatenate product offset if not synced from Reviso.
     *
     * @access public
     * @param product oject
     * @return product SKU string.
     */
	
	
	public function wooreviso_get_product_sku(WC_Product $product){ //todo fix this function.
	  $synced_from_reviso = get_post_meta($product->id, 'synced_from_reviso', true);
	  $product_sku = null;
	  if (isset($synced_from_reviso) && $synced_from_reviso) {
		$product_sku = $product->sku;
	  } else {
		$product_sku = $this->product_offset.$product->sku;
	  }
	  return $product_sku;
	}
	
	/**
     * Get product SKU from Reviso
     *
     * @access public
     * @param product oject
     * @return product SKU string.
     */
		
	public function wooreviso_get_product_sku_from_reviso($product_id){
		$product_offset = $this->product_offset;
		if (strpos($product_id, $this->product_offset) === false) // this is an woocommerce product
			return $this->product_offset.$product->sku;
		else
			return $product_id;
	}
	
	
	/**
     * Save WooCommerce Order to reviso
     *
     * @access public
     * @param  Soap client object, user object or NULL, order object or NULL and refund flag.
     * @return bool
     */
	public function save_invoice_to_reviso(SoapClient &$client, WP_User $user = NULL, WC_Order $order = NULL, $refund = NULL){
		global $wpdb;
		$draft_invoice_synced = false;
		try{
			
			$is_synced = $this->wooreviso_is_order_synced_already($client, $order);
			
			if ($is_synced['synced'] === true && $is_synced['type'] === 'order' ) {
				$order_handle = $this->save_order_to_reviso($client, $user, $order, $refund);
				
				if($order_handle !== true || $order_handle !== false){
					$current_invoice_handle = $client->Order_UpgradeToInvoice(array(
						'orderHandle' => $order_handle
					))->Order_UpgradeToInvoiceResult;
				}
				$draft_invoice_synced = true;
			}
			
			if ($is_synced['synced'] === true && $is_synced['type'] === 'invoice' ) {
				reviso_logthis("save_invoice_to_reviso: Current invoice already sent as Invoice.");
				reviso_logthis($is_synced);
				$draft_invoice_synced = true;
			}
			
			if (($is_synced['synced'] === true && $is_synced['type'] === 'current_invoice') || $is_synced['synced'] === false ) {
				reviso_logthis("save_invoice_to_reviso Getting debtor handle");
				$debtor_handle = $this->wooreviso_get_debtor_handle_from_reviso($client, $user, $order);
				if (!($debtor_handle)) {
					reviso_logthis("save_invoice_to_reviso debtor not found, can not create invoice");
					return false;
				}
				
				if($is_synced['type'] === 'current_invoice'){
					$current_invoice_handle = $is_synced['handle'];
				}else{
					$current_invoice_handle = $this->wooreviso_get_current_invoice_from_reviso($client, $this->order_reference_prefix.$order->id, $debtor_handle);
				}
				
				reviso_logthis("save_invoice_to_reviso reviso_get_current_invoice_from_reviso returned current invoice handle.");
				reviso_logthis($current_invoice_handle);
				
				$countries = new WC_Countries();			
				
				$formatted_state = $countries->states[$order->billing_country][$order->billing_state];
				$address = trim($order->billing_address_1 . "\n" . $order->billing_address_2 . "\n" . $formatted_state);
				$city = $order->billing_city;
				$postalcode = $order->billing_postcode;
				$country = $countries->countries[$order->billing_country];
				
				reviso_logthis("save_invoice_to_reviso CurrentInvoice_SetDebtor.");
				$debtorName = $order->billing_company != ''? $order->billing_company : $order->billing_first_name.' '.$order->billing_last_name;
				$client->CurrentInvoice_SetDebtor(array(
					'currentInvoiceHandle' => $current_invoice_handle,
					'valueHandle' => $debtor_handle
				));
				
				reviso_logthis("save_invoice_to_reviso CurrentInvoice_SetDebtorName.");
				$client->CurrentInvoice_SetDebtorName(array(
					'currentInvoiceHandle' => $current_invoice_handle,
					'value' => $debtorName
				));
				
				reviso_logthis("save_invoice_to_reviso CurrentInvoice_SetDebtorAddress.");
				$client->CurrentInvoice_SetDebtorAddress(array(
					'currentInvoiceHandle' => $current_invoice_handle,
					'value' => $address
				));
				
				reviso_logthis("save_invoice_to_reviso CurrentInvoice_SetDebtorCity.");
				$client->CurrentInvoice_SetDebtorCity(array(
					'currentInvoiceHandle' => $current_invoice_handle,
					'value' => $city
				));
				
				reviso_logthis("save_invoice_to_reviso CurrentInvoice_SetDebtorCountry.");
				$client->CurrentInvoice_SetDebtorCountry(array(
					'currentInvoiceHandle' => $current_invoice_handle,
					'value' => $country
				));
				
				reviso_logthis("save_invoice_to_reviso CurrentInvoice_SetDebtorPostalCode.");
				$client->CurrentInvoice_SetDebtorPostalCode(array(
					'currentInvoiceHandle' => $current_invoice_handle,
					'value' => $postalcode
				));
				
				
				$formatted_state = $countries->states[$order->shipping_country][$order->shipping_state];
				$address = trim($order->shipping_address_1 . "\n" . $order->shipping_address_2 . "\n" . $formatted_state);
				$city = $order->shipping_city;
				$postalcode = $order->shipping_postcode;
				$country = $countries->countries[$order->shipping_country];
				
				reviso_logthis("save_invoice_to_reviso CurrentInvoice_SetDeliveryAddress.");
				$client->CurrentInvoice_SetDeliveryAddress(array(
					'currentInvoiceHandle' => $current_invoice_handle,
					'value' => $address
				));
				
				reviso_logthis("save_invoice_to_reviso CurrentInvoice_SetDeliveryCity.");
				$client->CurrentInvoice_SetDeliveryCity(array(
					'currentInvoiceHandle' => $current_invoice_handle,
					'value' => $city
				));
				
				reviso_logthis("save_invoice_to_reviso CurrentInvoice_SetDeliveryPostalCode.");
				$client->CurrentInvoice_SetDeliveryPostalCode(array(
					'currentInvoiceHandle' => $current_invoice_handle,
					'value' => $postalcode
				));
				
				reviso_logthis("save_invoice_to_reviso CurrentInvoice_SetDeliveryCountry.");
				$client->CurrentInvoice_SetDeliveryCountry(array(
					'currentInvoiceHandle' => $current_invoice_handle,
					'value' => $country
				));
				
				reviso_logthis("save_invoice_to_reviso CurrentInvoice_SetCurrency.");
				$client->CurrentInvoice_SetCurrency(array(
					'currentInvoiceHandle' => $current_invoice_handle,
					'valueHandle' => array('Code' => get_option('woocommerce_currency'))
				));
				
				//Added for version 1.9.9.8
				//Set the order date
				$date = new DateTime($order->order_date);
				$client->CurrentInvoice_SetDate(array(
					'currentInvoiceHandle' => $current_invoice_handle,
					'value' => $date->format('c')
				));
				
				$currentInvoiceLines = $client->CurrentInvoice_GetLines(array(
					'currentInvoiceHandle' => $current_invoice_handle,
				))->CurrentInvoice_GetLinesResult;
				
				if(isset($currentInvoiceLines->CurrentInvoiceLineHandle)){
					if(is_array($currentInvoiceLines->CurrentInvoiceLineHandle)){
						foreach($currentInvoiceLines->CurrentInvoiceLineHandle as $currentInvoiceLine){
							$client->CurrentInvoiceLine_Delete(array(
								'currentInvoiceLineHandle' => $currentInvoiceLine,
							));
						}
					}else{
						$client->CurrentInvoiceLine_Delete(array(
							'currentInvoiceLineHandle' => $currentInvoiceLines->CurrentInvoiceLineHandle,
						));
					}
				}
				
				reviso_logthis("save_invoice_to_reviso call reviso_handle_invoice_lines_to_reviso.");			
				$this->wooreviso_handle_invoice_lines_to_reviso($order, $current_invoice_handle, $client, $refund);
				$draft_invoice_synced = true;
			}
			
			if($draft_invoice_synced === true){
				if($wpdb->query ("SELECT * FROM wcr_orders WHERE order_id=".$order->id.";")){
					$wpdb->update ("wcr_orders", array('synced' => 1), array('order_id' => $order->id), array('%d'), array('%d'));
				}else{
					$wpdb->insert ("wcr_orders", array('order_id' => $order->id, 'synced' => 1), array('%d', '%d'));
				}
				return true;
			}else{
				if($wpdb->query ("SELECT * FROM wcr_orders WHERE order_id=".$order->id.";")){
					$wpdb->update ("wcr_orders", array('synced' => 0), array('order_id' => $order->id), array('%d'), array('%d'));
				}else{
					$wpdb->insert ("wcr_orders", array('order_id' => $order->id, 'synced' => 0), array('%d', '%d'));
				}
				return false;
			}
		}catch (Exception $exception) {
			reviso_logthis("save_invoice_to_reviso could not save order: " . $exception->getMessage());
			$this->debug_client($client);
			reviso_logthis('Could not create invoice.');
			reviso_logthis($exception->getMessage());
			if($wpdb->query ("SELECT * FROM wcr_orders WHERE order_id=".$order->id." AND synced=0;")){
				return false;
			}else{
				$wpdb->insert ("wcr_orders", array('order_id' => $order->id, 'synced' => 0), array('%d', '%d'));
				return false;
			}
		}
	}
	
	/**
     * Get current invoice from reviso
     *
     * @access public
     * @param User object, SOAP client
     * @return current invoice handle object
     */	
	public function wooreviso_get_current_invoice_from_reviso(SoapClient &$client, $reference, &$debtor_handle){
		$current_invoice_handle = $client->CurrentInvoice_FindByOtherReference(array(
			'otherReference' => $reference
		))->CurrentInvoice_FindByOtherReferenceResult;
		
		$current_invoice_handle = $current_invoice_handle->CurrentInvoiceHandle;
		
		$current_invoice_handle_array = (array) $current_invoice_handle;
		
		if (empty($current_invoice_handle_array)) {
			reviso_logthis("wooreviso_get_current_invoice_from_reviso create CurrentInvoiceHandle.");
			$current_invoice_handle = $client->CurrentInvoice_Create(array(
				'debtorHandle' => $debtor_handle
			))->CurrentInvoice_CreateResult;
			
			$client->CurrentInvoice_SetOtherReference(array(
				'currentInvoiceHandle' => $current_invoice_handle,
				'value' => $reference
			));
		}
		//reviso_logthis("current_invoice_handle: ".$current_invoice_handle);
		reviso_logthis("wooreviso_get_current_invoice_from_reviso invoice handle found and ID is: ");
		reviso_logthis($current_invoice_handle);
		return $current_invoice_handle;
	}
	
	
	/**
     * Get order lines handle
     *
     * @access public
     * @param Order object, Invoice handle object, SOAP client, refund bool
     * @return debtor_handle object
     */	
	public function wooreviso_handle_invoice_lines_to_reviso(WC_Order $order, $current_invoice_handle, SoapClient &$client, $refund){
	  reviso_logthis("wooreviso_handle_invoice_lines_to_reviso - get all lines");
	
	  foreach ($order->get_items() as $item) {
		$product = $order->get_product_from_item($item);
		if(isset($product) && !empty($product)){
			$current_invoice_line_handle = null;
			$current_invoice_line_handle = $this->wooreviso_create_currentinvoice_orderline_at_reviso($current_invoice_handle, $this->wooreviso_get_product_sku($product), $client);
		
			reviso_logthis("wooreviso_handle_invoice_lines_to_reviso updating qty on id: " . $current_invoice_line_handle->Id . " number: " . $current_invoice_line_handle->Number);
			$quantity = ($refund) ? $item['qty'] * -1 : $item['qty'];
			$client->CurrentInvoiceLine_SetQuantity(array(
			  'currentInvoiceLineHandle' => $current_invoice_line_handle,
			  'value' => $quantity
			));
			
			$client->CurrentInvoiceLine_SetUnitNetPrice(array(
			  'currentInvoiceLineHandle' => $current_invoice_line_handle,
			  'value' => $item['line_subtotal']/$item['qty']
			));
			
			$discount = $item['line_subtotal']-$item['line_total'];
			if($discount != 0){
				$discount = ($discount*100)/$item['line_subtotal'];
				$client->CurrentInvoiceLine_SetDiscountAsPercent(array(
				  'currentInvoiceLineHandle' => $current_invoice_line_handle,
				  'value' => $discount
				));
			}
			
			reviso_logthis("wooreviso_handle_invoice_lines_to_reviso updated line");
		}
	  }
	  
	    $shippingItem = reset($order->get_items('shipping'));
		//reviso_logthis($shippingItem['method_id']);
		if(isset($shippingItem['method_id'])){
			reviso_logthis("wooreviso_handle_invoice_lines_to_reviso adding Shipping line");
			if(strlen($shippingItem['method_id']) > 25){
				$shippingID = substr($shippingItem['method_id'], 0, 24);
			}else{
				$shippingID = $shippingItem['method_id'];
			}
			$current_invoice_line_handle = null;
			$current_invoice_line_handle = $this->wooreviso_create_currentinvoice_orderline_at_reviso($current_invoice_handle, $shippingID, $client);
			reviso_logthis("wooreviso_handle_invoice_lines_to_reviso updating qty on id: " . $current_invoice_line_handle->Id . " number: " . $current_invoice_line_handle->Number);
			$quantity = ($refund) ? $item['qty'] * -1 : 1;
			$client->CurrentInvoiceLine_SetQuantity(array(
				'currentInvoiceLineHandle' => $current_invoice_line_handle,
				'value' => $quantity
			));
			$client->CurrentInvoiceLine_SetUnitNetPrice(array(
				'currentInvoiceLineHandle' => $current_invoice_line_handle,
				'value' => $shippingItem['cost']
			));
			reviso_logthis("wooreviso_handle_invoice_lines_to_reviso updated shipping line");
		}
		
		//reviso_logthis('coupon');
		//reviso_logthis($coupon);
		$coupon = reset($order->get_items('coupon'));
		if(isset($coupon['name'])){
			reviso_logthis("wooreviso_handle_invoice_lines_to_reviso adding Coupon line");
			if(strlen($coupon['name']) > 25){
				$couponID = substr($coupon['name'], 0, 24);
			}else{
				$couponID = $coupon['name'];
			}
			$current_invoice_line_handle = null;
			$current_invoice_line_handle = $this->wooreviso_create_currentinvoice_orderline_at_reviso($current_invoice_handle, $couponID , $client);
			reviso_logthis("wooreviso_handle_invoice_lines_to_reviso updating qty on id: " . $current_invoice_line_handle->Id . " number: " . $current_invoice_line_handle->Number);
			$quantity = ($refund) ? -1 : 1;
			$client->CurrentInvoiceLine_SetQuantity(array(
				'currentInvoiceLineHandle' => $current_invoice_line_handle,
				'value' => $quantity
			));
			/*$client->CurrentInvoiceLine_SetUnitNetPrice(array(
				'currentInvoiceLineHandle' => $current_invoice_line_handle,
				'value' => -$coupon['discount_amount']
			));*/
			reviso_logthis("wooreviso_handle_invoice_lines_to_reviso updated coupon line");
		}
	}
	
	
	/**
     * Get invoice lines to Reviso 
     *
     * @access public
     * @param 
     * @return current invoice line created for the order line.
     */
	public function wooreviso_create_currentinvoice_orderline_at_reviso($current_invoice_handle, $product_id, SoapClient &$client){
		$current_invoice_line_handle = $client->CurrentInvoiceLine_Create(array(
			'invoiceHandle' => $current_invoice_handle
		))->CurrentInvoiceLine_CreateResult;
		reviso_logthis("wooreviso_create_currentinvoice_orderline_at_reviso added line id: " . $current_invoice_line_handle->Id . " number: " . $current_invoice_line_handle->Number . " product_id: " . $product_id);
		$product_handle = $client->Product_FindByNumber(array(
			'number' => $product_id
		))->Product_FindByNumberResult;
		$client->CurrentInvoiceLine_SetProduct(array(
			'currentInvoiceLineHandle' => $current_invoice_line_handle,
			'valueHandle' => $product_handle
		));
		$product = $client->Product_GetData(array(
			'entityHandle' => $product_handle
		))->Product_GetDataResult;
		$client->CurrentInvoiceLine_SetDescription(array(
			'currentInvoiceLineHandle' => $current_invoice_line_handle,
			'value' => $product->Name
		));
		$client->CurrentInvoiceLine_SetUnitNetPrice(array(
			'currentInvoiceLineHandle' => $current_invoice_line_handle,
			'value' => $product->SalesPrice
		));
		
		reviso_logthis("wooreviso_create_currentinvoice_orderline_at_reviso added product to line ");
		return $current_invoice_line_handle;
	}

	
	/**
     * Get debtor handle from reviso
     *
     * @access public
     * @param User object, SOAP client
     * @return debtor_handle object
     */
	public function wooreviso_get_debtor_handle_from_reviso(SoapClient &$client, WP_User $user = NULL, WC_Order $order = NULL){
		try {
			if(is_object($user)){
				$debtorNumber = $user->get('debtor_number');
				$debtor_handle = NULL;
				reviso_logthis("wooreviso_get_debtor_handle_from_reviso trying to load : " . $debtorNumber);
				if (!isset($debtorNumber) || empty($debtorNumber)) {
					reviso_logthis("wooreviso_get_debtor_handle_from_reviso no handle found");
					$debtor_handle = array();
				}else{
					$debtor_handle = $client->Debtor_FindByNumber(array(
						'number' => $debtorNumber
					))->Debtor_FindByNumberResult;
				}
			}else{
				if(is_object($order) && $order->billing_email != ''){
					reviso_logthis("wooreviso_get_debtor_handle_from_reviso user not defined, guest user suspected, fetching debtorNumber by order email: ".$order->billing_email);
					$debtor_handles = $client->Debtor_FindByEmail(array(
						'email' => $order->billing_email
					))->Debtor_FindByEmailResult;
					$debtor_handle = $debtor_handles->DebtorHandle;
					reviso_logthis($debtor_handle);
				}
				else{
					reviso_logthis("wooreviso_get_debtor_handle_from_reviso user not defined, guest user suspected, fetching debtorNumber by email: ".$user);
					$debtor_handles = $client->Debtor_FindByEmail(array(
						'email' => $user
					))->Debtor_FindByEmailResult;
					$debtor_handle = $debtor_handles->DebtorHandle;
				}
			}	
			
			$debtor_handle_array = (array) $debtor_handle;	
			
			$tax_based_on = get_option('woocommerce_tax_based_on');
			$vatZone = 'HomeCountry';
			
			if($tax_based_on == 'billing'){
				$vatZone = $this->wooreviso_get_debtor_vat_zone('billing', $user, $order);
			}elseif($tax_based_on == 'shipping'){
				$vatZone = $this->wooreviso_get_debtor_vat_zone('shipping', $user, $order);
			}else{
				$vatZone = 'HomeCountry';
			}
			
			if (!empty($debtor_handle_array)) {
				reviso_logthis("wooreviso_get_debtor_handle_from_reviso debtor found for user.");
				//reviso_logthis($user != NULL? $user->ID : $order->billing_email);
				reviso_logthis($debtor_handle);
				if(is_array($debtor_handle)){
					$debtor_handle = $debtor_handle[0];
				}
				$client->Debtor_SetVatZone(array(
						//'number' => $user->ID,
						'debtorHandle' => $debtor_handle,
						'value' => $vatZone
					)
				);
			}
			else {
				// The debtor doesn't exist - lets create it
				reviso_logthis("wooreviso_get_debtor_handle_from_reviso debtor doesn't exit, creating debtor");
				$debtor_grouphandle_meta = $this->customer_group;
				reviso_logthis("wooreviso_get_debtor_handle_from_reviso debtor group: " . $debtor_grouphandle_meta);
				//reviso_logthis($user);
				
				if($user != NULL){	
					$billing_first_name = $user->get('billing_first_name');
					if(isset($billing_first_name) && $billing_first_name != ''){
						$debtor_name = $user->get('billing_first_name').' '.$user->get('billing_last_name');
					}else{
						$debtor_name = $user->get('billing_company');
					}
					
					if($debtor_name == NULL || empty($debtor_name)){
						return false;
					}
					
					reviso_logthis("wooreviso_get_debtor_handle_from_reviso name: " . $debtor_name);
					reviso_logthis("wooreviso_get_debtor_handle_from_reviso billing_comnpany: " . $billing_company);

					if(empty($debtor_name)){
						return false;
					}
				
					$debtor_grouphandle = $client->DebtorGroup_FindByNumber(array(
						'number' => $debtor_grouphandle_meta
					))->DebtorGroup_FindByNumberResult;					
					
					$debtor_handle = $client->Debtor_Create(array(
						//'number' => $user->ID,
						'debtorGroupHandle' => $debtor_grouphandle,
						'name' => $debtor_name,
						'vatZone' => $vatZone
					))->Debtor_CreateResult;
					
					update_user_meta($user->ID, 'debtor_number', $debtor_handle->Number);
					reviso_logthis("wooreviso_get_debtor_handle_from_reviso debtor created using user object: " . $name);
				}else{
					reviso_logthis("wooreviso_get_debtor_handle_from_reviso name: " . $order->billing_first_name. " " . $order->billing_last_name);
					reviso_logthis("wooreviso_get_debtor_handle_from_reviso billing_comnpany: " . $order->billing_company);
				
					$debtor_grouphandle = $client->DebtorGroup_FindByNumber(array(
						'number' => $debtor_grouphandle_meta
					))->DebtorGroup_FindByNumberResult;
				
					$debtor_number = mt_rand( 9999, 99999 );
					
					if(isset($order->billing_company) && $order->billing_company != ''){
						$debtor_name = $order->billing_company;
					}else{
						$debtor_name = $order->billing_first_name.' '.$order->billing_last_name;
					}
				
					$debtor_handle = $client->Debtor_Create(array(
						//'number' => $debtor_number,
						'debtorGroupHandle' => $debtor_grouphandle,
						'name' => $debtor_name,
						'vatZone' => $vatZone
					))->Debtor_CreateResult;
					
					update_user_meta($user->ID, 'debtor_number', $debtor_handle->Number);
					reviso_logthis("wooreviso_get_debtor_handle_from_reviso debtor created using order object: " . $order->billing_email);
				}
				//reviso_logthis("wooreviso_get_debtor_handle_from_reviso debtor created for user->id " . $user != NULL? $user->ID : $order->billing_email);
			}
			
			if(is_array($debtor_handle)){
				$debtor_handle = $debtor_handle[0];
			}	
			$client->Debtor_SetCurrency(array(
				'debtorHandle' => $debtor_handle,
				'valueHandle' => array('Code' => get_option('woocommerce_currency'))
			));		
			return $debtor_handle;
		}catch (Exception $exception) {
			reviso_logthis("wooreviso_get_debtor_handle_from_reviso could not get or create debtor handle: " . $exception->getMessage());
			return false;
		}
	}
	
	/**
     * Get debtor debtor vat Zone from WooCommerce user object or order object.
     *
     * @access public
     * @param Type of address billing or shipping, WP user object and WC order object
     * @return vatZone string.
     */
	public function wooreviso_get_debtor_vat_zone($type, WP_User $user = NULL, WC_Order $order = NULL){
		$default_country = get_option('woocommerce_default_country');
		$address = $type.'_country';
		reviso_logthis('reviso_get_debtor_vat_zone running...');
		//reviso_logthis($order->$address.' == '.$default_country);
		if(is_object($order)){
			if($order->$address == $default_country){
				return 'HomeCountry';
			}elseif(isset($this->eu[$order->$address])){
				return 'EU';
			}else{
				return 'Abroad';
			}
		}
		if(is_object($user)){
			$userCountry = get_user_meta($user->ID, $address, true);
			if($userCountry == $default_country){
				return 'HomeCountry';
			}elseif(isset($this->eu[$userCountry])){
				return 'EU';
			}else{
				return 'Abroad';
			}
		}
	}
	
	/**
     * Get debtor delivery locations handle from reviso
     *
     * @access public
     * @param User object, SOAP client
     * @return debtor_delivery_location_handles object
     */
	public function wooreviso_get_debtor_delivery_location_handles_from_reviso(SoapClient &$client, $debtor_handle){
		
		//$debtor_handle = $this->wooreviso_get_debtor_handle_from_reviso($user, $client);
		
		if (!isset($debtor_handle) || empty($debtor_handle)) {
			reviso_logthis("wooreviso_get_debtor_delivery_location_handles_from_reviso no handle found");
			return null;
		}
		
		reviso_logthis("wooreviso_get_debtor_delivery_location_handles_from_reviso getting delivery locations available for debtor debtor_delivery_location_handles");
		//reviso_logthis($debtor_handle);
		$debtor_delivery_location_handles = $client->Debtor_GetDeliveryLocations(array(
		'debtorHandle' => $debtor_handle
		))->Debtor_GetDeliveryLocationsResult;
		
		//reviso_logthis("debtor_delivery_location_handles");
		//reviso_logthis($debtor_delivery_location_handles);
		
		if (isset($debtor_delivery_location_handles->DeliveryLocationHandle->Id)){
			reviso_logthis("wooreviso_get_debtor_delivery_location_handles_from_reviso delivery location handle ID: ");
			reviso_logthis($debtor_delivery_location_handles->DeliveryLocationHandle->Id);
			return $debtor_delivery_location_handles->DeliveryLocationHandle;
		}
		else {
			$debtor_delivery_location_handle = $client->DeliveryLocation_Create(array(
			'debtorHandle' => $debtor_handle
			))->DeliveryLocation_CreateResult;
			reviso_logthis("wooreviso_get_debtor_delivery_location_handles_from_reviso delivery location handle: ");
			reviso_logthis($debtor_delivery_location_handle);
			return $debtor_delivery_location_handle;
		}
	}
	
	
	 /**
     * Save WooCommerce Order to Reviso
     *
     * @access public
     * @param product oject, user object, Soap client object, reference order ID and refund flag.
     * @return bool
     */
	public function save_order_to_reviso(SoapClient &$client, WP_User $user = NULL, WC_Order $order = NULL, $refund = NULL){
		global $wpdb;
		reviso_logthis("save_order_to_reviso Getting debtor handle");
		$debtor_handle = $this->wooreviso_get_debtor_handle_from_reviso($client, $user, $order);
		if (!($debtor_handle)) {
			reviso_logthis("save_order_to_reviso debtor not found, can not create order");
			return false;
		}
		try {
		
			$order_handle = $this->wooreviso_get_order_number_from_reviso($client, $order, $this->order_reference_prefix.$order->id, $debtor_handle);

			//$order_handle_array = (array) $order_handle;
			
			if($order_handle === true){
				reviso_logthis("save_order_to_reviso order is already synced to draft invoice or invoice.");
				return true;
			}
			
			if($order_handle === false){
				reviso_logthis("save_order_to_reviso order handle creation error.");
				return false;
			}

			
			$countries = new WC_Countries();
			
			/*$address = null;
			$city = null;
			$postalcode = null;
			$country = null;
			
			if (isset($order->shipping_address_1) || !empty($order->shipping_address_1)) {
				$formatted_state = $countries->states[$order->shipping_country][$order->shipping_state];
				$address = trim($order->shipping_address_1 . "\n" . $order->shipping_address_2 . "\n" . $formatted_state);
				$city = $order->shipping_city;
				$postalcode = $order->shipping_postcode;
				$country = $countries->countries[$order->shipping_country];
			} else {
				$formatted_state = $countries->states[$order->billing_country][$order->billing_state];
				$address = trim($order->billing_address_1 . "\n" . $order->billing_address_2 . "\n" . $formatted_state);
				$city = $order->billing_city;
				$postalcode = $order->billing_postcode;
				$country = $countries->countries[$order->billing_country];
			}*/
			
			$formatted_state = $countries->states[$order->billing_country][$order->billing_state];
			$address = trim($order->billing_address_1 . "\n" . $order->billing_address_2 . "\n" . $formatted_state);
			$city = $order->billing_city;
			$postalcode = $order->billing_postcode;
			$country = $countries->countries[$order->billing_country];
			
			reviso_logthis("save_order_to_reviso Order_SetDebtor.");
			$debtorName = $order->billing_company != ''? $order->billing_company : $order->billing_first_name.' '.$order->billing_last_name;
			$client->Order_SetDebtor(array(
				'orderHandle' => $order_handle,
				'valueHandle' => $debtor_handle
			));
			
			reviso_logthis("save_order_to_reviso Order_SetDebtorName.");
			$client->Order_SetDebtorName(array(
				'orderHandle' => $order_handle,
				'value' => $debtorName
			));
			
			reviso_logthis("save_order_to_reviso Order_SetDebtorAddress.");
			$client->Order_SetDebtorAddress(array(
				'orderHandle' => $order_handle,
				'value' => $address
			));
			
			reviso_logthis("save_order_to_reviso Order_SetDebtorCity.");
			$client->Order_SetDebtorCity(array(
				'orderHandle' => $order_handle,
				'value' => $city
			));
			
			reviso_logthis("save_order_to_reviso Order_SetDebtorCountry.");
			$client->Order_SetDebtorCountry(array(
				'orderHandle' => $order_handle,
				'value' => $country
			));
			
			reviso_logthis("save_order_to_reviso Order_SetDebtorPostalCode.");
			$client->Order_SetDebtorPostalCode(array(
				'orderHandle' => $order_handle,
				'value' => $postalcode
			));
			
			
			$formatted_state = $countries->states[$order->shipping_country][$order->shipping_state];
			$address = trim($order->shipping_address_1 . "\n" . $order->shipping_address_2 . "\n" . $formatted_state);
			$city = $order->shipping_city;
			$postalcode = $order->shipping_postcode;
			$country = $countries->countries[$order->shipping_country];
			
			reviso_logthis("save_order_to_reviso Order_SetDeliveryAddress.");
			$client->Order_SetDeliveryAddress(array(
				'orderHandle' => $order_handle,
				'value' => $address
			));
			
			reviso_logthis("save_order_to_reviso Order_SetDeliveryCity.");
			$client->Order_SetDeliveryCity(array(
				'orderHandle' => $order_handle,
				'value' => $city
			));
			
			reviso_logthis("save_order_to_reviso Order_SetDeliveryCountry.");
			$client->Order_SetDeliveryCountry(array(
				'orderHandle' => $order_handle,
				'value' => $country
			));
			
			reviso_logthis("save_order_to_reviso Order_SetDeliveryPostalCode.");
			$client->Order_SetDeliveryPostalCode(array(
				'orderHandle' => $order_handle,
				'value' => $postalcode
			));
			
			
			
			//Add for version 1.9.7 by Alvin
			//Set the currency of the Reviso order based on the store Currency.
			$client->Order_SetCurrency(array(
				'orderHandle' => $order_handle,
				'valueHandle' => array('Code' => get_option('woocommerce_currency'))
			));
			
			//Added for version 1.9.9.8
			//Set the order date
			$date = new DateTime($order->order_date);
			$client->Order_SetDate(array(
				'orderHandle' => $order_handle,
				'value' => $date->format('c')
			));
			
			//reviso_logthis($orderLines);
			
			$orderLines = $client->Order_GetLines(array(
				'orderHandle' => $order_handle,
			))->Order_GetLinesResult;
			
			if(isset($orderLines->OrderLineHandle)){
				if(is_array($orderLines->OrderLineHandle)){
					foreach($orderLines->OrderLineHandle as $orderLine){
						$client->OrderLine_Delete(array(
							'orderLineHandle' => $orderLine,
						));
					}
				}else{
					$client->OrderLine_Delete(array(
						'orderLineHandle' => $orderLines->OrderLineHandle,
					));
				}
			}
			
			reviso_logthis("save_order_to_reviso call reviso_handle_order_lines_to_reviso.");			
			$this->wooreviso_handle_order_lines_to_reviso($order, $order_handle, $client, $refund);

			//reviso_logthis("SELECT * FROM wcr_orders WHERE order_id=".$order->id.": ".$wpdb->query ("SELECT * FROM wcr_orders WHERE order_id=".$order->id.";"));
		
			if($wpdb->query ("SELECT * FROM wcr_orders WHERE order_id=".$order->id.";")){
				$wpdb->update ("wcr_orders", array('synced' => 1), array('order_id' => $order->id), array('%d'), array('%d'));
			}else{
				$wpdb->insert ("wcr_orders", array('order_id' => $order->id, 'synced' => 1), array('%d', '%d'));
			}
			return $order_handle;
		} catch (Exception $exception) {
			reviso_logthis("save_order_to_reviso could not save order: " . $exception->getMessage());
			$this->debug_client($client);
			reviso_logthis('Could not create invoice.');
			reviso_logthis($exception->getMessage());
			if($wpdb->query ("SELECT * FROM wcr_orders WHERE order_id=".$order->id." AND synced=0;")){
				return false;
			}else{
				$wpdb->insert ("wcr_orders", array('order_id' => $order->id, 'synced' => 0), array('%d', '%d'));
				return false;
			}
		}
	}
	
	/**
     * Update the payment method and payment status to reviso order or invoice.
     *
     * @access public
     * @param SOAP client, Order object
	 * @return order handle if found, otherwise 
	 * Update for version 1.9.9.8 to update payment method and status to Reviso order or invoice.
     */	
	 public function wooreviso_update_order_payment_to_reviso(SoapClient &$client, WC_Order $order){
		 $handle = $this->wooreviso_is_order_synced_already($client, $order);
		 if($handle['type'] == 'order'){
			 reviso_logthis('reviso_update_order_payment_to_reviso: updating order ');
			 $textline1 = __( 'Payment Method:', 'woocommerce' ).' '.$order->payment_method_title.' ('.current_time('Y-m-d').')';
			 $client->Order_SetTextLine1(array(
				'orderHandle' => $handle['handle'],
				'value' => $textline1
			 ));
			 reviso_logthis('reviso_update_order_payment_to_reviso: updated order ');
		 }
		 
		 if($handle['type'] == 'current_invoice'){
			 reviso_logthis('reviso_update_order_payment_to_reviso: updating current_invoice ');
			 $textline1 = __( 'Payment Method:', 'woocommerce' ).' '.$order->payment_method_title.' ('.current_time('Y-m-d').')';
			 $client->CurrentInvoice_SetTextLine1(array(
				'currentInvoiceHandle' => $handle['handle'],
				'value' => $textline1
			 ));
			 reviso_logthis('reviso_update_order_payment_to_reviso: updated current_invoice ');
		 }
	 }

	
	/**
     * Get or Create order number from reviso
     *
     * @access public
     * @param User object, SOAP client, debtor_handle
	 * @return order handle if found, otherwise 
	 * Update for version 1.9.9.1 to prevent duplicate order sync.
     */	
	public function wooreviso_get_order_number_from_reviso(SoapClient &$client, WC_Order $order, $reference, &$debtor_handle){
		$is_synced = $this->wooreviso_is_order_synced_already($client, $order);
		
		if($is_synced['synced'] === true && $is_synced['type'] === 'order'){
			$reviso_order = $is_synced['handle'];
			reviso_logthis("wooreviso_get_order_number_from_reviso order already exists");
			reviso_logthis($reviso_order);
			return $reviso_order;
		}
		
		if($is_synced['synced'] === false){
			reviso_logthis("wooreviso_get_order_number_from_reviso order doesn't exists, creating new order!");
			$reviso_order = $client->Order_Create(array(
				'debtorHandle' => $debtor_handle
			))->Order_CreateResult;
			if(isset($reviso_order->Id) && !empty($reviso_order->Id)){
				reviso_logthis("wooreviso_get_order_number_from_reviso orderId " . $reviso_order->Id . " created!");
				$client->Order_SetOtherReference(array(
					'orderHandle' => $reviso_order,
					'value' => $reference
				));
				return $reviso_order;
			}else{
				reviso_logthis("wooreviso_get_order_number_from_reviso creating new order failed!");
				return false;
			}
		}	
		
		if($is_synced['synced'] === true && $is_synced['type'] == 'current_invoice'){	
			reviso_logthis("wooreviso_get_order_number_from_reviso Reviso order is converted to Reviso, calling save_invoice_to_reviso_function");
			$this->save_invoice_to_reviso($client, NULL, $order, $refund = NULL);
			return true;
		}
		
		if($is_synced['synced'] === true && $is_synced['type'] !== 'order'){	
			return true;
		}
		
		
	}

	
	
	/**
     * Get order lines handle
     *
     * @access public
     * @param Order object, Invoice handle object, SOAP client, refund bool
     * @return debtor_handle object
     */	
	public function wooreviso_handle_order_lines_to_reviso(WC_Order $order, $order_handle, SoapClient &$client, $refund){
	  reviso_logthis("wooreviso_handle_order_lines_to_reviso - get all lines");
	
	  foreach ($order->get_items() as $item) {
		//reviso_logthis('orderline item');
		//reviso_logthis($item);
		$product = $order->get_product_from_item($item);
		//$line = $lines[$this->wooreviso_get_product_sku($product)];
		if(isset($product) && !empty($product)){
			$order_line_handle = null;
			$order_line_handle = $this->wooreviso_create_orderline_handle_at_reviso($order_handle, $this->wooreviso_get_product_sku($product), $client);
		
			reviso_logthis("wooreviso_handle_order_lines_to_reviso updating qty on id: " . $order_line_handle->Id . " number: " . $order_line_handle->Number);
			$quantity = ($refund) ? $item['qty'] * -1 : $item['qty'];
			
			$client->OrderLine_SetQuantity(array(
			  'orderLineHandle' => $order_line_handle,
			  'value' => $quantity
			));
			$client->OrderLine_SetUnitNetPrice(array(
			  'orderLineHandle' => $order_line_handle,
			  'value' => $item['line_subtotal']/$item['qty']
			));
			
			$discount = $item['line_subtotal']-$item['line_total'];
			if($discount != 0){
				$discount = ($discount*100)/$item['line_subtotal'];
				$client->OrderLine_SetDiscountAsPercent(array(
				  'orderLineHandle' => $order_line_handle,
				  'value' => $discount
				));
			}
			reviso_logthis("wooreviso_handle_order_lines_to_reviso updated line");
		}
	  }
	  
		$shippingItem = reset($order->get_items('shipping'));
		//reviso_logthis('shippingItem:');
		//reviso_logthis($shippingItem);
		if(isset($shippingItem['method_id'])){
			reviso_logthis("wooreviso_handle_order_lines_to_reviso adding Shipping line");
			if(strlen($shippingItem['method_id']) > 25){
				$shippingID = substr($shippingItem['method_id'], 0, 24);
			}else{
				$shippingID = $shippingItem['method_id'];
			}
			$order_line_handle = null;
			$order_line_handle = $this->wooreviso_create_orderline_handle_at_reviso($order_handle, $shippingID , $client);
			reviso_logthis("wooreviso_handle_order_lines_to_reviso updating qty on id: " . $order_line_handle->Id . " number: " . $order_line_handle->Number);
			$quantity = ($refund) ? -1 : 1;
			$client->OrderLine_SetQuantity(array(
			'orderLineHandle' => $order_line_handle,
			'value' => $quantity
			));
			$client->OrderLine_SetUnitNetPrice(array(
			  'orderLineHandle' => $order_line_handle,
			  'value' => $shippingItem['cost']
			));
			
			reviso_logthis("wooreviso_handle_order_lines_to_reviso updated shipping line");
		}
		

		$coupon = reset($order->get_items('coupon'));
		//reviso_logthis('coupon');
		//reviso_logthis($coupon);
		if(isset($coupon['name'])){
			reviso_logthis("wooreviso_handle_order_lines_to_reviso adding Coupon line");
			if(strlen($coupon['name']) > 25){
				$couponID = substr($coupon['name'], 0, 24);
			}else{
				$couponID = $coupon['name'];
			}
			$order_line_handle = null;
			$order_line_handle = $this->wooreviso_create_orderline_handle_at_reviso($order_handle, $couponID , $client);
			reviso_logthis("wooreviso_handle_order_lines_to_reviso updating qty on id: " . $order_line_handle->Id . " number: " . $order_line_handle->Number);
			$quantity = ($refund) ? -1 : 1;
			$client->OrderLine_SetQuantity(array(
			'orderLineHandle' => $order_line_handle,
			'value' => $quantity
			));
			/*$client->OrderLine_SetUnitNetPrice(array(
			  'orderLineHandle' => $order_line_handle,
			  'value' => -$coupon['discount_amount']
			));*/
			reviso_logthis("wooreviso_handle_order_lines_to_reviso updated coupon line");
		}
	}
	
	
	/**
     * Get order lines to reviso 
     *
     * @access public
     * @param 
     * @return array log
     */
	public function wooreviso_create_orderline_handle_at_reviso($order_handle, $product_id, SoapClient &$client){
		
		$product_handle = $client->Product_FindByNumber(array(
			'number' => $product_id
		))->Product_FindByNumberResult;
		
		$orderline_handle = $client->OrderLine_Create(array(
			'orderHandle' => $order_handle
		))->OrderLine_CreateResult;
		
		reviso_logthis("wooreviso_create_orderline_handle_at_reviso added line id: " . $orderline_handle->Id . " number: " . $orderline_handle->Number . " product_id: " . $product_id);
		
		$client->OrderLine_SetProduct(array(
			'orderLineHandle' => $orderline_handle,
			'valueHandle' => $product_handle
		));
		$product = $client->Product_GetData(array(
			'entityHandle' => $product_handle
		))->Product_GetDataResult;
		$client->OrderLine_SetDescription(array(
			'orderLineHandle' => $orderline_handle,
			'value' => $product->Name
		));
		$client->OrderLine_SetUnitNetPrice(array(
			'orderLineHandle' => $orderline_handle,
			'value' => $product->SalesPrice
		));
		
		reviso_logthis("wooreviso_create_orderline_handle_at_reviso added product to line ");
		return $orderline_handle;
	}
	
	
	
	/**
     * Check if the WooCommerce order is already synced as CurrentInvoice or Invoice, reviso order is fine for updating.
     *
     * @access public
     * @param 
     * @return array containing boolean flag for synced or no synced and handle if found.
     */
	 public function wooreviso_is_order_synced_already(SoapClient &$client, WC_Order $order){
		 
		$return = array('synced'=> false, 'type' => NULL, 'handle'=> NULL);
		 
		reviso_logthis('reviso_is_order_synced_already: finding order handle by other reference.');
		$reviso_order_handle = $client->Order_FindByOtherReference(array(
			'otherReference' => $this->order_reference_prefix.$order->id
		))->Order_FindByOtherReferenceResult; 
		
		$reviso_order_handle_array = (array) $reviso_order_handle;
		
		if(!empty($reviso_order_handle_array)) {
			reviso_logthis('reviso_is_order_synced_already: Order handle found.');
			$return['synced'] = true;
			$return['type'] = 'order';
			$return['handle'] = $reviso_order_handle->OrderHandle;
			return $return;
		}
		
		reviso_logthis('reviso_is_order_synced_already: finding current invoice handle by other reference.');
		$current_invoice_handle = $client->CurrentInvoice_FindByOtherReference(array(
			'otherReference' => $this->order_reference_prefix.$order->id
		))->CurrentInvoice_FindByOtherReferenceResult;
		
		$current_invoice_handle_array = (array) $current_invoice_handle;
		
		if(!empty($current_invoice_handle_array)) {
			reviso_logthis('reviso_is_order_synced_already: Current Invoice handle found.');
			$return['synced'] = true;
			$return['type'] = 'current_invoice';
			$return['handle'] = $current_invoice_handle->CurrentInvoiceHandle;
			return $return;
		}
		
		reviso_logthis('reviso_is_order_synced_already: finding invoice handle by other reference.');
		$invoice_handle = $client->Invoice_FindByOtherReference(array(
			'otherReference' => $this->order_reference_prefix.$order->id
		))->Invoice_FindByOtherReferenceResult;
		
		$invoice_handle_array = (array )$invoice_handle;		
		
		if(!empty($invoice_handle_array)) {
			reviso_logthis('reviso_is_order_synced_already: Invoice handle found.');
			$return['synced'] = true;
			$return['type'] = 'invoice';
			$return['handle'] = $invoice_handle->InvoiceHandle;
			return $return;
		}
		
		return $return;
		
	 }
	
	
	/**
     * Sync WooCommerce orders to reviso
     *
     * @access public
     * @param 
     * @return array log
     */
	public function sync_orders(){
		global $wpdb;
		$options = get_option('woocommerce_reviso_general_settings');
		$client = $this->wooreviso_client();
		if(!$client){
			$sync_log[0] = false;
			array_push($sync_log, array('status' => 'fail', 'msg' => 'Could not create reviso client, please try again later!' ));
			return $sync_log;
		}
		$orders = array();
		$sync_log = array();
		$sync_log[0] = true;
		reviso_logthis("sync_orders starting...");
        $unsynced_orders = $wpdb->get_results("SELECT * from wcr_orders WHERE synced = 0");

		foreach ($unsynced_orders as $order){
			$orderId = $order->order_id;
			array_push($orders, new WC_Order($orderId));
		}
		
		if($this->activate_oldordersync == "on"){
			$all_unsynced_orders = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."posts WHERE ID NOT IN (SELECT order_id FROM wcr_orders) AND post_type='shop_order' AND post_status != 'trash' AND post_status != 'wc-failed' AND post_status != 'wc-cancelled'");
			foreach ($all_unsynced_orders as $order){
				$orderId = $order->ID;
				array_push($orders, new WC_Order($orderId));
			}
		}
		
		if(!empty($orders)){
			foreach ($orders as $order) {
				reviso_logthis('sync_orders Order ID: ' . $order->id);
				if($order->customer_user != 0){
					$user = new WP_User($order->customer_user);
				}else{
					$user = NULL;
				}
				$this->save_customer_to_reviso($client, $user, $order);
				if($order->customer_user != 0){
					$user = new WP_User($order->customer_user);
				}else{
					$user = NULL;
				}				
				
				if($order->payment_method == 'reviso-invoice'){
					reviso_logthis("sync_orders syncing WC order for Reviso payment.");
					if($this->wooreviso_checkout == 'order'){
						if($this->save_order_to_reviso($client, $user, $order, false)){
							array_push($sync_log, array('status' => __('success', 'wooreviso'), 'order_id' => $order->id, 'msg' => __('Order synced successfully', 'wooreviso') ));
						}else{
							$sync_log[0] = false;
							array_push($sync_log, array('status' => __('fail', 'wooreviso'), 'order_id' => $order->id, 'msg' => __('Sync failed, please try again later!' , 'wooreviso')));
						}
					}
					
					if($this->wooreviso_checkout == 'draft invoice' || $this->wooreviso_checkout == 'invoice'){
						if($this->save_invoice_to_reviso($client, $user, $order, false)){
							array_push($sync_log, array('status' => __('success', 'wooreviso'), 'order_id' => $order->id, 'msg' => __('Order synced successfully' ), 'wooreviso'));
						}else{
							$sync_log[0] = false;
							array_push($sync_log, array('status' => __('fail', 'wooreviso'), 'order_id' => $order->id, 'msg' => __('Sync failed, please try again later!' , 'wooreviso')));
						}
							
						if($this->wooreviso_checkout == 'invoice'){
							if($this->send_invoice_reviso($client, $order)){
								reviso_logthis("sync_orders invoice for order: " . $order_id . " is sent to customer.");
							}else{
								reviso_logthis("sync_orders invoice for order: " . $order_id . " sending failed!");
							}
						}
					}
				}else{
					reviso_logthis("sync_orders syncing WC order for payment method except Reviso.");
					if($this->other_checkout == 'do nothing'){
						reviso_logthis("sync_orders order: " . $order_id . " is not synced synced with reviso because do nothing is selected for Reviso payment.");
						array_push($sync_log, array('status' => __('success', 'wooreviso'), 'order_id' => $order->id, 'msg' => __('Order not synced, because Other checkout is set to "Do nothing"', 'wooreviso') ));
						continue; //Check if the payment is not Reviso and all order sync is active, if not breaks this iteration and continue with other orders.
					}
					
					if($this->other_checkout == 'order'){
						if($this->save_order_to_reviso($client, $user, $order, false)){
							array_push($sync_log, array('status' => __('success', 'wooreviso'), 'order_id' => $order->id, 'msg' => __('Order synced successfully', 'wooreviso') ));
						}else{
							$sync_log[0] = false;
							array_push($sync_log, array('status' => __('fail', 'wooreviso'), 'order_id' => $order->id, 'msg' => __('Sync failed, please try again later!' , 'wooreviso')));
						}
					}
					
					if($this->other_checkout == 'draft invoice' || $this->other_checkout == 'invoice'){
						if($this->save_invoice_to_reviso($client, $user, $order, false)){
							array_push($sync_log, array('status' => __('success', 'wooreviso'), 'order_id' => $order->id, 'msg' => __('Order synced successfully' ), 'wooreviso'));
						}else{
							$sync_log[0] = false;
							array_push($sync_log, array('status' => __('fail', 'wooreviso'), 'order_id' => $order->id, 'msg' => __('Sync failed, please try again later!' , 'wooreviso')));
						}
							
						if($this->other_checkout == 'invoice'){
							if($this->send_invoice_reviso($client, $order)){
								reviso_logthis("sync_orders invoice for order: " . $order_id . " is sent to customer.");
							}else{
								reviso_logthis("sync_orders invoice for order: " . $order_id . " sending failed!");
							}
						}
					}	
				}
			}
		}else{
			$sync_log[0] = true;
			array_push($sync_log, array('status' => __('success', 'wooreviso'), 'order_id' => '', 'msg' => __('All orders were already synced!', 'wooreviso') ));
		}
		
		$client->Disconnect();
		reviso_logthis("sync_orders ending...");
		return $sync_log;
	}
	
	
	 /**
     * Save WooCommerce Product to reviso
     *
     * @access public
     * @param product oject
     * @return bool
     */
	
	public function save_product_to_reviso(WC_Product $product, SoapClient &$client){
		if(!$client){
			return false;
		}

		$options = get_option('woocommerce_reviso_general_settings');
		
		
		
		reviso_logthis("save_product_to_reviso syncing product - sku: " . $product->sku . " title: " . $product->get_title());
		try	{
			$product_sku = $this->wooreviso_get_product_sku($product);
			//reviso_logthis("save_product_to_reviso - trying to find product in reviso with product number: ".$product_sku);
			
			// Find product by number
			reviso_logthis('Finding product by number: '.$product_sku);
			$product_handle = $client->Product_FindByNumber(array(
				'number' => $product_sku
			))->Product_FindByNumberResult;
			
			reviso_logthis('--product_handle--');
			reviso_logthis($product_handle);
			
			$productGroup = get_post_meta( $product->id, 'productGroup', true );
			if($productGroup == '' || $productGroup == NULL){
				reviso_logthis('save_product_to_reviso productGroup is used from the plugin settings: '. $this->product_group);
				$productGroup = $this->product_group;
			}else{
				reviso_logthis('save_product_to_reviso productGroup is used from the product meta: '. $productGroup);
				 if ($product_handle && !empty($product_handle)){
					  reviso_logthis('save_product_to_reviso setting productGroup for the Reviso product.');
					  $client->Product_SetProductGroup(array(
						'productHandle' => $product_handle,
						'valueHandle' => array('Number' => $productGroup)
					 ));
				 }
			}
			
			// Create product with name
			if (!$product_handle) {
				$productGroupHandle = $client->ProductGroup_FindByNumber(array(
					'number' => $productGroup
				))->ProductGroup_FindByNumberResult;
				$product_handle = $client->Product_Create(array(
					'number' => $product_sku,
					'productGroupHandle' => $productGroupHandle,
					'name' => utf8_encode($product->get_title())
				))->Product_CreateResult;
				reviso_logthis($product_handle);
				reviso_logthis("save_product_to_reviso - product created:" . $product->get_title());
			}else{
				$client->Product_SetProductGroup(array(
					'productHandle' => $product_handle,
					'valueHandle' => array('Number' => $productGroup)
				 ));
			}
			
			// Get product data
			$product_data = $client->Product_GetData(array(
				'entityHandle' => $product_handle
			))->Product_GetDataResult;
			
			
			//reviso_logthis($product_data);
			//return true;

			//reviso_logthis($product_data->DepartmentHandle);
			//reviso_logthis($product_data->DistrubutionKeyHandle);
			if($this->product_sync != "on"){
				reviso_logthis("Product sync exiting, because product sync is not activated");
				
				//Update InStock from Reviso to woocommerce
				if($product->managing_stock()){
					($product_data->InStock !=0 || $product_data->InStock =='') ? $product->set_stock($product_data->InStock) : reviso_logthis('Product stock not updated.');
					reviso_logthis('Product: '.$product->get_title().' Stock updated to '.$product_data->InStock);
				}else{
					reviso_logthis('Product: '.$product->get_title().' Stock management disabled');
				}
				return true;
			}else{
				// Update product data
				
				
				$Company = $client->Company_Get()->Company_GetResult;
				$Company_GetBaseCurrency = $client->Company_GetBaseCurrency(array(
					'companyHandle' => $Company
				))->Company_GetBaseCurrencyResult;
				//reviso_logthis('Company_GetBaseCurrency:');
				//reviso_logthis($Company_GetBaseCurrency);
				
				if($Company_GetBaseCurrency->Code == get_option('woocommerce_currency')){
					$sales_price = $product->get_price_excluding_tax(1, $product->get_price());
				}else{
					$sales_price = $client->Product_GetSalesPrice(array('productHandle'  => $product_handle))->Product_GetSalesPriceResult;
				}
				
				$client->Product_UpdateFromData(array(
				'data' => (object)array(
				'Handle' => $product_data->Handle,
				'Number' => $product_data->Number,
				'ProductGroupHandle' => $product_data->ProductGroupHandle,
				'Name' => utf8_encode($product->get_title()),
				'Description' => utf8_encode($this->wooreviso_product_content_trim($product->post->post_content, 255)),
				'BarCode' => "",
				//'SalesPrice' => (isset($product->price) && !empty($product->price) ? $product->price : 0.0),
				'SalesPrice' => (isset($sales_price) && !empty($sales_price) ? $sales_price : 0.0),
				'CostPrice' => (isset($product_data->CostPrice) ? $product_data->CostPrice : 0.0),
				'RecommendedPrice' => $product_data->RecommendedPrice,
				/*'UnitHandle' => (object)array(
				'Number' => 1
				),*/
				'IsAccessible' => true,
				'Volume' => $product_data->Volume,
				//'DepartmentHandle' => isset($product_data->DepartmentHandle) ? $product_data->DepartmentHandle : '',
				//'DistributionKeyHandle' => isset($product_data->DistrubutionKeyHandle) ? $product_data->DistrubutionKeyHandle : '',
				'InStock' => $product_data->InStock,
				'OnOrder' => $product_data->OnOrder,
				'Ordered' => $product_data->Ordered,
				'Available' => $product_data->Available)))->Product_UpdateFromDataResult;
				
				//Added in version 1.9.9.9.1 by Alvin for updaing the product price in store currency settings.
				if($Company_GetBaseCurrency->Code != get_option('woocommerce_currency')){
					$sales_price = $product->get_price_excluding_tax(1, $product->get_price());
					$productPriceHandle = $client->ProductPrice_FindByProductAndCurrency(array(
						'productHandle'  => $product_handle,
						'currencyHandle' => array('Code' => get_option('woocommerce_currency')),
					))->ProductPrice_FindByProductAndCurrencyResult;
					
					if(isset($productPriceHandle) && !empty($productPriceHandle)){
						reviso_logthis('productPriceHandle:');
						reviso_logthis($productPriceHandle);
						$client->ProductPrice_SetPrice(array(
							'productPriceHandle'  => $productPriceHandle,
							'value'			 => (isset($sales_price) && !empty($sales_price) ? $sales_price : 0.0)
						));
					}else{
						reviso_logthis('productPriceHandle not found, creating product price.');
						$client->ProductPrice_Create(array(
							'productHandle'  => $product_handle,
							'currencyHandle' => array('Code' => get_option('woocommerce_currency')),
							'price'			 => (isset($sales_price) && !empty($sales_price) ? $sales_price : 0.0)
						));
					}
				}
				
				//Update InStock from Reviso to woocommerce
				if($product->managing_stock()){
					($product_data->InStock !=0 || $product_data->InStock =='') ? $product->set_stock($product_data->InStock) : reviso_logthis('Product stock not updated.');
					reviso_logthis('Product: '.$product->get_title().' Stock updated to '.$product_data->InStock);
				}else{
					reviso_logthis('Product: '.$product->get_title().' Stock management disabled');
				}
			}			
			reviso_logthis("save_product_to_reviso - product updated : " . $product->get_title());
			return true;
		} catch (Exception $exception) {
			reviso_logthis("save_product_to_reviso could not create product: " . $exception->getMessage());
			$this->debug_client($client);
			reviso_logthis($exception->getMessage);
			return false;
		}
	}
	
	
	 /**
	 * Removes tags and shortens the string to length
	 */
	 public function wooreviso_product_content_trim($str, $max_len)
	 {
	  reviso_logthis("wooreviso_product_content_trim '" . $str . "'");
	  $result = strip_tags($str);
	  if (strlen($result) > $max_len)
		$result = substr($result, 0, $max_len-1);
	
	  reviso_logthis("wooreviso_product_content_trim result: '" . $result . "'");
	
	  return $result;
	 }


	/**
     * Sync WooCommerce Products to Reviso 
     *
     * @access public
     * @param 
     * @return array log
     */
	 
	 public function sync_products(){
		$client = $this->wooreviso_client();
		if(!$client){
			$sync_log[0] = false;
			array_push($sync_log, array('status' => 'fail', 'msg' => 'Could not create Reviso client, please try again later!' ));
			return $sync_log;
		}
		$products = array();
		$sync_log = array();
		$sync_log[0] = true;
		$args = array('post_type' => array('product'), 'nopaging' => true, 'fields' => 'ids');
		$product_ids = new WP_Query($args);
		//$posts = $product_query->get_posts();
		foreach ($product_ids->posts as $key=>$post_id) {
			array_push($products, $post_id);
		}
		
		//Added for 1.9.5 update by Alvin
		$variation_args = array('post_type' => array('product_variation'), 'nopaging' => true, 'fields' => 'ids');
		$product_variation_ids = new WP_Query($variation_args);
		//$variation_posts = $product_variation_query->get_posts();
		foreach ($product_variation_ids->posts as $key=>$variation_post_id) {
			$variation_parent_post_id = wp_get_post_parent_id( $variation_post_id );
			$variation_parent_post = get_post($variation_parent_post_id);
			if($variation_parent_post->post_status == 'publish'){
				array_push($products, $variation_post_id);
			}
		}
		
		reviso_logthis("sync_products starting...");
		foreach ($products as $key=>$productID) {
			$product = new WC_Product($productID);
			reviso_logthis('sync_products Product ID: ' . $product->id);
			reviso_logthis('sync_products saving product: ' . $product->get_title() . " sku: " . $product->sku);
			reviso_logthis('Product SKU: '. $product->sku );
			reviso_logthis('Product Title: '.$product->get_title());
			$title = $product->get_title();
			if (isset($product->sku) && !empty($product->sku) && isset($title) && !empty($title)) {
				if($this->save_product_to_reviso($product, $client)){
					if($this->product_sync != "on"){
						if($product->managing_stock()){
							array_push($sync_log, array('status' => __('success', 'wooreviso'), 'sku' => $product->sku, 'name' => $product->get_title(), 'msg' => __('Product sync: Disabled! Use "Activate product sync" settings to enable it. <br> Product stock sync: Successfull!', 'wooreviso') ));
						}else{
							array_push($sync_log, array('status' => __('success', 'wooreviso'), 'sku' => $product->sku, 'name' => $product->get_title(), 'msg' => __('Product sync: Disabled! Use "Activate product sync" settings to enable it. <br> Product stock sync: Stock management disabled, Stock management can be enabled at Product->Inventory.', 'wooreviso') ));
						}
					}else{
						if($product->managing_stock()){
							array_push($sync_log, array('status' => __('success', 'wooreviso'), 'sku' => $product->sku, 'name' => $product->get_title(), 'msg' => __('Product sync: Successful! <br> Product stock sync: Successfull!', 'wooreviso') ));
						}else{
							array_push($sync_log, array('status' => __('success', 'wooreviso'), 'sku' => $product->sku, 'name' => $product->get_title(), 'msg' => __('Product sync: Successful! <br> Product stock sync: Stock management disabled, Stock management can be enabled at Product->Inventory.', 'wooreviso') ));
						}
					}
				}else{
					$sync_log[0] = false;
					array_push($sync_log, array('status' => __('fail', 'wooreviso'), 'sku' => $product->sku, 'name' => $product->get_title(), 'msg' => __('Product not synced, someting went wrong. Please try product sync after some time!', 'wooreviso') ));
				}
			} else {
				reviso_logthis("Could not sync product: '". $product->get_title() ."' and id: '".$product->id."' to Reviso. Please update it with:");
				if (!isset($product->sku) || empty($product->sku)){
				  reviso_logthis("SKU");
				  $sync_log[0] = false;
				  array_push($sync_log, array('status' => __('fail', 'wooreviso'), 'sku' => '', 'name' => $product->get_title(), 'msg' => __('Product not synced, SKU is empty!', 'wooreviso') ));
				}
				if (!isset($title) || empty($title)){
				  reviso_logthis("Title");
				  $sync_log[0] = false;
				  array_push($sync_log, array('status' => __('fail', 'wooreviso'), 'sku' => $product->sku, 'name' => '', 'msg' => __('Product not synced, product title is empty!', 'wooreviso') ));
				}
			}
		}
		
		$client->Disconnect();
		reviso_logthis("sync_products ending...");
		return $sync_log;
	 }
	 
	 
	 
	 /**
     * Sync WooCommerce Products from reviso to WooCommerce.
     *
     * @access public
     * @param 
     * @return array log
     */
	 
	 public function sync_products_rw(){
		update_option('reviso_save_object', false);
		global $wpdb;
		$client = $this->wooreviso_client();
		$sync_log = array();
		$sync_log[0] = true;
		if(!$client){
			$sync_log[0] = false;
			array_push($sync_log, array('status' => __('fail', 'wooreviso'), 'msg' => __('Could not create Reviso client, please try again later!', 'wooreviso') ));
			return $sync_log;
		}
		
		$products = $client->Product_GetAll()->Product_GetAllResult;
		//reviso_logthis($products);
		
		$product_handles = array();
		
		foreach($products->ProductHandle as $product){
			$product_handles[$product->Number] = $client->Product_GetProductGroup(array('productHandle' => $product))->Product_GetProductGroupResult;
		}

		foreach($product_handles as $product_number => $group){
			$sku = str_replace($this->product_offset, '', $product_number);
			
			$product_name = $client->Product_GetName(array(
				'productHandle' => array('Number' => $product_number ),
			))->Product_GetNameResult;
			
			
			$product_post_ids = $wpdb->get_results("SELECT post_id FROM ".$wpdb->prefix."postmeta WHERE meta_key = '_sku' AND meta_value = '".$sku."'", OBJECT_K );
		
			if(!empty($product_post_ids)){
				foreach($product_post_ids as $product_post_id){
					$product_id = $product_post_id->post_id;
				}
				if(get_post_status( $product_id ) == 'trash'){
					continue;
				}
			}else{
				$product_id = NULL;
			}
			
			$product_data = $client->Product_GetData(array(
				'entityHandle' => array('Number' => $product_number ),
			))->Product_GetDataResult;	
			
			//reviso_logthis($product_data);
			
			if($product_id != NULL){
				reviso_logthis('update product : '.$product_number);
				
				if($this->product_sync == "on"){
					$product = new WC_Product($product_id);
					$post = array(
						'ID'		   => $product_id,
						'post_content' => $product_data->Description != ''? $product_data->Description : $product_data->Name,
						'post_title'   => $product_data->Name,
					);
					
					$post_id = wp_update_post( $post, true );
					if (is_wp_error($post_id)) {
						$errors = $post_id->get_error_messages();
						foreach ($errors as $error) {
							reviso_logthis($error);
						}
					}
					update_post_meta( $post_id, '_price', (int) $product_data->SalesPrice );
					update_post_meta( $post_id, 'productGroup', $group->Number );
					//update_post_meta( $post_id, '_sale_price', (int) $product_data->SalesPrice );
					if($product->managing_stock()){
						if((int)$product_data->InStock > 0){
							$product->set_stock($product_data->InStock);
							update_post_meta( $post_id, '_stock_status', 'instock' );
						}else{
							$product->set_stock(0);
							update_post_meta( $post_id, '_stock_status', 'outofstock' );
						}
						reviso_logthis('Product: '.$product->get_title().' Stock updated to '.$product_data->InStock);
						array_push($sync_log, array('status' => __('success', 'wooreviso'), 'sku' => $product_number, 'name' => $product_data->Name, 'msg' => __('Product sync: Successful! <br> Product stock sync: Successfull!', 'wooreviso') ));
					}else{
						array_push($sync_log, array('status' => __('success', 'wooreviso'), 'sku' => $product_number, 'name' => $product_data->Name, 'msg' => __('Product sync: Successful! <br> Product stock sync: Stock management disabled, Stock management can be enabled at Product->Inventory.', 'wooreviso') ));
					}
				}else{
					if($product->managing_stock()){
						($product_data->InStock !=0 || $product_data->InStock =='') ? $product->set_stock($product_data->InStock) : reviso_logthis('Product stock not updated.');
						reviso_logthis('Product: '.$product->get_title().' Stock updated to '.$product_data->InStock);
						array_push($sync_log, array('status' => __('success', 'wooreviso'), 'sku' => $product_number, 'name' => $product_data->Name, 'msg' => __('Product sync: Disabled! Use "Activate product sync" settings to enable it. <br> Product stock sync: Successfull!', 'wooreviso') ));
					}else{
						array_push($sync_log, array('status' => __('success', 'wooreviso'), 'sku' => $product_number, 'name' => $product_data->Name, 'msg' => __('Product sync: Disabled! Use "Activate product sync" settings to enable it. <br> Product stock sync: Stock management disabled, Stock management can be enabled at Product->Inventory.', 'wooreviso') ));
					}
				}
			}else{
				reviso_logthis('add product : '.$product_number);
				$post = array(
					'post_status'  => 'publish',
					'post_type'    => 'product',
					'post_title'   => $product_data->Name,
					'post_content' => $product_data->Description != ''? $product_data->Description : $product_data->Name,
					'post_excerpt' => $product_data->Description != ''? $product_data->Description : $product_data->Name,
				);
				
				$post_id = wp_insert_post( $post, true );
				if (is_wp_error($post_id)) {
					$errors = $post_id->get_error_messages();
					foreach ($errors as $error) {
						reviso_logthis('Product creation error');
						reviso_logthis($error);
					}
					continue;
				}
				$product = new WC_Product($post_id);
				update_post_meta( $post_id, '_sku', $sku );
				update_post_meta( $post_id, '_price', (string) $product_data->SalesPrice );
				update_post_meta( $post_id, 'productGroup', $group->Number );
				//update_post_meta( $post_id, '_sale_price', (int) $product_data->SalesPrice );
				if((int)$product_data->InStock > 0){
					$product->set_stock($product_data->InStock);
					update_post_meta( $post_id, '_stock_status', 'instock' );
				}else{
					$product->set_stock(0);
					update_post_meta( $post_id, '_stock_status', 'outofstock' );
				}

				array_push($sync_log, array('status' => __('success', 'wooreviso'), 'sku' => $product_number, 'name' => $product_data->Name, 'msg' => __('Product sync: Successful! <br> Product stock sync: Successfull!', 'wooreviso') ));
			}
		}
		update_option('reviso_save_object', true);
		return $sync_log;
	}
	 
	 /**
     * Save WooCommerce Product to reviso
     *
     * @access public
     * @param product oject
     * @return bool
     */
	 
	 public function save_customer_to_reviso(SoapClient &$client, WP_User $user = NULL, WC_Order $order = NULL){
	  reviso_logthis("save_customer_to_reviso creating client");
	  global $wpdb;	
	  try {
		$debtorHandle = $this->wooreviso_get_debtor_handle_from_reviso($client, $user, $order);
		
		if (isset($debtorHandle) && $debtorHandle !== false) {
			reviso_logthis("save_customer_to_reviso reviso_get_debtor_handle_from_reviso handle returned: " . $debtorHandle->Number);
			
			$debtor_delivery_location_handle = $this->wooreviso_get_debtor_delivery_location_handles_from_reviso($client, $debtorHandle);
			
			foreach ($this->user_fields as $meta_key) {
				$this->wooreviso_save_customer_meta_data_to_reviso($client, $meta_key, $order ? $order->$meta_key: $user->get($meta_key), $debtorHandle, $debtor_delivery_location_handle, $user, $order);
			}
			
			if(is_object($order)){
				$email = $order->billing_email;
			}
			
			if(is_object($user)){
				$email = $user->get('billing_email');
			}
			
			reviso_logthis("save_customer_to_reviso customer synced for email: " . $email);
			
			if($wpdb->query ("SELECT * FROM wcr_customers WHERE email='".$email."';")){
				$wpdb->update ("wcr_customers", array('synced' => 1, 'customer_number' => $debtorHandle->Number, 'email' => $email), array('email' => $email), array('%d', '%d', '%s'), array('%s'));
			}else{
				$wpdb->insert ("wcr_customers", array('user_id' => $user->ID, 'customer_number' => $debtorHandle->Number, 'email' => $email, 'synced' => 1), array('%d', '%s', '%s', '%d'));
			}
			return true;
		}else{
			reviso_logthis("save_customer_to_reviso debtor not found.");
			return false;
		}
	  } catch (Exception $exception) {
		reviso_logthis("save_customer_to_reviso could not save user to Reviso: " . $exception->getMessage());
		$this->debug_client($client);
		reviso_logthis("Could not create user.");
		reviso_logthis($exception->getMessage());
		if($wpdb->query ("SELECT * FROM wcr_customers WHERE email=".$email." AND synced=0;")){
			return false;
		}else{
			$wpdb->insert ("wcr_customers", array('user_id' => $user->ID, 'customer_number' => '0', 'email' => $email, 'synced' => 0), array('%d', '%s', '%s', '%d'));
			return false;
		}
	  }
	}
	
	/**
     * Save customer meta data to reviso
     *
     * @access public
     * @param user object, $meta_key, $meta_value
     * @return void
     */
	public function wooreviso_save_customer_meta_data_to_reviso(SoapClient &$client, $meta_key, $meta_value, $debtor_handle, $debtor_delivery_location_handle, WP_User $user = NULL, WC_Order $order = NULL){
	  reviso_logthis("wooreviso_save_customer_meta_data_to_reviso updating client");
	  //reviso_logthis($debtor_handle);
	  //reviso_logthis($debtor_delivery_location_handle);
	  if (!isset($debtor_handle)) {
		reviso_logthis("wooreviso_save_customer_meta_data_to_reviso debtor not found, can not update meta");
		return;
	  }
	  try {
	
		if ($meta_key == 'billing_phone') {
		  reviso_logthis("wooreviso_save_customer_meta_data_to_reviso key: " . $meta_key . " value: " . $meta_value);
		  $client->Debtor_SetTelephoneAndFaxNumber(array(
			'debtorHandle' => $debtor_handle,
			'value' => $meta_value
		  ));
		} elseif ($meta_key == 'billing_email') {
			  reviso_logthis("wooreviso_save_customer_meta_data_to_reviso key: " . $meta_key . " value: " . $meta_value);
			  $client->Debtor_SetEmail(array(
				'debtorHandle' => $debtor_handle,
				'value' => $meta_value
			  ));
	
		} elseif ($meta_key == 'billing_country') {
			  reviso_logthis("wooreviso_save_customer_meta_data_to_reviso key: " . $meta_key . " value: " . $meta_value);
			  $countries = new WC_Countries();
			  $country = $countries->countries[$meta_value];
			  reviso_logthis("wooreviso_save_customer_meta_data_to_reviso country: " . $country);
			  $client->Debtor_SetCountry(array(
				'debtorHandle' => $debtor_handle,
				'value' => $country
		  ));
		} elseif ($meta_key == 'billing_address_1') {
			  reviso_logthis("wooreviso_save_customer_meta_data_to_reviso key: " . $meta_key . " value: " . $meta_value);
			  $adr1 = $order ? $order->billing_address_1 : $user->get('billing_address_1');
			  $adr2 = $order ? $order->billing_address_2 : $user->get('billing_address_2');
			  $state = $order ? $order->billing_state : $user->get('billing_state');
			  $billing_country = $order ? $order->billing_country : $user->get('billing_country');
			  $countries = new WC_Countries();		
			  $formatted_state = (isset($state)) ? $countries->states[$billing_country][$state] : "";
			  $formatted_adr = trim($adr1."\n".$adr2."\n".$formatted_state);
			  reviso_logthis("wooreviso_save_customer_meta_data_to_reviso adr1: " . $adr1 . ", adr2: " . $adr2 . ", state: " . $formatted_state);
			  reviso_logthis("wooreviso_save_customer_meta_data_to_reviso formatted_adr: " . $formatted_adr);
			  $client->Debtor_SetAddress(array(
				'debtorHandle' => $debtor_handle,
				'value' => $formatted_adr
			  ));
	
		} elseif ($meta_key == 'billing_postcode') {
			  reviso_logthis("wooreviso_save_customer_meta_data_to_reviso key: " . $meta_key . " value: " . $meta_value);
			  $client->Debtor_SetPostalCode(array(
				'debtorHandle' => $debtor_handle,
				'value' => $meta_value
			  ));
	
		} elseif ($meta_key == 'billing_city') {
			  reviso_logthis("wooreviso_save_customer_meta_data_to_reviso key: " . $meta_key . " value: " . $meta_value);
			  $client->Debtor_SetCity(array(
				'debtorHandle' => $debtor_handle,
				'value' => $meta_value
			  ));
	
		} elseif ($meta_key == 'billing_company') {
			  reviso_logthis("wooreviso_save_customer_meta_data_to_reviso key: " . $meta_key . " value: " . $meta_value);
			  $meta_value = $order ? $order->billing_company ? $order->billing_company : $order->billing_first_name.' '.$order->billing_last_name : $user->get('user_login');
			  $client->Debtor_SetName(array(
				'debtorHandle' => $debtor_handle,
				'value' => $meta_value
			  ));
	
		} elseif($meta_key == 'billing_first_name' || $meta_key == 'billing_last_name') {
			  reviso_logthis("wooreviso_save_customer_meta_data_to_reviso key: " . $meta_key . " value: " . $meta_value);
			  $first = ($meta_key == 'billing_first_name') ? $meta_value : $order ? $order->billing_first_name : $user->get('billing_first_name');
			  $last = ($meta_key == 'billing_last_name') ? $meta_value : $order ? $order->billing_last_name : $user->get('billing_last_name');
			  $name = $first . " " . $last;
			  
			  $debtorContact = $client->DebtorContact_GetAll()->DebtorContact_GetAllResult;
			  
			  //reviso_logthis('DebtorContact_GetAll:');
			  //reviso_logthis($debtorContact); 
			  
			  $debtorContactArray = (array) $debtorContact;
			  if(empty( $debtorContactArray )){
				  reviso_logthis('reviso_save_customer_meta_data_to_reviso: creating new debtor contact'); 
				  $debtor_contact_handle = $client->DebtorContact_Create(array(
					'debtorHandle' => $debtor_handle,
					'name' => $name))->DebtorContact_CreateResult;
				  $client->Debtor_SetAttention(array(
					'debtorHandle' => $debtor_handle,
					'valueHandle' => $debtor_contact_handle
				  ));
			  }else{
				  reviso_logthis('reviso_save_customer_meta_data_to_reviso: using exiting debtor contact'); 
				  $client->Debtor_SetAttention(array(
					'debtorHandle' => $debtor_handle,
					'valueHandle' => $debtor_contact_handle->DebtorContactHandle[0]
				  ));
			  }
	
		} elseif ($meta_key == 'shipping_country') {
			  reviso_logthis("wooreviso_save_customer_meta_data_to_reviso key: " . $meta_key . " value: " . $meta_value);
			  $countries = new WC_Countries();
			  $country = $countries->countries[$meta_value];
			  reviso_logthis("wooreviso_save_customer_meta_data_to_reviso country: " . $country);
			  $client->DeliveryLocation_SetCountry (array(
				'deliveryLocationHandle' => $debtor_delivery_location_handle,
				'value' => $country
			  ));
		} elseif ($meta_key == 'shipping_postcode') {
			  reviso_logthis("wooreviso_save_customer_meta_data_to_reviso key: " . $meta_key . " value: " . $meta_value);
			  $client->DeliveryLocation_SetPostalCode(array(
				'deliveryLocationHandle' => $debtor_delivery_location_handle,
				'value' => $meta_value
			  ));
	
		} elseif ($meta_key == 'shipping_city') {
			  reviso_logthis("wooreviso_save_customer_meta_data_to_reviso key: " . $meta_key . " value: " . $meta_value);
			  $client->DeliveryLocation_SetCity (array(
				'deliveryLocationHandle' => $debtor_delivery_location_handle,
				'value' => $meta_value
			  ));
	
		}
		elseif($meta_key == 'shipping_address_1') {
			  reviso_logthis("wooreviso_save_customer_meta_data_to_reviso key: " . $meta_key . " value: " . $meta_value);
			  $adr1 = $order ? $order->shipping_address_1 : $user->get('shipping_address_1');
			  $adr2 = $order ? $order->shipping_address_2 : $user->get('shipping_address_2');
			  $state = $order ? $order->shipping_state : $user->get('shipping_state');
			  $shipping_country = $order ? $order->shipping_country : $user->get('shipping_country');
			  $countries = new WC_Countries();
			  $formatted_state = (isset($state)) ? $countries->states[$shipping_country][$state] : "";
			  $formatted_adr = trim("$adr1\n$adr2\n$formatted_state");
			  reviso_logthis("wooreviso_save_customer_meta_data_to_reviso adr1: " . $adr1 . ", adr2: " . $adr2 . ", state: " . $formatted_state);
			  //reviso_logthis("debtor_delivery_location_handle:");
			  //reviso_logthis($debtor_delivery_location_handle);
			  $client->DeliveryLocation_SetAddress(array(
				'deliveryLocationHandle' => $debtor_delivery_location_handle,
				'value' => $formatted_adr
			  ));
		}
		elseif($meta_key == 'billing_ean_number'){
			if($order != NULL){
				reviso_logthis("wooreviso_save_customer_meta_data_to_reviso key: " . $meta_key . " value: " . $order->billing_ean_number);
				$client->Debtor_SetEan(array(
					'debtorHandle' => $debtor_handle,
					'value' => $order->billing_ean_number
				));
			}
		} else{
			reviso_logthis("wooreviso_save_customer_meta_data_to_reviso unknown meta_key :".$meta_key." meta_value: ".$meta_value);
		}
		return true;
	  } catch (Exception $exception) {
		reviso_logthis("wooreviso_save_customer_meta_data_to_reviso could not update debtor: " . $exception->getMessage());
		$this->debug_client($client);
		reviso_logthis("Could not update debtor.");
		reviso_logthis($exception->getMessage());
		return false;
	  }
	}
	
	/**
     * Sync WooCommerce users to reviso
     *
     * @access public
     * @param 
     * @return array log
     */
	public function sync_contacts(){
		global $wpdb;
		$client = $this->wooreviso_client();
		$sync_log = array();
		if(!$client){
			$sync_log[0] = false;
			array_push($sync_log, array('status' => __('fail', 'wooreviso'), 'msg' => __('Could not create Reviso client, please try again later!', 'wooreviso') ));
			return $sync_log;
		}
		$users = array();
		$orders = array();
		$sync_log[0] = true;
		reviso_logthis("sync_contacts starting...");
		$args = array(
			'role' => 'customer',
		);
		$customers = get_users( $args );
		foreach ($customers as $customer){
			if($customer->get('debtor_number') == ''){
				array_push($users, $customer);
			}
		}
        $unsynced_users = $wpdb->get_results("SELECT * FROM wcr_customers WHERE synced = 0 AND user_id != 0");
		foreach ($unsynced_users as $user){
			array_push($users, new WP_User($user->user_id));
		}
		
		$unsynced_guest_users = $wpdb->get_results("SELECT * FROM wcr_customers WHERE synced = 0 AND user_id = 0");
		foreach ($unsynced_guest_users as $guest_user){
			$unsynced_guest_user_orders = $wpdb->get_results("SELECT * FROM  ".$wpdb->prefix."postmeta WHERE  meta_value = '".$guest_user->email."' ORDER BY post_id DESC");
			foreach ($unsynced_guest_user_orders as $order){
				array_push($orders, new WC_Order($order->post_id));
				break;
			}
		}
		
		//reviso_logthis($users);
		if(!empty($users)){
			foreach ($users as $user) {
				reviso_logthis('sync_contacts User ID: ' . $user->ID);
				if($this->save_customer_to_reviso($client, $user)){
					array_push($sync_log, array('status' => __('success', 'wooreviso'), 'user_id' => $user->ID, 'msg' => __('Customer synced successfully', 'wooreviso') ));
				}else{
					$sync_log[0] = false;
					array_push($sync_log, array('status' => __('fail', 'wooreviso'), 'user_id' => $user->ID, 'msg' => __('Sync failed, please try again later!', 'wooreviso') ));
				}
			}
		}elseif(!empty($orders)){
			foreach ($orders as $order) {
				reviso_logthis('sync_contacts User email (guest user): ' . $order->billing_email);
				if($this->save_customer_to_reviso($client, NULL, $order)){
					array_push($sync_log, array('status' => __('success', 'wooreviso'), 'user_id' => $order->billing_email, 'msg' => __('Guest customer synced successfully', 'wooreviso') ));
				}else{
					$sync_log[0] = false;
					array_push($sync_log, array('status' => __('fail', 'wooreviso'), 'user_id' => $order->billing_email, 'msg' => __('Guest customer sync failed, please try again later!', 'wooreviso') ));
				}
			}
		}else{
			$sync_log[0] = true;
			array_push($sync_log, array('status' => __('success', 'wooreviso'), 'user_id' => '', 'msg' => __('All customers were already synced!', 'wooreviso') ));
		}

		$client->Disconnect();
		reviso_logthis("sync_contacts ending...");
		return $sync_log;
	}
	
	
	/**
     * Sync reviso users to  WooCommerce
     *
     * @access public
     * @param 
     * @return array log
     */
	 
	public function sync_contacts_rw(){
		global $wpdb;
		$client = $this->wooreviso_client();
		$sync_log = array();
		$sync_log[0] = true;
		if(!$client){
			$sync_log[0] = false;
			array_push($sync_log, array('status' => __('fail', 'wooreviso'), 'msg' => __('Could not create Reviso client, please try again later!', 'wooreviso') ));
			return $sync_log;
		}
		
		$debtors = $client->Debtor_GetAll()->Debtor_GetAllResult;
		//reviso_logthis($debtors);
		
		$debtor_handles = array();
		
		foreach($debtors->DebtorHandle as $debtor){
			$debtor_handles[$debtor->Number] = $client->Debtor_GetDebtorGroup(array('debtorHandle' => $debtor))->Debtor_GetDebtorGroupResult;
		}
		//reviso_logthis('debtor_handle_list');
		foreach($debtor_handles as $debtor_number => $group){
			if($group->Number == $this->customer_group){
				$debtor_email = $client->Debtor_GetEmail(array(
					'debtorHandle' => array('Number' => $debtor_number ),
				))->Debtor_GetEmailResult;
				
				$debtor_name = explode(' ', $client->Debtor_GetName(array(
					'debtorHandle' => array('Number' => $debtor_number ),
				))->Debtor_GetNameResult);
				
				//reviso_logthis($debtor_email);
				//reviso_logthis($debtor_name);
				
				$user = get_user_by( 'email', $debtor_email );
								
				if($wpdb->query('SELECT user_id FROM '.$wpdb->prefix.'usermeta WHERE meta_key = "debtor_number" AND meta_value = '.$debtor_number)){
					reviso_logthis('update customer meta: '.$debtor_number);
					$userdata = array(
						'ID' => $user->ID,
						//'user_login' => strtolower($debtor_name[0]),
						'first_name' => $debtor_name[0],
						'last_name' => $debtor_name[1],
						'user_email' => $debtor_email,
						//'role' => 'customer'
					);
					
					$customer = wp_update_user( $userdata );
					//reviso_logthis($customer);
					if ( ! is_wp_error( $customer ) ) {
						reviso_logthis("User updated : ". $customer ." for debtor_number: ". $debtor_number);
						$sync_log[0] = true;
						update_user_meta($customer, 'debtor_number', $debtor_number);
						array_push($sync_log, array('status' => __('success', 'wooreviso'), 'user_id' => $customer, 'msg' => __('User '.$debtor_name[0].' '.$debtor_name[1].' with customer role updated!', 'wooreviso') ));	
					}else{
						reviso_logthis($customer);
					}
				}else{					
					if($user){
						reviso_logthis('update customer: '.$debtor_number);
						$userdata = array(
							'ID' => $user->ID,
							//'user_login' => strtolower($debtor_name[0]),
							'first_name' => $debtor_name[0],
							'last_name' => isset($debtor_name[1])? $debtor_name[1] : '',
							'user_email' => $debtor_email,
							//'role' => 'customer'
						);
						
						$customer = wp_update_user( $userdata );
						//reviso_logthis($customer);
						if ( ! is_wp_error( $customer ) ) {
							reviso_logthis("User updated : ". $customer ." for debtor_number: ". $debtor_number);
							$sync_log[0] = true;
							update_user_meta($customer, 'debtor_number', $debtor_number);
							array_push($sync_log, array('status' => __('success', 'wooreviso'), 'user_id' => $customer, 'msg' => __('User '.$debtor_name[0].' with customer role updated!', 'wooreviso') ));	
						}else{
							reviso_logthis($customer);
						}
					}else{
						reviso_logthis('add new customer: '.$debtor_number);
						$userdata = array(
							'user_login' => strtolower($debtor_name[0].$debtor_number),
							'first_name' => $debtor_name[0],
							'last_name' => $debtor_name[1],
							'user_email' => $debtor_email,
							'role' => 'customer'
						);
						
						$customer = wp_insert_user( $userdata );
						//reviso_logthis($customer);
						if ( ! is_wp_error( $customer ) ) {
							reviso_logthis("User created : ". $customer ." for debtor_number: ". $debtor_number);
							$sync_log[0] = true;
							update_user_meta($customer, 'debtor_number', $debtor_number);
							array_push($sync_log, array('status' => __('success', 'wooreviso'), 'user_id' => $customer, 'msg' => __('New user '.$debtor_name[0].' '.$debtor_name[1].' with customer role created!', 'wooreviso') ));	
							if(!$wpdb->query ("SELECT * FROM wcr_customers WHERE email='".$debtor_email."'")){
								$wpdb->insert ("wcr_customers", array('user_id' => $customer, 'customer_number' => $debtor_number, 'email' => $debtor_email, 'synced' => 1), array('%d', '%s', '%s', '%d'));
							}
						}else{
							if(!$wpdb->query ("SELECT * FROM wcr_customers WHERE email='".$debtor_email."'")){
								$wpdb->insert ("wcr_customers", array('user_id' => 0, 'customer_number' => $debtor_number, 'email' => $debtor_email, 'synced' => 0), array('%d', '%s', '%s', '%d'));
							}
						}
					}								
				}
			}else{
				//$sync_log[0] = false;
				//array_push($sync_log, array('status' => __('fail', 'wooreviso'), 'user_id' => NULL, 'msg' => __('Customer group doesn\'t match the Customer group in settings!', 'wooreviso') ));
				reviso_logthis("Customer group doesn't match the Customer group in settings: ".$customer);
			}
		}
		return $sync_log;
	}
	
	
	/**
     * Save WooCommerce Shipping to reviso
     *
     * @access public
     * @param shipping settings array
     * @return bool
     */
	
	public function save_shipping_to_reviso($shippingMethodObject, SoapClient &$client){
		if(!$client){
			return false;
		}
		
		reviso_logthis("save_shipping_to_reviso syncing shipping ID - sku: " . $shippingMethodObject->id . " title: " . $shippingMethodObject->title);
		try	{
			//reviso_logthis($shippingMethodObject);
			$shippingID = $shippingMethodObject->id;
			
			//Added to eleminate shipping ID/e-conomnic product ID length more than 25. This same check should be added when shipping method is added as orderline or invoice line.
			if(strlen($shippingID) > 25){
				$shippingID = substr($shippingID, 0, 24);
			}
			//$shippingTitle = $shippingMethodObject->title;
			reviso_logthis("save_shipping_to_reviso - trying to find shipping in reviso");
			
			// Find product by number
			$product_handle = $client->Product_FindByNumber(array(
			'number' => $shippingID))->Product_FindByNumberResult;
			
			// Create product with name
			if (!$product_handle) {
				$productGroupHandle = $client->ProductGroup_FindByNumber(array(
				'number' => $this->shipping_group))->ProductGroup_FindByNumberResult;
				$product_handle = $client->Product_Create(array(
				'number' => $shippingID,
				'productGroupHandle' => $productGroupHandle,
				'name' => $shippingMethodObject->title))->Product_CreateResult;
				reviso_logthis("save_shipping_to_reviso - shipping created:" . $shippingMethodObject->title);
			}else{
				$client->Product_SetProductGroup(array(
					'productHandle' => $product_handle,
					'valueHandle' => array('Number' => $this->shipping_group)
				 ));
			}
			
			// Get product data
			$product_data = $client->Product_GetData(array(
			'entityHandle' => $product_handle))->Product_GetDataResult;
			
			if(isset($shippingMethodObject->settings['additional_costs']) && $shippingMethodObject->settings['additional_costs'] > 0){
				$shippingCost = $shippingMethodObject->settings['cost_per_order'] + $shippingMethodObject->settings['cost'] + $shippingMethodObject->settings['additional_costs'];
				if($shippingMethodObject->fee >= $shippingMethodObject->minimum_fee){
					$shippingCost = $shippingCost + $shippingMethodObject->fee;
				}else{
					$shippingCost = $shippingCost + $shippingMethodObject->minimum_fee;
				}
			}else{
				$shippingCost = $shippingMethodObject->settings['cost_per_order'] + $shippingMethodObject->settings['cost'];
				if($shippingMethodObject->fee >= $shippingMethodObject->minimum_fee){
					$shippingCost = $shippingCost + $shippingMethodObject->fee;
				}else{
					$shippingCost = $shippingCost + $shippingMethodObject->minimum_fee;
				}
			}
			
			
			
			$Company = $client->Company_Get()->Company_GetResult;
			$Company_GetBaseCurrency = $client->Company_GetBaseCurrency(array(
				'companyHandle' => $Company
			))->Company_GetBaseCurrencyResult;
			reviso_logthis('Company_GetBaseCurrency:');
			reviso_logthis($Company_GetBaseCurrency);
			
			if($Company_GetBaseCurrency->Code == get_option('woocommerce_currency')){
				$shippingCost1 = $shippingCost;
			}else{
				$shippingCost1 = $client->Product_GetSalesPrice(array('productHandle'  => $product_handle))->Product_GetSalesPriceResult;
			}
			
			// Update product data
			$client->Product_UpdateFromData(array(
			'data' => (object)array(
			'Handle' => $product_data->Handle,
			'Number' => $product_data->Number,
			'ProductGroupHandle' => $product_data->ProductGroupHandle,
			'Name' => $shippingMethodObject->title,
			'Description' => $shippingMethodObject->title,
			'BarCode' => "",
			'SalesPrice' => $shippingCost1 > 0 ? $shippingCost1 : 0.0,
			'CostPrice' => (isset($product_data->CostPrice) ? $product_data->CostPrice : 0.0),
			'RecommendedPrice' => $product_data->RecommendedPrice,
			/*'UnitHandle' => (object)array(
			'Number' => 1
			),*/
			'IsAccessible' => true,
			'Volume' => $product_data->Volume,
			//'DepartmentHandle' => $product_data->DepartmentHandle,
			//'DistributionKeyHandle' => $product_data->DistrubutionKeyHandle,
			'InStock' => $product_data->InStock,
			'OnOrder' => $product_data->OnOrder,
			'Ordered' => $product_data->Ordered,
			'Available' => $product_data->Available)))->Product_UpdateFromDataResult;
			
			//Added in version 1.9.9.9.1 by Alvin for updaing the product price in store currency settings.
			if($Company_GetBaseCurrency->Code != get_option('woocommerce_currency')){
				$productPriceHandle = $client->ProductPrice_FindByProductAndCurrency(array(
					'productHandle'  => $product_handle,
					'currencyHandle' => array('Code' => get_option('woocommerce_currency')),
				))->ProductPrice_FindByProductAndCurrencyResult;
				
				if(isset($productPriceHandle) && !empty($productPriceHandle)){
					reviso_logthis('productPriceHandle:');
					reviso_logthis($productPriceHandle);
					$client->ProductPrice_SetPrice(array(
						'productPriceHandle'  => $productPriceHandle,
						'value'			 	  => $shippingCost > 0 ? $shippingCost : 0.0
					));
				}else{
					reviso_logthis('productPriceHandle not found, creating product price.');
					$client->ProductPrice_Create(array(
						'productHandle'  => $product_handle,
						'currencyHandle' => array('Code' => get_option('woocommerce_currency')),
						'price'			 => $shippingCost > 0 ? $shippingCost : 0.0
					));
				}
			}
			
			reviso_logthis("save_shipping_to_reviso - product updated : " . $shippingMethodObject->title);
			return true;
		} catch (Exception $exception) {
			reviso_logthis("save_shipping_to_reviso could not create product: " . $exception->getMessage());
			$this->debug_client($client);
			reviso_logthis($exception->getMessage);
			return false;
		}
	}
	
	
	/**
     * Sync WooCommerce shipping as products to reviso 
     *
     * @access public
     * @param 
     * @return array log
     */
	public function sync_shippings(){
		$client = $this->wooreviso_client();
		if(!$client){
			$sync_log[0] = false;
			array_push($sync_log, array('status' => __('fail', 'wooreviso'), 'msg' => __('Could not create Reviso client, please try again later!', 'wooreviso') ));
			return $sync_log;
		}
		$sync_log = array();
		$sync_log[0] = true;
		$shipping = new WC_Shipping();
		$shippingMethods = $shipping->load_shipping_methods();
		
		//Added for WC_Shipping_Table_Rate support 1.9.9.3
		/*$WC_Shipping_Table_Rate = new WC_Shipping_Table_Rate();
		$shippingMethods['table_rate'] = $WC_Shipping_Table_Rate;*/
		//Added for WC_Shipping_Table_Rate support 1.9.9.3
		
		reviso_logthis("sync_shippings starting...");
		//reviso_logthis($shippingMethods);
		foreach ($shippingMethods as $shippingMethod => $shippingMethodObject) {
			reviso_logthis('Shipping ID: '. $shippingMethodObject->id );
			reviso_logthis('Shipping Title: '. $shippingMethodObject->title);
			$title = $shippingMethodObject->title;
			if($this->save_shipping_to_reviso($shippingMethodObject, $client)){
				array_push($sync_log, array('status' => __('success', 'wooreviso'), 'sku' => $shippingMethodObject->id, 'name' => $shippingMethodObject->title, 'msg' => __('Shipping synced successfully', 'wooreviso') ));
			}else{
				$sync_log[0] = false;
				array_push($sync_log, array('status' => __('fail', 'wooreviso'), 'sku' => $shippingMethodObject->id, 'name' => $shippingMethodObject->title, 'msg' => __('Shipping not synced, please try again!', 'wooreviso') ));
			}
		}
		
		$client->Disconnect();
		reviso_logthis("sync_shippings ending...");
		return $sync_log;
	}
	
	
	/**
     * Save WooCommerce Coupon to reviso
     *
     * @access public
     * @param coupon object
     * @return bool
     */
	
	public function save_coupon_to_reviso($coupon, SoapClient &$client){
		if(!$client){
			return false;
		}
		$couponID = $coupon->post_title;
			
		//Added to eleminate shipping ID/e-conomnic product ID length more than 25. This same check should be added when shipping method is added as orderline or invoice line.
		if(strlen($couponID) > 25){
			$couponID = substr($couponID, 0, 24);
		}
	
		reviso_logthis("save_coupon_to_reviso syncing shipping ID: " . $coupon->ID . " title: " . $coupon->post_title);
		try	{			
			reviso_logthis("save_coupon_to_reviso - trying to find shipping in reviso");
			
			// Find product by number
			$product_handle = $client->Product_FindByNumber(array(
			'number' => $couponID))->Product_FindByNumberResult;
			
			// Create product with name
			if (!$product_handle) {
				$productGroupHandle = $client->ProductGroup_FindByNumber(array(
				'number' => $this->coupon_group))->ProductGroup_FindByNumberResult;
				$product_handle = $client->Product_Create(array(
				'number' => $couponID,
				'productGroupHandle' => $productGroupHandle,
				'name' => $coupon->post_title))->Product_CreateResult;
				reviso_logthis("save_coupon_to_reviso - coupon created:" . $coupon->post_title);
			}else{
				$client->Product_SetProductGroup(array(
					'productHandle' => $product_handle,
					'valueHandle' => array('Number' => $this->coupon_group)
				 ));
			}
			
			// Get product data
			$product_data = $client->Product_GetData(array(
			'entityHandle' => $product_handle))->Product_GetDataResult;
			
			$couponCost = 0.0;
			
			
			// Update product data
			$client->Product_UpdateFromData(array(
			'data' => (object)array(
			'Handle' => $product_data->Handle,
			'Number' => $product_data->Number,
			'ProductGroupHandle' => $product_data->ProductGroupHandle,
			'Name' => $coupon->post_title,
			'Description' => $coupon->post_excerpt,
			'BarCode' => "",
			'SalesPrice' => $couponCost,
			'CostPrice' => (isset($product_data->CostPrice) ? $product_data->CostPrice : 0.0),
			'RecommendedPrice' => $product_data->RecommendedPrice,
			/*'UnitHandle' => (object)array(
			'Number' => 1
			),*/
			'IsAccessible' => true,
			'Volume' => $product_data->Volume,
			//'DepartmentHandle' => $product_data->DepartmentHandle,
			//'DistributionKeyHandle' => $product_data->DistrubutionKeyHandle,
			'InStock' => $product_data->InStock,
			'OnOrder' => $product_data->OnOrder,
			'Ordered' => $product_data->Ordered,
			'Available' => $product_data->Available)))->Product_UpdateFromDataResult;
			
			reviso_logthis("save_coupon_to_reviso - product updated : " . $coupon->post_title);
			return true;
		} catch (Exception $exception) {
			reviso_logthis("save_coupon_to_reviso could not create coupon: " . $exception->getMessage());
			$this->debug_client($client);
			reviso_logthis($exception->getMessage);
			return false;
		}
	}
	
	
	
	/**
     * Sync WooCommerce Coupons as products to reviso Added in version 1.9.9.4
     *
     * @access public
     * @param 
     * @return array log
     */
	public function sync_coupons(){
		$client = $this->wooreviso_client();
		if(!$client){
			$sync_log[0] = false;
			array_push($sync_log, array('status' => __('fail', 'wooreviso'), 'msg' => __('Could not create Reviso client, please try again later!', 'wooreviso') ));
			return $sync_log;
		}
		$sync_log = array();
		$sync_log[0] = true;
		$args = array(
			'posts_per_page'   => -1,
			'orderby'          => 'title',
			'order'            => 'asc',
			'post_type'        => 'shop_coupon',
			'post_status'      => 'publish',
		);	
		$coupons = get_posts( $args );
		
		foreach ($coupons as $coupon) {
			//reviso_logthis($coupon);
			//reviso_logthis('Coupon ID: '. $coupon->ID );
			reviso_logthis('Coupon Title: '. $coupon->post_title);
			$title = $coupon->post_title;
			if($this->save_coupon_to_reviso($coupon, $client)){
				array_push($sync_log, array('status' => __('success', 'wooreviso'), 'sku' => $coupon->ID, 'name' => $coupon->post_title, 'msg' => __('Coupon synced successfully', 'wooreviso') ));
			}else{
				$sync_log[0] = false;
				array_push($sync_log, array('status' => __('fail', 'wooreviso'), 'sku' => $coupon->ID, 'name' => $coupon->post_title, 'msg' => __('Coupon not synced, please try again!', 'wooreviso') ));
			}
		}
		
		$client->Disconnect();
		reviso_logthis("sync_coupons ending...");
		return $sync_log;
	}
	
	
	/**
     * Send inovice of an order from reviso to customers
     *
     * @access public
     * @param user object, order object, reviso client
     * @return boolean
     */
	public function send_invoice_reviso(SoapClient &$client, WC_Order $order = NULL){
		try{
			$current_invoice_handle = $client->CurrentInvoice_FindByOtherReference(array(
				'otherReference' => $this->order_reference_prefix.$order->id
			))->CurrentInvoice_FindByOtherReferenceResult;
			
			reviso_logthis('send_invoice_reviso CurrentInvoiceHandleId:'. $current_invoice_handle->CurrentInvoiceHandle->Id);
			reviso_logthis($current_invoice_handle);
			
			reviso_logthis('send_invoice_reviso book invoice');
			
			$invoice = $client->CurrentInvoice_Book(array(
				'currentInvoiceHandle' => $current_invoice_handle->CurrentInvoiceHandle
			))->CurrentInvoice_BookResult;
			
			reviso_logthis('send_invoice_reviso invoice: '. $invoice->Number);
			reviso_logthis($invoice);
			
			$pdf_invoice = $client->Invoice_GetPdf(array(
				'invoiceHandle' => $invoice
			))->Invoice_GetPdfResult;
			
			//reviso_logthis('send_invoice_reviso pdf_base64_data:');
			//reviso_logthis($pdf_invoice);
			
			reviso_logthis('send_invoice_reviso Creating PDF invoice');
			$filename = 'ord_'.$order->id.'-inv_'.$invoice->Number.'.pdf';
			$path = dirname(__FILE__).'/invoices/';
			$file = $path.$filename;
			if(!file_exists($file)){
				$fileobject = fopen($file, 'w');
			}
			fwrite ($fileobject, $pdf_invoice);
			fclose ($fileobject);
			reviso_logthis('send_invoice_reviso Invoice '.$file.' is created');
			
			$to = $order->billing_email;
			$orderDate = explode(' ', $order->order_date);
			$subject = get_bloginfo( $name ).' - Invoice no. '.$invoice->Number.' - '.$orderDate[0];
			$body = '';
			/*$random_hash = md5(date('r', time())); 
			//$headers = 'Content-Type: text/html; charset=UTF-8';
			//$headers. = 'From: '.get_bloginfo( 'name' ).' <'.get_bloginfo( 'admin_email' ).'>';
			
			$headers = "MIME-Version: 1.0" . "\r\n";
			//$headers .= "Content-Type: text/html; charset=UTF-8" . "\r\n";
			$headers .= "Content-Type: multipart/mixed; boundary=\"PHP-mixed-".$random_hash."\""; 
			$headers .= "From: ".get_bloginfo( 'name' )." <"..">"."\r\n";
		
			//reviso_logthis('To: '.$to.'/n Subject: '.$subject.'/n Headers: '.$headers);*/
			if($order->payment_method == 'reviso-invoice'){
				reviso_logthis('send_invoice_reviso calling mail_attachment');
				return $this->mail_attachment($filename, $path, $to, get_bloginfo( 'admin_email' ), get_bloginfo( 'name' ), get_bloginfo( 'admin_email' ), $subject, $body );
			}
			return true;
		}catch (Exception $exception) {
			reviso_logthis($exception->getMessage);
			$this->debug_client($client);
			return false;
		}
	}
	
	public function mail_attachment($filename, $path, $mailto, $from_mail, $from_name, $replyto, $subject, $message) {
		$file = $path.$filename;
		$file_size = filesize($file);
		//reviso_logthis('file_size: '.$file_size);
		$handle = fopen($file, "r");
		$content = fread($handle, $file_size);
		//reviso_logthis('content: '.$content);
		fclose($handle);
		$content = chunk_split(base64_encode($content));
		$uid = md5(uniqid(time()));
		$name = basename($file);
		$header = "From: ".$from_name." <".$from_mail.">\r\n";
		$header .= "Reply-To: ".$replyto."\r\n";
		$header .= "MIME-Version: 1.0\r\n";
		$header .= "Content-Type: multipart/mixed; boundary=\"".$uid."\"\r\n\r\n";
		$header .= "This is a multi-part message in MIME format.\r\n";
		$header .= "--".$uid."\r\n";
		$header .= "Content-type:text/plain; charset=iso-8859-1\r\n";
		$header .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
		$header .= $message."\r\n\r\n";
		$header .= "--".$uid."\r\n";
		$header .= "Content-Type: application/octet-stream; name=\"".$filename."\"\r\n"; // use different content types here
		$header .= "Content-Transfer-Encoding: base64\r\n";
		$header .= "Content-Disposition: attachment; filename=\"".$filename."\"\r\n\r\n";
		$header .= $content."\r\n\r\n";
		$header .= "--".$uid."--";
		reviso_logthis('mail_attachment sending mail');
		return mail($mailto, $subject, "", $header);
	}

}
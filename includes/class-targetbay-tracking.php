<?php
/**
 * TargetBay Reviews and Ecommerce Emails Targetbay_tracking.
 *
 * @since   0.1.0
 * @package TargetBay_Reviews_and_Ecommerce_Emails
 */

use GuzzleHttp\Client;

/**
 * TargetBay Reviews and Ecommerce Emails Targetbay_tracking.
 *
 * @since 0.1.0
 */
class TBWC_Targetbay_Tracking
{
	/**
	 * Parent plugin class.
	 *
	 * @since 0.1.0
	 *
	 * @var   TargetBay_Reviews_and_Ecommerce_Emails
	 */
	protected $apiKey;

	protected $indexName;

	protected $targetBay;

	protected $tokenTb;

    protected $userName;

    protected $userMail;

    protected $userId;

    protected $sessionId;

    protected $tbPath;

    protected $utmToken;

    protected $utmSource;

    protected $utmMedium;

    protected $tbProReview;

    protected $tbBulkReview;

    protected $tbOrderId;

    protected $authToken;

	/**
	 * Constructor.
	 *
	 * @since  0.1.0
	 *
	 * @param  TargetBay_Reviews_and_Ecommerce_Emails $plugin Main plugin object.
	 */
	public function __construct($plugin)
	{
		try {
			$wcSessionNew = new WC_Session_Handler();
			$utmCheck = isset($_GET['utm_source']) ? $_GET['utm_source'] : '';
			$utmTokenCheck = isset($_GET['utm_token']) ? $_GET['utm_token'] : '';
			$utmMediumCheck = isset($_GET['utm_medium']) ? $_GET['utm_medium'] : '';
			$this->utmToken = isset($_COOKIE['utm_token']) ? $_COOKIE['utm_token'] : '';
			$this->utmSource =isset($_COOKIE['utm_source']) ? $_COOKIE['utm_source'] : '';
			$this->utmMedium =isset($_COOKIE['utm_medium']) ? $_COOKIE['utm_medium'] : '';
			if (trim($utmCheck) !== '') {
				if(isset($_COOKIE['utm_source'])){
					unset($_COOKIE['utm_source']);
				}
				setcookie('utm_source', $utmCheck, time() + (86400 * 30), '/', '.'.$_SERVER['HTTP_HOST']);
				$this->utmSource = $utmCheck;
			}
			if (trim($utmTokenCheck) !== '') {
				if(isset($_COOKIE['utm_token'])){
					unset($_COOKIE['utm_token']);
				}
				setcookie('utm_token', $utmTokenCheck, time() + (86400 * 30), '/', '.'.$_SERVER['HTTP_HOST']);
				$this->utmToken = $utmTokenCheck;
			}
			if (trim($utmMediumCheck) !== '') {
				if(isset($_COOKIE['utm_medium'])){
					unset($_COOKIE['utm_medium']);
				}
		
				setcookie('utm_medium', $utmMediumCheck, time() + (86400 * 30), '/', '.'.$_SERVER['HTTP_HOST']);
				$this->utmMedium = $utmMediumCheck;
			}
			$settingsDetails = get_option('targetbay_settings', $this->wc_targetbay_get_default_settings());
			if (isset($settingsDetails) && count($settingsDetails) > 0) {
				if(isset($settingsDetails['tb_api_secret']) && $settingsDetails['tb_api_secret'] !== '') {
					$paramsArray = explode('&', base64_decode($settingsDetails['tb_api_secret']));
					$apiToken = explode('=', $paramsArray[0]);
					$indexName = explode('=', $paramsArray[1]);
					$this->authToken = $settingsDetails['tb_api_secret'];
					$this->apiKey = $indexName[1];
					$this->indexName = $apiToken[1];
					$this->tbProReview = isset($settingsDetails['tb_pro_review']) ? $settingsDetails['tb_pro_review'] : '';
					$this->tbBulkReview = isset($settingsDetails['tb_bulk_review']) ? $settingsDetails['tb_bulk_review'] : '';

					$pathNew = 'app';
					if ($settingsDetails['tb_server'] === 'dev') {
						$pathNew = 'dev';
					} elseif ($settingsDetails['tb_server'] === 'stage') {
						$pathNew = 'stage';
					}
					$this->tbPath = $pathNew;
					$this->plugin = $plugin;
					$this->hooks();
					$userLogin =  isset($_COOKIE['tb_user_login']) ? $_COOKIE['tb_user_login'] : '';
					$userSession = isset($_COOKIE['tb_user_session']) ? $_COOKIE['tb_user_session'] : '';
					$sessionIdTb = (string) mt_rand(1000000000, 9999999999);
					if (is_user_logged_in()) {
						$current_user = wp_get_current_user();
						$this->userName = $current_user->user_login;
						$this->userMail = $current_user->user_email;
						$this->userId = $current_user->ID;
						$this->sessionId = $current_user->ID;
						$afterSession = $userSession;
						$userDataCreated = 1;
						if (($userLogin == '' || $userSession == '')) {
							setcookie('tb_user_login', 1, time() + (86400 * 30), '/');
							setcookie('tb_user_session', $sessionIdTb, time() + (86400 * 30), '/');
							$afterSession = $sessionIdTb;
						}

						$inputSrc = '_un=' . $this->userName;
						$inputSrc .= '&_uid=' . $this->userId;
						$inputSrc .= '&_uem=' . $this->userMail;
						$inputSrc .= '&_utid=' . $this->sessionId;
						$inputSrc .= '&_usid=' . $this->userId;
						$inputSrc .= '&_uc=1&_ulogin=' . $userDataCreated;
						$inputSrc .= '&_uasid=' . $afterSession;

						$this->tb_set_cookie($inputSrc);
					} else {
						$tbSessionId = '';
							if(isset($_COOKIE['targetbay_session_id'])){
							$tbSessionId = $_COOKIE['targetbay_session_id'];
						}
						$this->userName = 'anonymous';
						$this->userMail = '';
						$this->userId = $tbSessionId;
						$this->sessionId = $tbSessionId;
						$userDataCreated = '';
						$afterSession = $userSession;

						if (($userLogin == 1 || $userSession == '')) {
							$this->sessionId = $sessionIdTb;
							$this->userId = $sessionIdTb;
							setcookie('tb_user_login', '', time() + (86400 * 30), '/');
							setcookie('tb_user_session', $sessionIdTb, time() + (86400 * 30), '/');
							$afterSession = $sessionIdTb;
						}
					}
									// setcookie('targetbay_session_id', $this->userId, time() + (86400 * 30), '/', '.'.$_SERVER['HTTP_HOST']);
				}
			}
		} catch (\Exception $e) {
			$errorMsg = ', Message: ' . $e->getMessage();
			$errorMsg .= ', Line: ' . $e->getLine();
		}
	}

	/**
	 * Encode public data.
	 *
	 * @param $plaintext
	 */
	public function tb_set_cookie($plaintext)
	{	
	  setcookie('tb_fetch_points', base64_encode($plaintext), time() + (86400 * 30), '/', '.'.$_SERVER['HTTP_HOST']);
	}

	public function tb_get_cookie($checkKey){
        $uid_slice = '';
	    if(isset($_COOKIE['tb_fetch_points'])){
            $cookie_val = $_COOKIE['tb_fetch_points'];
            $cookie_decode_val = base64_decode($cookie_val);
            parse_str($cookie_decode_val, $TbFetchArray);

            if(isset($TbFetchArray[$checkKey])){
            	return $TbFetchArray[$checkKey];
        	}
        }
    }

	/**
	 * Decode public data.
	 *
	 * @param $cipher_text_base64
	 * @return bool|string
	 */
	/*public function tb_data_decrypt($cipher_text_base64)
	{
		return base64_decode($cipher_text_base64);
	}*/

	/**
	 * Initiate our hooks.
	 *
	 * @since  0.1.0
	 */
	public function hooks()
	{
		try {
			add_action('wp_logout', array($this, 'tb_logout'));
			add_action('wp_login', array($this, 'tb_login'), 10, 2);
			if (!is_admin()) {
				add_action('woocommerce_add_to_cart', array($this, 'tb_action_woocommerce_add_to_cart'), 10, 2);
				add_action('woocommerce_ajax_added_to_cart', array($this, 'tb_action_woocommerce_add_to_cart'), 10, 2);
				add_action('woocommerce_update_cart_action_cart_updated', array($this, 'tb_action_woocommerce_update_to_cart'), 10, 2);
				add_action('woocommerce_cart_item_removed', array($this, 'tb_action_woocommerce_cart_item_removed'), 10, 2);

				//Product reviews start.
				if ($this->tbProReview === 'enable') {
					add_action('woocommerce_after_single_product_summary', array($this, 'tb_single_product_closing_div'), 10, 2);
					add_action('woocommerce_single_product_summary', array($this, 'tb_action_after_single_product_title'), 10, 2);
				}
				//Product reviews end.

				//Bulk reviews start.
				if ($this->tbBulkReview === 'enable') {
					add_action('woocommerce_loop_add_to_cart_args', array($this, 'tb_action_loop_product'), 10, 2);
				}
				//Bulk reviews end.

				add_action('woocommerce_checkout_before_customer_details', array($this, 'tb_checkout_before_customer_details'), 10, 2);
				add_action('woocommerce_thankyou', array($this, 'tb_action_woocommerce_thankyou'), 10, 2);
			}

			add_action('wp_footer', array($this, 'tb_add_script'), 10, 2);
			add_action('user_register', array($this, 'tb_user_register'), 10, 2);
			add_action('woocommerce_order_status_completed', array($this, 'tb_order_completed'), 10, 2);
			add_action('woocommerce_order_status_cancelled', array($this, 'tb_order_cancelled'), 10, 2);
			add_action('woocommerce_order_status_refunded', array($this, 'tb_order_refunded'), 10, 2);
			add_action('woocommerce_order_status_processing', array($this, 'tb_order_processing'), 10, 2);
			add_action('woocommerce_order_status_on-hold', array($this, 'tb_order_on_hold'), 10, 2);
			add_action('transition_post_status', array($this, 'tb_save_post_product'), 10, 3);
		} catch (\Exception $e) {
			$errorMsg = ', Message: ' . $e->getMessage();
			$errorMsg .= ', Line: ' . $e->getLine();
		}
	}

	/**
	 * User register event.
	 *
	 * @param $user_id
	 */
	public function tb_user_register($user_id)
	{
		try {	
			$dataList['user_name'] = isset($_POST['display_name']) ? $_POST['display_name'] : '';
			if ($dataList['user_name'] === '') {
				$dataList['user_name'] = isset($_POST['first_name']) ? $_POST['first_name'] : '';
			}

			if ($dataList['user_name'] === '') {
				$dataList['user_name'] = isset($_POST['email']) ? $_POST['email'] : '';
			}

			$dataList['user_mail'] = $_POST['email'];
			$dataList['firstname'] = isset($_POST['first_name']) ? $_POST['first_name'] : '';
			$dataList['lastname'] = isset($_POST['last_name']) ? $_POST['last_name'] : '';
			$dataList['session_id'] = $user_id;
			$dataList['user_id'] = $user_id;
			$dataList['account_created'] = date('Y-m-d');
			$dataList['timestamp'] = strtotime(date('Y-m-d'));
			$dataList['previous_session_id'] = $this->sessionId;
			$dataList['ip_address'] = $this->get_user_ip();
			$dataList['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
			$this->tb_send_data($dataList, 'customer-created');

			$dataListLogin['user_name'] = isset($dataList['user_name']) ? $dataList['user_name'] : $dataList['user_mail'];
			$dataListLogin['user_mail'] = $dataList['user_mail'];
			$dataListLogin['session_id'] = $user_id;
			$dataListLogin['user_id'] = $user_id;
			$dataListLogin['login_date'] = date('Y-m-d');
			$dataListLogin['timestamp'] = strtotime(date('Y-m-d'));
			$dataListLogin['previous_session_id'] = $this->sessionId;
			$dataListLogin['ip_address'] = $this->get_user_ip();
			$dataListLogin['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
			$this->tb_send_data($dataListLogin, 'login');
		} catch (\Exception $e) {
			$errorMsg = ', Message: ' . $e->getMessage();
			$errorMsg .= ', Line: ' . $e->getLine();
		}
	}

	/**
	 * TargetBay bulk reviews placeholder.
	 */
	public function tb_action_loop_product()
	{
		global $product;

		echo '<div class="targetbay_star_container" id="' . $product->get_id() . '"></div>';
	}

	/**
	 * TargetBay placeholder for single product.
	 */
	public function tb_action_after_single_product_title()
	{
		echo '<div class="product-name"></div>';
	}

	/**
	 * TargetBay reviews placeholder for single product.
	 */
	public function tb_single_product_closing_div()
	{
		echo '<div id="targetbay_reviews"></div>';
	}

	/**
	 * TargetBay page tracking.
	 */
	public function tb_track_views()
	{
		try {
			if (is_home() || is_archive() || is_category() || is_single() || is_page() || is_search()) {
				$dataList['page_type'] = 'pages';
				$this->insertTracking($dataList);
			}

			if (is_product_category()) {
				$dataList['page_type'] = 'product-category';
				$this->insertTracking($dataList);
			}

			if (is_product()) {
				$dataList['user_name'] = $this->userName;
				$dataList['user_mail'] = $this->userMail;
				$dataList['session_id'] = $this->sessionId;
				$dataList['user_id'] = $this->userId;
				$product = wc_get_product();
				$dataList['product_id'] = $product->get_id();
				$dataList['product_name'] = $product->get_title();
				$dataList['page_url'] = $product->get_permalink();
				$dataList['ip_address'] = $this->get_user_ip();
				$dataList['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
				$dataList['referrer'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
				$dataList['utm_sources'] = $this->utmSource;
				$dataList['utm_token'] = $this->utmToken;
				$dataList['utm_medium'] = $this->utmMedium;
				$imgDetails = get_the_post_thumbnail_url($product->get_id());
				if ($imgDetails === '') {
					$imgDetails = 'https://' . $this->tbPath . '.targetbay.com/images/no-image.jpg';
				}
				$dataList['productimg'] = $imgDetails;
				$this->tb_send_data($dataList, 'product-view');
			}
		} catch (\Exception $e) {
			$errorMsg = ', Message: ' . $e->getMessage();
			$errorMsg .= ', Line: ' . $e->getLine();
		}
	}

	/**
	 * @param $dataList
	 */
	public function insertTracking($dataList)
	{
		try {
			$data['page_type'] = $dataList['page_type'];
			$data['tbcustomer_name'] = $this->userName;
			$data['tbcustomer_email'] = $this->userMail;
			$data['session_id'] = $this->sessionId;
			$data['tbcustomer_id'] = $this->userId;
			$data['tb_pageurl'] = get_page_link();
			$data['tbpage_title'] = get_the_title();
			$data['tb_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
			$data['referrer'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
			$data['utm_sources'] = $this->utmSource;
			$data['utm_token'] = $this->utmToken;
			$data['utm_medium'] = $this->utmMedium;
			$this->tb_send_data($data, 'page-visit');
		} catch (\Exception $e) {
			$errorMsg = ', Message: ' . $e->getMessage();
			$errorMsg .= ', Line: ' . $e->getLine();
		}
	}

	/**
	 * @param $cartItem
	 * @param $cartItemKey
	 */
	public function tb_action_woocommerce_add_to_cart($cartItem, $cartItemKey)
	{
		try {
		$dataList = [];
		$arr = [];
		$updateData = false;
			if (count(WC()->cart->get_cart()) > 0) {
				foreach (WC()->cart->get_cart() as $cart_item) {
					if ($cart_item['key'] === $cartItem && $cart_item['product_id'] === $cartItemKey) {
						$productDetails = wc_get_product($cart_item['data']->get_id());
						$productCats = wp_get_post_terms($cart_item['data']->get_id(), 'product_cat', array('fields' => 'names'));
						$imgDetails = get_the_post_thumbnail_url($cart_item['product_id']);
						$priceDetails = null !== get_post_meta($cart_item['product_id'], '_price', true) && '' !== get_post_meta($cart_item['product_id'], '_price', true) ? get_post_meta($cart_item['product_id'], '_price', true) : '';
						if (isset($_COOKIE['tb_old_qty_'. $cart_item['product_id']])) {
							$updateData = true;
							$dataListCart['order_id'] = $cart_item['key'];
							$dataListCart['product_id'] = $cart_item['product_id'];
							$dataListCart['product_sku'] = $productDetails->get_sku();
							$dataListCart['product_name'] = $productDetails->get_title();
							$dataListCart['price'] = $priceDetails;
							$dataListCart['special_price'] = $productDetails->get_sale_price();
							$dataListCart['productimg'] = $imgDetails;
							$dataListCart['category_name'] = implode(',', $productCats);
							$dataListCart['category'] = '';
							$oldQty = $_COOKIE['tb_old_qty_'. $cart_item['product_id']];
							$dataListCart['old_quantity'] = isset($oldQty) ? $oldQty : $cart_item['quantity'];
							if($dataListCart['old_quantity'] == $cart_item['quantity']){
								$cart_item['quantity'] = $dataListCart['old_quantity'] + 1;

							}
							$dataListCart['new_quantity'] = $cart_item['quantity'];
							$dataListCart['page_url'] = $productDetails->get_permalink();
							$dataListCart['product_type'] = $productDetails->get_type();
							$arr[] = $dataListCart;
						}
						if (!$updateData) {
							$dataList['product_id'] = $cart_item['product_id'];
							$dataList['product_sku'] = $productDetails->get_sku();
							$dataList['product_name'] = $productDetails->get_title();
							$dataList['price'] = $priceDetails;
							$dataList['special_price'] = $productDetails->get_sale_price();
							$dataList['productimg'] = $imgDetails;
							$dataList['category_name'] = implode(',', $productCats);
							$dataList['category'] = '';
							$dataList['quantity'] = $cart_item['quantity'];
							$dataList['page_url'] = $productDetails->get_permalink();
							$dataList['product_type'] = $productDetails->get_type();
						}
						setcookie('tb_old_qty_'. $cart_item['product_id'], $cart_item['quantity'], time() + (86400 * 30), '/');
					}
				}
			}
			$url = 'add-to-cart';
			// $dataList['order_id'] = $this->userId;
			$dataList['user_id'] = $this->userId;
			$dataList['session_id'] = $this->sessionId;
			$dataList['user_name'] = $this->userName;
			$dataList['user_mail'] = $this->userMail;
			//$dataList['index_name'] = $this->apiKey;
			$dataList['utm_sources'] = $this->utmSource;
			$dataList['utm_token'] = $this->utmToken;
			$dataList['utm_medium'] = $this->utmMedium;
			if ($updateData) {
				$dataList['cart_items'] = $arr;
				$url = 'update-cart';
			}
			$this->tb_send_data($dataList, $url);
		} catch (\Exception $e) {
			$errorMsg = ', Message: ' . $e->getMessage();
			$errorMsg .= ', Line: ' . $e->getLine();
		}
	}

	/**
	 * Update cart event tracking.
	 */
	public function tb_action_woocommerce_update_to_cart()
	{
		$arr = [];
		try {
			if (isset($_REQUEST['cart']) && count($_REQUEST['cart']) > 0) {
				foreach ($_REQUEST['cart'] as $key => $value) {
					if (count(WC()->cart->get_cart()) > 0) {
						foreach (WC()->cart->get_cart() as $cart_item) {
							if ($cart_item['key'] === $key) {
								$productDetails = wc_get_product($cart_item['data']->get_id());
								$productCats = wp_get_post_terms($cart_item['data']->get_id(), 'product_cat', array('fields' => 'names'));
								$imgDetails = get_the_post_thumbnail_url($cart_item['product_id']);
								$priceDetails = null !== get_post_meta($cart_item['product_id'], '_price', true) && '' !== get_post_meta($cart_item['product_id'], '_price', true) ? get_post_meta($cart_item['product_id'], '_price', true) : '';
								$dataListCart['order_id'] = $cart_item['key'];
								$dataListCart['product_id'] = $cart_item['product_id'];
								$dataListCart['product_sku'] = $productDetails->get_sku();
								$dataListCart['product_name'] = $productDetails->get_title();
								$dataListCart['price'] = $priceDetails;
								$dataListCart['special_price'] = $productDetails->get_sale_price();
								$dataListCart['productimg'] = $imgDetails;
								$dataListCart['category_name'] = implode(',', $productCats);
								$dataListCart['category'] = '';
								$dataListCart['old_quantity'] = isset($_COOKIE['tb_old_qty_'. $cart_item['product_id']]) ? $_COOKIE['tb_old_qty_'. $cart_item['product_id']] : $cart_item['quantity'];
								if($dataListCart['old_quantity'] == $cart_item['quantity']){
									$cart_item['quantity'] = $dataListCart['old_quantity'] + 1;

								}
								$dataListCart['new_quantity'] = $cart_item['quantity'];
								$dataListCart['page_url'] = $productDetails->get_permalink();
								$dataListCart['product_type'] = $productDetails->get_type();
								setcookie('tb_old_qty_'. $cart_item['product_id'], $cart_item['quantity'], time() + (86400 * 30), '/');
								$arr[] = $dataListCart;

							}
						}
					}
				}
				$dataList['order_id'] = $this->userId;
				$dataList['user_id'] = $this->userId;
				$dataList['session_id'] = $this->sessionId;
				$dataList['user_name'] = $this->userName;
				$dataList['user_mail'] = $this->userMail;
				$dataList['utm_sources'] = $this->utmSource;
				$dataList['utm_token'] = $this->utmToken;
				$dataList['utm_medium'] = $this->utmMedium;
				$dataList['cart_items'] = $arr;
				$this->tb_send_data($dataList, 'update-cart');
			}
		} catch (\Exception $e) {
			$errorMsg = ', Message: ' . $e->getMessage();
			$errorMsg .= ', Line: ' . $e->getLine();
		}
	}

	/**
	 * Remove cart item event.
	 *
	 * @param $cart_item_key
	 * @param $cartItemKey
	 */
	public function tb_action_woocommerce_cart_item_removed($cart_item_key, $cartItemKey)
	{
	
	try {
		$dataCheckList = (null !== $cartItemKey->get_removed_cart_contents() && '' !== $cartItemKey->get_removed_cart_contents()) ? $cartItemKey->get_removed_cart_contents() : false;
			if ($dataCheckList) {
				$dataInsert = $cartItemKey->get_removed_cart_contents();
				foreach ($dataInsert as $key => $cart_item) {
					$productDetails = wc_get_product($cart_item['product_id']);
					$productCats = wp_get_post_terms($cart_item['product_id'], 'product_cat', array('fields' => 'names'));
					$imgDetails = get_the_post_thumbnail_url($cart_item['product_id']);
					$priceDetails = (null !== get_post_meta($cart_item['product_id'], '_price', true) && '' !== get_post_meta($cart_item['product_id'], '_price', true)) ? get_post_meta($cart_item['product_id'], '_price', true) : '';
					$dataList['user_id'] = $this->userId;
					$dataList['session_id'] = $this->sessionId;
					$dataList['user_name'] = $this->userName;
					$dataList['user_mail'] = $this->userMail;
					$dataList['order_id'] = '';
					$dataList['product_id'] = $cart_item['product_id'];
					$dataList['product_sku'] = $productDetails->get_sku();
					$dataList['product_name'] = $productDetails->get_title();
					$dataList['price'] = $priceDetails;
					$dataList['special_price'] = $productDetails->get_sale_price();
					$dataList['productimg'] = $imgDetails;
					$dataList['category_name'] = implode(',', $productCats);
					$dataList['category'] = '';
					$dataList['quantity'] = $cart_item['quantity'];
					$dataList['page_url'] = $productDetails->get_permalink();
					$dataList['product_type'] = $productDetails->get_type();
					$dataList['utm_sources'] = $this->utmSource;
					$dataList['utm_token'] = $this->utmToken;
					$dataList['utm_medium'] = $this->utmMedium;
					setcookie('tb_old_qty_'. $cart_item['product_id'], 0, time() + (86400 * 30), '/');
					$this->tb_send_data($dataList, 'remove-to-cart');
				}
			}
		} catch (\Exception $e) {
			$errorMsg = ', Message: ' . $e->getMessage();
			$errorMsg .= ', Line: ' . $e->getLine();
		}	
	}

	/**
	 * @param $wccm_checkout_text_before
	 */
	public function tb_checkout_before_customer_details($wccm_checkout_text_before)
	{
		$arr = [];
		$arrQty = [];
		$arrTotal = [];
		try {
			if (count(WC()->cart->get_cart()) > 0) {
				foreach (WC()->cart->get_cart() as $cart_item) {
					$productDetails = wc_get_product($cart_item['data']->get_id());
					$productCats = wp_get_post_terms($cart_item['data']->get_id(), 'product_cat', array('fields' => 'names'));
					$imgDetails = get_the_post_thumbnail_url($cart_item['product_id']);
					$priceDetails = (null !== get_post_meta($cart_item['product_id'], '_price', true) && '' !== get_post_meta($cart_item['product_id'], '_price', true)) ? get_post_meta($cart_item['product_id'], '_price', true) : '';
					$dataListCart['order_id'] = $cart_item['key'];
					$dataListCart['product_id'] = $cart_item['product_id'];
					$dataListCart['product_sku'] = $productDetails->get_sku();
					$dataListCart['product_name'] = $productDetails->get_title();
					$dataListCart['price'] = $priceDetails;
					$dataListCart['special_price'] = $productDetails->get_sale_price();
					$dataListCart['productimg'] = $imgDetails;
					$dataListCart['category_name'] = implode(',', $productCats);
					$dataListCart['category'] = '';
					$dataListCart['quantity'] = $cart_item['quantity'];
					$arrQty[] = $cart_item['quantity'];
					$arrTotal[] = $priceDetails * $cart_item['quantity'];
					$dataListCart['page_url'] = $productDetails->get_permalink();
					$dataListCart['product_type'] = $productDetails->get_type();
					$arr[] = $dataListCart;
				}
				$dataList['page_title'] = 'Checkout';
				$dataList['total_qty'] = @array_sum($arrQty);
				$dataList['total_amount'] = @array_sum($arrTotal);
				$dataList['user_id'] = $this->userId;
				$dataList['session_id'] = $this->sessionId;
				$dataList['user_name'] = $this->userName;
				$dataList['user_mail'] = $this->userMail;
				$dataList['utm_sources'] = $this->utmSource;
				$dataList['utm_token'] = $this->utmToken;
				$dataList['utm_medium'] = $this->utmMedium;
				$dataList['order_id'] = $this->userId;
				$dataList['cart_items'] = $arr;
				$this->tb_send_data($dataList, 'checkout');
			}
			} catch (\Exception $e) {
				$errorMsg = ', Message: ' . $e->getMessage();
				$errorMsg .= ', Line: ' . $e->getLine();
			}
         }

	/**
	 * @param $order_id
	 */
	public function tb_action_woocommerce_thankyou($order_id)
	{
		try {
			$this->tbOrderId = $order_id;
			$dataList['order_id'] = $order_id;
			$dataList['user_id'] = $this->userId;
			$dataList['session_id'] = $this->sessionId;
			$dataList['utm_sources'] = $this->utmSource;
			$dataList['utm_token'] = $this->utmToken;
			$dataList['utm_medium'] = $this->utmMedium;
			$this->tb_send_data($dataList, 'order-created');
			$this->tb_adroll_conversion($order_id);
		} catch (\Exception $e) {
			$errorMsg = ', Message: ' . $e->getMessage();
			$errorMsg .= ', Line: ' . $e->getLine();
		}
	}

	/**
	 * @param $order_id
	 */

	public function tb_adroll_conversion($order_id)
	{
		try {
			// Getting an instance of the order object
			$order = wc_get_order( $order_id );
			$orderValue = $order->get_total();

			$adroll_conversion_script = "<script type='text/javascript'>adroll_conversion_value = $orderValue;adroll_currency = 'USD';adroll_custom_data = {'ORDER_ID': $order_id};</script>";

			echo $adroll_conversion_script;
		} catch (\Exception $e) {
			$errorMsg = ', Message: ' . $e->getMessage();
			$errorMsg .= ', Line: ' . $e->getLine();
		}
	}


	/**
	 * @param $order_id
	 */
	public function tb_order_completed($order_id)
	{
		try {
			//$dataList['index_name'] = $this->apiKey;
			$dataList['order_id'] = $order_id;
			$dataList['order_status'] = 'completed';
			//$url = 'https://' . $this->tbPath . '.targetbay.com/api/v1/woo/order-updated?api_token=' . $this->indexName;
			$this->tb_send_data($dataList, 'order-updated');
		} catch (\Exception $e) {
			$errorMsg = ', Message: ' . $e->getMessage();
			$errorMsg .= ', Line: ' . $e->getLine();
		}
	}

	/**
	 * @param $order_id
	 */
	public function tb_order_cancelled($order_id)
	{
		try {	
			$dataList['order_id'] = $order_id;
			$dataList['order_status'] = 'cancelled';
			$this->tb_send_data($dataList, 'order-updated');
		} catch (\Exception $e) {
			$errorMsg = ', Message: ' . $e->getMessage();
			$errorMsg .= ', Line: ' . $e->getLine();
		}	
	}

	/**
	 * @param $order_id
	 */
	public function tb_order_refunded($order_id)
	{
		try {
			$dataList['order_id'] = $order_id;
			$dataList['order_status'] = 'refunded';
			$this->tb_send_data($dataList, 'order-updated');
		} catch (\Exception $e) {
			$errorMsg = ', Message: ' . $e->getMessage();
			$errorMsg .= ', Line: ' . $e->getLine();
		}
	}

	/**
	 * @param $order_id
	 */
	public function tb_order_processing($order_id)
	{
		try {
			$dataList['order_id'] = $order_id;
			$dataList['order_status'] = 'processing';
			$this->tb_send_data($dataList, 'order-updated');
		} catch (\Exception $e) {
			$errorMsg = ', Message: ' . $e->getMessage();
			$errorMsg .= ', Line: ' . $e->getLine();
		}
	}

	/**
	 * @param $order_id
	 */
	public function tb_order_on_hold($order_id)
	{
		try {
			$dataList['order_id'] = $order_id;
			$dataList['order_status'] = 'hold';
			$this->tb_send_data($dataList, 'order-updated');
		} catch (\Exception $e) {
			$errorMsg = ', Message: ' . $e->getMessage();
			$errorMsg .= ', Line: ' . $e->getLine();
		}
	}

	/**
	 * @param $new_status
	 * @param $old_status
	 * @param $post
	 */
	public function tb_save_post_product($new_status, $old_status, $post)
	{
		
		try {
			if (trim($post->post_title) !== 'AUTO-DRAFT' && (isset($post->post_type) && $post->post_type === 'product')) {
				$dataList['product_id'] = isset($post->ID) ? $post->ID : 0;
				$this->tb_send_data($dataList, 'product-created');
			}
		} catch (\Exception $e) {
			$errorMsg = ', Message: ' . $e->getMessage();
			$errorMsg .= ', Line: ' . $e->getLine();
		}
	}

	/**
	 * @param array $dataList
	 * @param string $endPoint
	 */
	public function tb_send_data($dataList, $endPoint)
	{
		try {
			$tbApi = $endPoint . '?_t=' . $this->authToken;
			$baseUrl = 'https://' . $this->tbPath . '.targetbay.com/api/v1/woo/';
			$client = new Client(array('base_uri' => $baseUrl, 'debug' => false));
			$client->post($baseUrl.$tbApi, array('json' => $dataList, 'debug' => false));
		} catch (\Exception $e) {
			$errorMsg = ', Message: ' . $e->getMessage();
			$errorMsg .= ', Line: ' . $e->getLine();
		}
	}

	/**
	 * Script
	 */
	public function tb_add_script()
	{
		try {
			$productName = '';
			$productId = '';
			$productUrl = '';
			$productImg = '';
			$pageUrl = '';
			$proCat = '';
			if (is_product_category()) {
				$pageUrl = 'category-view';
				$category = get_queried_object();
				$categoryId = $category->term_id;
				$categoryUrl = get_category_link( $category->term_id ) ;
				$categoryName = $category->name;
				$proCat = 'category: {id: ' . $categoryId . ', link: \'' . $categoryUrl . '\', name: \'' . $categoryName .'\'},';
			}

			if (is_home() || is_archive() || is_single() || is_page()) {
				$pageUrl = 'page-visit';
			}
			if(is_category()){
				$pageUrl = 'category-view';
			}

			if (is_search()) {
				$pageUrl = 'searched';
			}

			if (is_product()) {
				$pageUrl = 'product-view';
				$product = wc_get_product();
				$productId = $product->get_id();
				$productName = $product->get_title();
				$productUrl = $product->get_permalink();
				$productImg = get_the_post_thumbnail_url($product->get_id());

				$AdRollProductScript = "<script type='text/javascript'> adroll_custom_data = {'product_id': '$productId'};</script>";

				echo $AdRollProductScript;
			}
				$settingsDetails = get_option('targetbay_settings', $this->wc_targetbay_get_default_settings());
				if (isset($settingsDetails) && count($settingsDetails) > 0) {
					$scriptData = "<script type='text/javascript'>
						window.tbConfig = {
							platform: 'wc',
							apiStatus: '$this->tbPath',
							publicKey: '$this->authToken',
							apiKey: '$this->apiKey',
							apiToken: '$this->indexName',
							apiVersion: 'v1',
							trackingType: '1',
							productName: '" . $productName . "',
							productId: '" . $productId . "',
							productImageUrl: '" . $productImg . "',
							productUrl: '" . $productUrl . "',
							productStockStatus: '0',
							childProduct: '0',
							userId: '$this->userId',
							userMail: '$this->userMail',
							userName: '$this->userName',
							userAvatar: '',
							pageUrl: '$pageUrl',
							utmSources: '$this->utmSource',
							utmToken: '$this->utmToken',
							pageData: '',
							orderId: '$this->tbOrderId',                    
							tbWooBulkReview : true,
							$proCat
							tbTrack: true,
							tbMessage: true,
							tbRecommendations: true,
							tbReview: {
								tbSiteReview: true,
								tbProductReview: true,
								tbBulkReview: true,
								tbQa: true,
								tbReviewBadge: true
							}
						};";
					if ($settingsDetails['disable_wp_review_system']) {
						//For site review creating div.
						$scriptData .= "var iDiv = document.createElement('div');
						iDiv.id = 'targetbay_site_reviews';
						var innerDiv = document.createElement('div');
						innerDiv.className = 'block-2';
						iDiv.appendChild(innerDiv);
						document.getElementsByTagName('body')[0].appendChild(iDiv);";
					}
					//For adroll script
					if (isset($settingsDetails['tb_adroll_adv_id']) && $settingsDetails['tb_adroll_adv_id'] !== '') {
				
					$scriptData .= "adroll_adv_id = '".$settingsDetails['tb_adroll_adv_id']."';
					adroll_pix_id = '".$settingsDetails['tb_adroll_pix_id']."';(function () {
					var _onload = function(){
						if (document.readyState && !/loaded|complete/.test(document.readyState)){setTimeout(_onload, 10);return}
						if (!window.__adroll_loaded){__adroll_loaded=true;setTimeout(_onload, 50);return}
						var scr = document.createElement('script');
						var host = (('https:' == document.location.protocol) ? 'https://s.adroll.com' : 'http://a.adroll.com');
						scr.setAttribute('async', 'true');
						scr.type = 'text/javascript';
						scr.src = host + '/j/roundtrip.js';
						((document.getElementsByTagName('head') || [null])[0] ||
						document.getElementsByTagName('script')[0].parentNode).appendChild(scr);
						};
						if (window.addEventListener) {window.addEventListener('load', _onload, false);}
						else {window.attachEvent('onload', _onload)}
							}());";
					}

				//For message popups creating div
				$scriptData .= "var iDivP = document.createElement('div');
					iDivP.id = 'targetbay_message';
					var innerDivP = document.createElement('div');
					innerDivP.className = 'block-2';
					iDivP.appendChild(innerDivP);
					document.getElementsByTagName('body')[0].appendChild(iDivP);

						//For order reviews
						var iDivNew = document.createElement('div');
						iDivNew.id = 'targetbay_order_reviews';
						iDivNew.className = 'targetbay_order_reviews';
						var innerDivNew = document.createElement('div');
						innerDivNew.className = 'block-2';
						iDivNew.appendChild(innerDivNew);
						document.getElementsByTagName('body')[0].appendChild(iDivNew);

						var sNew = document.scripts[0], gNew;
						gNew = document.createElement('script');
						gNew.src = '" . 'https://' . $this->tbPath . '.targetbay.com' . "/js/wc-events.js';
						//gNew.src = '" . 'http://' . $this->tbPath . '.targetbay.localhost' . "/js/wc-events.js';
						gNew.type = 'text/javascript';
						gNew.async = true;
						sNew.parentNode.insertBefore(gNew, sNew);
						</script>";

					echo $scriptData;
				}
			} catch (\Exception $e) {
				$errorMsg = ', Message: ' . $e->getMessage();
				$errorMsg .= ', Line: ' . $e->getLine();
			}
	}

	/**
	 * @param $redirect
	 * @param $user
	 */
	public function tb_login($redirect, $user)
	{
		try {
			if (isset($user->ID)) {
				$dataList['user_name'] = isset($user->display_name) ? $user->display_name : $user->user_email;
				$dataList['user_mail'] = $user->user_email;
				$dataList['session_id'] = $user->ID;
				$dataList['user_id'] = $user->ID;
				$dataList['login_date'] = date('Y-m-d');
				$dataList['timestamp'] = strtotime(date('Y-m-d'));
				$dataList['previous_session_id'] = $this->sessionId;
				$dataList['ip_address'] = $this->get_user_ip();
				$dataList['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
				$this->tb_send_data($dataList, 'login');
			}
		} catch (\Exception $e) {
				$errorMsg = ', Message: ' . $e->getMessage();
				$errorMsg .= ', Line: ' . $e->getLine();
		}
	}

	/**
	 * Logout event.
	 */
	public function tb_logout()
	{
		try {
			$dataList['user_id'] = $this->userId;
			$dataList['session_id'] = $this->sessionId;
			$dataList['user_name'] = $this->userName;
			$dataList['user_mail'] = $this->userMail;
			$dataList['logout_date'] = date('Y-m-d');
			$dataList['timestamp'] = strtotime(date('Y-m-d'));
			$dataList['ip_address'] = $this->get_user_ip();
			$this->tb_send_data($dataList, 'logout');
		} catch (\Exception $e) {
			$errorMsg = ', Message: ' . $e->getMessage();
			$errorMsg .= ', Line: ' . $e->getLine();
		}
	}

	/**
	 * Get visitor IP address.
	 *
	 * @return mixed
	 */
	private function get_user_ip()
	{
		try {
			if (isset($_SERVER['HTTP_CLIENT_IP'])) {
				return $_SERVER['HTTP_CLIENT_IP'];
			} elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				return $_SERVER['HTTP_X_FORWARDED_FOR'];
			} elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
				return $_SERVER['HTTP_X_FORWARDED'];
			} elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
				return $_SERVER['HTTP_FORWARDED_FOR'];
			} elseif (isset($_SERVER['HTTP_FORWARDED'])) {
				return $_SERVER['HTTP_FORWARDED'];
			} else {
				return $_SERVER['REMOTE_ADDR'];
			}
		} catch (\Exception $e) {
			$errorMsg = ', Message: ' . $e->getMessage();
			$errorMsg .= ', Line: ' . $e->getLine();
			return '';
		}
	}

	/**
	 * @return array
	 */
	public function wc_targetbay_get_default_settings()
	{
		try {
			return array(
				'tb_server' => 'live',
				'tb_api_secret' => '',
				'tb_tracking_type' => 'back',
				'tb_rich_snippets' => 'manual',
				'tb_pro_review' => 'enable',
				'tb_bulk_review' => 'enable',
				'disable_wp_review_system' => true,
				'wp_star_ratings_enabled' => 'no'
			);
		} catch (\Exception $e) {
			$errorMsg = ', Message: ' . $e->getMessage();
			$errorMsg .= ', Line: ' . $e->getLine();
			$dataList = array();
			return $dataList;
		}
	}
}

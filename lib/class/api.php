<?php
class SEFW_API{
	
	private $api_key;
	
	private $api_shop;
	
	private $api_checkbox_content_url;
	
	private $api_checkbox_content_send_url;
	
	function __construct() {
		global $wpdb;
		
		register_shutdown_function( array( $this, '__destruct' ) );
		
		$this->api_key							= stripslashes( get_option( 'activation_key' ) );
		$this->api_shop							= ( $_SERVER['HTTP_HOST'] == 'localhost' ) ? 'localhost.com' : $_SERVER['HTTP_HOST'];
		$this->api_checkbox_content_url			= 'https://admin.seesiu.com/wp-json/gminus/v1/checkbox/key='.$this->api_key.'&shop='.$this->api_shop;
		$this->api_checkbox_content_send_url	= 'https://admin.seesiu.com/wp-json/gminus/v1/order';
	}
	
	function __destruct() {
		return true;
	}
	
	function get_checkbox_content(){
		$url	= $this->api_checkbox_content_url;
		
		$response	= wp_remote_get( $url );
		$body		= wp_remote_retrieve_body( $response );
		$http_code	= wp_remote_retrieve_response_code( $response );
		
		if( $http_code == 200 ){
			$result				= json_decode( $body, true );
			$price				= $result['price'];
			$converted_price	= $this->get_converted_price( $price );
			$result['price']	= $converted_price; //Replace price by converted price
			
			return $result;
		}else{
			return false;
		}
	}
	
	function send_checkbox_content( $apidata ){
		
		$url = $this->api_checkbox_content_send_url;
		
		$args = array(
			'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'body'        => json_encode( $apidata ),
			'method'      => 'POST',
			'data_format' => 'body',
			'sslverify'   => false,
		);
		
		$response	= wp_remote_post( $url, $args );
		$http_code	= wp_remote_retrieve_response_code( $response );
		if( $http_code == 200 ){
			return true;
		}else{
			return false;
		}
	}
	
	function get_converted_price( $price ){
		$currency_code				= get_woocommerce_currency();
		$exchange_rates 			= sefw_get_exchange_rates();
		
		$gbp_rate					= $exchange_rates['GBP']; //get currency rate from EUR to GBP
		$current_currency_rate		= $exchange_rates[$currency_code]; //get currency rate from EUR to selected woocommerce currency rate
		
		//convert price from GBP to EUR
		$euro_price					= ( $price / $gbp_rate );
		//convert price from EUR to WC selected currency
		$converted_price			= ( $euro_price * $current_currency_rate );
		
		return $converted_price;
	}

}
?>
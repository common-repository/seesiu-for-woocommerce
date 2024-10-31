<?php
/*
Plugin Name: Seesiu for WooCommerce
Description: Seesiu helps Woocommerce retailers give their customers the choice to make their deliveries greener. Shop greener with Seesiu.  
Version: 0.0.4
Author: Seesiu
Author URI: http://seesiu.com
*/

define('SEFW_BMC_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ));
define('SEFW_BMC_LIB_PATH', SEFW_BMC_PATH.'/lib' );
define('SEFW_BMC_TEMPLATE_PATH', SEFW_BMC_PATH.'/templates' );

include( SEFW_BMC_LIB_PATH.'/index.php' );

// Plugin init hook.
add_action( 'plugins_loaded', 'sefw_initialize_plugin' );

//Initialize plugin.
function sefw_initialize_plugin() {

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'sefw_deactivate_notice' );
		return;
	}
	
	new SEFW_WoocommerceExtension();
}

//WooCommerce Deactivated Notice.
function sefw_deactivate_notice() {
	/* translators: %s: WooCommerce link */
	echo '<div class="error"><p>' . sprintf( esc_html__( 'WooCommerce Seesiu requires %s to be installed and active.', 'woocommerce-seesiu' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</p></div>';
}

class SEFW_WoocommerceExtension{
	var $version = '0.0.1';
	
	var $activation_key;
	
	var $shop;

	function __construct(){
		
		$this->activation_key = stripslashes( get_option( 'activation_key' ) );
		$this->shop = ( $_SERVER['HTTP_HOST'] == 'localhost' ) ? 'localhost.com' : $_SERVER['HTTP_HOST'];
		
		if( $this->activation_key && ( sefw_is_valid_key( $this->activation_key, $this->shop ) == 'success' ) ){
			add_action( 'wp_enqueue_scripts', array( $this, 'sefw_enqueue_scripts' ), 10 );
			add_action( 'woocommerce_checkout_before_order_review', array( $this, 'sefw_add_custom_checkbox' ) );
			
			add_action( 'wp_ajax_gmAjax', array( $this, 'sefw_handle_ajax_request') );
			add_action( 'wp_ajax_nopriv_gmAjax', array( $this, 'sefw_handle_ajax_request') );
			
			add_action( 'woocommerce_cart_calculate_fees', array( $this, 'sefw_set_installment_fee' ) );
			add_filter( 'woocommerce_form_field' , array( $this, 'sefw_remove_optional_txt_from_installment_checkbox'), 10, 4 );
			
			add_action( 'woocommerce_thankyou', array( $this, 'sefw_after_placed_new_order' ), 1, 1  );
			
			//add column in order table
			add_filter( 'manage_edit-shop_order_columns', array( $this, 'sefw_new_order_column' ), 20 );
			add_action( 'manage_shop_order_posts_custom_column' , array( $this, 'sefw_custom_orders_list_column_content' ), 20, 2 );
			
			add_action( 'restrict_manage_posts', array( $this, 'sefw_display_admin_shop_order_seesiu_filter' ) );
			add_action( 'pre_get_posts', array( $this, 'sefw_process_admin_shop_order_seesiu_filter' ) );
		}
		
		//settings page link
		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'sefw_settings_link' ) );
		
		add_action( 'admin_menu', array( $this, 'sefw_admin_menu' ) );
		
	}
		
	function sefw_enqueue_scripts(){
		if( !is_admin() ){
			wp_register_style( 'seesiu-page-style', plugins_url( '/assets/css/style.css', __FILE__ ), $this->version );
			wp_enqueue_style( 'seesiu-page-style' );
			
			wp_register_script( 'seesiu-script', plugins_url( '/assets/js/script.js', __FILE__ ), array('jquery'), $this->version );
			wp_enqueue_script( 'seesiu-script');
			
			wp_localize_script( 'seesiu-script', 'seesiu', array( 
																	'ajaxurl' => admin_url( 'admin-ajax.php' )
																)
															);
		}
	}
	
	function sefw_add_custom_checkbox(){
		$apiObject	= new SEFW_API();
		$data		= $apiObject->get_checkbox_content();
		
		if( $data ){
			$price 				= $data['price'];
			$currency_symbol	= get_woocommerce_currency_symbol();
			$text 				= str_replace( '[SeesiuFees]', $currency_symbol.number_format((float)$price, 2, '.', ''), $data['checkbox_value'] );
			
			echo '<div class="custom_checkbox_wrap">
					<ul>
						<li data-price="'.$price.'">';
							woocommerce_form_field( 'installment_fee', array(
								'type'          => 'checkbox',
								'class'         => array('installment-fee form-row-wide'),
								'label'         => __(''),
								'placeholder'   => __(''),
							), WC()->session->get('installment_fee') ? '1' : '' );
						echo '</li>
							<li>
								<p class="checkbox_content">'.$text.'</p>
							</li>
							<li><img src="'.esc_url( $data['checkbox_picture'] ).'"></li>
						</ul>
					</div>';
		}else{
			echo '<div class="custom_checkbox_wrap"><ul><li><p>'.esc_html('No Records Found.').'</p></li></ul></div>';
		}
	}

	function sefw_set_installment_fee( $cart ){
		if ( is_admin() && ! defined('DOING_AJAX') || ! is_checkout() )
			return;
	
		if ( WC()->session->get('installment_fee') ) {
			$label_text	= stripslashes( get_option( 'label_text' ) );
			$fee_amount	= WC()->session->get('installment_fee');
			WC()->cart->add_fee( $label_text, $fee_amount );
		}
	}
	
	function sefw_checkout_fee_script() {
		// Only on Checkout
		if( is_checkout() && ! is_wc_endpoint_url() ){
			if( WC()->session->__isset('installment_fee') )
				WC()->session->__unset('installment_fee');
		}
	}

	function sefw_remove_optional_txt_from_installment_checkbox( $field, $key, $args, $value ) {
		// Only on checkout page for Order notes field
		if( 'installment_fee' === $key && is_checkout() ) {
			$optional	= '&nbsp;<span class="optional">(' . esc_html__( 'optional', 'woocommerce' ) . ')</span>';
			$field		= str_replace( $optional, '', $field );
		}
		return $field;
	}
	
	//display column in order table
	function sefw_new_order_column( $columns ){
		$reordered_columns = array();
	
		// Inserting columns to a specific location
		foreach( $columns as $key => $column){
			$reordered_columns[$key] = $column;
			if( $key ==  'order_status' ){
				$label_text	= stripslashes( get_option( 'label_text' ) );
				// Inserting after "Status" column
				$reordered_columns['goal-minus-fees'] = __( $label_text, 'theme_domain' );
			}
		}
		return $reordered_columns;
	}
	
	function sefw_custom_orders_list_column_content( $column, $post_id ){
		global $post;
		switch ( $column ){
			case 'goal-minus-fees' :
				$order = wc_get_order( $post->ID );
				
				$total				= (float) $order->get_total();
				$shipping_charge	= $order->get_shipping_total();
				$subtotal			= 0;
				$discount_total		= $order->get_discount_total();
				
				foreach ( $order->get_items() as  $item_key => $item_values ) {
					$item_data	= $item_values->get_data();
					$subtotal	= $subtotal + $item_data['subtotal'];
				}
				$installment_fee = $total - $subtotal - $shipping_charge + $discount_total;

				if( !empty( $installment_fee ) ){
					echo number_format((float)$installment_fee, 2, '.', '');
				}else{
					echo '<small>(<em>'.esc_html('(No Value)').'</em>)</small>';
				}
			break;
		}
	}
	
	function sefw_after_placed_new_order( $order_id ){
		$apiObject	= new SEFW_API();
		
		$this->sefw_checkout_fee_script(); //remove the checkout price from wc session
		
		$order			= wc_get_order( $order_id );
		$total			= (float) $order->get_total();
		$subtotal		= 0;
		$order_items	= $order->get_items();
		
		$product_list = array();
		foreach ( $order_items as $item_id => $item ){
		   $product_id		= $item->get_product_id();
		   $product_title	= $item->get_name();
		   $product_price	= $item->get_total();
		   $subtotal		= $subtotal + $item->get_subtotal();
		   $product_list[]	= array( 'product_id' => $product_id, 'product_title' => $product_title, 'product_price' => $product_price );
		}
		
		$discount_total		= $order->get_discount_total();
		$shipping_charge	= $order->get_shipping_total();
		$seesiu_charge		= $total - $subtotal - $shipping_charge + $discount_total;
		if( $seesiu_charge && ( $seesiu_charge > 0 ) ){
			$charges			= array( 'shipping_charge' => $shipping_charge, 'seesiu_charge' => $seesiu_charge );
			$order_data			= $order->get_data(); // The Order data
			
			$shipping_address	= array( 'first_name' => $order_data['shipping']['first_name'], 'last_name' => $order_data['shipping']['last_name'], 'country_name' => $order_data['shipping']['country'], 'city_name' => $order_data['shipping']['city'], 'zip_Code' => $order_data['shipping']['postcode'], 'address_1' => $order_data['shipping']['address_1'] );
			
			$date_of_order		= $order_data['date_created']->date('Y-m-d H:i:s');
			$order_number		= $order_data['id'];
			$retailer_currency	= $order_data['currency'];
			$customer_name		= $order_data['shipping']['first_name'].' '.$order_data['shipping']['last_name'];
			$customer_email		= $order_data['billing']['email'];
			
			$data = array(
						'key'				=> $this->activation_key,
						'shop'				=> $this->shop,
						'date_of_order'		=> $date_of_order,
						'order_number'		=> $order_number,
						'cost'				=> $total,
						'retailer_currency' => $retailer_currency,
						'customer_name'		=> $customer_name,
						'customer_email'	=> $customer_email,
						'product_list'		=> $product_list,
						'shipping_Address'	=> $shipping_address,
						'charges'			=> $charges,
					);
			$apiObject->send_checkbox_content( $data );
			update_post_meta( $order_id, 'goal_minus_fees', 'gm_fees' );
			WC()->session->set( 'installment_fee', false );
		}
	}
	
	function sefw_settings_link( $links ) {
		$url			= esc_url( add_query_arg( 'page', 'seesiu-settings', get_admin_url().'admin.php' ) );
		$settings_link	= "<a href='$url'>" . __( 'Settings' ) . '</a>';
		array_push( $links, $settings_link );

		return $links;
	}
	
	function sefw_admin_menu(){
		add_menu_page( "Seesiu", "Seesiu", 8, 'seesiu-settings', array( $this, 'sefw_seesiu' ) );
	}
	
	function sefw_seesiu(){
		include ( SEFW_BMC_PATH. '/includes/admin-menu/seesiu-settings.php');
	}
	
	function sefw_handle_ajax_request(){
		$action = sanitize_text_field( $_POST['gmAction'] );
		global $current_user;
	
		$result			= array( 'error'=>1, 'msg'=>'Sorry! we are unable process your Request','field'=>'' );
		$data			= (array) json_decode( rawurldecode(stripslashes( $_POST['data'])  ) );
		$data_array 	= array_map( 'sanitize_text_field', wp_unslash( $data ) );
		$data			= (object) $data_array;
						
		switch($action){
			case 'UpdatePrice':
				$is_checked	= $data->is_checked;
				$price		= $data->price;

				WC()->session->set( 'installment_fee', ( $is_checked ? $price : false ) );
				
				$result['error'] = 0;			
			break;
		}
		echo json_encode($result);
		exit;
	}
	
	function sefw_display_admin_shop_order_seesiu_filter(){
		global $pagenow, $post_type;
	
		if( 'shop_order' === $post_type && 'edit.php' === $pagenow ) {
			$domain    = 'woocommerce';
			$languages = array( __('Seesiu Fees', $domain), __('All', $domain) );
	
			echo '<select name="filter_seesiu">
			<option value="">' . __('Filter By ', $domain) . '</option>
			<option value="'.esc_attr( 'all' ).'" '.esc_attr( isset( $_GET['filter_seesiu'] ) ? selected( "all", $_GET['filter_seesiu'], false ) : '' ).'>'.esc_html( 'All', $domain ).'</option>
			<option value="'.esc_attr( 'gm_fees' ).'" '.esc_attr( isset( $_GET['filter_seesiu'] ) ? selected( "gm_fees", $_GET['filter_seesiu'], false ) : '' ).'>'.esc_html( 'Seesiu Fees', $domain ).'</option>
			';
			echo '</select>';
		}
	}

	function sefw_process_admin_shop_order_seesiu_filter( $query ) {
		global $pagenow;
	
		if ( $query->is_admin && $pagenow == 'edit.php' && isset( $_GET['filter_seesiu'] ) 
			&& $_GET['filter_seesiu'] != '' && $_GET['post_type'] == 'shop_order' && $_GET['filter_seesiu'] != 'all' ) {
	
			$meta_query = $query->get( 'meta_query' ); // Get the current "meta query"
	
			$meta_query[] = array( // Add to "meta query"
				'meta_key' => 'goal_minus_fees',
				'value'    => esc_attr( $_GET['filter_seesiu'] ),
			);
			$query->set( 'meta_query', $meta_query ); // Set the new "meta query"
	
			$query->set( 'posts_per_page', 10 ); // Set "posts per page"
	
			$query->set( 'paged', ( get_query_var('paged') ? get_query_var('paged') : 1 ) ); // Set "paged"
		}
	}
	
}
?>
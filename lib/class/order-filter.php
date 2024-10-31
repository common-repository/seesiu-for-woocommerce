<?php
class SEFW_Filter_Orders_By_Seesiu {


	const VERSION = '1.1.0';

	/** @var WC_Filter_Orders_By_Coupon single instance of this plugin */
	protected static $instance;

	/**
	 * WC_Filter_Orders constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// load translations
		add_action( 'init', array( $this, 'sefw_load_translation' ) );

		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {

			// adds the coupon filtering dropdown to the orders page
			add_action( 'restrict_manage_posts', array( $this, 'sefw_filter_orders_by_coupon_used' ) );

			// makes coupons filterable
			add_filter( 'posts_join',  array( $this, 'sefw_add_order_items_join' ) );
			add_filter( 'posts_where', array( $this, 'sefw_add_filterable_where' ) );
		}
	}


	/** Plugin methods ***************************************/


	/**
	 * Adds the coupon filtering dropdown to the orders list
	 *
	 * @since 1.0.0
	 */
	public function sefw_filter_orders_by_coupon_used() {
	?>

		<select name="filter_by" id="filter_by">
			<option value="">
				<?php esc_html_e( 'Filter by', 'wc-filter-orders' ); ?>
			</option>
			<option value="<?php esc_attr_e( 'all' ); ?>" <?php echo esc_attr( isset( $_GET['filter_by'] ) ? selected( "all", $_GET['filter_by'], false ) : '' ); ?>><?php esc_html_e( 'All', 'wc-filter-orders' ); ?></option>
			<option value="<?php esc_attr_e( 'Seesiu Fees' ); ?>" <?php echo esc_attr( isset( $_GET['filter_by'] ) ? selected( "Seesiu Fees", $_GET['filter_by'], false ) : '' ); ?>><?php esc_html_e( 'Seesiu Fees', 'wc-filter-orders' ); ?></option>
		</select>
	<?php
	}


	/**
	 * Modify SQL JOIN for filtering the orders by any coupons used
	 *
 	 * @since 1.0.0
	 *
	 * @param string $join JOIN part of the sql query
	 * @return string $join modified JOIN part of sql query
	 */
	public function sefw_add_order_items_join( $join ) {
		global $typenow, $wpdb;

		if ( 'shop_order' === $typenow && isset( $_GET['filter_by'] ) && ! empty( $_GET['filter_by'] ) ) {

			$join .= "LEFT JOIN {$wpdb->prefix}woocommerce_order_items woi ON {$wpdb->posts}.ID = woi.order_id";
		}

		return $join;
	}


	/**
	 * Modify SQL WHERE for filtering the orders by any coupons used
	 *
	 * @since 1.0.0
	 *
	 * @param string $where WHERE part of the sql query
	 * @return string $where modified WHERE part of sql query
	 */
	public function sefw_add_filterable_where( $where ) {
		global $typenow, $wpdb;

		if ( 'shop_order' === $typenow && isset( $_GET['filter_by'] ) && ! empty( $_GET['filter_by'] ) && ( $_GET['filter_by'] == 'all' ) ) {

			// Main WHERE query part
			$where .= $wpdb->prepare( " GROUP BY order_id" );
		}else if( $_GET['filter_by'] == 'Seesiu Fees' ){
			$where .= $wpdb->prepare( " AND woi.order_item_type='fee' AND woi.order_item_name='%s'", wc_clean( $_GET['filter_by'] ) );
		}
		return $where;
	}


	/** Helper methods ***************************************/


	/**
	 * Load Translations
	 *
	 * @since 1.0.0
	 */
	public function sefw_load_translation() {
		// localization
		load_plugin_textdomain( 'wc-filter-orders', false, dirname( plugin_basename( __FILE__ ) ) . '/i18n/languages' );
	}


	/**
	 * Main WC_Filter_Orders_By_Coupon Instance, ensures only one instance
	 * is/can be loaded
	 *
	 * @since 1.1.0
	 *
	 * @see wc_filter_orders_by_coupon()
	 * @return WC_Filter_Orders_By_Coupon
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
		 	self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Cloning instances is forbidden due to singleton pattern.
	 *
	 * @since 1.1.0
	 */
	public function __clone() {
		/* translators: Placeholders: %s - plugin name */
		_doing_it_wrong( __FUNCTION__, sprintf( esc_html__( 'You cannot clone instances of %s.', 'wc-filter-orders' ), 'Filter WC Orders by Seesiu' ), '1.1.0' );
	}


	/**
	 * Unserializing instances is forbidden due to singleton pattern.
	 *
	 * @since 1.1.0
	 */
	public function __wakeup() {
		/* translators: Placeholders: %s - plugin name */
		_doing_it_wrong( __FUNCTION__, sprintf( esc_html__( 'You cannot unserialize instances of %s.', 'wc-filter-orders' ), 'Filter WC Orders by Seesiu' ), '1.1.0' );
	}


}
?>
<?php
/**
 * Admin class premium
 *
 * @author  YITH
 * @package YITH\Deposits\Classes
 * @version 1.0.0
 */

if ( ! defined( 'YITH_WCDP' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCDP_Admin_Premiuim' ) ) {
	/**
	 * WooCommerce Deposits and Down Payments Admin Premium
	 *
	 * @since 1.0.0
	 */
	class YITH_WCDP_Admin_Premium extends YITH_WCDP_Admin {

		/**
		 * Single instance of the class
		 *
		 * @var \YITH_WCDP_Admin_Premium
		 * @since 1.0.0
		 */
		protected static $instance;

		/**
		 * Constructor method
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			// add variation settings.
			add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'print_variation_deposit_settings' ), 10, 3 );
			add_action( 'woocommerce_save_product_variation', array( $this, 'save_variation_deposits_settings' ), 10, 2 );

			// filters admin options.
			add_filter( 'yith_wcdp_general_settings', array( $this, 'add_premium_general_settings' ) );
			add_filter( 'yith_wcdp_available_admin_tabs', array( $this, 'add_premium_options_tab' ) );

			// add shop order bulk actions.
			add_action( 'admin_footer', array( $this, 'bulk_admin_footer' ), 15 );
			add_action( 'load-edit.php', array( $this, 'bulk_action' ) );

			// add resend notification email action.
			add_action( 'admin_action_yith_wcdp_send_notification_email', array( $this, 'resend_notification_email' ) );
			add_action( 'admin_notices', array( $this, 'print_resend_notification_email_notice' ), 15 );

			// add resend new deposit email action.
			add_action( 'woocommerce_order_action_new_deposit', array( $this, 'resend_new_deposit_email' ), 10, 1 );

			// add order views.
			add_filter( 'views_edit-shop_order', array( $this, 'add_to_refund_deposit_view' ) );
			add_action( 'pre_get_posts', array( $this, 'filter_order_for_view' ) );

			// handle ajax actions.
			add_action( 'wp_ajax_json_search_roles', array( $this, 'get_roles_via_ajax' ) );

			parent::__construct();

			// print admin order notices.
			add_action( 'woocommerce_before_order_itemmeta', array( $this, 'print_item_to_refund_notice' ), 10, 2 );
		}

		/**
		 * Enqueue admin side scripts
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function enqueue() {
			parent::enqueue();

			wp_localize_script(
				'yith-wcdp',
				'yith_wcdp',
				array(
					'empty_row' => sprintf( '<tr class="no-items"><td class="colspanchange" colspan="0">%s</td></tr>', __( 'No items found.', 'yith-woocommerce-deposits-and-down-payments' ) ),
					'labels'    => array(
						'max_rate_notice' => __( 'Value entered cannot exceed 100%', 'yith-woocommerce-deposits-and-down-payments' ),
					),
					'nonce'     => wp_create_nonce( 'deposits' ),
				)
			);
		}

		/* === ORDERS VIEW METHODS === */

		/**
		 * Filter orders for custom plugin views
		 *
		 * @return void
		 * @todo  review code when WC switches to custom tables
		 *
		 * @since 1.0.0
		 */
		public function filter_order_for_view() {
			if ( ! empty( $_GET['deposit_to_refund'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				add_filter( 'posts_join', array( $this, 'filter_order_join_for_view' ) );
				add_filter( 'posts_where', array( $this, 'filter_order_where_for_view' ) );
			}
		}

		/**
		 * Add joins to order view query
		 *
		 * @param string $join Original join query section.
		 *
		 * @return string filtered join query section
		 * @since 1.0.0
		 */
		public function filter_order_join_for_view( $join ) {
			global $wpdb;

			$join .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_items as i ON {$wpdb->posts}.ID = i.order_id
				LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS im ON i.order_item_id = im.order_item_id";

			return $join;
		}

		/**
		 * Add conditions to order view query
		 *
		 * @param string $where Original where query section.
		 *
		 * @return string filtered where query section
		 * @since 1.0.0
		 */
		public function filter_order_where_for_view( $where ) {
			global $wpdb;

			$where .= $wpdb->prepare( ' AND im.meta_key = %s AND im.meta_value = %d', array( '_deposit_needs_manual_refund', 1 ) );

			return $where;
		}

		/**
		 * Output the suborder metaboxes
		 * Child class version adds "Resend button" to parent order metabox
		 *
		 * @param WP_Post $post  The post object.
		 * @param array   $param Callback args.
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function render_metabox_output( $post, $param ) {
			// print base template.
			parent::render_metabox_output( $post, $param );

			$order    = wc_get_order( $post );
			$order_id = $order->get_id();

			$deposit_expire    = get_option( 'yith_wcdp_deposit_expiration_enable', 'no' );
			$notification_days = get_option( 'yith_wcdp_notify_customer_deposit_expiring_days_limit', 15 );

			$send_available = false;

			if ( $order ) {

				// check if current order has a deposit.
				if ( ! $order->get_meta( '_has_deposit' ) ) {
					return;
				}

				// retrieve current order suborders.
				$suborders = YITH_WCDP_Suborders_Premium()->get_suborder( $order_id );

				// check if order have suborders.
				if ( ! $suborders ) {
					return;
				}

				// enable "re-send notify email" only if at least one suborder is not expired, and not completed or cancelled.
				foreach ( $suborders as $suborder_id ) {
					$suborder = wc_get_order( $suborder_id );

					if ( ! $order->get_meta( '_has_expired' ) && ! $suborder->has_status( array( 'completed', 'processing', 'cancelled' ) ) ) {
						$send_available = true;
					}
				}
			}

			switch ( $param['args']['metabox'] ) {
				case 'suborders':
					if ( apply_filters( 'yith_wcdp_change_deposit_expiration_enable', false ) || 'yes' === $deposit_expire && $notification_days && $send_available ) {
						$resend_url = esc_url(
							add_query_arg(
								array(
									'action'   => 'yith_wcdp_send_notification_email',
									'order_id' => $order_id,
								),
								wp_nonce_url( admin_url( 'admin.php' ), 'resend_notification_email', 'resend_notification_email_nonce' )
							)
						);
						echo sprintf( '<a class="button" href="%s">%s</a>', esc_url( $resend_url ), esc_html__( 'Send notification email', 'yith-woocommerce-deposits-and-down-payments' ) );
					}
					break;
			}
		}

		/**
		 * Add extra bulk action options to mark orders as complete or processing
		 * Using Javascript until WordPress core fixes: http://core.trac.wordpress.org/ticket/16031
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function bulk_admin_footer() {
			global $post_type;

			if ( 'shop_order' === $post_type ) {
				?>
				<script type="text/javascript">
					jQuery(function () {
						jQuery('<option>').val('remind_deposit_expiring').text('<?php esc_html_e( 'Remind deposit expiring', 'yith-woocommerce-deposits-and-down-payments' ); ?>').appendTo('select[name="action"]');
						jQuery('<option>').val('remind_deposit_expiring').text('<?php esc_html_e( 'Remind deposit expiring', 'yith-woocommerce-deposits-and-down-payments' ); ?>').appendTo('select[name="action2"]');
					});
				</script>
				<?php
			}
		}

		/**
		 * Process the new bulk actions for changing order status
		 *
		 * @return void
		 * @todo  review code when WC switches to custom tables
		 *
		 * @since 1.0.0
		 */
		public function bulk_action() {
			$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
			$action        = $wp_list_table->current_action();

			// Bail out if this is not a status-changing action.
			if ( 'remind_deposit_expiring' !== $action ) {
				return;
			}

			if ( empty( $_REQUEST['post'] ) || ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'bulk-posts' ) ) {
				return;
			}

			$changed  = 0;
			$post_ids = array_map( 'absint', (array) $_REQUEST['post'] );

			foreach ( $post_ids as $post_id ) {
				do_action( 'yith_wcdp_deposits_expiring', $post_id, false, true );
				$changed ++;
			}

			$sendback = add_query_arg(
				array(
					'post_type' => 'shop_order',
					'changed'   => $changed,
					'ids'       => join( ',', $post_ids ),
				),
				''
			);

			if ( isset( $_GET['post_status'] ) ) {
				$sendback = add_query_arg( 'post_status', sanitize_text_field( wp_unslash( $_GET['post_status'] ) ), $sendback );
			}

			wp_safe_redirect( esc_url_raw( $sendback ) );
			exit();
		}

		/**
		 * Re-send notification email and to edit order page
		 *
		 * @return void
		 * @todo  review code when WC switches to custom tables
		 *
		 * @since 1.0.0
		 */
		public function resend_notification_email() {
			if ( isset( $_GET['order_id'] ) && isset( $_GET['resend_notification_email_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['resend_notification_email_nonce'] ) ), 'resend_notification_email' ) ) {
				$order_id = intval( $_GET['order_id'] );
				do_action( 'yith_wcdp_deposits_expiring', $order_id, false, true );

				$return_url = add_query_arg( 'notification_email_sent', true, str_replace( '&amp;', '&', get_edit_post_link( $order_id ) ) );
				wp_safe_redirect( esc_url_raw( $return_url ) );
				die();
			}
		}

		/**
		 * Re-send new deposit email for customer and to edit order page
		 *
		 * @param WC_Order $order Order object.
		 *
		 * @return void
		 * @todo  review code when WC switches to custom tables
		 *
		 * @since 1.0.0
		 */
		public function resend_new_deposit_email( $order ) {

			$order_id     = $order->get_id();
			$notify_admin = get_option( 'yith_wcdp_notify_customer_deposit_created' );

			// is_enabled always enable to send it manually.
			if ( 'yes' !== $notify_admin ) {
				add_filter( 'yith_wcdp_customer_deposit_created_email_enabled', '__return_true' );
			}

			do_action( 'yith_wcdp_deposits_created', $order_id );

		}

		/**
		 * Print "Notification Email Sent" notice
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function print_resend_notification_email_notice() {
			global $post, $pagenow;

			if ( 'shop_order' === get_post_type( $post ) && 'post.php' === $pagenow && ! empty( $_GET['notification_email_sent'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				echo '<div class="updated notice notice-success is-dismissible below-h2">';
				echo '<p>' . esc_html__( 'Notification email sent', 'yith-woocommerce-deposits-and-down-payments' ) . '</p>';
				echo '</div>';
			}
		}

		/**
		 * Print item refund notice
		 *
		 * @param int           $item_id Current item id.
		 * @param WC_Order_Item $item    Current item.
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function print_item_to_refund_notice( $item_id, $item ) {
			$suborder_id = $item->get_meta( 'full_payment_id' );

			if ( $suborder_id ) {
				return;
			}

			$order_id        = $item->get_order_id();
			$schedule        = get_option( 'yith_wcdp_deposit_expiration_enable', 'no' );
			$expiration_type = get_option( 'yith_wcdp_deposits_expiration_type', 'num_of_days' );

			if ( 'num_of_days' === $expiration_type ) {
				$expiration_days = get_option( 'yith_wcdp_deposits_expiration_duration', 30 );
			} else {
				$suborder = wc_get_order( $suborder_id );

				if( ! $suborder ){
					return;
				}

				$suborder_expires    = $suborder->get_meta( '_will_suborder_expire', true );
				$suborder_expiration = $suborder->get_meta( '_suborder_expiration', true );

				if ( 'yes' === $suborder_expires && $suborder_expiration ) {
					$expiration_date = $suborder_expiration;
				}
			}

			$message = 'num_of_days' === $expiration_type ?
				// translators: 1. Number of days before expiration.
				sprintf( __( 'This item should be manually refunded by admin, since the %d days available to complete payment have passed and deposit has expired', 'yith-woocommerce-deposits-and-down-payments' ), $expiration_days ) :
				// translators: 1. Expiration date.
				sprintf( __( 'This item should be manually refunded by admin, since deposit has expired on %s', 'yith-woocommerce-deposits-and-down-payments' ), ! empty( $expiration_date ) ? $expiration_date : __( 'N/A', 'yith-woocommerce-deposits-and-down-payments' ) );

			if ( isset( $item['deposit_needs_manual_refund'] ) && $item['deposit_needs_manual_refund'] && 'yes' === $schedule ) {
				$create_refund_for_item_url = esc_url(
					wp_nonce_url(
						add_query_arg(
							array(
								'action'   => 'yith_wcdp_refund_item',
								'order_id' => $order_id,
								'item_id'  => $item_id,
							),
							admin_url( 'admin.php' )
						),
						'yith_wcdp_refund_item'
					)
				);
				$hide_notice_for_item_url   = esc_url(
					wp_nonce_url(
						add_query_arg(
							array(
								'action'   => 'yith_wcdp_delete_refund_notice',
								'order_id' => $order_id,
								'item_id'  => $item_id,
							),
							admin_url( 'admin.php' )
						),
						'yith_wcdp_delete_refund_notice'
					)
				);

				?>
				<div class="yith-wcdp-to-refund-notice error-notice">
					<p>
						<small>
							<?php echo esc_html( $message ); ?>
							<a href="<?php echo esc_url( $create_refund_for_item_url ); ?>"><?php esc_html_e( 'Create refund', 'yith-woocommerce-deposits-and-down-payments' ); ?></a>
							|
							<a href="<?php echo esc_url( $hide_notice_for_item_url ); ?>"><?php esc_html_e( 'Hide this notice', 'yith-woocommerce-deposits-and-down-payments' ); ?></a>
						</small>
					</p>
				</div>
				<?php
			} elseif ( isset( $item['deposit_refunded_after_expiration'] ) && $item['deposit_refunded_after_expiration'] ) {
				$refund_id = $item['deposit_refunded_after_expiration'];
				?>
				<div class="yith-wcdp-to-refund-notice info-notice">
					<p>
						<small>
							<?php
							// translators: 1. Refund id.
							echo esc_html( sprintf( __( 'This item has been refunded, due to deposit expiration (refund #%d)', 'yith-woocommerce-deposits-and-down-payments' ), $refund_id ) );
							?>
						</small>
					</p>
				</div>
				<?php
			}
		}

		/**
		 * Add a view o default order status view, to filter orders that needs manual refund
		 *
		 * @param array $views Current order views.
		 *
		 * @return mixed Filtered array of views
		 * @todo  review code when WC switches to custom tables
		 *
		 * @since 1.0.0
		 */
		public function add_to_refund_deposit_view( $views ) {
			$order_to_refund_count = YITH_WCDP_Suborders_Premium()->count_deposit_to_refund();

			if ( $order_to_refund_count ) {
				$filter_url   = esc_url(
					add_query_arg(
						array(
							'post_type'         => 'shop_order',
							'deposit_to_refund' => true,
						),
						admin_url( 'edit.php' )
					)
				);
				$filter_class = isset( $_GET['deposit_to_refund'] ) ? 'current' : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				$views['deposit_to_refund'] = sprintf( '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>', $filter_url, $filter_class, __( 'Deposit to Refund', 'yith-woocommerce-deposits-and-down-payments' ), $order_to_refund_count );
			}

			return $views;
		}

		/**
		 * Hide plugin item meta, when not in debug mode
		 *
		 * @param array $hidden_items Array of meta to hide on admin side.
		 *
		 * @return mixed Filtered array of meta to hide
		 * @since 1.0.0
		 */
		public function hide_order_item_meta( $hidden_items ) {
			$hidden_items = parent::hide_order_item_meta( $hidden_items );

			if ( ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
				$hidden_items = array_merge(
					$hidden_items,
					array(
						'_deposit_refunded_after_expiration',
						'_deposit_needs_manual_refund',
					)
				);
			}

			return $hidden_items;
		}

		/* === BULK PRODUCT EDITING === */

		/**
		 * Print Quick / Bulk editing fields
		 *
		 * @param string $column_name Current column Name.
		 * @param string $post_type   Current post type.
		 *
		 * @return void
		 * @since 1.0.2
		 */
		public function print_bulk_editing_fields( $column_name, $post_type ) {
			global $post;

			if ( 'product' !== $post_type || 'product_tag' !== $column_name ) {
				return;
			}

			$product = wc_get_product( $post->ID );

			// define variables to use in template.
			$enable_deposit        = 'default';
			$deposit_default       = 'default';
			$force_deposit         = 'default';
			$create_balance_orders = 'default';
			$product_note          = '';

			if ( $product ) {
				// get product meta.
				extract( $this->get_product_meta( $product ) ); // phpcs:ignore WordPress.PHP.DontExtract
			}

			include YITH_WCDP_DIR . 'templates/admin/product-deposit-bulk-edit-premium.php';
		}

		/* === PRODUCT TABS METHODS === */

		/**
		 * Init product's meta
		 *
		 * @return void
		 * @since 4.2.0
		 */
		public function init_product_meta() {
			parent::init_product_meta();

			$this->product_meta = array_merge(
				$this->product_meta,
				array(
					'_deposit_default'                     => array(
						'default' => 'default',
						'options' => array(
							'yes',
							'no',
							'default',
						),
					),
					'_create_balance_orders'               => array(
						'default' => 'default',
						'options' => array(
							'yes',
							'no',
							'default',
						),
					),
					'_product_note'                        => array(
						'default' => '',
					),
					'_deposit_expiration_product_fallback' => array(
						'default' => 'default',
						'options' => array(
							'do_nothing',
							'disable_deposit',
							'item_not_purchasable',
							'hide_item',
						),
					),
					'_deposit_expiration_date'             => array(
						'default' => '',
					),
				)
			);
		}

		/**
		 * Print product tab for deposit plugin
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function print_product_deposit_tabs() {
			global $post;

			$product = wc_get_product( $post->ID );

			// get product meta.
			extract( $this->get_product_meta( $product ) ); // phpcs:ignore WordPress.PHP.DontExtract

			$expiration_type                  = get_option( 'yith_wcdp_deposits_expiration_type' );
			$deposit_expires_on_specific_date = 'specific_date' === $expiration_type;

			include YITH_WCDP_DIR . 'templates/admin/product-deposit-tab-premium.php';
		}

		/* === ADDITIONAL VARIATION OPTIONS */

		/**
		 * Print additional fields on variation tab
		 *
		 * @param int   $loop           Unique ID for current variation row.
		 * @param array $variation_data Array of variation attributes.
		 * @param int   $variation      Variation id.
		 *
		 * @return void
		 * @since 1.0.4
		 */
		public function print_variation_deposit_settings( $loop, $variation_data, $variation ) {

			if (
				apply_filters( 'yith_wcdp_disable_deposit_variation_option', false, $variation ) ||
				! apply_filters( 'yith_wcdp_generate_add_deposit_to_cart_variations_field', true, $variation )
			) {
				return;
			}

			$variation = wc_get_product( $variation );

			// get variation meta.
			extract( $this->get_product_meta( $variation ) ); // phpcs:ignore WordPress.PHP.DontExtract

			// additional data.
			$expiration_type                  = get_option( 'yith_wcdp_deposits_expiration_type' );
			$deposit_expires_on_specific_date = 'specific_date' === $expiration_type;

			include YITH_WCDP_DIR . 'templates/admin/product-deposit-variation.php';
		}

		/**
		 * Save additional fields on variation tab
		 *
		 * @param int $variation_id Variation id.
		 * @param int $loop         Unique ID for current variation row.
		 *
		 * @return void
		 * @since 1.0.4
		 */
		public function save_variation_deposits_settings( $variation_id, $loop ) {
			// we don't need to check for nonce, as WooCommerce already does this on /wp-content/plugins/woocommerce/includes/admin/class-wc-admin-meta-boxes.php:200.
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			if (
				apply_filters( 'yith_wcdp_disable_deposit_variation_option', false, $variation_id ) ||
				! apply_filters( 'yith_wcdp_generate_add_deposit_to_cart_variations_field', true, $variation_id )
			) {
				return;
			}

			$variation = wc_get_product( $variation_id );

			// nonce verification not needed.
			$clean_data = $this->get_deposit_posted_data( $_POST, $loop );

			foreach ( $clean_data as $meta_key => $meta_value ) {
				$variation->update_meta_data( $meta_key, $meta_value );
			}

			$variation->save();
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		}

		/* === PREMIUM OPTIONS === */

		/**
		 * Add tabs for premium options
		 *
		 * @param array $tabs Array of currently available tabs.
		 *
		 * @return mixed Filtered array of tabs
		 * @since 1.0.0
		 */
		public function add_premium_options_tab( $tabs ) {
			$tabs['deposits'] = __( 'Deposits', 'yith-woocommerce-deposits-and-down-payments' );

			unset( $tabs['premium'] );

			return $tabs;
		}

		/**
		 * Adds premium settings to "General" tab in admin panel
		 *
		 * @param array $settings Original array of settings.
		 *
		 * @return mixed Filtered array of settings
		 * @since 1.0.0
		 */
		public function add_premium_general_settings( $settings ) {
			$settings_array = $settings['settings'];

			$deposit_default_option = array(
				'general-deposit-default' => array(
					'title'     => __( 'Deposit checked', 'yith-woocommerce-deposits-and-down-payments' ),
					'type'      => 'yith-field',
					'yith-type' => 'onoff',
					'desc'      => __( 'Whether deposit option should be selected by default or not', 'yith-woocommerce-deposits-and-down-payments' ),
					'id'        => 'yith_wcdp_general_deposit_default',
					'default'   => 'yes',
				),
			);

			/* @since 1.2.0 */
			$deposit_general_option = array(
				'general-enable-ajax-variation-handling' => array(
					'title'     => __( 'Enable AJAX variation', 'yith-woocommerce-deposits-and-down-payments' ),
					'type'      => 'yith-field',
					'yith-type' => 'onoff',
					'desc'      => __( 'Enable this option if you want to load deposits options via AJAX. This should reduce loading time for single product page', 'yith-woocommerce-deposits-and-down-payments' ),
					'id'        => 'yith_wcdp_general_enable_ajax_variation',
					'default'   => 'no',
				),
			);

			$deposit_type_option = array(
				'general-deposit-type' => array(
					'title'     => __( 'Deposit type', 'yith-woocommerce-deposits-and-down-payments' ),
					'type'      => 'yith-field',
					'yith-type' => 'select',
					'class'     => 'wc-enhanced-select',
					'desc'      => __( 'Select the type of deposit you want to apply to selected products', 'yith-woocommerce-deposits-and-down-payments' ),
					'id'        => 'yith_wcdp_general_deposit_type',
					'options'   => array(
						'amount' => __( 'Fixed amount', 'yith-woocommerce-deposits-and-down-payments' ),
						'rate'   => __( 'Percent value of product price', 'yith-woocommerce-deposits-and-down-payments' ),
					),
					'default'   => 'amount',
					'desc_tip'  => true,
				),
			);

			$deposit_rate_option = array(
				'general-deposit-rate' => array(
					'title'             => __( 'Deposit Rate', 'yith-woocommerce-deposits-and-down-payments' ),
					'type'              => 'number',
					'desc'              => __( 'Percentage of product total price required as deposit', 'yith-woocommerce-deposits-and-down-payments' ),
					'id'                => 'yith_wcdp_general_deposit_rate',
					'css'               => 'min-width: 100px;',
					'default'           => 10,
					'custom_attributes' => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 'any',
					),
					'desc_tip'          => true,
				),
			);

			$balance_options = array(
				'balance-options'               => array(
					'title' => __( 'Balance', 'yith-woocommerce-deposits-and-down-payments' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'yith_wcdp_balance_options',
				),

				'balance-type'                  => array(
					'title'     => __( 'Balance type', 'yith-woocommerce-deposits-and-down-payments' ),
					'type'      => 'yith-field',
					'yith-type' => 'select',
					'class'     => 'wc-enhanced-select',
					'options'   => array(
						'none'     => __( 'Do not create any balance order', 'yith-woocommerce-deposits-and-down-payments' ),
						'single'   => __( 'Create a single balance order for all items purchased with deposit', 'yith-woocommerce-deposits-and-down-payments' ),
						'multiple' => __( 'Create one balance order for each item purchased with deposit', 'yith-woocommerce-deposits-and-down-payments' ),
					),
					'id'        => 'yith_wcdp_balance_type',
					'default'   => 'multiple',
				),

				'general-create-balance-orders' => array(
					'title'     => __( 'Let users pay balance orders online', 'yith-woocommerce-deposits-and-down-payments' ),
					'type'      => 'yith-field',
					'yith-type' => 'onoff',
					'desc'      => __( 'Check this option to create balance orders with "Pending Payment" status, so that users can complete purchases online; otherwise "On Hold" status will be applied (this behaviour can be overridden on product level)', 'yith-woocommerce-deposits-and-down-payments' ),
					'id'        => 'yith_wcdp_general_create_balance_orders',
					'default'   => 'yes',
				),

				'balance-options-end'           => array(
					'type' => 'sectionend',
					'id'   => 'yith_wcdp_balance_options',
				),
			);

			$deposit_labels_options = array(
				'deposit-labels-options'               => array(
					'title' => __( 'Labels & Messages', 'yith-woocommerce-deposits-and-down-payments' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'yith_wcdp_deposit_labels_options',
				),

				'deposit-labels-deposit'               => array(
					'title'   => __( 'Deposit', 'yith-woocommerce-deposits-and-down-payments' ),
					'type'    => 'text',
					'css'     => 'min-width: 300px;',
					'id'      => 'yith_wcdp_deposit_labels_deposit',
					'default' => __( 'Deposit', 'yith-wcdp' ),
				),

				'deposit-labels-pay-deposit'           => array(
					'title'   => __( 'Pay deposit', 'yith-woocommerce-deposits-and-down-payments' ),
					'type'    => 'text',
					'css'     => 'min-width: 300px;',
					'id'      => 'yith_wcdp_deposit_labels_pay_deposit',
					'default' => __( 'Pay Deposit', 'yith-woocommerce-deposits-and-down-payments' ),
				),

				'deposit-labels-pay-full-amount'       => array(
					'title'   => __( 'Pay full amount', 'yith-woocommerce-deposits-and-down-payments' ),
					'type'    => 'text',
					'css'     => 'min-width: 300px;',
					'id'      => 'yith_wcdp_deposit_labels_pay_full_amount',
					'default' => __( 'Pay Full Amount', 'yith-woocommerce-deposits-and-down-payments' ),
				),

				'deposit-labels-partially-paid-status' => array(
					'title'   => __( 'Partially Paid', 'yith-woocommerce-deposits-and-down-payments' ),
					'type'    => 'text',
					'css'     => 'min-width: 300px;',
					'id'      => 'yith_wcdp_deposit_labels_partially_paid_status',
					'default' => __( 'Partially Paid', 'yith-woocommerce-deposits-and-down-payments' ),
				),

				'deposit-labels-full-price-label'      => array(
					'title'   => __( 'Full price label', 'yith-woocommerce-deposits-and-down-payments' ),
					'type'    => 'text',
					'css'     => 'min-width: 300px;',
					'id'      => 'yith_wcdp_deposit_labels_full_price_label',
					'default' => __( 'Full price', 'yith-woocommerce-deposits-and-down-payments' ),
				),

				'deposit-labels-balance-label'         => array(
					'title'   => __( 'Balance label', 'yith-woocommerce-deposits-and-down-payments' ),
					'type'    => 'text',
					'css'     => 'min-width: 300px;',
					'id'      => 'yith_wcdp_deposit_labels_balance_label',
					'default' => __( 'Balance', 'yith-woocommerce-deposits-and-down-payments' ),
				),

				'deposit-labels-pay-in-loco'           => array(
					'title'   => __( 'Pay on location', 'yith-woocommerce-deposits-and-down-payments' ),
					'type'    => 'textarea',
					'css'     => 'width: 100%; min-height: 150px;',
					'id'      => 'yith_wcdp_deposit_labels_pay_in_loco',
					'default' => __( 'You can complete this order on location', 'yith-woocommerce-deposits-and-down-payments' ),
				),

				'deposit-labels-product-note-position' => array(
					'title'     => __( 'Position of product note', 'yith-woocommerce-deposits-and-down-payments' ),
					'type'      => 'yith-field',
					'yith-type' => 'select',
					'class'     => 'wc-enhanced-select',
					'options'   => array(
						'none'                         => __( 'Do not show any note on product', 'yith-woocommerce-deposits-and-down-payments' ),
						'woocommerce_template_single_title' => __( 'Below product title', 'yith-woocommerce-deposits-and-down-payments' ),
						'woocommerce_template_single_price' => __( 'Below product price', 'yith-woocommerce-deposits-and-down-payments' ),
						'woocommerce_template_single_excerpt' => __( 'Below product excerpt', 'yith-woocommerce-deposits-and-down-payments' ),
						'woocommerce_template_single_add_to_cart' => __( 'Below single Add to Cart', 'yith-woocommerce-deposits-and-down-payments' ),
						'woocommerce_product_meta_end' => __( 'Below product meta', 'yith-woocommerce-deposits-and-down-payments' ),
						'woocommerce_template_single_sharing' => __( 'Below product share', 'yith-woocommerce-deposits-and-down-payments' ),
					),
					'id'        => 'yith_wcdp_deposit_labels_product_note_position',
					'default'   => '',
				),

				'deposit-labels-product-note'          => array(
					'title'    => __( 'Product note', 'yith-woocommerce-deposits-and-down-payments' ),
					'type'     => 'textarea',
					'css'      => 'width: 100%; min-height: 150px;',
					'id'       => 'yith_wcdp_deposit_labels_product_note',
					'desc'     => __( 'You can override this option from single product edit page', 'yith-woocommerce-deposits-and-down-payments' ),
					'desc_tip' => true,
					'default'  => '',
				),

				'deposit-labels-options-end'           => array(
					'type' => 'sectionend',
					'id'   => 'yith_wcdp_deposit_labels_options',
				),
			);

			$deposit_expiration_options = array(
				'deposit-expiration-options'          => array(
					'title' => __( 'Deposit expiration', 'yith-woocommerce-deposits-and-down-payments' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'yith_wcdp_deposit_expiration_options',
				),

				'deposit-expiration-enable'           => array(
					'title'     => __( 'Enable deposit expiration', 'yith-woocommerce-deposits-and-down-payments' ),
					'type'      => 'yith-field',
					'yith-type' => 'onoff',
					'desc'      => __( 'Check this option, if you want to set a number of days, after which order with deposits cannot be completed anymore', 'yith-woocommerce-deposits-and-down-payments' ),
					'id'        => 'yith_wcdp_deposit_expiration_enable',
					'default'   => 'no',
				),

				'deposit-expiration-type'             => array(
					'title'     => __( 'Deposit expires', 'yith-woocommerce-deposits-and-down-payments' ),
					'type'      => 'yith-field',
					'yith-type' => 'select',
					'class'     => 'wc-enhanced-select',
					'desc'      => __( 'Choose how plugin should calculate when a deposit is expired', 'yith-woocommerce-deposits-and-down-payments' ),
					'id'        => 'yith_wcdp_deposits_expiration_type',
					'css'       => 'min-width: 100px;',
					'default'   => 'num_of_days',
					'options'   => array(
						'num_of_days'   => __( 'After some days from its creation', 'yith-woocommerce-deposits-and-down-payments' ),
						'specific_date' => __( 'On a specific date', 'yith-woocommerce-deposits-and-down-payments' ),
					),
					'desc_tip'  => true,
				),

				'deposit-expiration-duration'         => array(
					'title'             => __( 'Days before expiration', 'yith-woocommerce-deposits-and-down-payments' ),
					'type'              => 'number',
					'desc'              => __( 'Number of days after which order with deposit cannot be completed anymore', 'yith-woocommerce-deposits-and-down-payments' ),
					'id'                => 'yith_wcdp_deposits_expiration_duration',
					'css'               => 'min-width: 100px;',
					'default'           => 30,
					'custom_attributes' => array(
						'min'  => 1,
						'max'  => 9999999,
						'step' => 1,
					),
					'desc_tip'          => true,
				),

				'deposit-expiration-date'             => array(
					'title'    => __( 'Expiration date', 'yith-woocommerce-deposits-and-down-payments' ),
					'type'     => 'text',
					'desc'     => __( 'Expiration date for depist (you can override this setting in product page)', 'yith-woocommerce-deposits-and-down-payments' ),
					'id'       => 'yith_wcdp_deposits_expiration_date',
					'css'      => 'min-width: 100px;',
					'default'  => '',
					'class'    => 'date-picker',
					'desc_tip' => true,
				),

				'deposit-expiration-product-fallback' => array(
					'title'     => __( 'Product status', 'yith-woocommerce-deposits-and-down-payments' ),
					'type'      => 'yith-field',
					'yith-type' => 'select',
					'class'     => 'wc-enhanced-select',
					'desc'      => __( 'Choose what changes in product when deposit expires', 'yith-woocommerce-deposits-and-down-payments' ),
					'id'        => 'yith_wcdp_deposits_expiration_product_fallback',
					'css'       => 'min-width: 100px;',
					'default'   => 'disable_deposit',
					'options'   => array(
						'do_nothing'           => __( 'Do nothing', 'yith-woocommerce-deposits-and-down-payments' ),
						'disable_deposit'      => __( 'Just disable deposit', 'yith-woocommerce-deposits-and-down-payments' ),
						'item_not_purchasable' => __( 'Make item no longer purchasable', 'yith-woocommerce-deposits-and-down-payments' ),
						'hide_item'            => __( 'Hide item from catalog visibility', 'yith-woocommerce-deposits-and-down-payments' ),
					),
					'desc_tip'  => true,
				),

				'deposit-expiration-fallback'         => array(
					'title'     => __( 'Expiration fallback', 'yith-woocommerce-deposits-and-down-payments' ),
					'type'      => 'yith-field',
					'yith-type' => 'select',
					'class'     => 'wc-enhanced-select',
					'desc'      => __( 'Select an action to carry out when a deposit expires', 'yith-woocommerce-deposits-and-down-payments' ),
					'id'        => 'yith_wcdp_deposit_expiration_fallback',
					'options'   => array(
						'none'   => __( 'Do nothing', 'yith-woocommerce-deposits-and-down-payments' ),
						'refund' => __( 'Refund deposit for the product', 'yith-woocommerce-deposits-and-down-payments' ),
					),
					'default'   => 'none',
					'desc_tip'  => true,
				),

				'deposit-expiration-options-end'      => array(
					'type' => 'sectionend',
					'id'   => 'yith_wcdp_deposit_expiration_options',
				),
			);

			$notify_options = array(
				'notify-options'                   => array(
					'title' => __( 'Notify options', 'yith-woocommerce-deposits-and-down-payments' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'yith_wcdp_notify_options',
				),

				'notify-customer-deposit-created'  => array(
					'title'     => __( 'Customer deposit created', 'yith-woocommerce-deposits-and-down-payments' ),
					'type'      => 'yith-field',
					'yith-type' => 'onoff',
					'desc'      => __( 'Send an email to customer when an order with deposit is created', 'yith-woocommerce-deposits-and-down-payments' ),
					'id'        => 'yith_wcdp_notify_customer_deposit_created',
					'default'   => 'yes',
				),

				'notify-admin-deposit-created'     => array(
					'title'     => __( 'Admin - for deposit created', 'yith-woocommerce-deposits-and-down-payments' ),
					'type'      => 'yith-field',
					'yith-type' => 'onoff',
					'desc'      => __( 'Send an email to admin(s) when an order with deposit is created', 'yith-woocommerce-deposits-and-down-payments' ),
					'id'        => 'yith_wcdp_notify_admin_deposit_created',
					'default'   => 'yes',
				),

				'notify-customer-deposit-expiring' => array(
					'title'     => __( 'Customer - deposit expiring', 'yith-woocommerce-deposits-and-down-payments' ),
					'type'      => 'yith-field',
					'yith-type' => 'onoff',
					'desc'      => __( 'Send an email to customer when an order is expiring', 'yith-woocommerce-deposits-and-down-payments' ),
					'id'        => 'yith_wcdp_notify_customer_deposit_expiring',
					'default'   => 'yes',
				),

				'notify-customer-deposit-expiring-days-limit' => array(
					'title'             => __( 'Days before notification', 'yith-woocommerce-deposits-and-down-payments' ),
					'type'              => 'number',
					'desc'              => __( 'Set here the number of days before expiration of a deposit, when a notifying email will be sent to customers', 'yith-woocommerce-deposits-and-down-payments' ),
					'id'                => 'yith_wcdp_notify_customer_deposit_expiring_days_limit',
					'css'               => 'min-width: 100px;',
					'default'           => 15,
					'custom_attributes' => array(
						'min'  => 1,
						'max'  => 9999999,
						'step' => 1,
					),
					'desc_tip'          => true,
				),

				'notify-options-end'               => array(
					'type' => 'sectionend',
					'id'   => 'yith_wcdp_notify_options',
				),
			);

			$array_chunk_1 = array_slice( $settings_array, 0, 3 );
			$array_chunk_2 = array_splice( $settings_array, 3, count( $settings_array ) - 1 );

			$settings_array = array_merge(
				$array_chunk_1,
				$deposit_default_option,
				$array_chunk_2
			);

			$array_chunk_1 = array_slice( $settings_array, 0, 5 );
			$array_chunk_2 = array_splice( $settings_array, 5, count( $settings_array ) - 1 );

			$settings_array = array_merge(
				$array_chunk_1,
				$deposit_general_option,
				$array_chunk_2
			);

			$array_chunk_1 = array_slice( $settings_array, 0, 8 );
			$array_chunk_2 = array_splice( $settings_array, 8, count( $settings_array ) - 1 );

			$settings_array = array_merge(
				$array_chunk_1,
				$deposit_type_option,
				$array_chunk_2
			);

			$array_chunk_1 = array_slice( $settings_array, 0, 10 );
			$array_chunk_2 = array_splice( $settings_array, 10, count( $settings_array ) - 1 );

			$settings_array = array_merge(
				$array_chunk_1,
				$deposit_rate_option,
				$array_chunk_2
			);

			$array_chunk_1 = $settings_array;
			$array_chunk_2 = array();

			$settings_array = array_merge(
				$array_chunk_1,
				$balance_options,
				$deposit_labels_options,
				$deposit_expiration_options,
				$notify_options,
				$array_chunk_2
			);

			$settings['settings'] = $settings_array;

			return $settings;
		}

		/* === AJAX REQUEST METHODS === */

		/**
		 * Print json encoded list of user's role matching filter (param $term in request used to filter)
		 * Array is formatted as role_slug => Verbose role description
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function get_roles_via_ajax() {
			global $wp_roles;

			ob_start();

			check_ajax_referer( 'search-products', 'security' );

			if ( ! current_user_can( 'edit_shop_orders' ) ) {
				die( - 1 );
			}

			$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : false;

			if ( empty( $term ) ) {
				die();
			}

			$found_roles = array();
			$roles_names = $wp_roles->get_names();

			if ( ! empty( $roles_names ) ) {
				foreach ( $roles_names as $slug => $name ) {
					$name = translate_user_role( $name );
					if ( strpos( strtolower( $name ), strtolower( $term ) ) !== false ) {
						$found_roles[ $slug ] = $name;
					}
				}
			}

			wp_send_json( $found_roles );
		}

		/**
		 * Returns single instance of the class
		 *
		 * @return \YITH_WCDP_Admin_Premium
		 * @since 1.0.0
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}
}

/**
 * Unique access to instance of YITH_WCDP_Admin_Premium class
 *
 * @return \YITH_WCDP_Admin_Premium
 * @since 1.0.0
 */
function YITH_WCDP_Admin_Premium() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return YITH_WCDP_Admin_Premium::get_instance();
}

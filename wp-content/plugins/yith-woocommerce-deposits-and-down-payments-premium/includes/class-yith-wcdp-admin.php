<?php
/**
 * Admin class
 *
 * @author  YITH
 * @package YITH\Deposits\Classes
 * @version 1.0.0
 */

if ( ! defined( 'YITH_WCDP' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCDP_Admin' ) ) {
	/**
	 * WooCommerce Deposits and Down Payments Admin
	 *
	 * @since 1.0.0
	 */
	class YITH_WCDP_Admin {

		/**
		 * Single instance of the class
		 *
		 * @var \YITH_WCDP_Admin
		 * @since 1.0.0
		 */
		protected static $instance;

		/**
		 * An array of meta available for the product
		 *
		 * @var array
		 */
		protected $product_meta = array();

		/**
		 * Docs url
		 *
		 * @var string Official documentation url
		 * @since 1.0.0
		 */
		public $doc_url = 'https://yithemes.com/docs-plugins/yith-woocommerce-deposits-and-down-payments/';

		/**
		 * Premium landing url
		 *
		 * @var string Premium landing url
		 * @since 1.0.0
		 */
		public $premium_landing_url = 'https://yithemes.com/themes/plugins/yith-woocommerce-deposits-and-down-payments/';

		/**
		 * Live demo url
		 *
		 * @var string Live demo url
		 * @since 1.0.0
		 */
		public $live_demo_url = 'https://plugins.yithemes.com/yith-woocommerce-deposits-and-down-payments/';

		/**
		 * List of available tab for deposit panel
		 *
		 * @var array
		 * @access public
		 * @since  1.0.0
		 */
		public $available_tabs = array();

		/**
		 * Constructor method
		 *
		 * @return \YITH_WCDP_Admin
		 * @since 1.0.0
		 */
		public function __construct() {
			// init product additional data.
			$this->init_product_meta();

			// sets available tab.
			$this->available_tabs = apply_filters(
				'yith_wcdp_available_admin_tabs',
				array(
					'settings' => __( 'Settings', 'yith-woocommerce-deposits-and-down-payments' ),
					'premium'  => __( 'Premium Version', 'yith-woocommerce-deposits-and-down-payments' ),
				)
			);

			// register plugin panel.
			add_action( 'admin_menu', array( $this, 'register_panel' ), 5 );
			add_action( 'yith_wcdp_premium_tab', array( $this, 'print_premium_tab' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );

			// register product tabs.
			add_filter( 'woocommerce_product_data_tabs', array( $this, 'register_product_tabs' ) );
			add_action( 'woocommerce_product_data_panels', array( $this, 'print_product_deposit_tabs' ), 10 );

			// register quick edit / bulk edit.
			add_action( 'quick_edit_custom_box', array( $this, 'print_bulk_editing_fields' ), 10, 2 );
			add_action( 'bulk_edit_custom_box', array( $this, 'print_bulk_editing_fields' ), 10, 2 );
			add_action( 'save_post', array( $this, 'save_bulk_editing_fields' ), 10, 2 );

			// save tabs options.
			add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_deposit_tabs' ), 10, 1 );

			// admin order view handling.
			add_filter( 'request', array( $this, 'filter_order_list' ), 10, 1 );
			add_filter( 'wp_count_posts', array( $this, 'filter_order_counts' ), 10, 3 );
			add_filter( 'manage_shop_order_posts_columns', array( $this, 'shop_order_columns' ), 15 );
			add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_shop_order_columns' ) );
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 30 );
			add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hide_order_item_meta' ) );
			add_action( 'woocommerce_before_order_itemmeta', array( $this, 'print_full_payment_order_itemmeta' ), 10, 2 );

			add_filter( 'woocommerce_order_item_get_name', array( $this, 'filter_order_items' ), 10, 2 );
			add_filter( 'woocommerce_order_get_items', array( $this, 'filter_order_items' ), 10, 2 );

			// filter woocomerce reports.
			add_filter( 'woocommerce_reports_get_order_report_data_args', array( $this, 'filter_sales_report' ) );

			// register plugin links & meta row.
			add_filter( 'plugin_action_links_' . YITH_WCDP_INIT, array( $this, 'action_links' ) );
			add_filter( 'yith_show_plugin_row_meta', array( $this, 'add_plugin_meta' ), 10, 5 );
		}

		/* === HELPER METHODS === */

		/**
		 * Return array of screen ids for affiliate plugin
		 *
		 * @return mixed Array of available screens
		 * @since 1.0.0
		 */
		public function get_screen_ids() {
			$base = sanitize_title( __( 'YITH Plugins', 'yith-plugin-fw' ) );

			$screen_ids = array(
				$base . '_page_yith_wcdp_panel',
			);

			return apply_filters( 'yith_wcdp_screen_ids', $screen_ids );
		}

		/* === PANEL METHODS === */

		/**
		 * Register panel
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function register_panel() {
			$args = array(
				'create_menu_page' => true,
				'parent_slug'      => '',
				'page_title'       => 'YITH WooCommerce Deposits and Down Payments',
				'menu_title'       => 'Deposits and Down Payments',
				'capability'       => 'manage_options',
				'class'            => yith_set_wrapper_class(),
				'parent'           => '',
				'parent_page'      => 'yith_plugin_panel',
				'page'             => 'yith_wcdp_panel',
				'admin-tabs'       => $this->available_tabs,
				'options-path'     => YITH_WCDP_DIR . 'plugin-options',
			);

			/* === Fixed: not updated theme  === */
			if ( ! class_exists( 'YIT_Plugin_Panel_WooCommerce' ) ) {
				require_once YITH_WCDP_DIR . 'plugin-fw/lib/yit-plugin-panel-wc.php';
			}

			$this->_panel = new YIT_Plugin_Panel_WooCommerce( $args );
		}

		/**
		 * Print premium tab
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function print_premium_tab() {
			include YITH_WCDP_DIR . 'templates/admin/deposits-premium-panel.php';
		}

		/**
		 * Enqueue admin side scripts
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function enqueue() {
			// enqueue scripts.
			$screen = get_current_screen();
			$path   = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'unminified/' : '';
			$suffix = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? '' : '.min';

			$scripts_screens = array_merge(
				$this->get_screen_ids(),
				array(
					'edit-shop_order',
					'shop_order',
					'product',
				)
			);

			if ( in_array( $screen->id, $scripts_screens, true ) ) {
				wp_register_script(
					'yith-wcdp',
					YITH_WCDP_URL . 'assets/js/admin/' . $path . 'yith-wcdp' . $suffix . '.js',
					array(
						'jquery',
						'jquery-ui-datepicker',
						'wc-admin-meta-boxes',
					),
					YITH_WCDP::YITH_WCDP_VERSION,
					true
				);
				do_action( 'yith_wcdp_before_admin_script_enqueue' );
				wp_enqueue_script( 'yith-wcdp' );

				wp_register_style( 'yith-wcdp', YITH_WCDP_URL . 'assets/css/admin/yith-wcdp.css', array(), YITH_WCDP::YITH_WCDP_VERSION );
				do_action( 'yith_wcdp_before_admin_style_enqueue' );
				wp_enqueue_style( 'yith-wcdp' );
			}
		}

		/* === BULK PRODUCT EDITING === */

		/**
		 * Print Quick / Bulk editing fields
		 *
		 * @param string $column_name Current column Name.
		 * @param string $post_type   Current post type.
		 *
		 * @return void
		 * @todo  review code when WC switches to custom table
		 * @since 1.0.2
		 */
		public function print_bulk_editing_fields( $column_name, $post_type ) {
			global $post;

			$product = wc_get_product( $post->ID );

			if ( 'product' !== $post_type || 'product_type' !== $column_name || ! $product ) {
				return;
			}

			// define variables to use in template.
			$enable_deposit = 'default';
			$force_deposit  = 'default';

			if ( $product ) {
				// get product meta.
				extract( $this->get_product_meta( $product ) ); // phpcs:ignore WordPress.PHP.DontExtract
			}

			include YITH_WCDP_DIR . 'templates/admin/product-deposit-bulk-edit.php';
		}

		/**
		 * Save Quick / Bulk editing fields
		 *
		 * @param int     $post_id Post id.
		 * @param WP_Post $post    Post object.
		 *
		 * @return void
		 * @since 1.0.2
		 */
		public function save_bulk_editing_fields( $post_id, $post ) {
			// If this is an autosave, our form has not been submitted, so we don't want to do anything.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			// Don't save revisions and autosaves.
			if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || 'product' !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			// Check nonce.
			if ( ! isset( $_REQUEST['woocommerce_quick_edit_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['woocommerce_quick_edit_nonce'] ) ), 'woocommerce_quick_edit_nonce' ) ) {
				return;
			}

			$post_ids   = ( ! empty( $_REQUEST['post'] ) ) ? array_map( 'absint', (array) $_REQUEST['post'] ) : array();
			$clean_data = $this->get_deposit_posted_data( $_REQUEST );

			if ( empty( $post_ids ) ) {
				$post_ids = array( $post_id );
			}

			// if everything is in order.
			if ( ! empty( $post_ids ) && is_array( $post_ids ) ) {
				foreach ( $post_ids as $post_id ) {
					$product = wc_get_product( $post_id );

					if ( ! $product ) {
						continue;
					}

					if ( ! current_user_can( 'edit_post', $post_id ) ) {
						continue;
					}

					foreach ( $clean_data as $meta_key => $meta_value ) {
						$product->update_meta_data( $meta_key, $meta_value );
					}

					$product->save();
				}
			}
		}

		/* == PRODUCT TABS METHODS === */

		/**
		 * Init product's meta
		 *
		 * @return void
		 * @since 4.2.0
		 */
		public function init_product_meta() {
			$this->product_meta = array(
				'_enable_deposit' => array(
					'default' => 'default',
					'options' => array(
						'yes',
						'no',
						'default',
					),
				),
				'_force_deposit'  => array(
					'default' => 'default',
					'options' => array(
						'yes',
						'no',
						'default',
					),
				),
			);
		}

		/**
		 * Returns deposit meta for passed product
		 *
		 * @param WC_Product $product Product object.
		 *
		 * @return array Array of deposit-related meta for the product.
		 */
		public function get_product_meta( $product ) {
			$product_meta = array();

			if ( ! $product ) {
				return $product_meta;
			}

			foreach ( $this->product_meta as $meta_key => $meta_options ) {
				$variable = 0 === strpos( $meta_key, '_' ) ? substr( $meta_key, 1 ) : $meta_key;
				$default  = isset( $meta_options['default'] ) ? $meta_options['default'] : '';

				$meta_value = $product->get_meta( $meta_key );

				$product_meta[ $variable ] = ! empty( $meta_value ) ? $meta_value : $default;
			}

			return $product_meta;
		}

		/**
		 * Get cleaned product meta, as submitted in the posted data
		 *
		 * @param array $posted Posted data.
		 * @param int   $loop   Index for current row (in case of variations).
		 *
		 * @return array Clean posted data.
		 * @since 4.2.0
		 */
		public function get_deposit_posted_data( $posted, $loop = false ) {
			$cleaned = array();

			if ( ! $this->product_meta ) {
				return $cleaned;
			}

			foreach ( $this->product_meta as $meta_key => $meta_options ) {
				$posted_value = false;

				if ( false !== $loop && isset( $posted[ $meta_key ][ $loop ] ) ) {
					$posted_value = $posted[ $meta_key ][ $loop ];
				} elseif ( false === $loop && isset( $posted[ $meta_key ] ) ) {
					$posted_value = $posted[ $meta_key ];
				}

				if ( isset( $meta_options['options'] ) && in_array( $posted_value, $meta_options['options'], true ) ) {
					$cleaned[ $meta_key ] = $posted_value;
				} elseif ( ! isset( $meta_options['options'] ) ) {
					$cleaned[ $meta_key ] = sanitize_text_field( wp_unslash( $posted_value ) );
				}

				if ( empty( $cleaned[ $meta_key ] ) && isset( $meta_options['default'] ) ) {
					$cleaned[ $meta_key ] = $meta_options['default'];
				}
			}

			return $cleaned;
		}

		/**
		 * Register product tabs for deposit plugin
		 *
		 * @param array $tabs Registered tabs.
		 *
		 * @return array Filtered array of registered tabs
		 * @since 1.0.0
		 */
		public function register_product_tabs( $tabs ) {
			$tabs = array_merge(
				$tabs,
				array(
					'deposit' => array(
						'label'  => __( 'Deposit', 'yith-woocommerce-deposits-and-down-payments' ),
						'target' => 'yith_wcdp_deposit_tab',
						'class'  => array( 'hide_if_grouped', 'hide_if_external' ),
					),
				)
			);

			return $tabs;
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

			// define variables to use in template.
			$enable_deposit = $product->get_meta( '_enable_deposit' );
			$enable_deposit = ! empty( $enable_deposit ) ? $enable_deposit : 'default';

			$force_deposit = $product->get_meta( '_force_deposit' );
			$force_deposit = ! empty( $force_deposit ) ? $force_deposit : 'default';

			include YITH_WCDP_DIR . 'templates/admin/product-deposit-tab.php';
		}

		/**
		 * Save deposit tab options
		 *
		 * @param int $post_id Current product id.
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function save_product_deposit_tabs( $post_id ) {
			// we don't need to check for nonce, as WooCommerce already does this on /wp-content/plugins/woocommerce/includes/admin/class-wc-admin-meta-boxes.php:200.
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			$product = wc_get_product( $post_id );

			// nonce verification not needed.
			$clean_data = $this->get_deposit_posted_data( $_POST );

			foreach ( $clean_data as $meta_key => $meta_value ) {
				$product->update_meta_data( $meta_key, $meta_value );
			}

			$product->save();
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		}

		/* === PLUGIN LINK METHODS === */

		/**
		 * Get the premium landing uri
		 *
		 * @return  string The premium landing link
		 * @author  Andrea Grillo <andrea.grillo@yithemes.com>
		 * @since   1.0.0
		 */
		public function get_premium_landing_uri() {
			return $this->premium_landing_url;
		}

		/**
		 * Add plugin action links
		 *
		 * @param array $links Plugins links array.
		 *
		 * @return array Filtered link array
		 * @since 1.0.0
		 */
		public function action_links( $links ) {
			$links = yith_add_action_links( $links, 'yith_wcdp_panel', defined( 'YITH_WCDP_PREMIUM_INIT' ), YITH_WCDP_SLUG );

			return $links;
		}

		/**
		 * Adds plugin row meta
		 *
		 * @param array    $new_row_meta_args  New arguments.
		 * @param string[] $plugin_meta        An array of the plugin's metadata, including the version, author, author URI, and plugin URI.
		 * @param string   $plugin_file        Path to the plugin file relative to the plugins directory.
		 * @param array    $plugin_data        An array of plugin data.
		 * @param string   $status             Status filter currently applied to the plugin list.
		 * @param string   $init_file          Constant with plugin_file.
		 *
		 * @return array Filtered array of plugin meta
		 * @since 1.0.0
		 */
		public function add_plugin_meta( $new_row_meta_args, $plugin_meta, $plugin_file, $plugin_data, $status, $init_file = 'YITH_WCDP_INIT' ) {
			if ( defined( $init_file ) && constant( $init_file ) === $plugin_file ) {
				$new_row_meta_args['slug'] = 'yith-woocommerce-deposits-and-down-payments';
			}

			if ( defined( 'YITH_WCDP_PREMIUM_INIT' ) ) {
				$new_row_meta_args['is_premium'] = true;

			}

			return $new_row_meta_args;
		}

		/* === ORDER VIEW METHODS === */

		/**
		 * Only show parent orders
		 *
		 * @param array $query Orders query.
		 *
		 * @return array          Modified request
		 * @todo   review code when WC switches to custom tables
		 *
		 * @since  1.0.0
		 */
		public function filter_order_list( $query ) {
			global $typenow;

			if ( 'shop_order' === $typenow ) {
				$query['post__not_in'] = YITH_WCDP_Suborders()->get_all_balances_ids();

				$query = apply_filters( "yith_wcdp_{$typenow}_request", $query );
			}

			return $query;
		}

		/**
		 * Filter views count for admin views, to count only parent orders
		 *
		 * @param array  $counts Array of post stati count.
		 * @param string $type   Current post type.
		 * @param string $perm   The permission to determine if the posts are 'readable' by the current user.
		 *
		 * @return array filtered array of counts
		 * @todo  review code when WC switches to custom tables
		 *
		 * @since 1.1.1
		 */
		public function filter_order_counts( $counts, $type, $perm ) {
			global $wpdb;

			if ( 'shop_order' === $type ) {
				$query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s AND ID NOT IN ( SELECT post_ID FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s )";

				if ( 'readable' === $perm && is_user_logged_in() ) {
					$post_type_object = get_post_type_object( $type );
					if ( ! current_user_can( $post_type_object->cap->read_private_posts ) ) {
						$query .= $wpdb->prepare(
							" AND (post_status != 'private' OR ( post_author = %d AND post_status = 'private' ))",
							get_current_user_id()
						);
					}
				}
				$query .= ' GROUP BY post_status';

				$results = (array) $wpdb->get_results( $wpdb->prepare( $query, $type, '_created_via', 'yith_wcdp_balance_order' ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL

				if ( ! empty( $results ) ) {
					$results = array_combine( wp_list_pluck( $results, 'post_status' ), array_map( 'intval', wp_list_pluck( $results, 'num_posts' ) ) );
				}

				foreach ( array_keys( wc_get_order_statuses() ) as $order_status ) {
					$counts->{$order_status} = isset( $results[ $order_status ] ) ? $results[ $order_status ] : 0;
				}
			}

			return $counts;
		}

		/**
		 * Add and reorder order table column
		 *
		 * @param array $order_columns Order table's columns.
		 *
		 * @return array Filtered list of oreder table's columns.
		 * @todo  review code when WC switches to custom tables
		 *
		 * @since 1.0.0
		 */
		public function shop_order_columns( $order_columns ) {
			$suborder      = array( 'balance' => _x( 'Balances', 'Admin: column heading in "Orders" table', 'yith-woocommerce-deposits-and-down-payments' ) );
			$ref_pos       = array_search( 'order_status', array_keys( $order_columns ), true );
			$order_columns = array_slice( $order_columns, 0, $ref_pos + 1, true ) + $suborder + array_slice( $order_columns, $ref_pos + 1, count( $order_columns ) - 1, true );

			return $order_columns;
		}

		/**
		 * Output custom columns for coupons
		 *
		 * @param string $column Column to render.
		 */
		public function render_shop_order_columns( $column ) {
			global $post, $the_order;

			if ( empty( $the_order ) || $the_order->get_id() !== $post->ID ) {
				$_the_order = wc_get_order( $post->ID );
			} else {
				$_the_order = $the_order;
			}

			$order_id = $_the_order->get_id();

			$suborder_ids = YITH_WCDP_Suborders()->get_suborder( $order_id );

			switch ( $column ) {
				case 'balance':
					if ( $suborder_ids ) {
						foreach ( $suborder_ids as $suborder_id ) {
							$suborder        = wc_get_order( $suborder_id );
							$items           = $suborder->get_items( 'line_item' );
							$order_uri       = esc_url( 'post.php?post=' . absint( $suborder_id ) . '&action=edit' );
							$items_to_string = array();

							if ( ! empty( $items ) ) {
								foreach ( $items as $item ) {
									$product_id        = method_exists( $item, 'get_product_id' ) ? $item->get_product_id() : $item['product_id'];
									$product_uri       = get_edit_post_link( $product_id );
									$items_to_string[] = sprintf( '<a href="%s">%s</a> (%s)', $product_uri, $item['name'], wc_price( $suborder->get_item_total( $item ) ) );
								}
							}

							$items_to_string  = implode( '', $items_to_string );
							$additional_class = version_compare( WC()->version, '3.2.0', '>=' ) ? 'new-style' : '';

							printf(
								'<div class="suborder-details %1$s">
                                    <mark class="order-status tips status-%2$s " data-tip="%3$s"><span>%3$s</span></mark>
                                    <a href="#" class="order-preview" data-order-id="%4$d" title="%5$s">%5$s</a>
                                </div>',
								esc_attr( $additional_class ),
								esc_attr( sanitize_title( $suborder->get_status() ) ),
								esc_attr( wc_get_order_status_name( $suborder->get_status() ) ),
								esc_attr( $suborder_id ),
								esc_attr__( 'Preview', 'yith-woocommerce-deposits-and-down-payments' )
							);
						}
					} else {
						echo '<span class="na">&ndash;</span>';
					}

					break;
				case 'order_status':
					$column = '';

					if ( $suborder_ids ) {
						$count_uncompleted = 0;
						foreach ( $suborder_ids as $suborder_id ) {

							$suborder = wc_get_order( $suborder_id );

							if ( ! $suborder->has_status( array( 'completed', 'processing', 'cancelled', 'refunded' ) ) ) {
								$count_uncompleted ++;
							}
						}

						if ( $count_uncompleted ) {
							$column .= '<span class="pending-count">' . esc_html( $count_uncompleted ) . '</span>';
						}
					}

					echo wp_kses_post( $column );

					break;
			}
		}

		/**
		 * Add suborder metaboxes for Deposit order
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function add_meta_boxes() {
			global $post;

			if ( ! $post ) {
				return;
			}

			$order = wc_get_order( $post->ID );

			if ( ! $order ) {
				return;
			}

			$has_suborder = YITH_WCDP_Suborders()->get_suborder( absint( $post->ID ) );
			$is_suborder  = YITH_WCDP_Suborders()->is_suborder( $post->ID );

			if ( $has_suborder ) {
				$metabox_suborder_description = _x( 'Balance Orders', 'Admin: Single order page. Suborder details box', 'yith-woocommerce-deposits-and-down-payments' ) . ' <span class="tips" data-tip="' . esc_attr__( 'Note: from this box you can monitor the status of suborders concerning full payments.', 'yith-woocommerce-deposits-and-down-payments' ) . '">[?]</span>';
				add_meta_box(
					'yith-wcdp-woocommerce-suborders',
					$metabox_suborder_description,
					array(
						$this,
						'render_metabox_output',
					),
					'shop_order',
					'side',
					'core',
					array( 'metabox' => 'suborders' )
				);
			} elseif ( $is_suborder ) {
				$metabox_parent_order_description = _x( 'Deposit order', 'Admin: Single order page. Info box with parent order details', 'yith-woocommerce-deposits-and-down-payments' );
				add_meta_box(
					'yith-wcdp-woocommerce-parent-order',
					$metabox_parent_order_description,
					array(
						$this,
						'render_metabox_output',
					),
					'shop_order',
					'side',
					'high',
					array( 'metabox' => 'parent-order' )
				);
			}
		}

		/**
		 * Output the suborder metaboxes
		 *
		 * @param WP_Post $post  The post object.
		 * @param array   $param Callback args.
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function render_metabox_output( $post, $param ) {
			$order = wc_get_order( $post->ID );

			switch ( $param['args']['metabox'] ) {
				case 'suborders':
					$suborder_ids = YITH_WCDP_Suborders()->get_suborder( absint( $post->ID ) );
					echo '<ul class="suborders-list single-orders">';
					foreach ( $suborder_ids as $suborder_id ) {
						$suborder        = wc_get_order( absint( $suborder_id ) );
						$items           = $suborder->get_items( 'line_item' );
						$suborder_uri    = esc_url( 'post.php?post=' . absint( $suborder_id ) . '&action=edit' );
						$items_to_string = array();

						if ( ! empty( $items ) ) {
							foreach ( $items as $item ) {
								$items_to_string[] = $item['name'];
							}
						}

						$items_to_string = implode( ' | ', $items_to_string );

						echo '<li class="suborder-info">';
						printf(
							'<mark class="%1$s tips" data-tip="%2$s">%2$s</mark> <strong><a href="%3$s">#%4$s</a></strong> <small>(%5$s)</small><br/>',
							esc_attr( sanitize_title( $suborder->get_status() ) ),
							esc_attr( wc_get_order_status_name( $suborder->get_status() ) ),
							esc_url( $suborder_uri ),
							esc_html( $suborder_id ),
							esc_html( $items_to_string )
						);
						echo '<li>';
					}
					echo '</ul>';
					break;

				case 'parent-order':
					$parent_order_id  = $order->get_parent_id();
					$parent_order_uri = esc_url( 'post.php?post=' . absint( $parent_order_id ) . '&action=edit' );
					printf( '<a href="%s">&#8592; %s</a>', esc_url( $parent_order_uri ), esc_html_x( 'Back to main order', 'Admin: single order page. Link to parent order', 'yith-woocommerce-deposits-and-down-payments' ) );
					break;
			}
		}

		/**
		 * Filter order items to add label for deposit orders
		 *
		 * @param mixed         $arg1 Mixed value (order item).
		 * @param WC_Order_item $arg2 Order items object.
		 *
		 * @return mixed Filtered array of order items
		 * @since 1.0.0
		 */
		public function filter_order_items( $arg1, $arg2 ) {
			global $pagenow;

			// apply this filter only when in single post page.
			if ( 'post.php' !== $pagenow ) {
				return $arg1;
			}

			$deposit_prefix = apply_filters( 'yith_wcdp_deposit_label', __( 'Deposit', 'yith-woocommerce-deposits-and-down-payments' ) ) . ': ';
			$balance_prefix = apply_filters( 'yith_wcdp_full_payment_label', __( 'Balance', 'yith-woocommerce-deposits-and-down-payments' ) ) . ': ';

			if ( version_compare( WC()->version, '2.7.0', '>=' ) ) {

				if ( is_string( $arg1 ) ) {

					if ( wc_get_order_item_meta( $arg2->get_id(), '_deposit' ) && strpos( $arg1, $deposit_prefix ) === false ) {
						$arg1 = $deposit_prefix . $arg1;
					} elseif ( wc_get_order_item_meta( $arg2->get_id(), '_full_payment' ) && strpos( $arg1, $balance_prefix ) === false ) {
						$arg1 = $balance_prefix . $arg1;
					}
				}

				return $arg1;
			} else {
				/*
				 * Using temp array to avoid to change internal $items pointer, used by WooCommerce template on backend (includes/admin/meta-boxes/views/html-order-items.php)
				 */
				$tmp = $arg1;

				if ( ! empty( $tmp ) ) {
					foreach ( $tmp as $key => $elem ) {
						if ( isset( $elem['deposit'] ) && $elem['deposit'] && strpos( $arg1[ $key ]['name'], $deposit_prefix ) === false ) {
							$arg1[ $key ]['name'] = apply_filters( 'yith_wcdp_deposit_label', __( 'Deposit', 'yith-woocommerce-deposits-and-down-payments' ) ) . ': ' . $elem['name'];
						} elseif ( isset( $elem['full_payment'] ) && $elem['full_payment'] && strpos( $arg1[ $key ]['name'], $balance_prefix ) === false ) {
							$arg1[ $key ]['name'] = apply_filters( 'yith_wcdp_full_payment_label', __( 'Balance', 'yith-woocommerce-deposits-and-down-payments' ) ) . ': ' . $elem['name'];
						}
					}
				}

				return $arg1;
			}
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
			if ( ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
				$hidden_items = array_merge(
					$hidden_items,
					array(
						'_deposit',
						'_deposit_type',
						'_deposit_amount',
						'_deposit_rate',
						'_deposit_value',
						'_deposit_balance',
						'_deposit_shipping_method',
						'_full_payment',
						'_full_payment_id',
						'_deposit_id',
					)
				);
			}

			return $hidden_items;
		}

		/**
		 * Print Full Payment link to before order item meta section of order edit admin page
		 *
		 * @param int   $item_id Current order item id.
		 * @param array $item    Current item data.
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function print_full_payment_order_itemmeta( $item_id, $item ) {
			if ( isset( $item['deposit'] ) && $item['deposit'] ) {
				$suborder = wc_get_order( $item['full_payment_id'] );

				if ( ! $suborder ) {
					return;
				}

				$suborder_id = $suborder->get_id();
				?>
				<div class="yith-wcdp-full-payment">
					<small>
						<a href="<?php echo esc_url( get_edit_post_link( $suborder_id ) ); ?>">
							<?php printf( '%s: #%d', esc_html__( 'Full payment order', 'yith-woocommerce-deposits-and-down-payments' ), esc_html( $suborder_id ) ); ?>
						</a>
					</small>
				</div>
				<?php
			}
		}

		/* === WOOCOMMERCE REPORT === */

		/**
		 * Filters report data, to remove balance from items sold count
		 *
		 * @param array $args Report args.
		 *
		 * @return array Filtered array of arguments
		 */
		public function filter_sales_report( $args ) {
			if ( isset( $args['data'] ) && isset( $args['data']['_qty'] ) && 'order_item_meta' === $args['data']['_qty']['type'] ) {

				$args['where'][] = array(
					'key'      => 'order_items.order_id',
					'value'    => YITH_WCDP_Suborders()->get_all_balances_ids(),
					'operator' => 'not in',
				);
			}

			return $args;
		}

		/**
		 * Returns single instance of the class
		 *
		 * @return \YITH_WCDP_Admin
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
 * Unique access to instance of YITH_WCDP_Admin class
 *
 * @return \YITH_WCDP_Admin
 * @since 1.0.0
 */
function YITH_WCDP_Admin() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return YITH_WCDP_Admin::get_instance();
}

<?php
/**
 * General WCDP email class
 *
 * @author  YITH
 * @package YITH\Deposits\Classes\Emails
 * @version 1.0.0
 */

if ( ! defined( 'YITH_WCDP' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCDP_Emails' ) ) {
	/**
	 * General plugin email class (used only as base for extension)
	 *
	 * @since 1.0.0
	 */
	class YITH_WCDP_Emails extends WC_Email {

		/**
		 * Order customer object
		 *
		 * @var \WP_User
		 * @since 1.0.0
		 */
		public $customer;

		/**
		 * Order related full amount suborders (ids array)
		 *
		 * @var mixed
		 * @since 1.0.0
		 */
		public $suborders;

		/**
		 * Set replaces for current emails
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function set_replaces() {
			$find = array(
				'order-date'     => '{order_date}',
				'order-number'   => '{order_number}',
				'order-id'       => '{order_id}',
				'order-status'   => '{order_status}',
				'order-state'    => '{order_state}',
				'customer-name'  => '{customer_name}',
				'customer-login' => '{customer_login}',
				'customer-email' => '{customer_email}',
				'deposit-table'  => '{deposit_table}',
				'suborder-table' => '{suborder_table}',
				'deposit-list'   => '{deposit_list}',
				'suborder-list'  => '{suborder_list}',
			);

			$replace = array(
				'order-date'     => date_i18n( wc_date_format(), strtotime( $this->object->get_date_completed() ) ),
				'order-number'   => $this->object->get_order_number(),
				'order-id'       => $this->object->get_id(),
				'order-status'   => $this->object->get_status(),
				'order-state'    => $this->object->get_status(),
				'customer-name'  => $this->customer->display_name,
				'customer-login' => $this->customer->user_login,
				'customer-email' => $this->customer->user_email,
				'deposit-table'  => $this->get_deposit_table(),
				'suborder-table' => $this->get_deposit_table(),
				'deposit-list'   => $this->get_deposit_list(),
				'suborder-list'  => $this->get_deposit_list(),
			);

			$this->placeholders = array_merge(
				$this->placeholders,
				array_combine( array_values( $find ), array_values( $replace ) )
			);
		}

		/**
		 * Returns deposit table template (plain/html)
		 *
		 * @return string Deposit table template
		 * @since 1.0.0
		 */
		public function get_deposit_table() {
			ob_start();

			$template = 'deposit-table.php';
			$template = 'emails/' . ( ( 'plain' === $this->get_email_type() ) ? 'plain/' : '' ) . $template;

			yith_wcdp_get_template( $template, array( 'parent_order' => $this->object ) );

			return $this->format_string( ob_get_clean() );
		}

		/**
		 * Returns deposit list template (plain/html)
		 *
		 * @return string Deposit list template
		 * @since 1.0.0
		 */
		public function get_deposit_list() {
			ob_start();

			$template = 'deposit-list.php';
			$template = 'emails/' . ( ( 'plain' === $this->get_email_type() ) ? 'plain/' : '' ) . $template;

			yith_wcdp_get_template(
				$template,
				array(
					'parent_order' => $this->object,
					'suborder'     => isset( $this->suborder_id ) ? wc_get_order( $this->suborder_id ) : false,
				)
			);

			return $this->format_string( ob_get_clean() );
		}

		/**
		 * Get HTML content for the mail
		 *
		 * @return string HTML content of the mail
		 * @since 1.0.0
		 */
		public function get_content_html() {
			ob_start();

			yith_wcdp_get_template(
				$this->template_html,
				array(
					'parent_order'  => $this->object,
					'child_orders'  => $this->suborders,
					'email_heading' => $this->get_heading(),
					'sent_to_admin' => true,
					'plain_text'    => false,
					'email'         => $this,
				)
			);

			return $this->format_string( ob_get_clean() );
		}

		/**
		 * Get plain text content of the mail
		 *
		 * @return string Plain text content of the mail
		 * @since 1.0.0
		 */
		public function get_content_plain() {
			ob_start();

			yith_wcdp_get_template(
				$this->template_plain,
				array(
					'parent_order'  => $this->object,
					'child_orders'  => $this->suborders,
					'email_heading' => $this->get_heading(),
					'sent_to_admin' => true,
					'plain_text'    => true,
					'email'         => $this,
				)
			);

			return $this->format_string( ob_get_clean() );
		}
	}
}

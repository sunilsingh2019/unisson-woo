<?php
/**
 * Deposits Admin Panel
 *
 * @author YITH
 * @package YITH\Deposits\Templates
 * @version 1.0.0
 */

if ( ! defined( 'YITH_WCDP' ) ) {
	exit;
} // Exit if accessed directly
?>

<div id="yith_wcdp_panel_deposits">
	<form id="plugin-fw-wc" class="general-deposits-table" method="post">

		<div class="yith-users-roles-new-deposit">
			<h4><?php esc_html_e( 'Add new user role deposit', 'yith-woocommerce-deposits-and-down-payments' ); ?></h4>
			<?php
			yit_add_select2_fields(
				array(
					'name'             => 'yith_new_user_role_deposit[role]',
					'class'            => 'yith-roles-select wc-product-search',
					'data-action'      => 'json_search_roles',
					'data-placeholder' => __( 'Search for a role&hellip;', 'yith-woocommerce-deposits-and-down-payments' ),
					'style'            => 'width: 300px;',
				)
			);
			?>
			<select name="yith_new_user_role_deposit[type]" class="yith-deposit-type wc-enhanced-select" style="width: 100px;">
				<option value="amount"><?php esc_html_e( 'Amount', 'yith-woocommerce-deposits-and-down-payments' ); ?></option>
				<option value="rate"><?php esc_html_e( 'Rate', 'yith-woocommerce-deposits-and-down-payments' ); ?></option>
			</select>
			<input type="number" name="yith_new_user_role_deposit[value]" min="0" max="999999" step="any" value="" style="max-width: 100px;" />
			<input type="submit" class="yith-role-deposit-submit button button-primary" value="<?php echo esc_attr( __( 'Add Deposit', 'yith-woocommerce-deposits-and-down-payments' ) ); ?>" />
		</div>

		<h2><?php esc_html_e( 'User role deposits', 'yith-woocommerce-deposits-and-down-payments' ); ?></h2>
		<div class="yith-role-deposit">
			<?php $role_deposits_table->display(); ?>
		</div>

		<div class="clear separator"></div>

		<div class="yith-products-new-deposit">
			<h4><?php esc_html_e( 'Add new product deposit', 'yith-woocommerce-deposits-and-down-payments' ); ?></h4>
			<?php
			yit_add_select2_fields(
				array(
					'name'             => 'yith_new_product_deposit[product]',
					'class'            => 'yith-products-select wc-product-search',
					'data-placeholder' => __( 'Search for a product&hellip;', 'yith-woocommerce-deposits-and-down-payments' ),
					'style'            => 'width: 300px;',
				)
			);
			?>
			<select name="yith_new_product_deposit[type]" class="yith-deposit-type wc-enhanced-select" style="width: 100px;">
				<option value="amount"><?php esc_html_e( 'Amount', 'yith-woocommerce-deposits-and-down-payments' ); ?></option>
				<option value="rate"><?php esc_html_e( 'Rate', 'yith-woocommerce-deposits-and-down-payments' ); ?></option>
			</select>
			<input type="number" name="yith_new_product_deposit[value]" min="0" max="999999" step="any" value="" style="max-width: 100px;" />
			<input type="submit" class="yith-products-commission-submit button button-primary" value="<?php echo esc_attr( __( 'Add Deposit', 'yith-woocommerce-deposits-and-down-payments' ) ); ?>" />
		</div>

		<h2><?php esc_html_e( 'Product deposits', 'yith-woocommerce-deposits-and-down-payments' ); ?></h2>
		<div class="yith-product-deposit">
			<?php $product_deposits_table->display(); ?>
		</div>

		<div class="clear separator"></div>

		<div class="yith-products-categories-new-deposit">
			<h4><?php esc_html_e( 'Add new product category deposit', 'yith-woocommerce-deposits-and-down-payments' ); ?></h4>
			<select name="yith_new_product_category_deposit[term]" class="yith-categories-select wc-enhanced-select" data-allow_clear="1" data-placeholder="<?php esc_attr_e( 'Search for a category&hellip;', 'yith-woocommerce-deposits-and-down-payments' ); ?>" style="width: 300px;">
				<option></option>
				<?php if ( ! empty( $product_categories ) ) : ?>
					<?php foreach ( $product_categories as $term_id => $term_name ) : ?>
							<option value="<?php echo esc_attr( $term_id ); ?>"><?php echo esc_html( $term_name ); ?></option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select>
			<select name="yith_new_product_category_deposit[type]" class="wc-enhanced-select yith-deposit-type" style="width: 100px;">
				<option value="amount"><?php esc_html_e( 'Amount', 'yith-woocommerce-deposits-and-down-payments' ); ?></option>
				<option value="rate"><?php esc_html_e( 'Rate', 'yith-woocommerce-deposits-and-down-payments' ); ?></option>
			</select>
			<input type="number" name="yith_new_product_category_deposit[value]" min="0" max="999999" step="any" value="" style="max-width: 100px;" />
			<input type="submit" class="yith-products-commission-submit button button-primary" value="<?php echo esc_attr( __( 'Add Deposit', 'yith-woocommerce-deposits-and-down-payments' ) ); ?>" />
		</div>

		<h2><?php esc_html_e( 'Deposits for product categories', 'yith-woocommerce-deposits-and-down-payments' ); ?></h2>
		<div class="yith-category-deposit">
			<?php $product_category_deposits_table->display(); ?>
		</div>

		<?php wp_nonce_field( 'deposits' ); ?>

	</form>
</div>

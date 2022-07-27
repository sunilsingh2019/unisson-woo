<?php
/**
 * Product deposit option tab
 *
 * @author YITH
 * @package YITH\Deposits\Templates
 * @version 1.0.0
 */

if ( ! defined( 'YITH_WCDP' ) ) {
	exit;
} // Exit if accessed directly
?>

<div class="panel woocommerce_options_panel">
	<div class="options_group">
		<p class="form-field _enable_deposit">
			<label for="_enable_deposit"><?php esc_html_e( 'Enable deposit', 'yith-woocommerce-deposits-and-down-payments' ); ?></label>
			<span>
				<input type="radio" class="enable_deposit" name="_enable_deposit[<?php echo esc_attr( $loop ); ?>]" value="default" <?php checked( ( 'default' === $enable_deposit ) || empty( $enable_deposit ) ); ?> />
				<?php esc_html_e( 'Default', 'yith-woocommerce-deposits-and-down-payments' ); ?>
			</span>
			<?php echo wp_kses_post( wc_help_tip( __( 'Check this option to enable payment of deposit for this product', 'yith-woocommerce-deposits-and-down-payments' ) ) ); ?><br/>

			<span>
				<input type="radio" class="enable_deposit" name="_enable_deposit[<?php echo esc_attr( $loop ); ?>]" value="yes" <?php checked( $enable_deposit, 'yes' ); ?> />
				<?php esc_html_e( 'Yes', 'yith-woocommerce-deposits-and-down-payments' ); ?>
			</span><br/>

			<span>
				<input type="radio" class="enable_deposit" name="_enable_deposit[<?php echo esc_attr( $loop ); ?>]" value="no" <?php checked( $enable_deposit, 'no' ); ?> />
				<?php esc_html_e( 'No', 'yith-woocommerce-deposits-and-down-payments' ); ?>
			</span><br/>
		</p>
	</div>
	<div class="options_group">
		<p class="form-field _deposit_default">
			<label for="_deposit_default"><?php esc_html_e( 'Deposit checked?', 'yith-woocommerce-deposits-and-down-payments' ); ?></label>
			<span>
				<input type="radio" class="deposit_default" name="_deposit_default[<?php echo esc_attr( $loop ); ?>]" value="default" <?php checked( ( 'default' === $deposit_default ) || empty( $force_deposit ) ); ?>/>
				<?php esc_html_e( 'Default', 'yith-woocommerce-deposits-and-down-payments' ); ?>
			</span><br/>

			<span>
				<input type="radio" class="deposit_default" name="_deposit_default[<?php echo esc_attr( $loop ); ?>]" value="yes" <?php checked( $deposit_default, 'yes' ); ?>/>
				<?php esc_html_e( 'Yes', 'yith-woocommerce-deposits-and-down-payments' ); ?>
			</span><br/>

			<span>
				<input type="radio" class="deposit_default" name="_deposit_default[<?php echo esc_attr( $loop ); ?>]" value="no" <?php checked( $deposit_default, 'no' ); ?>/>
				<?php esc_html_e( 'No', 'yith-woocommerce-deposits-and-down-payments' ); ?>
			</span>
		</p>
		<p class="form-field _force_deposit">
			<label for="_force_deposit"><?php esc_html_e( 'Accept or force deposit', 'yith-woocommerce-deposits-and-down-payments' ); ?></label>
			<span>
				<input type="radio" class="force_deposit" name="_force_deposit[<?php echo esc_attr( $loop ); ?>]" value="default" <?php checked( ( 'default' === $force_deposit ) || empty( $force_deposit ) ); ?>/>
				<?php esc_html_e( 'Default', 'yith-woocommerce-deposits-and-down-payments' ); ?>
			</span><br/>

			<span>
				<input type="radio" class="force_deposit" name="_force_deposit[<?php echo esc_attr( $loop ); ?>]" value="yes" <?php checked( $force_deposit, 'yes' ); ?>/>
				<?php esc_html_e( 'Force deposit', 'yith-woocommerce-deposits-and-down-payments' ); ?>
			</span><br/>

			<span>
				<input type="radio" class="force_deposit" name="_force_deposit[<?php echo esc_attr( $loop ); ?>]" value="no" <?php checked( $force_deposit, 'no' ); ?>/>
				<?php esc_html_e( 'Allow deposit', 'yith-woocommerce-deposits-and-down-payments' ); ?>
			</span>
		</p>
		<p class="form-field _create_balance_orders">
			<label for="_enable_full_payment"><?php esc_html_e( 'Create balance orders', 'yith-woocommerce-deposits-and-down-payments' ); ?></label>
			<span>
				<input type="radio" class="create_balance_orders" name="_create_balance_orders[<?php echo esc_attr( $loop ); ?>]" value="default" <?php checked( ( 'default' === $create_balance_orders ) || empty( $create_balance_orders ) ); ?>/>
				<?php esc_html_e( 'Default', 'yith-woocommerce-deposits-and-down-payments' ); ?>
			</span><br/>

			<span>
				<input type="radio" class="create_balance_orders" name="_create_balance_orders[<?php echo esc_attr( $loop ); ?>]" value="yes" <?php checked( $create_balance_orders, 'yes' ); ?>/>
				<?php esc_html_e( 'Let users pay the balance online', 'yith-woocommerce-deposits-and-down-payments' ); ?>
			</span><br/>

			<span>
				<input type="radio" class="create_balance_orders" name="_create_balance_orders[<?php echo esc_attr( $loop ); ?>]" value="no" <?php checked( $create_balance_orders, 'no' ); ?>/>
				<?php esc_html_e( 'Customers will pay the balance using other means', 'yith-woocommerce-deposits-and-down-payments' ); ?>
			</span>
		</p>
		<p class="form-field _product_note">
			<label for="_product_note"><?php esc_html_e( 'Additional product notes', 'yith-woocommerce-deposits-and-down-payments' ); ?></label>
			<textarea name="_product_note[<?php echo esc_attr( $loop ); ?>]" id="_product_note" cols="30" rows="10"><?php echo esc_html( $product_note ); ?></textarea>
			<?php echo wp_kses_post( wc_help_tip( __( 'This option is variation specific, and won\'t in any way affect note specified for overall product; this note will be shown in single product page, before deposit template, whenever correct variation is selected by the customer', 'yith-woocommerce-deposits-and-down-payments' ) ) ); ?>
		</p>
	</div>
	<?php if ( $deposit_expires_on_specific_date ) : ?>
		<div class="options_group">
			<p class="form-field _deposit_default">
				<label for="_deposit_expiration_date"><?php esc_html_e( 'Expiration date', 'yith-woocommerce-deposits-and-down-payments' ); ?></label>
				<input type="text" class="date-picker deposit_expiration_date" name="_deposit_expiration_date[<?php echo esc_attr( $loop ); ?>]" value="<?php echo esc_attr( $deposit_expiration_date ); ?>" />
			</p>
			<p class="form-field _deposit_default">
				<label for="_deposit_expiration_product_fallback"><?php esc_html_e( 'Product status', 'yith-woocommerce-deposits-and-down-payments' ); ?></label>
				<span>
					<input type="radio" class="deposit_expiration_product_fallback" name="_deposit_expiration_product_fallback[<?php echo esc_attr( $loop ); ?>]" value="default" <?php checked( empty( $deposit_expiration_product_fallback ) || ( 'default' === $deposit_expiration_product_fallback ) ); ?>/>
					<?php esc_html_e( 'Default', 'yith-woocommerce-deposits-and-down-payments' ); ?>
				</span><br/>

				<span>
					<input type="radio" class="deposit_expiration_product_fallback" name="_deposit_expiration_product_fallback[<?php echo esc_attr( $loop ); ?>]" value="do_nothing" <?php checked( $deposit_expiration_product_fallback, 'do_nothing' ); ?>/>
					<?php esc_html_e( 'Do nothing', 'yith-woocommerce-deposits-and-down-payments' ); ?>
				</span><br/>

				<span>
					<input type="radio" class="deposit_expiration_product_fallback" name="_deposit_expiration_product_fallback[<?php echo esc_attr( $loop ); ?>]" value="disable_deposit" <?php checked( $deposit_expiration_product_fallback, 'disable_deposit' ); ?>/>
					<?php esc_html_e( 'Just disable deposit', 'yith-woocommerce-deposits-and-down-payments' ); ?>
				</span><br/>

				<span>
					<input type="radio" class="deposit_expiration_product_fallback" name="_deposit_expiration_product_fallback[<?php echo esc_attr( $loop ); ?>]" value="item_not_purchasable" <?php checked( $deposit_expiration_product_fallback, 'item_not_purchasable' ); ?>/>
					<?php esc_html_e( 'Make item no longer purchasable', 'yith-woocommerce-deposits-and-down-payments' ); ?>
				</span><br/>
			</p>
		</div>
	<?php endif; ?>
</div>

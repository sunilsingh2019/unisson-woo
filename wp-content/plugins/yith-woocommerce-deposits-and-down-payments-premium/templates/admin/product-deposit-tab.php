<?php
/**
 * Product deposit option tab
 *
 * @author  Your Inspiration Themes
 * @package YITH WooCommerce Deposits and Down Payments
 * @version 1.0.0
 */

if ( ! defined( 'YITH_WCDP' ) ) {
	exit;
} // Exit if accessed directly
?>

<div id="yith_wcdp_deposit_tab" class="panel woocommerce_options_panel">
	<div class="options_group">
		<p class="form-field _enable_deposit">
			<label for="_enable_deposit"><?php esc_html_e( 'Enable deposit', 'yith-woocommerce-deposits-and-down-payments' ); ?></label>
			<span>
				<input type="radio" class="enable_deposit" name="_enable_deposit" value="default" <?php checked( 'default' === $enable_deposit || empty( $enable_deposit ) ); ?> />
				<?php esc_html_e( 'Default', 'yith-woocommerce-deposits-and-down-payments' ); ?>
			</span>
			<?php echo wp_kses_post( wc_help_tip( __( 'Check this option to enable payment of deposit for this product', 'yith-woocommerce-deposits-and-down-payments' ) ) ); ?>

			<span>
				<input type="radio" class="enable_deposit" name="_enable_deposit" value="yes" <?php checked( $enable_deposit, 'yes' ); ?> />
				<?php esc_html_e( 'Yes', 'yith-woocommerce-deposits-and-down-payments' ); ?>
			</span><br/>
			<span>
				<input type="radio" class="enable_deposit" name="_enable_deposit" value="no" <?php checked( $enable_deposit, 'no' ); ?> />
				<?php esc_html_e( 'No', 'yith-woocommerce-deposits-and-down-payments' ); ?>
			</span><br/>
		</p>
	</div>
	<div class="options_group">
		<p class="form-field _force_deposit">
			<label for="_force_deposit"><?php esc_html_e( 'Accept or force deposit', 'yith-woocommerce-deposits-and-down-payments' ); ?></label>
			<span>
				<input type="radio" class="force_deposit" name="_force_deposit" value="default" <?php checked( 'default' === $force_deposit || empty( $force_deposit ) ); ?>/>
				<?php esc_html_e( 'Default', 'yith-woocommerce-deposits-and-down-payments' ); ?>
			</span><br/>

			<span>
				<input type="radio" class="force_deposit" name="_force_deposit" value="yes" <?php checked( $force_deposit, 'yes' ); ?>/>
				<?php esc_html_e( 'Force deposit', 'yith-woocommerce-deposits-and-down-payments' ); ?>
			</span><br/>

			<span>
				<input type="radio" class="force_deposit" name="_force_deposit" value="no" <?php checked( $force_deposit, 'no' ); ?>/>
				<?php esc_html_e( 'Allow deposit', 'yith-woocommerce-deposits-and-down-payments' ); ?>
			</span>
		</p>
	</div>
</div>

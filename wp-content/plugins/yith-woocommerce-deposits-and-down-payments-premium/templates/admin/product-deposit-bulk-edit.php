<?php
/**
 * Product deposit Quick/Bulk edit template
 *
 * @author  YITH
 * @package YITH\Deposits\Templates
 * @version 1.0.0
 */

if ( ! defined( 'YITH_WCDP' ) ) {
	exit;
} // Exit if accessed directly
?>

<fieldset class="inline-edit-col-center">
	<label for="_enable_deposit" class="alignleft">
		<span class="title"><?php esc_html_e( 'Deposit?', 'yith-woocommerce-deposits-and-down-payments' ); ?></span>
		<span class="input-text-wrap">
			<select name="_enable_deposit" id="enable_deposit" class="enable_deposit">
				<option value="default" <?php selected( 'default' === $enable_deposit || empty( $enable_deposit ) ); ?> >
					<?php esc_html_e( 'Default', 'yith-woocommerce-deposits-and-down-payments' ); ?>
				</option>
				<option value="yes" <?php selected( $enable_deposit, 'yes' ); ?> >
					<?php esc_html_e( 'Yes', 'yith-woocommerce-deposits-and-down-payments' ); ?>
				</option>
				<option value="no" <?php selected( $enable_deposit, 'no' ); ?> >
					<?php esc_html_e( 'No', 'yith-woocommerce-deposits-and-down-payments' ); ?>
				</option>
			</select>
		</span>
	</label>
	<br class="clear">
	<label for="_enable_deposit" class="alignleft">
		<span class="title"><?php esc_html_e( 'Force?', 'yith-woocommerce-deposits-and-down-payments' ); ?></span>
		<span class="input-text-wrap">
			<select name="_force_deposit" id="force_deposit" class="force_deposit">
				<option value="default" <?php selected( 'default' === $force_deposit || empty( $force_deposit ) ); ?> >
					<?php esc_html_e( 'Default', 'yith-woocommerce-deposits-and-down-payments' ); ?>
				</option>
				<option value="yes" <?php selected( $force_deposit, 'yes' ); ?> >
					<?php esc_html_e( 'Force deposit', 'yith-woocommerce-deposits-and-down-payments' ); ?>
				</option>
				<option value="no" <?php selected( $force_deposit, 'no' ); ?> >
					<?php esc_html_e( 'Allow deposit', 'yith-woocommerce-deposits-and-down-payments' ); ?>
				</option>
			</select>
		</span>
	</label>
</fieldset>

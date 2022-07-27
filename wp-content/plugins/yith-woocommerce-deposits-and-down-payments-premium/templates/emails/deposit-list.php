<?php
/**
 * Depossit list - used in plugin emails
 *
 * @author YITH
 * @package YITH\Deposits\Templates\Emails
 * @version 1.0.0
 */

if ( ! defined( 'YITH_WCDP' ) ) {
	exit;
} // Exit if accessed directly

?>

<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; border: none;" border="1">
	<tbody>
	<?php
	$items     = $parent_order->get_items( 'line_item' );
	$suborders = ! empty( $suborder ) ? (array) $suborder : YITH_WCDP_Suborders()->get_suborder( $parent_order->get_id() );

	if ( ! empty( $suborders ) ) :
		foreach ( $suborders as $suborder ) :
			if ( ! $suborder instanceof WC_Order ) {
				$suborder = wc_get_order( $suborder );
			}

			if ( ! $suborder || $suborder->has_status( array( 'completed', 'processing' ) ) ) {
				continue;
			}

			$suborder_items = $suborder->get_items( 'line_item' );
			$suborder_names = array();

			foreach ( $suborder_items as $suborder_item ) {
				$suborder_names[] = $suborder_item['name'];
			}

			?>
			<tr>
				<td class="td" style="width: 60%; text-align:left; vertical-align:middle; border: none; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word; font-size: 30px; line-height: 1.2;">
					<?php echo esc_html( implode( ' | ', $suborder_names ) ); ?>
					<small style="font-size: 17px;"> - <?php echo wp_kses_post( wc_price( $suborder->get_total(), array( 'currency' => $suborder->get_currency() ) ) ); ?></small>
				</td>
				<td class="td" style="width: 40%; vertical-align:middle; border: none; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word; text-align: right;">
					<a href="<?php echo esc_url( $suborder->get_checkout_payment_url() ); ?>" style="display: inline-block; background-color: #ebe9eb; color: #515151; white-space: nowrap; padding: .618em 1em; border-radius: 3px; text-decoration: none;">
						<?php esc_html_e( 'Pay now!', 'yith-woocommerce-deposits-and-down-payments' ); ?>
					</a>
				</td>
			</tr>
			<?php
		endforeach;
	endif;
	?>
	</tbody>
</table>

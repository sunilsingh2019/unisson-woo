<?php
/**
 * Deposits settings page
 *
 * @author  YITH
 * @package YITH\Deposits\Options
 * @version 1.0.0
 */

if ( ! defined( 'YITH_WCDP' ) ) {
	exit;
} // Exit if accessed directly

return apply_filters(
	'yith_wcdp_deposits_settings',
	array(
		'deposits' => array(
			'deposits_panel' => array(
				'type'   => 'custom_tab',
				'action' => 'yith_wcdp_deposits_panel',
			),
		),
	)
);

<?php
/**
 * Darven Extra Price Info.
 *
 * @package Darven\Epi
 */

/**
 * @var InstallmentsPrice $skeleton_price The skeleton price
 * @var array $args Arguments for the template
 */
list( 'skeleton_price' => $skeleton_price ) = $args;

?>
<div class="darven-epi-installments">
	<p class="darven-epi-installments-text">
		<?php
		echo esc_html( $skeleton_price->prefix );
		echo( $skeleton_price->get_maximum_installments() );
		echo esc_html__( 'x de ', DARVEN_EXTRA_PRICE_INFO_DOMAIN );
		echo( wc_price( $skeleton_price->price_with_interest ) );
		echo esc_html( $skeleton_price->suffix );
		?>
	</p>
</div>

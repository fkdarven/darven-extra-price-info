<?php
/**
 * Darven Extra Price Info.
 *
 * @package Darven\Epi
 */

/**
 * @var IncashPrice $skeleton_price The skeleton price
 * @var array $args Arguments for the template
 */

list( 'skeleton_price' => $skeleton_price ) = $args;

?>
<div class="darven-epi-incash">
	<p class="darven-epi-incash-text">
		<span class="darven-epi-incash-text-prefix">
		<?php
		echo esc_html( $skeleton_price->prefix );
		?>
		</span>
		<span class="darven-epi-incash-text-price">
		<?php
		echo wc_price( $skeleton_price->get_price_with_discount() );
		?>
		</span>
		<span class="darven-epi-incash-text-suffix">
		<?php
		echo esc_html( $skeleton_price->suffix );
		?>
		</span>
	</p>
</div>


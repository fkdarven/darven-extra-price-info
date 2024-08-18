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

list(
	'skeleton_price' => $skeleton_price
) = $args;

?>
<div class="darven-epi-installments">
	<p class="darven-epi-installments-text">
		<span class="darven-epi-installments-text-prefix">
			<?php echo esc_html( $skeleton_price->prefix ); ?>
		</span>
		<span class="darven-epi-installments-text-price">
			<?php
			echo $skeleton_price->get_maximum_installments();
			echo esc_html__( 'x de ', DARVEN_EXTRA_PRICE_INFO_DOMAIN );
			?>
		</span>
		<span class="darven-epi-installments-text-price-with-interest">
			<?php
			echo wc_price( $skeleton_price->get_interest( $skeleton_price->get_maximum_installments() ) );
			?>
		</span>
		<span class="darven-epi-installments-text-suffix">
			<?php echo esc_html( $skeleton_price->suffix ); ?>
		</span>
	</p>
	<button id="toggleTableButton" class="toggle-table-button">Ver Parcelas</button>
	<div id="installmentTable" class="installment-table" style="display: none;">
		<table>
			<thead>
				<tr>
					<th>Número de parcelas</th>
					<th>Valor por parcela</th>
				</tr>
			</thead>
			<tbody>
				<?php for ( $i = (int) $skeleton_price->interest_fee_from; $i <= $skeleton_price->get_maximum_installments(); $i++ ) : ?>
					<tr>
						<td><?php echo $i; ?></td>
						<td><?php echo wc_price( $skeleton_price->get_interest( $i ) ); ?></td>
					</tr>
				<?php endfor; ?>
			</tbody>
		</table>
	</div>
</div>
<script>
	document.getElementById('toggleTableButton').addEventListener('click', function () {
		var table = document.getElementById('installmentTable');
		if (table.style.display === 'none') {
			table.style.display = 'block';
			this.textContent = 'Esconder opções de parcelamento';
		} else {
			table.style.display = 'none';
			this.textContent = 'Mostrar opções de parcelamento';
		}
	});
</script>
<style>
	.darven-epi-installments-text-prefix{
		color: <?php echo esc_attr( $skeleton_price->prefix_color ); ?>;
		font-size: <?php echo esc_attr( $skeleton_price->prefix_size ); ?>px;

	}
	.darven-epi-installments-text-suffix{
		color: <?php echo esc_attr( $skeleton_price->suffix_color ); ?>;
	}
</style>

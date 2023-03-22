
<?php $tab = filter_input(INPUT_GET, 'tab');?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<nav class="nav-tab-wrapper">
		<a href="?page=darven-epi-admin" class="nav-tab <?php if ( $tab === null ): ?>nav-tab-active<?php endif; ?>"><?php echo esc_attr(__('Configurações Gerais'))?></a>
		<a href="?page=darven-epi-admin&tab=colorsandstyles"
		   class="nav-tab <?php if ( $tab === 'colorsandstyles' ): ?>nav-tab-active<?php endif; ?>"><?php echo esc_attr(__('Cores e Estilos'))?></a>
        <a href="?page=darven-epi-admin&tab=positions"
           class="nav-tab <?php if ( $tab === 'positions' ): ?>nav-tab-active<?php endif; ?>"><?php echo esc_attr(__('Posições'))?></a>
	</nav>

	<div class="tab-content wrap">
		<?php switch ( $tab ) :
			case 'settings':
				echo 'Settings';
				break;
			case 'colorsandstyles':
				?>
				<div class="colorsandstyles">
					<form method="post" action="<?php echo esc_url( add_query_arg(
						'tab', 'colorsandstyles', admin_url( 'options.php' )
					) ); ?>">
						<?php
						settings_errors();
						settings_fields( 'darven_epi_option_group' );
						do_settings_sections( 'darven-epi-admin' );
						submit_button();
						?>

					</form>
				</div>
				<?php
				break;
			default:
				?>
				<div>
					<form method="post" action="<?php echo esc_url( add_query_arg(
						'tab', null, admin_url( 'options.php' )
					) ); ?>">
						<?php
						settings_errors();
						settings_fields( 'darven_epi_option_group' );
						do_settings_sections( 'darven-epi-admin' );
						submit_button();
						?>

					</form>
				</div>
				<?php
				break;
		endswitch; ?>
	</div>
</div>
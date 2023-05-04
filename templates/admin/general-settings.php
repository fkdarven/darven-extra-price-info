
<?php $tab = filter_input(INPUT_GET, 'tab');?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<nav class="nav-tab-wrapper">
		<a href="?page=darven-epi-admin" class="nav-tab <?php if ( $tab === null ): ?>nav-tab-active<?php endif; ?>"><?php echo esc_attr(__('General Settings', DARVEN_EPI_LANGUAGE_DOMAIN))?></a>
		<!--<a href="?page=darven-epi-admin&tab=colorsandstyles"
		   class="nav-tab <?php if ( $tab === 'colorsandstyles' ): ?>nav-tab-active<?php endif; ?>"><?php echo esc_attr(__('Colors and Styles' , DARVEN_EPI_LANGUAGE_DOMAIN))?></a> -->
        <a href="?page=darven-epi-admin&tab=positions"
           class="nav-tab <?php if ( $tab === 'positions' ): ?>nav-tab-active<?php endif; ?>"><?php echo esc_attr(__('Positions', DARVEN_EPI_LANGUAGE_DOMAIN))?></a>
        <a href="?page=darven-epi-admin&tab=compatibility"
           class="nav-tab <?php if ( $tab === 'compatibility' ): ?>nav-tab-active<?php endif; ?>"><?php echo esc_attr(__('Compatibility Mode', DARVEN_EPI_LANGUAGE_DOMAIN))?></a>
	</nav>

	<div class="tab-content wrap">
        <div class="positions">
            <form method="post" action="<?php echo esc_url( add_query_arg(
				'tab', $tab , admin_url( 'options.php' )
			) ); ?>">
				<?php
				settings_errors();
				settings_fields( 'darven_epi_option_group' );
				do_settings_sections( 'darven-epi-admin' );
				submit_button();
				?>

            </form>
        </div>
	</div>
</div>
<style>
	<?php

    $colors_options       = get_option( "darven_epi_option_colorsandstyles" );
    $installments_price_color = get_option( "darven_epi_option_colorsandstyles" );


//$installment_price = get_option("darven_woo_parcelas_color_of_installment_price");

?>

    #incash-prefix{

        color: <?php echo esc_attr($colors_options['darven_epi_color_of_incash_prefix']); ?>;
    }

    #incash-price {

        color: <?php echo esc_attr($colors_options['darven_epi_color_of_incash_price']); ?>;

    }

    #incash-suffix {

        color: <?php echo esc_attr($colors_options['darven_epi_color_of_incash_suffix']); ?>;
    }


    #installment-prefix{

        color: <?php echo esc_attr($colors_options['darven_epi_color_of_installments_prefix']); ?>;
    }

    #installment-install{

        color: <?php echo esc_attr($colors_options['darven_epi_color_of_installments_install']); ?>;
    }

    #installment-price {

        color: <?php echo esc_attr($colors_options['darven_epi_color_of_installments_price']); ?>;

    }

    #installment-suffix {

        color: <?php echo esc_attr($colors_options['darven_epi_color_of_installments_suffix']); ?>;
    }


</style>
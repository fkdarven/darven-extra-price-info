<?php
$colors_options       = get_option( "darven_epi_option_colorsandstyles" );
$installments_price_color = get_option( "darven_epi_option_colorsandstyles" );

//$installment_price = get_option("darven_woo_parcelas_color_of_installment_price");

?>
<style>


    #incash-prefix {

        color: <?php echo esc_attr($colors_options['darven_epi_color_of_incash_prefix']); ?>;
        font-size: <?php echo esc_attr($colors_options['darven_epi_font_size_of_incash_prefix']) ?? 1;  ?>em;
    }

    #incash-price {

        color: <?php echo esc_attr($colors_options['darven_epi_color_of_incash_price']); ?> !important;
        font-size: <?php echo esc_attr($colors_options['darven_epi_font_size_of_incash_price']) ?? 1;  ?>em;

    }

    #incash-suffix {

        color: <?php echo esc_attr($colors_options['darven_epi_color_of_incash_suffix']); ?>;
        font-size: <?php echo esc_attr($colors_options['darven_epi_font_size_of_incash_suffix']) ?? 1;  ?>em;
    }


    #installment-prefix {

        color: <?php echo esc_attr($colors_options['darven_epi_color_of_installments_prefix']); ?>;
        font-size: <?php echo esc_attr($colors_options['darven_epi_font_size_of_installments_prefix']) ?? 1;  ?>em;

    }

    #installment-install {

        color: <?php echo esc_attr($colors_options['darven_epi_color_of_installments_install']); ?>;
        font-size: <?php echo esc_attr($colors_options['darven_epi_font_size_of_installments_install']) ?? 1;  ?>em;

    }

    #installment-price {

        color: <?php echo esc_attr($colors_options['darven_epi_color_of_installments_price']); ?>;
        font-size: <?php echo esc_attr($colors_options['darven_epi_font_size_of_installments_price']) ?? 1;  ?>em;

    }

    #installment-suffix {

        color: <?php echo esc_attr($colors_options['darven_epi_color_of_installments_suffix']); ?>;
        font-size: <?php echo esc_attr($colors_options['darven_epi_font_size_of_installments_suffix']) ?? 1;  ?>em;
    }


</style>
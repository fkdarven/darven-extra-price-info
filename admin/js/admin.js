window.onload = function () {

    var modal = document.getElementById("installments_auxiliary_table_div");
    var btn = document.getElementById("myBtn");

    var span = document.getElementsByClassName("close")[0];

    btn.onclick = function() {
        modal.style.display = "block";
    }

// When the user clicks on <span> (x), close the modal
    span.onclick = function() {
        modal.style.display = "none";
    }

// When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    let is_incash_enabled = document.getElementById('darven_epi_incash_is_enabled');
    let incash_array = ['darven_epi_type_of_discount', 'darven_epi_value_of_incash_discount', 'darven_epi_minimum_incash_value', 'darven_epi_incash_prefix', 'darven_epi_incash_suffix'];

    let is_installments_enabled = document.getElementById('darven_epi_installments_is_enabled');
    let installments_array = ['darven_epi_mode_of_view', 'darven_epi_popup_text', 'darven_epi_max_installments', 'darven_epi_minimum_installments_value', 'darven_epi_installments_prefix', 'darven_epi_installments_suffix', 'darven_epi_installments_interest_fee_first_install', 'darven_epi_installments_interest_fee', 'darven_epi_installments_interest_fee_from', 'darven_epi_installments_interest_fee_is_table_enabled', 'darven_epi_installments_interest_fee_table'];

    let is_table_enabled = document.getElementById('darven_epi_installments_interest_fee_is_table_enabled');


    shallEnable('incash');
    shallEnable('');
    shallEnableTable();

    is_incash_enabled.onchange = function () {
        shallEnable('incash')
    };
    is_installments_enabled.onchange = function () {
        shallEnable()
    };
    is_table_enabled.onchange = function () {
        shallEnableTable()
    }

    function shallEnableTable() {
        if (is_table_enabled.checked) {
            document.getElementById('darven_epi_installments_interest_fee').setAttribute('readonly', true);
            document.getElementById('darven_epi_installments_interest_fee_first_install').setAttribute('readonly', true);
            document.getElementById('darven_epi_installments_interest_fee_table').removeAttribute('readonly');
            return;
        }
        document.getElementById('darven_epi_installments_interest_fee_table').setAttribute('readonly', true);
        document.getElementById('darven_epi_installments_interest_fee').removeAttribute('readonly');
        document.getElementById('darven_epi_installments_interest_fee_first_install').removeAttribute('readonly');
    }

    function shallEnable(section) {

        if (section === 'incash') {
            if (!is_incash_enabled.checked) {
                incash_array.forEach(function (element) {
                    document.getElementById(element).setAttribute('readonly', 'true');
                });
                return;
            }
            incash_array.forEach(function (element) {
                document.getElementById(element).removeAttribute('readonly');
            });
        }
        if (!is_installments_enabled.checked) {

            installments_array.forEach(function (element) {
                document.getElementById(element).setAttribute('readonly', 'true');
            });
            return;
        }
        installments_array.forEach(function (element) {
            document.getElementById(element).removeAttribute('readonly');
        });

    }
}

jQuery.noConflict();
jQuery(document).ready(function () {
    customized_values = customized_values.split('|');
    let installments_table_div = jQuery('#installments_auxiliary_table_div');
    for (let install_number = 0; install_number < max_install; install_number++) {
        if (install_number < (first_install - 1)) {
            jQuery('<p><label for="darven_epi_install_numbers">' + (install_number + 1) + 'ª Parcela <input type="text" id="darven_epi_install_number" size="20" name="darven_epi_install_number_' + install_number + '" value="0" readonly placeholder="Insira a Taxa" /></label></p>').appendTo(installments_table_div);

        } else {
            if(customized_values[install_number - first_install + 1] === undefined){
                jQuery('<p><label for="darven_epi_install_numbers">' + (install_number + 1) + 'ª Parcela <input type="text" id="darven_epi_install_number" size="20" name="darven_epi_install_number_' + install_number + '" value="0" placeholder="Insira a Taxa" /></label></p>').appendTo(installments_table_div);
            }
            else{

                jQuery('<p><label for="darven_epi_install_numbers">' + (install_number + 1) + 'ª Parcela <input type="text" id="darven_epi_install_number" size="20" name="darven_epi_install_number_' + install_number + '" value="' + customized_values[install_number - first_install + 1] + '" placeholder="Insira a Taxa" /></label></p>').appendTo(installments_table_div);
            }

        }
    }
    jQuery('#customized_values_button').on('click', function () {
        let installments_final_result = "";
        for (let child = first_install - 1; child < max_install; child++) {
            if (document.getElementsByName('darven_epi_install_number_' + child)[0].value === '' && document.getElementsByName('darven_epi_install_number_' + child)[0].readonly === true) {
               
            } else {
                installments_final_result += document.getElementsByName('darven_epi_install_number_' + child)[0].value + "|";
            }

        }
        document.getElementsByName('darven_epi_option_general[darven_epi_installments_interest_fee_table]')[0].value = installments_final_result.slice(0, -1);
    });
    jQuery('#show_auxiliar_table').on('click', function () {
        document.getElementById("installments_auxiliar_table_div").style.display = "block";
        document.getElementById("customized_values_button").style.display = "block";
    })

});

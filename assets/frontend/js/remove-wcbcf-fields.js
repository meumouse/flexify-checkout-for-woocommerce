/**
 * Hide Brazilian Market fields if country is different of Brazil
 * 
 * @since 3.0.0
 */
jQuery(document).ready( function($) {
    function hide_fields_based_on_country() {
        var selected_country = $('#billing_country').val();
        var target_fields = $('#billing_persontype_field, #billing_cpf_field, #billing_rg_field, #billing_cnpj_field, #billing_ie_field, #billing_cellphone_field, #billing_birthdate_field, #billing_sex_field, #billing_number_field, #billing_neighborhood_field');

        if (selected_country === 'BR') {
            target_fields.removeClass('d-none');
        } else {
            target_fields.addClass('d-none');
        }
    }

    // Call the function when loading the page and whenever the country is changed
    $(document).ready(hide_fields_based_on_country);
    $('#billing_country').change(hide_fields_based_on_country);
});
/**
 * Autofill company fields on digit CNPJ
 * 
 * @since 1.4.5
 * @version 2.0.0
 */
jQuery(document).ready( function($) {
    function autofillCheckoutFields(data) {
        $('#billing_phone').val(data.telefone);
        $('#billing_company').val(data.nome);
        $('#billing_company_field').addClass('is-active');
        $('#billing_address_1').val(data.logradouro);
        $('#billing_number').val(data.numero);
        $('#billing_neighborhood').val(data.bairro);
        $('#billing_city').val(data.municipio);
        $('#billing_state').val(data.uf); 
    }

    $('#billing_cnpj').blur( function() {
        var cnpj = $(this).val().replace(/\D/g, '');
    
        if (cnpj.length === 14) {
    
            $('form.checkout').block({
                message: null,
                overlayCSS: {
                    background: "#fff",
                    opacity: .6
                }
            });
    
            $.ajax({
                type: 'POST',
                url: flexify_checkout_vars.ajax_url,
                data: {
                    action: 'cnpj_autofill_query',
                    cnpj: cnpj,
                },
                success: function(response) {
                    if (response.success) {
                        var cepWithoutSpecialChars = response.data.cep.replace(/[^\d]/g, '');
                        var formattedCep = cepWithoutSpecialChars.replace(/^(\d{5})(\d{3})/, '$1-$2');

                        $('#billing_postcode').val(formattedCep);

                        autofillCheckoutFields(response.data);
                    }
                },
                error: function() {
                    console.log(response);
                },
                complete: function() {
                    $('form.checkout').unblock();
                }
            });
        }
    });    
});
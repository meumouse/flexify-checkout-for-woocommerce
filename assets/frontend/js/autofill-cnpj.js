/**
 * Auto fill checkout fields on digit cnpj
 * 
 * @since 1.4.5
 * @version 3.5.0
 * @package MeuMouse.com
 */
jQuery(document).ready( function($) {

    /**
     * Fill checkout fields on get response
     * 
     * @since 1.4.5
     * @version 3.5.0
     * @param {object} data | Object data
     */
    function autofill_fields(data) {
        if (data.telefone) $('#billing_phone').val(data.telefone);
        if (data.nome) $('#billing_company').val(data.nome);
        $('#billing_company_field').addClass('is-active');
        if (data.logradouro) $('#billing_address_1').val(data.logradouro);
        if (data.numero) $('#billing_number').val(data.numero);
        if (data.bairro) $('#billing_neighborhood').val(data.bairro);
        if (data.municipio) $('#billing_city').val(data.municipio);
        if (data.uf) $('#billing_state').val(data.uf); 
    }

    $('#billing_cnpj').blur( function() {
        var cnpj = $(this).val().replace(/\D/g, '');
    
        if (cnpj.length === 14) {
            $('form.checkout').block({
                message: null,
                overlayCSS: {
                    background: "#fff",
                    opacity: 0.6,
                }
            });
    
            // send AJAX call
            $.ajax({
                type: 'POST',
                url: flexify_checkout_vars.ajax_url,
                data: {
                    action: 'cnpj_autofill_query',
                    cnpj: cnpj,
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data && response.data.cep) {
                            var get_postcode = response.data.cep;
                            var postcode_sanitized = get_postcode.replace(/[^\d]/g, '');
                            var formatted_postcode = postcode_sanitized.replace(/^(\d{5})(\d{3})/, '$1-$2');
                            $('#billing_postcode').val(formatted_postcode);
                        }
                        autofill_fields(response.data);
                    }
                },
                error: function() {
                    console.log('Error:', response);
                },
                complete: function() {
                    $('form.checkout').unblock();
                }
            });
        }
    });    
});
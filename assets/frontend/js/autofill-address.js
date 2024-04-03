/**
 * Auto fill address on enter postcode
 * 
 * @since 1.0.0
 * @version 3.2.0
 * @package MeuMouse.com
 */
jQuery(function($) {
    ({
        init: function() {
            var self = this;

            // Check if billing postcode is already filled
            if ( $("#billing_postcode").val() && !$("#billing_address_1").val() ) {
                self.autofill("billing");
            }

            // Check if shipping postcode is already filled
            if ( $("#shipping_postcode").val() && !$("#shipping_address_1").val() ) {
                self.autofill("shipping");
            }

            // Listen for keyup event on billing postcode field
            $("#billing_postcode").on("keyup", function() {
                self.autofill("billing");
            });

            // Listen for keyup event on shipping postcode field
            $("#shipping_postcode").on("keyup", function() {
                self.autofill("shipping");
            });
        },
        block: function() {
            $("form.checkout").block({
                message: null,
                overlayCSS: {
                    background: "#fff",
                    opacity: .6
                }
            });
        },
        unblock: function() {
            $("form.checkout").unblock();
        },
        autofill: function(type) {
            var self = this;
            var postcodeField = $("#" + type + "_postcode");
            var postcode = postcodeField.val().replace(/\D/g, "");

            if ( postcode && postcode.length === 8 ) {
                postcodeField.blur();
                this.block();

                $.ajax({
                    type: 'GET',
                    url: fcw_auto_fill_address_api_params.api_service.replace("{postcode}", postcode),
                    dataType: 'json',
                    contentType: 'application/json',
                    success: function(response) {
                        if (response) {
                            self.fill_fields(type, response);
                        }
                    },
                    error: function(error) {
                        console.log(error);
                    },
                    complete: function() {
                        self.unblock();
                    }
                });
            }
        },
        fill_fields: function(type, data) {
            $("#" + type + "_address_1").val(data[fcw_auto_fill_address_api_params.address_param]).change();
            $("#" + type + "_neighborhood").val(data[fcw_auto_fill_address_api_params.neightborhood_param]).change();
            $("#" + type + "_city").val(data[fcw_auto_fill_address_api_params.city_param]).change();
            $("#" + type + "_state").val(data[fcw_auto_fill_address_api_params.state_param]).trigger("change").change();
        }
    }).init();
});
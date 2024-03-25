/**
 * Fill address on enter postcode
 * 
 * @since 1.0.0
 * @package MeuMouse.com
 */
jQuery(( function(n) {
    ({
        init: function() {
            var i = this;
            
            n(document.body).find("#billing_postcode").val() && !n(document.body).find("#billing_address_1").val() && this.autofill("billing"), n(document.body).find("#shipping_postcode").val() && !n(document.body).find("#shipping_address_1").val() && this.autofill("shipping"), n(document.body).find("#billing_postcode").on("keyup", (function(n) {
                return i.autofill("billing")
            })), n(document.body).find("#shipping_postcode").on("keyup", ( function(n) {
                return i.autofill("shipping")
            }))
        },
        block: function() {
            n("form.checkout").block({
                message: null,
                overlayCSS: {
                    background: "#fff",
                    opacity: .6
                }
            })
        },
        unblock: function() {
            n("form.checkout").unblock()
        },
        autofill: function(i, o) {
            var l = this;
            o = o || !1;
            var t = n("#" + i + "_country").val();

            if (!n("#" + i + "_country").length || "BR" === t) {
                var e = n("#" + i + "_postcode"),
                    c = e.val().replace(/\D/g, "");
                c && 8 === c.length && (e.blur(), this.block(), n.ajax({
                    type: "GET",
                    url: "https://brasilapi.com.br/api/cep/v1/".concat(c),
                    dataType: "json",
                    contentType: "application/json",
                    success: function(n) {
                        if (n.state && (l.fillFields(i, n), o)) {
                            var t = "billing" === i ? "shipping" : "billing";
                            l.fillFields(t, n)
                        }
                    },
                    error: function(n) {
                        console.log(n)
                    },
                    complete: function() {
                        l.unblock()
                    }
                }))
            }
        },
        fillFields: function(i, o) {
            n("#" + i + "_address_1").val(o.street).change(), n("#" + i + "_neighborhood").length ? n("#" + i + "_neighborhood").val(o.neighborhood).change() : n("#" + i + "_address_2").val(o.neighborhood).change(), n("#" + i + "_city").val(o.city).change(), n("#" + i + "_state").val(o.state).trigger("change").change()
        }
    }).init()
}));
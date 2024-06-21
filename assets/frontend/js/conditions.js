( function($) {
    "use strict";

    /**
     * Build checkout fields conditions
     * 
     * @since 3.5.0
     * @package MeuMouse.com
     */
    jQuery(document).ready( function($) {
        /**
         * Check condition
         * 
         * @since 3.5.0
         * @param {string} condition - Check condition
         * @param {string} value - Get condition value
         * @param {string} value_compare - Optional value for compare with value
         * @return {boolean}
         */
        function check_condition(condition, value, value_compare = '') {
            switch (condition) {
                case 'is':
                    return value === value_compare;

                case 'is_not':
                    return value !== value_compare;

                case 'empty':
                    return value === '';

                case 'not_empty':
                    return value !== '';

                case 'contains':
                    return value.indexOf(value_compare) !== -1;

                case 'not_contain':
                    return value.indexOf(value_compare) === -1;

                case 'start_with':
                    return value.startsWith(value_compare);

                case 'finish_with':
                    return value.endsWith(value_compare);

                case 'bigger_then':
                    return parseFloat(value) > parseFloat(value_compare);

                case 'less_than':
                    return parseFloat(value) < parseFloat(value_compare);

                case '':
                    return false;

                case 'none':
                default:
                    return false;
            }
        }

        // get field conditions from backend
        var field_conditions = fcw_condition_param.field_condition || {};

        /**
         * Check field visibility if has condition
         * 
         * @since 3.5.0
         */
        function check_field_visibility() {
            // get each field condition
            $.each(field_conditions, function(index, condition) {
                var verification_condition_value = $('#' + condition.verification_condition_field);
                
                if (condition.type_rule === 'show') {
                    if (condition.verification_condition === 'field') {
                        if (check_condition(condition.condition, verification_condition_value.val(), condition.condition_value)) {
                            // CSS class "temp-hidden" then skip validate field
                            $('#' + condition.component_field).prop('required', true).closest('.form-row').removeClass('temp-hidden').addClass('required required-field').show();
                        } else {
                            $('#' + condition.component_field).prop('required', false).closest('.form-row').removeClass('required required-field woocommerce-invalid validate-required').addClass('temp-hidden').hide();
                        }
                    }
                } else if (condition.type_rule === 'hide') {
                    if (check_condition(condition.condition, verification_condition_value.val(), condition.condition_value)) {
                        // CSS class "temp-hidden" then skip validate field
                        $('#' + condition.component_field).prop('required', false).closest('.form-row').removeClass('required required-field woocommerce-invalid validate-required').addClass('temp-hidden').hide();
                    } else {
                        $('#' + condition.component_field).prop('required', true).closest('.form-row').removeClass('temp-hidden').addClass('required required-field').show();
                    }
                }
            });

            // check if field is required and add abbr element
            $('label.has-condition.required-field > span.optional').remove();

            $('label.has-condition.required-field').each(function() {
                // Check if the label already contains an abbr element with the required class
                if ($(this).find('abbr.required').length < 1) {
                    // Append the abbr element if it doesn't already exist
                    $(this).append('<abbr class="required" title="' + flexify_checkout_vars.i18n.required_field + '">*</abbr>');
                }
            });
        }

        // check condition on change value
        $.each(field_conditions, function(index, condition) {
            var verification_field_id = '#' + condition.verification_condition_field;

            // Use event delegation
            $(document).on('change input keyup', verification_field_id, function() {
                check_field_visibility();
                $('form.checkout').trigger('update_checkout');
                $('form.checkout').trigger('wc_fragment_refresh');
            });
        });

        // load condition on load page
        check_field_visibility();

        // validation process on place order
        $('form.checkout').on('checkout_place_order', function() {
            check_field_visibility();
        });

        // force validation on submit form
        $('form.checkout').submit(function(event) {
            check_field_visibility();
        });
    });

})(jQuery);
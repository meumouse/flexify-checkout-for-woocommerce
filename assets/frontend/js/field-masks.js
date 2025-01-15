/**
 * Add masks to inputs
 * 
 * @since 3.5.0
 * @package MeuMouse.com
 */
jQuery(document).ready( function($) {
    var get_field_masks = fcw_field_masks.get_input_masks;

    // add mask for each field with input mask defined
    $.each(get_field_masks, function(id, value) {
        var field_id = $('#' + id);

        $(field_id).mask(value);
    });
});
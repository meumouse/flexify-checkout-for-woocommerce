/**
 * Function to change container visibility
 * 
 * @since 1.0.0
 * @param {string} method | Activation element selector
 * @param {string} container | Container selector
 * @pacakge MeuMouse.com
 */
function container_visibility(method, container) {
    let checked = jQuery(method).prop('checked');

    jQuery(container).toggleClass('d-none', !checked);
}

/**
 * Update visibility state on click
 * 
 * @since 3.5.0
 * @param {string} toggle | Activation element selector
 * @param {string} container | Container selector
 * @package MeuMouse.com
 */
function visibility_controller(toggle, container) {
    container_visibility(toggle, container);

    jQuery(toggle).click( function() {
        container_visibility(toggle, container);
    });
}

/**
 * Check visibility with select component
 * 
 * @since 3.5.0
 * @param {string} select | Select ID
 * @param {Array} options | Target options for display container
 * @param {string} container | Container to be displayed
 * @package MeuMouse.com
 */
function select_visibility_controller(select, options, container) {
    // Remove any existing 'change' event handlers
    jQuery(document).off('change', select);

    // Adds a single 'change' event handler
    jQuery(document).on('change', select, function() {
        var get_option = jQuery(select).val();

        if (options.includes(get_option)) {
            jQuery(container).removeClass('d-none');
        } else {
            jQuery(container).addClass('d-none');
        }
    });

    // Initialize visibility on page load
    var initial_option = jQuery(select).val();
    
    if (options.includes(initial_option)) {
        jQuery(container).removeClass('d-none');
    } else {
        jQuery(container).addClass('d-none');
    }
}
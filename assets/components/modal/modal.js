/**
 * Function for display popups based on Bootstrap
 * 
 * @since 2.3.0
 * @param {string} trigger | Trigger for display popup
 * @param {string} container | Container for display content
 * @param {string} close | Close button popup
 * @package MeuMouse.com
 */
function display_popup(trigger, container, close) {
    // open modal on click to trigger
    trigger.on('click', function(e) {
        e.preventDefault();
        container.addClass('show');
    });

    // close modal on click outside container
    container.on('click', function(e) {
        if (e.target === this) {
            jQuery(this).removeClass('show');
        }
    });

    // close modal on click close button
    close.on('click', function(e) {
        e.preventDefault();
        container.removeClass('show');
    });
}
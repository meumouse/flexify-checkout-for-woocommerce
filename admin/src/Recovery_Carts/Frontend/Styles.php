<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Frontend;

use MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Admin;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Helpers;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Render frontend styles
 * 
 * @since 1.0.0
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Frontend
 * @author MeuMouse.com
 */
class Styles {

    /**
     * Construct function
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        add_action( 'wp_head', array( $this, 'set_root_variables' ) );
    }


    /**
     * Set root variables for CSS
     * 
     * @since 1.0.0
     * @return void
     */
    public function set_root_variables() {
        $primary_color = Admin::get_setting('primary_color');

        if ( ! Helpers::is_product() ) {
            return;
        }

        ob_start(); ?>

		:root {
            --fc-recovery-carts-primary: <?php echo $primary_color ?>;
            --fc-recovery-carts-primary-rgb: <?php echo self::hex_to_rgb( $primary_color ) ?>;
            --fc-recovery-carts-primary-hover: rgba( <?php echo self::adjust_brightness( $primary_color, -25 ) ?>, 1 );
        }

		<?php $css = ob_get_clean();
		$css = wp_strip_all_tags( $css );

		printf( __('<style>%s</style>'), $css );
    }


    /**
     * Convert HEX color to RGB
     *
     * @since 1.0.0
     * @param string $hex | Color in hexadecimal format (e.g., #ff0000 or ff0000)
     * @return string|false RGB color values as an associative array or false if invalid
     */
    public static function hex_to_rgb( $hex ) {
        // Remove the "#" if present
        $hex = ltrim( $hex, '#' );

        // Convert short HEX (e.g., "f00") to full HEX format (e.g., "ff0000")
        if ( strlen( $hex ) === 3 ) {
            $hex = str_repeat( $hex[0], 2 ) . str_repeat( $hex[1], 2 ) . str_repeat( $hex[2], 2 );
        }

        // Validate the HEX format
        if ( strlen( $hex ) !== 6 || !ctype_xdigit( $hex ) ) {
            return false; // Return false if the color is invalid
        }

        // Convert HEX to RGB values
        list( $r, $g, $b ) = sscanf( $hex, "%02x%02x%02x" );

        // Return RGB string format
        return sprintf( '%d, %d, %d', $r, $g, $b );
    }


    /**
     * Adjust the brightness of a HEX color and return RGBA
     *
     * @since 1.0.0
     * @param string $hex HEX color (e.g., #ff0000)
     * @param int $brightness Change in brightness (-255 to 255)
     * @param float $alpha Alpha transparency (0 to 1)
     * @return string RGBA color in format "rgba(r, g, b, a)"
     */
    public static function adjust_brightness( $hex, $brightness = 0 ) {
        // Get the RGB color as a string (e.g., "255, 87, 51")
        $rgb = self::hex_to_rgb( $hex );
    
        // Convert the RGB string into an array
        $rgb_values = explode( ', ', $rgb );
    
        // Ensure the values are valid
        if ( count( $rgb_values ) !== 3 ) {
            return '0, 0, 0'; // Return black if the input is invalid
        }
    
        // Adjust brightness
        $r = max( 0, min( 255, (int) $rgb_values[0] + $brightness ) );
        $g = max( 0, min( 255, (int) $rgb_values[1] + $brightness ) );
        $b = max( 0, min( 255, (int) $rgb_values[2] + $brightness ) );
    
        // Return RGB string without "rgb()"
        return sprintf( '%d, %d, %d', $r, $g, $b );
    }
}
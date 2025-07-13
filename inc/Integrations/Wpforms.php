<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with WPForms plugin
 *
 * @since 3.8.8
 * @version 5.0.0
 * @package MeuMouse.com
 */
class WPforms {
	
	/**
     * Construct function
     *
     * @since 3.8.8
     * @version 5.0.0
     * @return void
     */
    public function __construct() {
		add_action( 'template_redirect', array( $this, 'remove_scripts' ), 20 );
	}


    /**
     * Remove WPForms scripts
     * 
     * @since 3.8.8
     * @version 5.0.0
     * @return void
     */
    public function remove_scripts() {
        if ( is_flexify_checkout() && function_exists('wpforms') ) {
            ob_start( array( $this, 'force_remove_wpforms_scripts' ) );
        }
    }


	/**
	 * Force removal WPForms scripts
	 * 
	 * @since 3.8.8
     * @version 5.0.0
     * @param string $buffer |  HTML buffer
     * @return string
	 */
	public function force_remove_wpforms_scripts( $buffer ) {
        $buffer = preg_replace( '/<script[^>]*src=["\'].*?\/wp-content\/plugins\/wpforms\/assets\/pro\/lib\/intl-tel-input\/jquery\.intl-tel-input\.min\.js[^>]*><\/script>/i', '', $buffer );
        $buffer = preg_replace( '/<link[^>]*href=["\'].*?\/wp-content\/plugins\/wpforms\/assets\/pro\/css\/fields\/phone\/intl-tel-input\.min\.css[^>]*>/i', '', $buffer );
        
        return $buffer;
	}
}

new WPforms();
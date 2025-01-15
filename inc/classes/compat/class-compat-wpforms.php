<?php

namespace MeuMouse\Flexify_Checkout\Compat;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with WPForms plugin
 *
 * @since 3.8.8
 * @package MeuMouse.com
 */
class WPForms {
	
	/**
     * Construct function
     *
     * @since 3.8.8
     * @return void
     */
    public function __construct() {
		add_action( 'template_redirect', array( __CLASS__, 'remove_scripts' ), 20 );
	}


    /**
     * Remove WPForms scripts
     * 
     * @since 3.8.8
     * @return void
     */
    public static function remove_scripts() {
      if ( is_flexify_checkout() && function_exists('wpforms') ) {
        ob_start( array( __CLASS__, 'force_remove_wpforms_scripts' ) );
      }
    }


	/**
	 * Force removal WPForms scripts
	 * 
	 * @since 3.8.8
     * @return object
	 */
	public static function force_remove_wpforms_scripts( $buffer ) {
        $buffer = preg_replace( '/<script[^>]*src=["\'].*?\/wp-content\/plugins\/wpforms\/assets\/pro\/lib\/intl-tel-input\/jquery\.intl-tel-input\.min\.js[^>]*><\/script>/i', '', $buffer );
        $buffer = preg_replace( '/<link[^>]*href=["\'].*?\/wp-content\/plugins\/wpforms\/assets\/pro\/css\/fields\/phone\/intl-tel-input\.min\.css[^>]*>/i', '', $buffer );
        
        return $buffer;
	}
}

new WPForms();
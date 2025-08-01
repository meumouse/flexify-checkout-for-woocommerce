<?php

namespace MeuMouse\Flexify_Checkout\Validations;

use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Vendor util functions for validations
 *
 * @since 5.0.0
 * @package MeuMouse.com
 */
class Utils {

    /**
	 * Checks if the CPF is valid
	 *
	 * @since 3.2.0
	 * @version 5.0.0
	 * @param string $cpf | CPF to validate
	 * @return bool
	 */
	public static function validate_cpf( $cpf ) {
		$cpf = preg_replace( '/[^0-9]/', '', $cpf );

		if ( 11 !== strlen( $cpf ) || preg_match( '/^([0-9])\1+$/', $cpf ) ) {
			return false;
		}

		$digit = substr( $cpf, 0, 9 );

		for ( $j = 10; $j <= 11; $j++ ) {
			$sum = 0;

			for ( $i = 0; $i < $j - 1; $i++ ) {
				$sum += ( $j - $i ) * intval( $digit[ $i ] );
			}

			$summod11 = $sum % 11;
			$digit[ $j - 1 ] = $summod11 < 2 ? 0 : 11 - $summod11;
		}

		return intval( $digit[9] ) === intval( $cpf[9] ) && intval( $digit[10] ) === intval( $cpf[10] );
	}


	/**
	 * Checks if the CNPJ is valid
	 *
	 * @since 3.2.0
	 * @version 5.0.0
	 * @param string $cnpj | CNPJ to validate
	 * @return bool
	 */
	public static function validate_cnpj( $cnpj ) {
		$cnpj = sprintf( '%014s', preg_replace( '{\D}', '', $cnpj ) );

		if ( 14 !== strlen( $cnpj ) || 0 === intval( substr( $cnpj, -4 ) ) ) {
			return false;
		}

		for ( $t = 11; $t < 13; ) {
			for ( $d = 0, $p = 2, $c = $t; $c >= 0; $c--, ( $p < 9 ) ? $p++ : $p = 2 ) {
				$d += $cnpj[ $c ] * $p;
			}

			$d = ( ( 10 * $d ) % 11 ) % 10;

			if ( intval( $cnpj[ ++$t ] ) !== $d ) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Automatically validate an international phone number
	 * 
	 * @since 5.0.0
	 * @param string $phone | Phone number in E.164 format (e.g., +5541998765432)
	 * @return bool
	 */
	public static function is_valid_phone( $phone ) {
		try {
			$phoneUtil = PhoneNumberUtil::getInstance();
			// Pass null as region — only works if phone has country code (e.g., starts with +)
			$numberProto = $phoneUtil->parse( $phone, null );

			return $phoneUtil->isValidNumber( $numberProto );
		} catch ( NumberParseException $e ) {
			return false;
		}
	}
}
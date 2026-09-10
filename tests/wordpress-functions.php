<?php
/**
 * Minimal WordPress hook stubs for unit tests.
 *
 * @package AlanSmodic\AiProviderForUsai
 */

declare( strict_types=1 );

if ( ! function_exists( 'apply_filters' ) ) {
	$GLOBALS['ai_provider_for_usai_test_filters'] = array();

	/**
	 * Applies registered test filters.
	 *
	 * @param string $hook_name Filter name.
	 * @param mixed  $value     Value to filter.
	 * @return mixed Filtered value.
	 */
	function apply_filters( $hook_name, $value ) {
		if ( empty( $GLOBALS['ai_provider_for_usai_test_filters'][ $hook_name ] ) ) {
			return $value;
		}

		foreach ( $GLOBALS['ai_provider_for_usai_test_filters'][ $hook_name ] as $callback ) {
			$value = $callback( $value );
		}

		return $value;
	}

	/**
	 * Registers a test filter.
	 *
	 * @param string   $hook_name Filter name.
	 * @param callable $callback  Callback.
	 * @return true
	 */
	function add_filter( $hook_name, $callback ) {
		$GLOBALS['ai_provider_for_usai_test_filters'][ $hook_name ][] = $callback;
		return true;
	}
}

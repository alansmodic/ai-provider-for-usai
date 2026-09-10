<?php
/**
 * Credential and endpoint resolution.
 *
 * @package AlanSmodic\AiProviderForUsai
 * @since   1.0.0
 */

declare( strict_types=1 );

namespace AlanSmodic\AiProviderForUsai\Support;

/**
 * Resolves the USAi API key and agency endpoint.
 *
 * @since 1.0.0
 */
class Credentials {

	/**
	 * Provider ID. Core derives the connector setting and constant names from this.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public const PROVIDER_ID = 'usai';

	/**
	 * Option name Core registers for this connector.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public const OPTION_NAME = 'connectors_ai_usai_api_key';

	/**
	 * Environment variable and PHP constant name for the API key.
	 *
	 * Matches both Core's derived name for this provider ID and the convention used by the
	 * community Node client for USAi.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public const KEY_CONSTANT = 'USAI_API_KEY';

	/**
	 * Environment variable and PHP constant name for the agency endpoint.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public const BASE_URL_CONSTANT = 'USAI_BASE_URL';

	/**
	 * Option name used when the endpoint is stored in the database.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public const BASE_URL_OPTION = 'ai_provider_for_usai_base_url';

	/**
	 * Path prefix for the USAi REST API, appended to the agency base URL.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public const API_PATH = '/api/v1/';

	/**
	 * Resolves the API key, matching Core's precedence: env var, constant, then option.
	 *
	 * @since 1.0.0
	 *
	 * @return string The API key, or an empty string when not configured.
	 */
	public static function api_key(): string {
		$env = self::env( self::KEY_CONSTANT );
		if ( '' !== $env ) {
			return $env;
		}

		$constant = self::constant_value( self::KEY_CONSTANT );
		if ( '' !== $constant ) {
			return $constant;
		}

		return self::option_value( self::OPTION_NAME );
	}

	/**
	 * Resolves the agency-specific USAi base URL.
	 *
	 * USAi does not publish a single public endpoint. Each agency receives its own base URL
	 * after signing in, so there is deliberately no default: without an endpoint the provider
	 * reports itself unconfigured rather than guessing.
	 *
	 * Precedence matches the API key: env var, constant, then option.
	 *
	 * @since 1.0.0
	 *
	 * @return string The sanitized HTTPS base URL without a trailing slash, or empty when unset.
	 */
	public static function base_url(): string {
		$raw = self::env( self::BASE_URL_CONSTANT );

		if ( '' === $raw ) {
			$raw = self::constant_value( self::BASE_URL_CONSTANT );
		}

		if ( '' === $raw ) {
			$raw = self::option_value( self::BASE_URL_OPTION );
		}

		return self::sanitize_base_url( $raw );
	}

	/**
	 * Builds an absolute URL for a USAi API path.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path Path relative to the API root, such as `chat/completions`.
	 * @return string The absolute URL, or an empty string when the base URL is not configured.
	 */
	public static function url( string $path = '' ): string {
		$base = self::base_url();
		if ( '' === $base ) {
			return '';
		}

		return $base . self::API_PATH . ltrim( $path, '/' );
	}

	/**
	 * Reads an environment variable, preferring VIP's helper when available.
	 *
	 * @since 1.0.1
	 *
	 * @param string $name Environment variable name.
	 * @return string The value, or an empty string when unset.
	 */
	private static function env( string $name ): string {
		if ( function_exists( 'vip_get_env_var' ) ) {
			$value = vip_get_env_var( $name, '' );
			return is_string( $value ) ? $value : '';
		}

		$value = getenv( $name );

		return is_string( $value ) ? $value : '';
	}

	/**
	 * Reads a PHP constant when it is defined and non-empty.
	 *
	 * @since 1.0.1
	 *
	 * @param string $name Constant name.
	 * @return string The value, or an empty string when unset.
	 */
	private static function constant_value( string $name ): string {
		if ( ! defined( $name ) ) {
			return '';
		}

		$value = constant( $name );

		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * Reads a WordPress option as a string.
	 *
	 * @since 1.0.1
	 *
	 * @param string $option Option name.
	 * @return string The value, or an empty string when unset or unavailable.
	 */
	private static function option_value( string $option ): string {
		if ( ! function_exists( 'get_option' ) ) {
			return '';
		}

		$value = get_option( $option, '' );

		return is_string( $value ) ? $value : '';
	}

	/**
	 * Sanitizes a configured USAi endpoint to an HTTPS URL with no credentials.
	 *
	 * Agency endpoints may live on private networks, so private-IP rejection is not applied.
	 * Only HTTPS is accepted.
	 *
	 * @since 1.0.1
	 *
	 * @param string $url Candidate base URL.
	 * @return string The sanitized URL without a trailing slash, or empty when invalid.
	 */
	private static function sanitize_base_url( string $url ): string {
		$url = trim( $url );
		if ( '' === $url ) {
			return '';
		}

		if ( function_exists( 'esc_url_raw' ) ) {
			$url = esc_url_raw( $url, array( 'https' ) );
		} elseif ( 0 !== stripos( $url, 'https://' ) ) {
			return '';
		}

		if ( '' === $url ) {
			return '';
		}

		if ( function_exists( 'wp_parse_url' ) ) {
			$parts = wp_parse_url( $url );
		} else {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Fallback when WordPress is not loaded.
			$parts = parse_url( $url );
		}
		if ( ! is_array( $parts ) ) {
			return '';
		}

		$scheme = isset( $parts['scheme'] ) ? strtolower( (string) $parts['scheme'] ) : '';
		$host   = isset( $parts['host'] ) ? (string) $parts['host'] : '';

		if ( 'https' !== $scheme || '' === $host || isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
			return '';
		}

		return rtrim( $url, '/' );
	}
}

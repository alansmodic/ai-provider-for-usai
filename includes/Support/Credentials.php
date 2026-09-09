<?php
/**
 * Credential and endpoint resolution.
 *
 * @package AlanSmodic\AiProviderForUsai
 * @since   1.0.0
 */

declare( strict_types=1 );

namespace AlanSmodic\AiProviderForUsai\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		$env = getenv( self::KEY_CONSTANT );
		if ( is_string( $env ) && '' !== $env ) {
			return $env;
		}

		if ( defined( self::KEY_CONSTANT ) && constant( self::KEY_CONSTANT ) ) {
			return (string) constant( self::KEY_CONSTANT );
		}

		if ( ! function_exists( 'get_option' ) ) {
			return '';
		}

		$option = get_option( self::OPTION_NAME, '' );

		return is_string( $option ) ? $option : '';
	}

	/**
	 * Resolves the agency-specific USAi base URL.
	 *
	 * USAi does not publish a single public endpoint. Each agency receives its own base URL
	 * after signing in, so there is deliberately no default: without an endpoint the provider
	 * reports itself unconfigured rather than guessing.
	 *
	 * @since 1.0.0
	 *
	 * @return string The base URL without a trailing slash, or an empty string when not set.
	 */
	public static function base_url(): string {
		if ( defined( self::BASE_URL_CONSTANT ) && constant( self::BASE_URL_CONSTANT ) ) {
			return rtrim( (string) constant( self::BASE_URL_CONSTANT ), '/' );
		}

		$env = getenv( self::BASE_URL_CONSTANT );
		if ( is_string( $env ) && '' !== $env ) {
			return rtrim( $env, '/' );
		}

		if ( ! function_exists( 'get_option' ) ) {
			return '';
		}

		$option = get_option( self::BASE_URL_OPTION, '' );

		return is_string( $option ) ? rtrim( $option, '/' ) : '';
	}

	/**
	 * Builds an absolute URL for a USAi API path.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path Path relative to the API root, such as `chat/completions`.
	 * @return string The absolute URL.
	 */
	public static function url( string $path = '' ): string {
		return self::base_url() . self::API_PATH . ltrim( $path, '/' );
	}
}

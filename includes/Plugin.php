<?php
/**
 * Plugin initializer class.
 *
 * @package AlanSmodic\AiProviderForUsai
 * @since   1.0.0
 */

declare( strict_types=1 );

namespace AlanSmodic\AiProviderForUsai;

use AlanSmodic\AiProviderForUsai\Provider\UsaiProvider;
use AlanSmodic\AiProviderForUsai\Support\Credentials;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin class.
 *
 * @since 1.0.0
 */
class Plugin {

	/**
	 * Initializes the plugin.
	 *
	 * @since 1.0.0
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'register_provider' ), 5 );
		add_action( 'init', array( $this, 'register_authentication' ), 25 );
		add_filter(
			'plugin_action_links_' . plugin_basename( AI_PROVIDER_FOR_USAI_PLUGIN_FILE ),
			array( $this, 'plugin_action_links' )
		);
	}

	/**
	 * Registers the USAi provider with the AI Client.
	 *
	 * @since 1.0.0
	 */
	public function register_provider(): void {
		if ( ! class_exists( AiClient::class ) ) {
			return;
		}

		$registry = AiClient::defaultRegistry();

		if ( $registry->hasProvider( UsaiProvider::class ) ) {
			return;
		}

		$registry->registerProvider( UsaiProvider::class );
	}

	/**
	 * Registers bearer authentication when Core has not already done so.
	 *
	 * Core passes stored connector keys to the AI Client at priority 20, but skips keys supplied
	 * by an environment variable or constant. This fills that gap without overriding Core.
	 *
	 * @since 1.0.0
	 */
	public function register_authentication(): void {
		if ( ! class_exists( AiClient::class ) ) {
			return;
		}

		$api_key = Credentials::api_key();
		if ( '' === $api_key ) {
			return;
		}

		$registry = AiClient::defaultRegistry();

		if ( ! $registry->hasProvider( Credentials::PROVIDER_ID ) ) {
			return;
		}

		if ( null !== $registry->getProviderRequestAuthentication( Credentials::PROVIDER_ID ) ) {
			return;
		}

		$registry->setProviderRequestAuthentication(
			Credentials::PROVIDER_ID,
			new ApiKeyRequestAuthentication( $api_key )
		);
	}

	/**
	 * Adds a settings link to the plugin list table.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string> $links Existing action links.
	 * @return array<string> Modified action links.
	 */
	public function plugin_action_links( array $links ): array {
		$settings_link = sprintf(
			'<a href="%1$s">%2$s</a>',
			admin_url( 'options-general.php?page=connectors' ),
			esc_html__( 'Settings', 'ai-provider-for-usai' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}
}

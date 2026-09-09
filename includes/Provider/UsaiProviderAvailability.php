<?php
/**
 * USAi provider availability.
 *
 * @package AlanSmodic\AiProviderForUsai
 * @since   1.0.0
 */

declare( strict_types=1 );

namespace AlanSmodic\AiProviderForUsai\Provider;

use AlanSmodic\AiProviderForUsai\Support\Credentials;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reports whether the USAi provider is configured.
 *
 * USAi requires both a key and an agency-specific endpoint, so both must be present. A network
 * probe is deliberately avoided: agency endpoints sit behind restricted networks, and the API is
 * rate limited to three calls per second.
 *
 * @since 1.0.0
 */
class UsaiProviderAvailability implements ProviderAvailabilityInterface {

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	public function isConfigured(): bool {
		return '' !== Credentials::api_key() && '' !== Credentials::base_url();
	}
}

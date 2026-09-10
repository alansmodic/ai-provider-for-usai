<?php
/**
 * Uninstall handler.
 *
 * @package AlanSmodic\AiProviderForUsai
 */

declare( strict_types=1 );

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'ai_provider_for_usai_base_url' );

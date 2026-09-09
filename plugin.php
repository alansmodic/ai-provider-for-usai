<?php
/**
 * Plugin Name:       AI Provider for USAi
 * Plugin URI:        https://github.com/alansmodic/ai-provider-for-usai
 * Description:       USAi provider for the WordPress AI Client. GSA's FedRAMP-authorized generative AI platform for federal agencies.
 * Requires at least: 7.0
 * Requires PHP:      7.4
 * Version:           1.0.1
 * Author:            Alan Smodic
 * License:           GPL-2.0-or-later
 * License URI:       https://spdx.org/licenses/GPL-2.0-or-later.html
 * Text Domain:       ai-provider-for-usai
 *
 * @package AlanSmodic\AiProviderForUsai
 */

declare( strict_types=1 );

namespace AlanSmodic\AiProviderForUsai;

use WordPress\AiClient\AiClient;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AI_PROVIDER_FOR_USAI_MIN_PHP_VERSION', '7.4' );
define( 'AI_PROVIDER_FOR_USAI_MIN_WP_VERSION', '7.0' );
define( 'AI_PROVIDER_FOR_USAI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AI_PROVIDER_FOR_USAI_PLUGIN_FILE', __FILE__ );

/**
 * Displays an admin notice for requirement failures.
 *
 * @since 1.0.0
 *
 * @param string $message The error message to display.
 */
function requirement_notice( string $message ): void {
	if ( ! is_admin() ) {
		return;
	}
	?>
	<div class="notice notice-error">
		<p><?php echo wp_kses_post( $message ); ?></p>
	</div>
	<?php
}

/**
 * Displays an admin notice when the PHP AI Client is not available.
 *
 * @since 1.0.1
 */
function missing_client_notice(): void {
	requirement_notice(
		__( 'The USAi Provider plugin requires the WordPress AI Client. Please install and activate it, or upgrade to WordPress 7.0 or later.', 'ai-provider-for-usai' )
	);
}

/**
 * Checks if the PHP version meets the minimum requirement.
 *
 * @since 1.0.0
 *
 * @return bool True if PHP version is sufficient.
 */
function check_php_version(): bool {
	if ( version_compare( PHP_VERSION, AI_PROVIDER_FOR_USAI_MIN_PHP_VERSION, '<' ) ) {
		add_action(
			'admin_notices',
			static function () {
				requirement_notice(
					sprintf(
						/* translators: 1: Required PHP version, 2: Current PHP version */
						__( 'The USAi Provider plugin requires PHP version %1$s or higher. You are running PHP version %2$s.', 'ai-provider-for-usai' ),
						AI_PROVIDER_FOR_USAI_MIN_PHP_VERSION,
						PHP_VERSION
					)
				);
			}
		);
		return false;
	}
	return true;
}

/**
 * Checks if the WordPress version meets the minimum requirement.
 *
 * @since 1.0.0
 *
 * @global string $wp_version WordPress version.
 *
 * @return bool True if WordPress version is sufficient.
 */
function check_wp_version(): bool {
	if ( ! is_wp_version_compatible( AI_PROVIDER_FOR_USAI_MIN_WP_VERSION ) ) {
		add_action(
			'admin_notices',
			static function () {
				global $wp_version;
				requirement_notice(
					sprintf(
						/* translators: 1: Required WordPress version, 2: Current WordPress version */
						__( 'The USAi Provider plugin requires WordPress version %1$s or higher. You are running WordPress version %2$s.', 'ai-provider-for-usai' ),
						AI_PROVIDER_FOR_USAI_MIN_WP_VERSION,
						$wp_version
					)
				);
			}
		);
		return false;
	}
	return true;
}

/**
 * Registers a PSR-4 autoloader for the plugin namespace.
 *
 * Prefers the Composer autoloader when installed as a package; falls back to a minimal
 * PSR-4 loader so the plugin also works as a plain drop-in.
 *
 * @since 1.0.0
 */
function register_autoloader(): void {
	if ( file_exists( AI_PROVIDER_FOR_USAI_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
		require_once AI_PROVIDER_FOR_USAI_PLUGIN_DIR . 'vendor/autoload.php';
		return;
	}

	spl_autoload_register(
		static function ( string $class_name ): void {
			$prefix = __NAMESPACE__ . '\\';
			if ( 0 !== strpos( $class_name, $prefix ) ) {
				return;
			}

			$relative = str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';

			if ( false !== strpos( $relative, '..' ) ) {
				return;
			}

			if ( function_exists( 'validate_file' ) && 0 !== validate_file( $relative ) ) {
				return;
			}

			$path = AI_PROVIDER_FOR_USAI_PLUGIN_DIR . 'includes/' . $relative;
			if ( ! is_readable( $path ) ) {
				return;
			}

			// phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable -- Path is built from the plugin directory plus a validated class name.
			require_once $path;
		}
	);
}

/**
 * Loads the USAi provider plugin.
 *
 * @since 1.0.0
 */
function load(): void {
	static $loaded = false;

	if ( $loaded ) {
		return;
	}

	$loaded = true;

	if ( ! check_php_version() || ! check_wp_version() ) {
		return;
	}

	register_autoloader();

	if ( ! class_exists( AiClient::class ) ) {
		add_action( 'admin_notices', __NAMESPACE__ . '\\missing_client_notice' );
		return;
	}

	$plugin = new Plugin();
	$plugin->init();
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\load' );

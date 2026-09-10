<?php
/**
 * USAi provider.
 *
 * @package AlanSmodic\AiProviderForUsai
 * @since   1.0.0
 */

declare( strict_types=1 );

namespace AlanSmodic\AiProviderForUsai\Provider;

use AlanSmodic\AiProviderForUsai\Metadata\UsaiModelMetadataDirectory;
use AlanSmodic\AiProviderForUsai\Models\UsaiEmbeddingGenerationModel;
use AlanSmodic\AiProviderForUsai\Models\UsaiTextGenerationModel;
use AlanSmodic\AiProviderForUsai\Support\Credentials;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiProvider;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

/**
 * Class for the USAi provider.
 *
 * @since 1.0.0
 */
class UsaiProvider extends AbstractApiProvider {

	/**
	 * Fully qualified name of the SDK's embedding model contract.
	 *
	 * The capability enum ships ahead of the contracts in some AI Client releases, so this is
	 * checked before the embedding model is offered.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public const EMBEDDING_INTERFACE = 'WordPress\\AiClient\\Providers\\Models\\EmbeddingGeneration\\Contracts\\EmbeddingGenerationModelInterface';

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	protected static function baseUrl(): string {
		return Credentials::base_url();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	protected static function createProviderMetadata(): ProviderMetadata {
		$provider_meta = array(
			Credentials::PROVIDER_ID,
			'USAi',
			ProviderTypeEnum::cloud(),
			'https://www.usai.gov/api/',
			RequestAuthenticationMethod::apiKey(),
		);

		// Provider description support was added in AI Client 1.2.0.
		if ( version_compare( AiClient::VERSION, '1.2.0', '>=' ) ) {
			$provider_meta[] = function_exists( '__' )
				? __( 'GSA\'s FedRAMP-authorized generative AI platform for federal agencies.', 'ai-provider-for-usai' )
				: 'GSA\'s FedRAMP-authorized generative AI platform for federal agencies.';
		}

		// Provider logo path support was added in AI Client 1.3.0.
		if ( version_compare( AiClient::VERSION, '1.3.0', '>=' ) ) {
			$provider_meta[] = defined( 'AI_PROVIDER_FOR_USAI_PLUGIN_DIR' )
				? AI_PROVIDER_FOR_USAI_PLUGIN_DIR . 'includes/Provider/logo.svg'
				: dirname( __DIR__, 2 ) . '/includes/Provider/logo.svg';
		}

		return new ProviderMetadata( ...$provider_meta );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	protected static function createProviderAvailability(): ProviderAvailabilityInterface {
		return new UsaiProviderAvailability();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	protected static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface {
		return new UsaiModelMetadataDirectory();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @param ModelMetadata    $model_metadata    Metadata for the selected model.
	 * @param ProviderMetadata $provider_metadata Metadata for this provider.
	 * @return ModelInterface The model implementation.
	 *
	 * @throws RuntimeException If the model capabilities are not supported.
	 */
	protected static function createModel(
		ModelMetadata $model_metadata,
		ProviderMetadata $provider_metadata
	): ModelInterface {
		foreach ( $model_metadata->getSupportedCapabilities() as $capability ) {
			if ( $capability->isTextGeneration() ) {
				return new UsaiTextGenerationModel( $model_metadata, $provider_metadata );
			}

			// Only offered once the SDK ships the embedding contracts.
			if ( $capability->isEmbeddingGeneration() && interface_exists( self::EMBEDDING_INTERFACE ) ) {
				return new UsaiEmbeddingGenerationModel( $model_metadata, $provider_metadata );
			}
		}

		if ( function_exists( '__' ) ) {
			$message = sprintf(
				/* translators: %s: Model ID */
				__( 'Unsupported USAi model capabilities for model: %s', 'ai-provider-for-usai' ),
				$model_metadata->getId()
			);
		} else {
			$message = sprintf(
				'Unsupported USAi model capabilities for model: %s',
				$model_metadata->getId()
			);
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message, not output.
		throw new RuntimeException( $message );
	}
}

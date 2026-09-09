<?php
/**
 * USAi model metadata directory.
 *
 * @package AlanSmodic\AiProviderForUsai
 * @since   1.0.0
 */

declare( strict_types=1 );

namespace AlanSmodic\AiProviderForUsai\Metadata;

use AlanSmodic\AiProviderForUsai\Provider\UsaiProvider;
use AlanSmodic\AiProviderForUsai\Support\Credentials;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleModelMetadataDirectory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Discovers the models exposed by the agency's USAi instance.
 *
 * USAi implements OpenAI's `GET /models` shape, so the SDK's OpenAI-compatible directory base
 * handles listing and caching; only request creation and response parsing are supplied here.
 *
 * @since 1.0.0
 */
class UsaiModelMetadataDirectory extends AbstractOpenAiCompatibleModelMetadataDirectory {

	/**
	 * Substrings identifying models that generate embeddings rather than text.
	 *
	 * USAi's model list does not describe capabilities, so they are inferred from the model ID.
	 *
	 * @since 1.0.0
	 * @var list<string>
	 */
	private const EMBEDDING_MODEL_HINTS = array( 'embed', 'cohere_english' );

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	protected function createRequest( HttpMethodEnum $method, string $path, array $headers = array(), $data = null ): Request {
		// Metadata directories carry no RequestOptions; those exist only on models.
		return new Request(
			$method,
			Credentials::url( $path ),
			$headers,
			$data
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @return list<ModelMetadata> The model metadata list.
	 */
	protected function parseResponseToModelMetadataList( Response $response ): array {
		$data = $response->getData();

		if ( ! is_array( $data ) || ! isset( $data['data'] ) || ! is_array( $data['data'] ) ) {
			throw ResponseException::fromMissingData( 'USAi', 'data' );
		}

		$models = array();
		foreach ( $data['data'] as $entry ) {
			$model_id = '';
			if ( is_array( $entry ) && isset( $entry['id'] ) ) {
				$model_id = (string) $entry['id'];
			} elseif ( is_string( $entry ) ) {
				$model_id = $entry;
			}

			if ( '' === $model_id ) {
				continue;
			}

			$metadata = $this->build_model_metadata( $model_id );
			if ( null === $metadata ) {
				continue;
			}

			$models[] = $metadata;
		}

		return $models;
	}

	/**
	 * Builds metadata for a single USAi model.
	 *
	 * @since 1.0.0
	 *
	 * Embedding models are omitted entirely when the AI Client does not ship the embedding
	 * contracts, so a capability is never advertised that cannot be served.
	 *
	 * @param string $model_id The model ID.
	 * @return ModelMetadata|null The model metadata, or null when the model should be excluded.
	 */
	private function build_model_metadata( string $model_id ): ?ModelMetadata {
		if ( $this->is_embedding_model( $model_id ) ) {
			if ( ! interface_exists( UsaiProvider::EMBEDDING_INTERFACE ) ) {
				return null;
			}

			return $this->build_embedding_metadata( $model_id );
		}

		return new ModelMetadata(
			$model_id,
			$model_id,
			array(
				CapabilityEnum::textGeneration(),
				CapabilityEnum::chatHistory(),
			),
			$this->text_generation_options()
		);
	}

	/**
	 * Options honored by USAi's chat completions endpoint.
	 *
	 * USAi is modeled on the OpenAI Chat Completion API but documents two omissions: audio and
	 * structured output. `outputSchema` and `outputMimeType` are therefore not advertised, since
	 * advertising them would let the SDK send a `response_format` parameter USAi does not support.
	 *
	 * Function calling is not documented either way, so it is off by default and can be enabled
	 * per site once confirmed against an agency tenant.
	 *
	 * @since 1.0.0
	 *
	 * @return list<SupportedOption> The supported options.
	 */
	private function text_generation_options(): array {
		$text_only = array( array( ModalityEnum::text() ) );

		$options = array(
			new SupportedOption( OptionEnum::inputModalities(), $text_only ),
			new SupportedOption( OptionEnum::outputModalities(), $text_only ),
			new SupportedOption( OptionEnum::systemInstruction() ),
			new SupportedOption( OptionEnum::temperature() ),
			new SupportedOption( OptionEnum::maxTokens() ),
			new SupportedOption( OptionEnum::topP() ),
			new SupportedOption( OptionEnum::frequencyPenalty() ),
			new SupportedOption( OptionEnum::presencePenalty() ),
			new SupportedOption( OptionEnum::stopSequences() ),
			new SupportedOption( OptionEnum::customOptions() ),
		);

		if ( function_exists( 'apply_filters' ) ) {
			/**
			 * Filters the options advertised for USAi text generation models.
			 *
			 * Use this to enable options such as function calling once they are confirmed to work
			 * against a specific agency tenant.
			 *
			 * @since 1.0.0
			 *
			 * @param list<SupportedOption> $options The supported options.
			 */
			$options = (array) apply_filters( 'ai_provider_for_usai_supported_options', $options );
		}

		return $options;
	}

	/**
	 * Builds metadata for an embedding model.
	 *
	 * @since 1.0.0
	 *
	 * @param string $model_id The model ID.
	 * @return ModelMetadata The model metadata.
	 */
	private function build_embedding_metadata( string $model_id ): ModelMetadata {
		return new ModelMetadata(
			$model_id,
			$model_id,
			array( CapabilityEnum::embeddingGeneration() ),
			array(
				new SupportedOption( OptionEnum::inputModalities(), array( array( ModalityEnum::text() ) ) ),
				new SupportedOption( OptionEnum::customOptions() ),
			)
		);
	}

	/**
	 * Determines whether a model ID looks like an embedding model.
	 *
	 * @since 1.0.0
	 *
	 * @param string $model_id The model ID.
	 * @return bool True when the model appears to generate embeddings.
	 */
	private function is_embedding_model( string $model_id ): bool {
		$needle = strtolower( $model_id );

		foreach ( self::EMBEDDING_MODEL_HINTS as $hint ) {
			if ( false !== strpos( $needle, $hint ) ) {
				return true;
			}
		}

		return false;
	}
}

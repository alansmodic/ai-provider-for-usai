<?php
/**
 * USAi embedding generation model.
 *
 * @package AlanSmodic\AiProviderForUsai
 * @since   1.0.0
 */

declare( strict_types=1 );

namespace AlanSmodic\AiProviderForUsai\Models;

use AlanSmodic\AiProviderForUsai\Support\Credentials;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModel;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Http\Util\ResponseUtil;
use WordPress\AiClient\Providers\Models\EmbeddingGeneration\Contracts\EmbeddingGenerationModelInterface;
use WordPress\AiClient\Results\DTO\Embedding;
use WordPress\AiClient\Results\DTO\EmbeddingResult;
use WordPress\AiClient\Results\DTO\TokenUsage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates embeddings using USAi's embeddings endpoint.
 *
 * IMPORTANT: the AI Client ships the `embedding_generation` capability enum ahead of the embedding
 * contracts themselves. On releases without them, declaring this class would fatal, so it is only
 * ever referenced behind an `interface_exists()` guard in UsaiProvider::createModel(). PSR-4
 * autoloading means the file is not loaded until that guard passes.
 *
 * @since 1.0.0
 */
class UsaiEmbeddingGenerationModel extends AbstractApiBasedModel implements EmbeddingGenerationModelInterface {

	/**
	 * Default Cohere-style input type. Overridable via the `input_type` custom option.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const DEFAULT_INPUT_TYPE = 'search_document';

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @param list<MessagePart> $input The message parts to embed.
	 */
	public function generateEmbeddingResult( array $input ): EmbeddingResult {
		$request = new Request(
			HttpMethodEnum::POST(),
			Credentials::url( 'embeddings' ),
			array( 'Content-Type' => 'application/json' ),
			$this->prepare_params( $input ),
			$this->getRequestOptions()
		);

		$request  = $this->getRequestAuthentication()->authenticateRequest( $request );
		$response = $this->getHttpTransporter()->send( $request );

		ResponseUtil::throwIfNotSuccessful( $response );

		return $this->parse_response( $response );
	}

	/**
	 * Builds the embeddings request body.
	 *
	 * @since 1.0.0
	 *
	 * @param list<MessagePart> $input The message parts to embed.
	 * @return array<string, mixed> The request body.
	 *
	 * @throws InvalidArgumentException If the input is not a non-empty list of text parts.
	 */
	private function prepare_params( array $input ): array {
		if ( ! array_is_list( $input ) || empty( $input ) ) {
			throw new InvalidArgumentException( 'Embedding input must be a non-empty list of message parts.' );
		}

		$texts = array();
		foreach ( $input as $index => $part ) {
			$text = $part instanceof MessagePart ? $part->getText() : null;

			if ( null === $text || '' === $text ) {
				throw new InvalidArgumentException(
					// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message, not output.
					sprintf( 'Embedding input at index %d must be a text message part.', (int) $index )
				);
			}

			$texts[] = $text;
		}

		$custom = $this->getConfig()->getCustomOptions();

		$params = array(
			'model'          => $this->metadata()->getId(),
			'input'          => $texts,
			// Required by the Cohere models USAi exposes; harmless for the others.
			'input_type'     => isset( $custom['input_type'] ) ? (string) $custom['input_type'] : self::DEFAULT_INPUT_TYPE,
			'encodingFormat' => isset( $custom['encodingFormat'] ) ? (string) $custom['encodingFormat'] : 'float',
		);

		foreach ( $custom as $key => $value ) {
			if ( ! array_key_exists( $key, $params ) ) {
				$params[ $key ] = $value;
			}
		}

		return $params;
	}

	/**
	 * Converts an embeddings response into an EmbeddingResult.
	 *
	 * USAi follows OpenAI's response shape, where vectors arrive under `data[].embedding`. A
	 * flat `embeddings` list is accepted as a fallback.
	 *
	 * @since 1.0.0
	 *
	 * @param Response $response The HTTP response.
	 * @return EmbeddingResult The parsed result.
	 *
	 * @throws ResponseException If the response contains no usable vectors.
	 */
	private function parse_response( Response $response ): EmbeddingResult {
		$data    = $response->getData();
		$vectors = array();

		if ( is_array( $data ) && isset( $data['data'] ) && is_array( $data['data'] ) ) {
			foreach ( $data['data'] as $entry ) {
				if ( is_array( $entry ) && isset( $entry['embedding'] ) && is_array( $entry['embedding'] ) ) {
					$vectors[] = array_values( $entry['embedding'] );
				}
			}
		} elseif ( is_array( $data ) && isset( $data['embeddings'] ) && is_array( $data['embeddings'] ) ) {
			foreach ( $data['embeddings'] as $vector ) {
				if ( is_array( $vector ) ) {
					$vectors[] = array_values( $vector );
				}
			}
		}

		if ( empty( $vectors ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message, not output.
			throw ResponseException::fromMissingData( $this->providerMetadata()->getName(), 'data' );
		}

		$embeddings = array();
		foreach ( $vectors as $vector ) {
			$embeddings[] = new Embedding( $vector, count( $vector ) );
		}

		$usage         = isset( $data['usage'] ) && is_array( $data['usage'] ) ? $data['usage'] : array();
		$prompt_tokens = isset( $usage['prompt_tokens'] ) && is_numeric( $usage['prompt_tokens'] ) ? (int) $usage['prompt_tokens'] : 0;
		$total_tokens  = isset( $usage['total_tokens'] ) && is_numeric( $usage['total_tokens'] ) ? (int) $usage['total_tokens'] : $prompt_tokens;

		$additional_data = is_array( $data ) ? $data : array();
		unset( $additional_data['data'], $additional_data['embeddings'], $additional_data['usage'] );

		return new EmbeddingResult(
			isset( $data['id'] ) ? (string) $data['id'] : '',
			$embeddings,
			count( $embeddings[0]->getValues() ),
			new TokenUsage( $prompt_tokens, 0, $total_tokens ),
			$this->providerMetadata(),
			$this->metadata(),
			$additional_data
		);
	}
}

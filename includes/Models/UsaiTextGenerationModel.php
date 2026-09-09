<?php
/**
 * USAi text generation model.
 *
 * @package AlanSmodic\AiProviderForUsai
 * @since   1.0.0
 */

declare( strict_types=1 );

namespace AlanSmodic\AiProviderForUsai\Models;

use AlanSmodic\AiProviderForUsai\Support\Credentials;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleTextGenerationModel;

/**
 * Generates text using USAi's chat completions endpoint.
 *
 * USAi implements the OpenAI Chat Completion API, so the SDK's OpenAI-compatible base class
 * handles message mapping, sampling parameters, multi-turn conversations, tool calls and token
 * usage. Only request creation is provider specific.
 *
 * Authentication is a standard bearer token, so the SDK's own ApiKeyRequestAuthentication is used
 * unchanged and the header is applied by the base class.
 *
 * @since 1.0.0
 */
class UsaiTextGenerationModel extends AbstractOpenAiCompatibleTextGenerationModel {

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @param HttpMethodEnum           $method  HTTP method.
	 * @param string                   $path    Request path relative to the API root.
	 * @param array<string, string>    $headers Request headers.
	 * @param string|array<mixed>|null $data    Request body.
	 * @return Request The HTTP request.
	 */
	protected function createRequest( HttpMethodEnum $method, string $path, array $headers = array(), $data = null ): Request {
		return new Request(
			$method,
			Credentials::url( $path ),
			$headers,
			$data,
			$this->getRequestOptions()
		);
	}
}

<?php
/**
 * Mock HTTP transporter for tests.
 *
 * @package AlanSmodic\AiProviderForUsai
 */

declare( strict_types=1 );

namespace AlanSmodic\AiProviderForUsai\Tests;

use WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\RequestOptions;
use WordPress\AiClient\Providers\Http\DTO\Response;

/**
 * Records requests and returns queued responses.
 */
class MockHttpTransporter implements HttpTransporterInterface {

	/**
	 * Last request sent.
	 *
	 * @var Request|null
	 */
	private $last_request;

	/**
	 * Fallback response when the queue is empty.
	 *
	 * @var Response|null
	 */
	private $response_to_return;

	/**
	 * FIFO queue of responses.
	 *
	 * @var array<int, Response>
	 */
	private $responses_queue = array();

	/**
	 * {@inheritDoc}
	 */
	public function send( Request $request, ?RequestOptions $options = null ): Response {
		$this->last_request = $request;

		if ( ! empty( $this->responses_queue ) ) {
			return array_shift( $this->responses_queue );
		}

		return $this->response_to_return ? $this->response_to_return : new Response( 200, array(), '{}' );
	}

	/**
	 * Returns the last request sent.
	 *
	 * @return Request|null Last request.
	 */
	public function get_last_request() {
		return $this->last_request;
	}

	/**
	 * Sets the fallback response.
	 *
	 * @param Response $response Response to return.
	 */
	public function set_response_to_return( Response $response ): void {
		$this->response_to_return = $response;
	}

	/**
	 * Enqueues a response.
	 *
	 * @param Response $response Response to enqueue.
	 */
	public function queue_response( Response $response ): void {
		$this->responses_queue[] = $response;
	}
}

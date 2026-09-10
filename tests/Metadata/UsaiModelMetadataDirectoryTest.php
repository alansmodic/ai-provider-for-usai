<?php
/**
 * Tests for USAi model metadata discovery.
 *
 * @package AlanSmodic\AiProviderForUsai
 */

declare( strict_types=1 );

namespace AlanSmodic\AiProviderForUsai\Tests\Metadata;

use AlanSmodic\AiProviderForUsai\Metadata\UsaiModelMetadataDirectory;
use AlanSmodic\AiProviderForUsai\Provider\UsaiProvider;
use AlanSmodic\AiProviderForUsai\Tests\MockHttpTransporter;
use AlanSmodic\AiProviderForUsai\Tests\TestCase;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;

/**
 * @covers \AlanSmodic\AiProviderForUsai\Metadata\UsaiModelMetadataDirectory
 */
class UsaiModelMetadataDirectoryTest extends TestCase {

	/**
	 * Directory under test.
	 *
	 * @var UsaiModelMetadataDirectory
	 */
	private $directory;

	/**
	 * Mock transporter.
	 *
	 * @var MockHttpTransporter
	 */
	private $transporter;

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();
		putenv( 'USAI_BASE_URL=https://agency.example.gov' );
		putenv( 'USAI_API_KEY=test-key' );

		$this->transporter = new MockHttpTransporter();
		$this->directory   = new UsaiModelMetadataDirectory();
		$this->directory->setHttpTransporter( $this->transporter );
		$this->directory->setRequestAuthentication( new ApiKeyRequestAuthentication( 'test-key' ) );
		$this->directory->invalidateCaches();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		$this->directory->invalidateCaches();
		parent::tearDown();
	}

	public function test_list_models_requests_models_endpoint(): void {
		$this->transporter->set_response_to_return( $this->make_models_response( array( 'gpt-4o' ) ) );

		$models = $this->directory->listModelMetadata();

		$request = $this->transporter->get_last_request();
		$this->assertNotNull( $request );
		$this->assertTrue( $request->getMethod()->isGet() );
		$this->assertSame( 'https://agency.example.gov/api/v1/models', $request->getUri() );
		$this->assertCount( 1, $models );
		$this->assertSame( 'gpt-4o', $models[0]->getId() );
	}

	public function test_text_models_advertise_chat_and_omit_structured_output(): void {
		$this->transporter->set_response_to_return( $this->make_models_response( array( 'gpt-4o' ) ) );

		$models = $this->directory->listModelMetadata();
		$caps   = $models[0]->getSupportedCapabilities();
		$names  = array();
		foreach ( $caps as $capability ) {
			$names[] = $capability->value;
		}

		$this->assertContains( 'text_generation', $names );
		$this->assertContains( 'chat_history', $names );
		$this->assertNull( $this->find_option( $models[0]->getSupportedOptions(), 'isOutputSchema' ) );
		$this->assertNull( $this->find_option( $models[0]->getSupportedOptions(), 'isOutputMimeType' ) );
		$this->assertNull( $this->find_option( $models[0]->getSupportedOptions(), 'isFunctionDeclarations' ) );
	}

	public function test_embedding_model_ids_are_classified_when_contracts_exist(): void {
		if ( ! interface_exists( UsaiProvider::EMBEDDING_INTERFACE ) ) {
			$this->markTestSkipped( 'SDK does not ship embedding contracts.' );
		}

		$this->transporter->set_response_to_return(
			$this->make_models_response( array( 'embed-english-v3', 'cohere_english_v3', 'gpt-4o' ) )
		);

		$models = $this->directory->listModelMetadata();
		$by_id  = array();
		foreach ( $models as $model ) {
			$by_id[ $model->getId() ] = $model;
		}

		$this->assertArrayHasKey( 'embed-english-v3', $by_id );
		$this->assertArrayHasKey( 'cohere_english_v3', $by_id );
		$this->assertTrue( $by_id['embed-english-v3']->getSupportedCapabilities()[0]->isEmbeddingGeneration() );
		$this->assertTrue( $by_id['gpt-4o']->getSupportedCapabilities()[0]->isTextGeneration() );
	}

	public function test_string_model_entries_are_accepted(): void {
		$this->transporter->set_response_to_return(
			new Response( 200, array(), '{"data":["gpt-4o",""]}' )
		);

		$models = $this->directory->listModelMetadata();

		$this->assertCount( 1, $models );
		$this->assertSame( 'gpt-4o', $models[0]->getId() );
	}

	public function test_malformed_response_throws(): void {
		$this->transporter->set_response_to_return( new Response( 200, array(), '{"oops":true}' ) );

		$this->expectException( ResponseException::class );
		$this->directory->listModelMetadata();
	}

	public function test_supported_options_filter_keeps_only_supported_option_instances(): void {
		add_filter(
			'ai_provider_for_usai_supported_options',
			static function ( $options ) {
				$options[] = new SupportedOption( OptionEnum::functionDeclarations() );
				$options[] = 'not-an-option';
				return $options;
			}
		);

		$this->transporter->set_response_to_return( $this->make_models_response( array( 'gpt-4o' ) ) );

		$models = $this->directory->listModelMetadata();

		$this->assertNotNull( $this->find_option( $models[0]->getSupportedOptions(), 'isFunctionDeclarations' ) );
	}

	/**
	 * Builds a GET /models response body.
	 *
	 * @param array<int, string> $model_ids Model IDs.
	 * @return Response Response.
	 */
	private function make_models_response( array $model_ids ): Response {
		$entries = array();
		foreach ( $model_ids as $model_id ) {
			$entries[] = array( 'id' => $model_id );
		}

		return new Response(
			200,
			array(),
			(string) json_encode( array( 'data' => $entries ) )
		);
	}

	/**
	 * Finds a supported option by its is* method.
	 *
	 * @param array<int, SupportedOption> $options   Options.
	 * @param string                      $is_method Method name.
	 * @return SupportedOption|null Option.
	 */
	private function find_option( array $options, string $is_method ) {
		foreach ( $options as $option ) {
			if ( $option->getName()->$is_method() ) {
				return $option;
			}
		}

		return null;
	}
}

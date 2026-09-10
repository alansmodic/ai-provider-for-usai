<?php
/**
 * Tests for USAi embedding generation.
 *
 * @package AlanSmodic\AiProviderForUsai
 */

declare( strict_types=1 );

namespace AlanSmodic\AiProviderForUsai\Tests\Models;

use AlanSmodic\AiProviderForUsai\Models\UsaiEmbeddingGenerationModel;
use AlanSmodic\AiProviderForUsai\Provider\UsaiProvider;
use AlanSmodic\AiProviderForUsai\Tests\MockHttpTransporter;
use AlanSmodic\AiProviderForUsai\Tests\TestCase;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

/**
 * @covers \AlanSmodic\AiProviderForUsai\Models\UsaiEmbeddingGenerationModel
 */
class UsaiEmbeddingGenerationModelTest extends TestCase {

	/**
	 * Model under test.
	 *
	 * @var UsaiEmbeddingGenerationModel
	 */
	private $model;

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

		if ( ! interface_exists( UsaiProvider::EMBEDDING_INTERFACE ) ) {
			$this->markTestSkipped( 'SDK does not ship embedding contracts.' );
		}

		putenv( 'USAI_BASE_URL=https://agency.example.gov' );
		putenv( 'USAI_API_KEY=test-key' );

		$model_metadata = $this->make_model_metadata(
			'cohere_english_v3',
			array( CapabilityEnum::embeddingGeneration() )
		);

		$this->model       = new UsaiEmbeddingGenerationModel( $model_metadata, $this->make_provider_metadata() );
		$this->transporter = new MockHttpTransporter();
		$this->model->setHttpTransporter( $this->transporter );
		$this->model->setRequestAuthentication( new ApiKeyRequestAuthentication( 'test-key' ) );
	}

	public function test_generate_embedding_posts_cohere_defaults_and_parses_openai_shape(): void {
		$this->transporter->set_response_to_return(
			new Response(
				200,
				array(),
				(string) json_encode(
					array(
						'id'    => 'emb-1',
						'data'  => array(
							array( 'embedding' => array( 0.1, 0.2, 0.3 ) ),
						),
						'usage' => array(
							'prompt_tokens' => 8,
							'total_tokens'  => 8,
						),
					)
				)
			)
		);

		$result  = $this->model->generateEmbeddingResult( array( new MessagePart( 'memo text' ) ) );
		$request = $this->transporter->get_last_request();

		$this->assertNotNull( $request );
		$this->assertTrue( $request->getMethod()->isPost() );
		$this->assertSame( 'https://agency.example.gov/api/v1/embeddings', $request->getUri() );
		$this->assertSame( 'application/json', $request->getHeaderAsString( 'Content-Type' ) );
		$this->assertSame( 'Bearer test-key', $request->getHeaderAsString( 'Authorization' ) );
		$this->assertSame(
			array(
				'model'          => 'cohere_english_v3',
				'input'          => array( 'memo text' ),
				'input_type'     => 'search_document',
				'encodingFormat' => 'float',
			),
			$request->getData()
		);

		$embeddings = $result->getEmbeddings();
		$this->assertCount( 1, $embeddings );
		$this->assertSame( array( 0.1, 0.2, 0.3 ), $embeddings[0]->getValues() );
		$this->assertSame( 3, $result->getDimensions() );
		$this->assertSame( 8, $result->getTokenUsage()->getPromptTokens() );
		$this->assertSame( 'emb-1', $result->getId() );
	}

	public function test_custom_options_override_defaults(): void {
		$config = ModelConfig::fromArray( array() );
		$config->setCustomOptions(
			array(
				'input_type'     => 'search_query',
				'encodingFormat' => 'base64',
				'truncate'       => 'NONE',
			)
		);
		$this->model->setConfig( $config );

		$this->transporter->set_response_to_return(
			new Response( 200, array(), '{"data":[{"embedding":[1,2]}]}' )
		);

		$this->model->generateEmbeddingResult( array( new MessagePart( 'query' ) ) );

		$data = $this->transporter->get_last_request()->getData();
		$this->assertSame( 'search_query', $data['input_type'] );
		$this->assertSame( 'base64', $data['encodingFormat'] );
		$this->assertSame( 'NONE', $data['truncate'] );
	}

	public function test_parses_flat_embeddings_fallback(): void {
		$this->transporter->set_response_to_return(
			new Response( 200, array(), '{"embeddings":[[1,2],[3,4]]}' )
		);

		$result = $this->model->generateEmbeddingResult(
			array(
				new MessagePart( 'a' ),
				new MessagePart( 'b' ),
			)
		);

		$embeddings = $result->getEmbeddings();
		$this->assertCount( 2, $embeddings );
		$this->assertSame( array( 1, 2 ), $embeddings[0]->getValues() );
		$this->assertSame( array( 3, 4 ), $embeddings[1]->getValues() );
	}

	public function test_rejects_empty_input(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->model->generateEmbeddingResult( array() );
	}

	public function test_rejects_non_list_input(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->model->generateEmbeddingResult( array( 'text' => new MessagePart( 'nope' ) ) );
	}

	public function test_rejects_empty_text_part(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->model->generateEmbeddingResult( array( new MessagePart( '' ) ) );
	}

	public function test_empty_vectors_throw_response_exception(): void {
		$this->transporter->set_response_to_return( new Response( 200, array(), '{"data":[]}' ) );

		$this->expectException( ResponseException::class );
		$this->model->generateEmbeddingResult( array( new MessagePart( 'memo' ) ) );
	}
}

<?php
/**
 * Tests for the USAi provider.
 *
 * @package AlanSmodic\AiProviderForUsai
 */

declare( strict_types=1 );

namespace AlanSmodic\AiProviderForUsai\Tests\Provider;

use AlanSmodic\AiProviderForUsai\Models\UsaiEmbeddingGenerationModel;
use AlanSmodic\AiProviderForUsai\Models\UsaiTextGenerationModel;
use AlanSmodic\AiProviderForUsai\Provider\UsaiProvider;
use AlanSmodic\AiProviderForUsai\Provider\UsaiProviderAvailability;
use AlanSmodic\AiProviderForUsai\Tests\TestCase;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

/**
 * @covers \AlanSmodic\AiProviderForUsai\Provider\UsaiProvider
 */
class UsaiProviderTest extends TestCase {

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->clear_provider_caches();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		$this->clear_provider_caches();
		parent::tearDown();
	}

	public function test_metadata_uses_usai_provider_id(): void {
		$metadata = UsaiProvider::metadata();

		$this->assertSame( 'usai', $metadata->getId() );
		$this->assertSame( 'USAi', $metadata->getName() );
	}

	public function test_availability_is_usai_availability(): void {
		$this->assertInstanceOf( UsaiProviderAvailability::class, UsaiProvider::availability() );
	}

	public function test_create_model_returns_text_generation_model(): void {
		$model = $this->invoke_create_model(
			$this->make_model_metadata(
				'gpt-4o',
				array( CapabilityEnum::textGeneration(), CapabilityEnum::chatHistory() )
			)
		);

		$this->assertInstanceOf( UsaiTextGenerationModel::class, $model );
	}

	public function test_create_model_returns_embedding_model_when_supported(): void {
		if ( ! interface_exists( UsaiProvider::EMBEDDING_INTERFACE ) ) {
			$this->markTestSkipped( 'SDK does not ship embedding contracts.' );
		}

		$model = $this->invoke_create_model(
			$this->make_model_metadata(
				'cohere_english_v3',
				array( CapabilityEnum::embeddingGeneration() )
			)
		);

		$this->assertInstanceOf( UsaiEmbeddingGenerationModel::class, $model );
	}

	public function test_create_model_throws_for_unsupported_capabilities(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Unsupported USAi model capabilities for model: mystery-model' );

		$this->invoke_create_model(
			$this->make_model_metadata(
				'mystery-model',
				array( CapabilityEnum::imageGeneration() )
			)
		);
	}

	/**
	 * Invokes the protected createModel() factory.
	 *
	 * @param \WordPress\AiClient\Providers\Models\DTO\ModelMetadata $model_metadata Model metadata.
	 * @return \WordPress\AiClient\Providers\Models\Contracts\ModelInterface Model.
	 */
	private function invoke_create_model( $model_metadata ) {
		$method = new \ReflectionMethod( UsaiProvider::class, 'createModel' );
		$method->setAccessible( true );

		return $method->invoke( null, $model_metadata, $this->make_provider_metadata() );
	}
}

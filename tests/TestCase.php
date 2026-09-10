<?php
/**
 * Shared PHPUnit helpers.
 *
 * @package AlanSmodic\AiProviderForUsai
 */

declare( strict_types=1 );

namespace AlanSmodic\AiProviderForUsai\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use WordPress\AiClient\Providers\AbstractProvider;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

/**
 * Base test case that resets USAi environment state.
 */
abstract class TestCase extends BaseTestCase {

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->clear_usai_env();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		$this->clear_usai_env();
		parent::tearDown();
	}

	/**
	 * Clears USAi environment variables and test filters.
	 */
	protected function clear_usai_env(): void {
		putenv( 'USAI_API_KEY' );
		putenv( 'USAI_BASE_URL' );
		$GLOBALS['ai_provider_for_usai_test_filters'] = array();
	}

	/**
	 * Clears static caches on the SDK provider base class.
	 */
	protected function clear_provider_caches(): void {
		$reflection = new \ReflectionClass( AbstractProvider::class );
		foreach ( array( 'metadataCache', 'availabilityCache', 'modelMetadataDirectoryCache' ) as $property_name ) {
			$property = $reflection->getProperty( $property_name );
			$property->setAccessible( true );
			$property->setValue( null, array() );
		}
	}

	/**
	 * Builds provider metadata for tests.
	 *
	 * @return ProviderMetadata Metadata.
	 */
	protected function make_provider_metadata(): ProviderMetadata {
		return new ProviderMetadata( 'usai', 'USAi', ProviderTypeEnum::cloud() );
	}

	/**
	 * Builds model metadata with the given capabilities.
	 *
	 * @param string                  $model_id     Model ID.
	 * @param array<CapabilityEnum> $capabilities Capabilities.
	 * @return ModelMetadata Metadata.
	 */
	protected function make_model_metadata( string $model_id, array $capabilities ): ModelMetadata {
		return new ModelMetadata( $model_id, $model_id, $capabilities, array() );
	}
}

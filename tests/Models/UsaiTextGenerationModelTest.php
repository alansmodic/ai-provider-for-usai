<?php
/**
 * Tests for USAi text generation request creation.
 *
 * @package AlanSmodic\AiProviderForUsai
 */

declare( strict_types=1 );

namespace AlanSmodic\AiProviderForUsai\Tests\Models;

use AlanSmodic\AiProviderForUsai\Models\UsaiTextGenerationModel;
use AlanSmodic\AiProviderForUsai\Tests\TestCase;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

/**
 * @covers \AlanSmodic\AiProviderForUsai\Models\UsaiTextGenerationModel
 */
class UsaiTextGenerationModelTest extends TestCase {

	public function test_create_request_uses_agency_chat_completions_url(): void {
		putenv( 'USAI_BASE_URL=https://agency.example.gov/' );

		$model = new UsaiTextGenerationModel(
			$this->make_model_metadata(
				'gpt-4o',
				array( CapabilityEnum::textGeneration() )
			),
			$this->make_provider_metadata()
		);

		$method = new \ReflectionMethod( UsaiTextGenerationModel::class, 'createRequest' );
		$method->setAccessible( true );
		$request = $method->invoke(
			$model,
			HttpMethodEnum::POST(),
			'chat/completions',
			array( 'Content-Type' => 'application/json' ),
			array( 'model' => 'gpt-4o' )
		);

		$this->assertTrue( $request->getMethod()->isPost() );
		$this->assertSame( 'https://agency.example.gov/api/v1/chat/completions', $request->getUri() );
		$this->assertSame( array( 'model' => 'gpt-4o' ), $request->getData() );
	}
}

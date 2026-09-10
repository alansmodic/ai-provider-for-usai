<?php
/**
 * Tests for provider availability.
 *
 * @package AlanSmodic\AiProviderForUsai
 */

declare( strict_types=1 );

namespace AlanSmodic\AiProviderForUsai\Tests\Provider;

use AlanSmodic\AiProviderForUsai\Provider\UsaiProviderAvailability;
use AlanSmodic\AiProviderForUsai\Tests\TestCase;

/**
 * @covers \AlanSmodic\AiProviderForUsai\Provider\UsaiProviderAvailability
 */
class UsaiProviderAvailabilityTest extends TestCase {

	public function test_is_not_configured_without_key_or_url(): void {
		$availability = new UsaiProviderAvailability();

		$this->assertFalse( $availability->isConfigured() );
	}

	public function test_is_not_configured_with_only_a_key(): void {
		putenv( 'USAI_API_KEY=secret' );

		$availability = new UsaiProviderAvailability();

		$this->assertFalse( $availability->isConfigured() );
	}

	public function test_is_not_configured_with_only_a_url(): void {
		putenv( 'USAI_BASE_URL=https://agency.example.gov' );

		$availability = new UsaiProviderAvailability();

		$this->assertFalse( $availability->isConfigured() );
	}

	public function test_is_not_configured_when_url_is_http(): void {
		putenv( 'USAI_API_KEY=secret' );
		putenv( 'USAI_BASE_URL=http://agency.example.gov' );

		$availability = new UsaiProviderAvailability();

		$this->assertFalse( $availability->isConfigured() );
	}

	public function test_is_configured_with_key_and_https_url(): void {
		putenv( 'USAI_API_KEY=secret' );
		putenv( 'USAI_BASE_URL=https://agency.example.gov' );

		$availability = new UsaiProviderAvailability();

		$this->assertTrue( $availability->isConfigured() );
	}
}

<?php
/**
 * Tests for credential and endpoint resolution.
 *
 * @package AlanSmodic\AiProviderForUsai
 */

declare( strict_types=1 );

namespace AlanSmodic\AiProviderForUsai\Tests\Support;

use AlanSmodic\AiProviderForUsai\Support\Credentials;
use AlanSmodic\AiProviderForUsai\Tests\TestCase;

/**
 * @covers \AlanSmodic\AiProviderForUsai\Support\Credentials
 */
class CredentialsTest extends TestCase {

	public function test_url_appends_api_path_and_strips_trailing_slash(): void {
		putenv( 'USAI_BASE_URL=https://agency.example.gov/' );

		$this->assertSame(
			'https://agency.example.gov/api/v1/chat/completions',
			Credentials::url( 'chat/completions' )
		);
	}

	public function test_url_strips_leading_slash_from_path(): void {
		putenv( 'USAI_BASE_URL=https://agency.example.gov' );

		$this->assertSame(
			'https://agency.example.gov/api/v1/embeddings',
			Credentials::url( '/embeddings' )
		);
	}

	public function test_url_is_empty_when_base_url_is_missing(): void {
		$this->assertSame( '', Credentials::url( 'models' ) );
	}

	public function test_http_urls_are_rejected(): void {
		putenv( 'USAI_BASE_URL=http://agency.example.gov' );

		$this->assertSame( '', Credentials::base_url() );
	}

	public function test_urls_with_embedded_credentials_are_rejected(): void {
		putenv( 'USAI_BASE_URL=https://user:pass@agency.example.gov' );

		$this->assertSame( '', Credentials::base_url() );
	}

	public function test_non_https_schemes_are_rejected(): void {
		putenv( 'USAI_BASE_URL=javascript:alert(1)' );

		$this->assertSame( '', Credentials::base_url() );
	}

	public function test_api_key_comes_from_environment(): void {
		putenv( 'USAI_API_KEY=from-env' );

		$this->assertSame( 'from-env', Credentials::api_key() );
	}

	public function test_api_key_is_empty_when_unset(): void {
		$this->assertSame( '', Credentials::api_key() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_api_key_falls_back_to_constant(): void {
		putenv( 'USAI_API_KEY' );
		define( 'USAI_API_KEY', 'from-constant' );

		$this->assertSame( 'from-constant', Credentials::api_key() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_env_wins_over_constant_for_api_key(): void {
		putenv( 'USAI_API_KEY=from-env' );
		define( 'USAI_API_KEY', 'from-constant' );

		$this->assertSame( 'from-env', Credentials::api_key() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_base_url_falls_back_to_constant(): void {
		putenv( 'USAI_BASE_URL' );
		define( 'USAI_BASE_URL', 'https://from-constant.example.gov/' );

		$this->assertSame( 'https://from-constant.example.gov', Credentials::base_url() );
	}
}

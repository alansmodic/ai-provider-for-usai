# Changelog

All notable changes to this project are documented in this file.

## [Unreleased]

### Added
- PHPUnit suite covering credential resolution, availability, model discovery, and
  embeddings against a mock HTTP transporter.

## [1.0.1]

### Changed
- PHPCS ruleset now matches WordPress VIP Go plus WordPress Core, Extra, and Docs, with
  PHPCS 4-compatible `text_domain` syntax.
- API key and base URL resolution share the same precedence: environment variable, constant,
  then option. On WordPress VIP, `vip_get_env_var()` is preferred over `getenv()`.
- Configured base URLs are sanitized to HTTPS with no embedded credentials.

### Fixed
- Show an admin notice when the WordPress AI Client is not available, instead of failing silently.
- Escape the plugin list Settings link URL.
- Replace `array_is_list()` with a PHP 7.4-compatible helper so embeddings work as a Composer
  package without WordPress polyfills.
- Validate `ai_provider_for_usai_supported_options` filter results as `SupportedOption` instances.
- Autoloader paths are checked with `validate_file()` before include.

### Added
- `uninstall.php` deletes the plugin's base URL option.

## [1.0.0]

### Added
- USAi provider registration for the WordPress AI Client.
- Text generation via USAi's OpenAI-compatible `/api/v1/chat/completions` endpoint, including
  multi-turn conversations, sampling parameters and token usage.
- Live model discovery via `/api/v1/models`.
- Embedding generation via `/api/v1/embeddings`, activated automatically once the AI Client ships
  its embedding contracts.
- `ai_provider_for_usai_supported_options` filter for enabling options once confirmed against an
  agency tenant.

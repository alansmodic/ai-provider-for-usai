# Changelog

All notable changes to this project are documented in this file.

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

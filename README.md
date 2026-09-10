# AI Provider for USAi

> [!WARNING]
> **This is a prototype.** It is not production software and is not affiliated with, endorsed by, or
> approved by GSA or USAi. It has **never been run against a live USAi endpoint** — USAi issues
> agency-specific base URLs only to authorized government users, so there is no public endpoint to
> test against. All request paths were exercised against mocked HTTP responses. Review and validate
> against your agency's tenant before deploying.

USAi provider for the [WordPress AI Client](https://make.wordpress.org/core/2026/03/24/introducing-the-ai-client-in-wordpress-7-0/). Lets federal agencies route WordPress AI requests through [USAi](https://www.usai.gov/), GSA's FedRAMP-authorized generative AI platform.

Structured to match the official provider plugins and [Fueled's Ollama provider](https://github.com/Fueled/ai-provider-for-ollama).

## Install

```bash
composer require alansmodic/ai-provider-for-usai
```

Or drop the directory into `wp-content/plugins/` and activate — a fallback PSR-4 autoloader is included, so `composer install` is optional.

USAi requires **two** settings, because each agency receives its own endpoint:

```php
// wp-config.php
define( 'USAI_BASE_URL', 'https://<your-agency-endpoint>' ); // shown after you sign in to USAi
define( 'USAI_API_KEY', getenv( 'USAI_API_KEY' ) );          // or use Settings > Connectors
```

There is deliberately **no default base URL**. Without one the provider reports itself unconfigured
rather than guessing at an endpoint. Only `https://` URLs are accepted.

On WordPress VIP, set `USAI_API_KEY` and `USAI_BASE_URL` with `vip config envvar`; the plugin
reads them through `vip_get_env_var()` when that helper exists.

## Tests

```bash
composer install
composer test
```

PHPUnit talks to a mock HTTP transporter. Nothing is sent to a live USAi endpoint.

## Usage

No USAi-specific code is required:

```php
use WordPress\AiClient\AiClient;

$text = AiClient::prompt( 'Summarize this policy memo.' )->generateText();
```

## Architecture

| Class | Extends | Role |
|---|---|---|
| `Provider\UsaiProvider` | `AbstractApiProvider` | Provider registration |
| `Models\UsaiTextGenerationModel` | `AbstractOpenAiCompatibleTextGenerationModel` | `/chat/completions` |
| `Models\UsaiEmbeddingGenerationModel` | `AbstractApiBasedModel` | `/embeddings` (see below) |
| `Metadata\UsaiModelMetadataDirectory` | `AbstractOpenAiCompatibleModelMetadataDirectory` | Live discovery via `/models` |

Because USAi implements the OpenAI Chat Completion API and authenticates with a standard bearer
token, the SDK does nearly all the work: the text model supplies only `createRequest()`, and the
SDK's own `ApiKeyRequestAuthentication` is used unchanged.

## Embeddings

USAi exposes `/api/v1/embeddings`, and this plugin implements it — but note:

**The AI Client ships the `embedding_generation` capability enum ahead of the embedding contracts
themselves.** As of AI Client 1.3.1 there is no `EmbeddingGenerationModelInterface`, no
`EmbeddingResult` DTO, and no facade method. The embedding model is therefore only referenced behind
an `interface_exists()` guard, and PSR-4 autoloading means its file is never loaded until that guard
passes. It activates automatically once Core ships the contracts; until then it is inert.

Models are classified as embedding models by ID (`embed`, `cohere_english`), since USAi's `/models`
response does not describe capabilities.

## Option support

Advertised: `inputModalities`, `outputModalities`, `systemInstruction`, `temperature`, `maxTokens`,
`topP`, `frequencyPenalty`, `presencePenalty`, `stopSequences`, `customOptions`.

**Not advertised:** `outputSchema` and `outputMimeType`, because USAi documents that it lacks
structured output — advertising them would let the SDK send a `response_format` parameter USAi does
not support. Audio is likewise unsupported.

`functionDeclarations` is **off by default**: USAi's docs neither confirm nor deny tool calling.
Enable it once verified against your tenant:

```php
add_filter( 'ai_provider_for_usai_supported_options', function ( $options ) {
    $options[] = new \WordPress\AiClient\Providers\Models\DTO\SupportedOption(
        \WordPress\AiClient\Providers\Models\Enums\OptionEnum::functionDeclarations()
    );
    return $options;
} );
```

## Rate limits

USAi documents 3 calls/second for models and chat completions, and 100 calls/second for embeddings,
per API key. Exceeding them returns HTTP 429. The 3/sec chat limit is low enough to matter for bulk
editorial workloads; this plugin does not throttle on your behalf.

## Notes

- The bundled logo is a **neutral placeholder**, not a US government seal or the USAi mark. Federal
  seals and agency logos carry usage restrictions, so no official artwork is included.
- USAi is currently free to agencies; GSA has said it will move to a cost-recovery model in FY2027.

## License

GPL-2.0-or-later

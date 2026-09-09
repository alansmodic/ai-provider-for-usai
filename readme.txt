=== AI Provider for USAi ===
Contributors: alansmodic
Tags: ai, usai, gsa, fedramp, govtech
Requires at least: 7.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

USAi provider for the WordPress AI Client. GSA's FedRAMP-authorized generative AI platform.

== Description ==

**PROTOTYPE — not production software.** Not affiliated with or endorsed by GSA or USAi. It has
never been run against a live USAi endpoint, because access requires government authorization.
Validate against your agency tenant before deploying.

Registers USAi as a provider for the WordPress AI Client, so any AI-aware plugin, block or Ability
can use an agency's USAi account with no USAi-specific code.

USAi implements the OpenAI Chat Completion API, so text generation supports multi-turn
conversations, sampling parameters and token usage. Models are discovered live from the agency's
own endpoint.

= Configuration =

USAi requires two settings, because each agency receives its own endpoint:

1. `USAI_BASE_URL` — your agency endpoint, shown after signing in to USAi (constant or environment variable)
2. `USAI_API_KEY` — environment variable, PHP constant, or Settings > Connectors

== Frequently Asked Questions ==

= Does this support embeddings? =

Yes, but it stays inactive until WordPress ships the AI Client's embedding contracts. The capability
enum exists as of AI Client 1.3.1, while the model interface and result objects do not.

= Why is function calling not available? =

USAi's documentation does not confirm whether tool calling is supported. It is therefore not
advertised by default, so automatic model selection will not route tool-using requests to USAi. Use
the `ai_provider_for_usai_supported_options` filter to enable it once confirmed.

== Changelog ==

= 1.0.0 =
* Initial release.

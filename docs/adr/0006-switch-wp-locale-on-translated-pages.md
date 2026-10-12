---
status: accepted
---

# Switch the WordPress locale on translated pages after the query is resolved

All visitor-facing strings (theme, plugins, dates) must be in the page's language, not just `<html lang>`. Some plugins on this site (Complianz, SimpleShop) load their text domains before regular plugins run, so a `locale` filter alone would miss them.

Decision: once the main query has resolved the page (on the `wp` action), if it sits under a language root, call `switch_to_locale()` with that language's locale. WordPress then reloads all text domains. Default-language pages pay nothing.

## Considered Options

- **Must-use plugin matching the URL prefix**: earlier and cheaper, but tied to slugs and requires writing a file into `mu-plugins` on activation. Rejected.

## Consequences

- Strings rendered before the `wp` action stay in the default language (rare on the front end).
- Text you enter yourself (CF7 messages, Complianz banner, MailerLite form, block-theme header/footer) is content, not translations, and is not affected. English versions need separate forms or template parts.
- AJAX/REST requests (e.g. form submissions) have no page context and run in the default locale.

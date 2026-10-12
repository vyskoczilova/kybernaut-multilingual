# Languages are defined in a settings page, not hardcoded

**Settings → Multilingual** holds the **default language** (hreflang code `cs`, switcher label `CZ`; its locale is always the site language from Settings → General) and a repeatable list of **extra languages** (code, label, locale; initially `en` / `EN` / `en_US`). A language root picks its language from the extra-language list. Adding German later means one settings row plus a `/de/` root page, with no code changes.

hreflang codes are validated (`xx`, `xx-YY` or `xx-Scrp`). The locale is picked from core's language dropdown, limited to installed languages, so an invalid or missing locale can't be chosen; the server falls back to `en_US` if it receives one anyway. The default switcher label comes from the site locale's country (`cs_CZ` → `CZ`). Saving settings invalidates the translation cache (ADR 0004).

# Head output: hreflang, x-default, html lang, og:locale

Whenever at least two language versions of a page are publicly viewable, the plugin prints `<link rel="alternate" hreflang>` for each of them (including a self-reference). It adds `x-default` pointing to the default-language version when that version is public. Pages without a public translation get no hreflang tags.

`<html lang>` and Yoast's `og:locale` follow the page's language. With locale switching (ADR 0006) they follow automatically. Yoast's sitemap is left untouched, since hreflang carries the language relation.

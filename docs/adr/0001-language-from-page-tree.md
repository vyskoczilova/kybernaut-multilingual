# Page language comes from its language root in the page tree

The site (cs_CZ, ollie-child block theme) needs only a handful of translated pages, so a full translation plugin (WPML, Polylang) is overkill. Instead, translations are nested pages: a top-level page such as `/en/` is flagged as a **language root**, and every page beneath it inherits that language. Pages outside any root are in the default language. Only pages are supported; posts are out of scope.

The language of every page inside a root is precomputed when the cached map is built (ADR 0004) by walking the page tree in memory; look-ups are a single array read, no queries.

## Considered Options

- **Per-page language dropdown**: rejected; needs setup on every page and can drift from the URL structure.
- **Translation plugin**: rejected; too heavy for a few pages.

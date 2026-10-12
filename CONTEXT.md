# Kybernaut Multilingual

Lightweight translation of a few WordPress pages by nesting them under a per-language parent page.

## Language

**Default language**:
The site's main language (Czech). Pages outside any language root belong to it.

**Extra language**:
An additional language defined in settings by hreflang code, switcher label and WP locale.

**Language root**:
A top-level page (e.g. `/en/`) flagged with an extra language. It and all its descendants are in that language.
_Avoid_: language home, language folder

**Translated page**:
A page under a language root.

**Counterpart**:
The default-language page (or the Site homepage) that a translated page is a translation of.
_Avoid_: original, source page, parent

**Site homepage**:
The front page of the site (currently the blog index). A valid counterpart even though it is not a page.

**Language switcher**:
The visitor-facing links between a page and its counterpart or translations.

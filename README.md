# Kybernaut Multilingual

Lightweight translations of a few WordPress pages, without a translation plugin.

Translated pages live under a **language root** page such as `/en/`. Every page under it is in that language. You link each translated page to its counterpart in the default language, and the plugin takes care of the rest:

- `hreflang` alternates and `x-default` in `<head>`
- switches the WordPress locale on translated pages, so theme and plugin strings, dates, `<html lang>`, Yoast `og:locale` and schema `inLanguage` follow the page language
- a **Language switcher** block and a `[kybernaut_ml_switcher]` shortcode
- a **Language** column in the Pages list

Only pages are supported, not posts.

## Setup

1. **Settings → Multilingual**: check the default language (hreflang code and switcher label) and the other languages (hreflang code, label, WordPress locale). The locale must be installed. `en_US` always is.
2. Create a top-level page, e.g. **English** with slug `en`. In its **Language** box choose *Language root: EN*. Set *Translation of: Site homepage* so that `/en/` ↔ `/`.
3. Create translated pages **under** that page. In the **Language** box choose *Translation of: <the Czech page>*.
   New pages: set the parent, save and reload the editor, and the field switches to the right mode.
4. Add the **Language switcher** block to the header: either inside the **Navigation** block, where it renders as ordinary menu items, or on its own next to it. You can also use the shortcode.

Each counterpart can have one translation per language, and each language has one root.

## Behaviour

- Drafts, private and password-protected pages are treated as missing: no hreflang and no switcher link.
- When a page has no translation, the switcher hides that language by default. It can link to the language home instead (setting).
- Block setting *Show current language* (or the shortcode's `show_current="no"`): turn it off to show only the links to other languages, e.g. just "EN" in a Czech menu.
- Inside a Navigation block, the switcher outputs core menu items (`li.wp-block-navigation-item.kbnt-ml-switcher__item > a.wp-block-navigation-item__content`). They inherit the menu's styling and the mobile overlay. When there's nothing to switch to, no item is output.
- Standalone, the switcher markup is `nav.kbnt-ml-switcher > ul > li.kbnt-ml-switcher__item(.is-current)`. The block wraps it in a `div` that carries the block's alignment, spacing and font size. A few lines of base CSS remove the list bullets and lay the items out in a row. Style the rest in your theme.

### Known limits

- Text you enter yourself (Contact Form 7 messages, Complianz banner, MailerLite forms, header/footer template parts) is content, not translations. Make separate English versions and place them on the English pages.
- AJAX and REST requests (e.g. form submissions) have no page context and run in the site language.

## Performance and safety

- All roots and links are kept in one small autoloaded transient (or the object cache), rebuilt only when a page, the plugin's post meta or the relevant options change. Front-end requests run no extra queries. Rendered switcher markup is cached as well.
- The locale is switched only on translated pages; default-language pages pay nothing.
- Capability and nonce checks on save, server-side validation of every link, and escaped output.
- `uninstall.php` removes all options, transients and post meta. Deactivation keeps them.

## Updates and releases

Updates come from GitHub Releases of the public repo `vyskoczilova/kybernaut-multilingual` through the bundled [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) v5.7 (`vendor/`, committed on purpose).

To release:

1. Bump the version in the plugin header **and** in `const VERSION` in `kybernaut-multilingual.php`.
2. Commit, then `git tag v1.0.1 && git push --tags`.
3. The *Release* GitHub Action checks the version, lints, builds `kybernaut-multilingual.zip` and publishes the release with the zip attached. Sites see the update within about 12 hours, or right away with *Check for updates* on the Plugins screen.

`bin/build-zip.sh` builds the same zip locally into `dist/`.

## Translations

The plugin is in English, with Czech in `languages/` (`.po` source plus the `.l10n.php` file that WordPress 6.5+ loads). After changing the `.po`, regenerate with `wp i18n make-php languages`.

## Docs

Design decisions are in `docs/adr/`, and the vocabulary is in `CONTEXT.md`.

## Author

Made by [Karolína Vyskočilová](https://kybernaut.cz) · [kybernaut.cz](https://kybernaut.cz)

## License

[GPL-3.0-or-later](LICENSE). The bundled Plugin Update Checker in `vendor/` is MIT-licensed.

=== Kybernaut Multilingual ===
Contributors: vyskoczilova
Tags: multilingual, hreflang, translation, language switcher, locale
Requires at least: 6.5
Tested up to: 7.1.3
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Translate a few pages without a translation plugin: nest them under a language root like /en/ and get hreflang, locale switching and a switcher.

== Description ==

Kybernaut Multilingual is for sites with a few translated pages, where a full translation plugin would be too much.

Translated pages live under a **language root** page such as `/en/`. Every page under it is in that language. You link each translated page to its counterpart in the default language, and the plugin does the rest:

* `hreflang` alternates and `x-default` in `<head>`
* switches the WordPress locale on translated pages, so theme and plugin strings, dates, `<html lang>`, Yoast SEO `og:locale` and schema `inLanguage` follow the page language
* a **Language switcher** block, usable on its own or inside the Navigation block as ordinary menu items
* a `[kybernaut_ml_switcher]` shortcode
* a **Language** column in the Pages list

Only pages are supported, not posts.

= Performance and safety =

* All roots and links are kept in one small cached map, rebuilt only when a page, the plugin's post meta or a relevant option changes. Front-end requests run no extra database queries.
* Rendered switcher markup is cached as well.
* The locale is switched only on translated pages; default-language pages pay nothing.
* Capability and nonce checks on save, server-side validation of every link, and escaped output.

= Behaviour =

* Drafts, private and password-protected pages are treated as missing: no hreflang and no switcher link.
* When a page has no translation, the switcher hides that language by default. In the settings you can make it link to the language's home page instead.
* Turn off the block setting *Show current language* (or use `show_current="no"` in the shortcode) to show only the links to other languages, e.g. just "EN" in a Czech menu.

= Known limits =

* Text you enter yourself (form messages, cookie banners, header and footer template parts) is content, not a translation. Create separate versions and place them on the translated pages.
* AJAX and REST requests (e.g. form submissions) have no page context and run in the site language.

= Development =

Source code, issues and releases: [github.com/vyskoczilova/kybernaut-multilingual](https://github.com/vyskoczilova/kybernaut-multilingual)

Made by [Karolína Vyskočilová](https://kybernaut.cz).

== Installation ==

1. Download `kybernaut-multilingual.zip` from the [latest GitHub release](https://github.com/vyskoczilova/kybernaut-multilingual/releases/latest) and upload it in **Plugins → Add New → Upload Plugin**. Activate it.
2. In **Settings → Multilingual**, check the default language (hreflang code and switcher label) and add the other languages (hreflang code, label, WordPress locale). The locale must be installed; `en_US` always is.
3. Create a top-level page, e.g. **English** with the slug `en`. In its **Language** box choose *Language root: EN* and set *Translation of: Site homepage*, so that `/en/` and `/` are linked.
4. Create translated pages **under** that page. In their **Language** box choose *Translation of* and pick the default-language page. For a new page, set the parent, save and reload the editor first.
5. Add the **Language switcher** block to your header, inside the Navigation block or next to it, or use the `[kybernaut_ml_switcher]` shortcode.

Updates are delivered from GitHub Releases and show up on the Plugins screen like any other update.

== Frequently Asked Questions ==

= Does it translate posts? =

No. Only pages are supported.

= Can a page have more than one translation in the same language? =

No. Each default-language page can have one translation per language, and each language has one root page.

= What happens to the plugin's data when I delete it? =

Deactivating keeps everything. Deleting the plugin removes its settings, cache and post meta. Your pages stay untouched.

= How do I style the switcher? =

Inside the Navigation block, the switcher outputs core menu items, so it inherits the menu's styling and mobile overlay. On its own, it outputs `nav.kbnt-ml-switcher > ul > li.kbnt-ml-switcher__item`, with `.is-current` on the current language. A few lines of base CSS lay the items out in a row; style the rest in your theme.

= Does it work with Yoast SEO? =

Yes. Because the WordPress locale is switched, Yoast's `og:locale` and schema `inLanguage` follow the page language. The hreflang links are printed by this plugin.

== Changelog ==

= 1.0.0 =
* Initial release: language root pages, translation links, hreflang and x-default, locale switching, Language switcher block (including Navigation menu items) and shortcode, Language column in the Pages list, Czech translation, updates from GitHub Releases.

== Upgrade Notice ==

= 1.0.0 =
Initial release.

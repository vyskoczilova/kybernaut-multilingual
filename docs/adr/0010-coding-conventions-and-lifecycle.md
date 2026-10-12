# Conventions, admin overview and uninstall

- Name "Kybernaut Multilingual" (originally drafted as "Multilingue"; renamed to the correct English spelling before the first release), slug and text domain `kybernaut-multilingual`, namespace `Kybernaut\Multilingual`; option/meta/transient prefix `kbnt_ml_`, PHP 7.4+, GPL-3.0-or-later (full text in `LICENSE`; the bundled Plugin Update Checker is MIT, which is compatible), main file plus `includes/`, no build step.
- Security baseline: capability checks and nonces on every save, sanitisation on input, escaping on output, translation targets validated to be pages in the default language.
- `uninstall.php` removes all options, transients and post meta. Deactivation keeps the data.
- A "Language" column in the Pages list shows language and translation link, read from the cached map.

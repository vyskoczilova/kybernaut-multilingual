# Distribution via GitHub with a bundled Plugin Update Checker

The plugin is distributed from GitHub, not wordpress.org. It bundles the YahnisElsts **Plugin Update Checker** library, so updates show up in WP admin from GitHub Releases (a tagged release with an attached zip), without requiring a separate updater plugin.

## Considered Options

- **Git Updater** (separate plugin, header-only integration): rejected; adds a dependency each site must install.
- **Manual zip uploads**: rejected; no update notifications.

Repository: `vyskoczilova/kybernaut-multilingual`, **public**, so no access token is needed on any site.

## Considered and rejected

- **Private repo with an embedded access token**: the token would ship in every copy and in backups (UpdraftPlus, Duplicator). Rejected in favour of a public repo.

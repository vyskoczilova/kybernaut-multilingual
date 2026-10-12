# All roots and translation links live in one cached map

With only a few translated pages, the whole relation (language roots and translation links in both directions, plus the permalinks the front end needs) fits in one small array. It is built from two queries and stored in an autoloaded transient (or the persistent object cache when there is one). It is rebuilt only when a page is saved or deleted, when the plugin's post meta changes, or when the settings, front page or permalink options change. Several changes in one request (bulk edits, the block editor's double save) are coalesced: the map is marked stale, rebuilt in memory for the rest of the request, and the transient is deleted once on shutdown.

The map is the single judge of validity: it decides which root wins, what language a page is in, and which links count. Admin and front end only read it, through `Map` helpers such as `key_of`, `translations_of`, `root_taken` and `link_taken`. That keeps the rule "static front page = Site homepage" in one place.

Front-end hreflang output, locale switching, the switcher and the admin column add no database queries per request.

## Consequences

The invalidation hooks (`save_post_page`, `deleted_post`, the plugin's meta keys, the relevant options) must stay complete. A missed hook means stale hreflang until the next page save.

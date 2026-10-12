# Translation link is stored once, on the translated page

Each page under a language root has a "Translation of" field (post meta) pointing to its default-language counterpart. The field offers a special value, **Site homepage**, because the Czech home is the blog index (`show_on_front = posts`), not a page, and `/en/` must map to `/`.

The link is one-to-one per language and works both ways: the default-language page finds its translations by reverse lookup in the cached map (ADR 0004). It is edited only on the translated page, so there is a single source of truth and no two-sided sync to keep consistent.

Translations that are not publicly viewable (draft, private, password-protected) are treated as missing on the front end, but remain selectable in the admin.

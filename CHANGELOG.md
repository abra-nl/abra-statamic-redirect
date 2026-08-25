# Changelog

All notable changes to `abra-nl/abra-statamic-redirect` are documented in this file.

## v3.1.0

### Added

- **The Control Panel listing now uses Statamic's `Listing` component**, replacing the
  hand-rolled table. This adds free-text search (matching host, source, and
  destination), sortable columns, and server-side pagination, none of which the
  previous table had.
- **Bulk delete.** Selecting multiple redirects now surfaces a bulk-actions toolbar,
  powered by a new `DeleteRedirect` action and `RedirectActionController`. Per-row
  delete still works the same way, now via the row's action dropdown instead of a
  standalone trash icon.
  - New routes: `POST redirects/actions` and `POST redirects/actions/list`.

### Changed

- The listing's empty state is now the `Listing` component's generic "No results"
  message rather than the previous "Start by creating your first redirect" call to
  action. The "Create redirect" button in the header is unaffected and always visible.
- `RedirectController::index()` no longer passes the full `redirects` array to the
  page; it now passes `jsonUrl` and `actionUrl`, and a new `json()` action serves the
  listing data. This is an internal change — no addon consumers integrate with these
  Inertia props directly, so it isn't called out as breaking.

### Fixed

- Searching the listing could throw a fatal error on file storage if any redirect
  predates host scoping and has no `host` key at all (only possible with the file
  driver — the database column always has a default). The search filter now treats a
  missing `host` the same way the rest of the codebase already does: as "any host".

## v3.0.0

### Added

- **Optional host scoping for redirects.** Each redirect can now be scoped to a specific
  host (e.g. `abra.nl`) via a new `host` column/field. A redirect with no host set
  matches its path on any domain — this is the default and is fully backward compatible
  with every redirect created before this release. A redirect with a host set only
  matches requests arriving on that exact host, which lets you run host-specific
  overrides (e.g. redirecting `/contact` differently on a legacy domain) without
  affecting or looping with content on your primary domain.
  - When both a global (`host = ''`) and a host-specific redirect exist for the same
    source, the host-specific one wins.
  - This applies to exact-match and wildcard (`*`) sources alike, and to both the
    database and file storage drivers.
  - The Control Panel create/edit forms now have an optional "Host" field, and the
    listing table shows each redirect's host (or "Any").

### Changed — breaking

- `RedirectRepository::find(string $source): ?array` is now
  `find(string $source, ?string $host = null): ?array`.
- `RedirectRepository::exists(string $source, ?string $excludeId = null): bool` is now
  `exists(string $source, ?string $host = null, ?string $excludeId = null): bool` —
  **note the parameter order change**: `$excludeId` moved from the 2nd to the 3rd
  argument.
- `store()`/`update()` accept an optional `host` key in their data array.
- Any custom implementation of `RedirectRepository` must update its method signatures
  to match, or PHP will raise a fatal "declaration must be compatible" error. This is
  why this release is a major version bump.
- Callers that only pass `$source` to `find()`/`exists()` are unaffected — the new
  parameters default to `null`, which preserves the pre-3.0 "match any host" behavior.
  Callers passing `$excludeId` as the second argument to `exists()` must update the call
  to pass it as the third argument instead.

### Database migration

- A new migration, `2026_08_25_000000_add_host_to_redirects_table`, adds a `host`
  column (`string`, default `''`, not nullable) to the `redirects` table and replaces
  the unique index on `source` with a composite unique index on `['host', 'source']`,
  so the same path can have both a global redirect and per-host overrides without a
  constraint violation. Existing rows are backfilled with `host = ''`, so nothing
  already stored changes behavior.
- This is a separate migration rather than an edit to the original
  `create_redirects_table` migration, because sites that installed an earlier version
  of the addon have already run that migration — Laravel won't re-run a migration file
  just because its contents changed, so a fix bundled into that file would never reach
  existing installs. Run `php artisan migrate` after updating to pick it up (publish the
  addon's migrations first with `php artisan vendor:publish` if you haven't already).

## Earlier versions

See git tags (`v0.0.1`–`v2.0.2`) for history prior to this changelog.

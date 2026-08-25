# Changelog

All notable changes to `abra-nl/abra-statamic-redirect` are documented in this file.

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

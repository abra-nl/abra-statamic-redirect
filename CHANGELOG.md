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

- The `redirects` table gains a `host` column (`string`, default `''`, not nullable).
  Existing rows are backfilled with `host = ''`, so nothing already stored changes
  behavior.
- The unique index on `source` is replaced with a composite unique index on
  `['host', 'source']`, so the same path can now have both a global redirect and
  per-host overrides without a constraint violation.

## Earlier versions

See git tags (`v0.0.1`–`v2.0.2`) for history prior to this changelog.

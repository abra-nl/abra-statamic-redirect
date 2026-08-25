<?php

declare(strict_types=1);

namespace Abra\AbraStatamicRedirect\Interfaces;

interface RedirectRepository
{
    /**
     * Get all redirects
     *
     * @return array<int, array{id: string, host: string, source: string, destination: string, status_code: int, created_at: string, updated_at: string}>
     */
    public function all(): array;

    /**
     * Find a redirect by source URL, optionally scoped to a request host.
     *
     * A redirect with an empty host matches any host. A redirect with a host
     * set only matches when $host is identical; if both a global and a
     * host-specific redirect match the same source, the host-specific one
     * takes precedence.
     *
     * @return array{id: string, host: string, source: string, destination: string, status_code: int, created_at: string, updated_at: string}|null
     */
    public function find(string $source, ?string $host = null): ?array;

    /**
     * Store a new redirect
     *
     * @param  array{host?: ?string, source: string, destination: string, status_code?: int}  $data
     * @return array{id: string, host: string, source: string, destination: string, status_code: int, created_at: string, updated_at: string}
     */
    public function store(array $data): array;

    /**
     * Update an existing redirect
     *
     * @param  array{host?: ?string, source?: string, destination?: string, status_code?: int}  $data
     * @return array{id: string, host: string, source: string, destination: string, status_code: int, created_at: string, updated_at: string}
     */
    public function update(string $id, array $data): array;

    /**
     *  Delete a redirect
     */
    public function delete(string $id): bool;

    /**
     * Check if a redirect exists for a source URL and host combination
     */
    public function exists(string $source, ?string $host = null, ?string $excludeId = null): bool;
}

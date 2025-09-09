<?php

namespace Abra\AbraStatamicRedirect\Interfaces;

interface RedirectRepository
{
    /**
     * Get all redirects
     *
     * @return array<int, array{id: string, source: string, destination: string, status_code: int, created_at: string, updated_at: string}>
     */
    public function all(): array;

    /**
     * Find a redirect by source URL
     *
     * @return array{id: string, source: string, destination: string, status_code: int, created_at: string, updated_at: string}|null
     */
    public function find(string $source): ?array;

    /**
     * Store a new redirect
     *
     * @param  array{source: string, destination: string, status_code?: int}  $data
     * @return array{id: string, source: string, destination: string, status_code: int, created_at: string, updated_at: string}
     */
    public function store(array $data): array;

    /**
     * Update an existing redirect
     *
     * @param  array{source?: string, destination?: string, status_code?: int}  $data
     * @return array{id: string, source: string, destination: string, status_code: int, created_at: string, updated_at: string}
     */
    public function update(string $id, array $data): array;

    /**
     *  Delete a redirect
     */
    public function delete(string $id): bool;

    /**
     * Check if a redirect exists by source URL
     */
    public function exists(string $source, ?string $excludeId = null): bool;
}

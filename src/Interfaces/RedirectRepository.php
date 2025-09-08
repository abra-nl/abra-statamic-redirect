<?php

namespace Abra\AbraStatamicRedirect\Interfaces;

interface RedirectRepository
{
    /**
     * Get all redirects
     */
    public function all(): array;

    /**
     * Find a redirect by source URL
     */
    public function find(string $source): ?array;

    /**
     * Store a new redirect
     */
    public function store(array $data): array;

    /**
     * Update an existing redirect
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

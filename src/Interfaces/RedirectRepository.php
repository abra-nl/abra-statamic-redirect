<?php

namespace Abra\AbraStatamicRedirect\Interfaces;

interface RedirectRepository
{
    /**
     * Get all redirects
     *
     * @return array
     */
    public function all(): array;

    /**
     * Find a redirect by source URL
     *
     * @param string $source
     * @return array|null
     */
    public function find(string $source): ?array;

    /**
     * Store a new redirect
     *
     * @param array $data
     * @return array
     */
    public function store(array $data): array;

    /**
     * Update an existing redirect
     *
     *
     * @param string $id
     * @param array $data
     * @return array
     */
    public function update(string $id, array $data): array;

    /**
     *  Delete a redirect
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool;

    /**
     * Check if a redirect exists by source URL
     *
     * @param string $source
     * @param string|null $excludeId
     * @return bool
     */
    public function exists(string $source, ?string $excludeId = null): bool;
}
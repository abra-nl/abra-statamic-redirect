<?php

namespace Abra\AbraStatamicRedirect\Repository;

use Abra\AbraStatamicRedirect\Concerns\ConvertsWildcardPatterns;
use Abra\AbraStatamicRedirect\Interfaces\RedirectRepository;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseRedirectRepository implements RedirectRepository
{
    use ConvertsWildcardPatterns;

    protected string $table;

    protected bool $cache_enabled;

    protected int $cache_expiry;

    public function __construct()
    {
        $this->table = Config::string('redirects.table', 'redirects');
        $this->cache_enabled = Config::boolean('redirects.cache_enabled', false);
        $this->cache_expiry = Config::integer('redirects.cache_expiry', 60);
    }

    /**
     * @return array<int, array{id: string, source: string, destination: string, status_code: int, created_at: string, updated_at: string}>
     */
    public function all(): array
    {
        if ($this->cache_enabled && Cache::has('redirects.all')) {
            /** @var array<int, array{id: string, source: string, destination: string, status_code: int, created_at: string, updated_at: string}> $redirects */
            $redirects = Cache::get('redirects.all');

            return $redirects;
        }

        $redirects = DB::table($this->table)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($item): array => (array) $item)
            ->toArray();

        if ($this->cache_enabled) {
            Cache::put('redirects.all', $redirects, $this->cache_expiry);
        }

        return $redirects;
    }

    /**
     * @return array{id: string, source: string, destination: string, status_code: int, created_at: string, updated_at: string}|null
     */
    public function find(string $source): ?array
    {
        $normalizedSource = $this->normalizeUrl($source);

        $redirect = DB::table($this->table)
            ->where('source', $normalizedSource)
            ->first();

        if ($redirect) {
            return (array) $redirect;
        }

        // If no exact match found, try wildcard patterns
        $allRedirects = $this->all();

        foreach ($allRedirects as $redirect) {
            $pattern = $redirect['source'];

            // Check if the redirect source contains a wildcard
            if (Str::contains($pattern, '*')) {
                // Convert wildcard pattern to regex
                $regexPattern = $this->wildcardToRegex($pattern);

                // Test the regex against the normalized source
                if (preg_match($regexPattern, $normalizedSource)) {
                    return $redirect;
                }
            }
        }

        return null;
    }

    /**
     * @param  array{source: string, destination: string, status_code?: int}  $data
     * @return array{id: string, source: string, destination: string, status_code: int, created_at: string, updated_at: string}
     */
    public function store(array $data): array
    {
        $now = now();
        $id = Str::uuid()->toString();

        $redirect = [
            'id' => $id,
            'source' => $this->normalizeUrl($data['source']),
            'destination' => $data['destination'],
            'status_code' => $data['status_code'] ?? 301,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        DB::table($this->table)->insert($redirect);

        if ($this->cache_enabled) {
            Cache::forget('redirects.all');
        }

        return $redirect;
    }

    /**
     * @param  array{source?: string, destination?: string, status_code?: int}  $data
     * @return array{id: string, source: string, destination: string, status_code: int, created_at: string, updated_at: string}
     */
    public function update(string $id, array $data): array
    {
        $redirect = DB::table($this->table)
            ->where('id', $id)
            ->first();

        if (! $redirect) {
            throw new Exception(sprintf('Redirect with ID %s not found', $id));
        }

        $updateData = [
            'updated_at' => now(),
        ];

        if (isset($data['source'])) {
            $updateData['source'] = $this->normalizeUrl($data['source']);
        }

        if (isset($data['destination'])) {
            $updateData['destination'] = $data['destination'];
        }

        if (isset($data['status_code'])) {
            $updateData['status_code'] = $data['status_code'];
        }

        DB::table($this->table)
            ->where('id', $id)
            ->update($updateData);

        if ($this->cache_enabled) {
            Cache::forget('redirects.all');
        }

        $updatedRedirect = DB::table($this->table)
            ->where('id', $id)
            ->first();

        return (array) $updatedRedirect;
    }

    public function delete(string $id): bool
    {
        $deleted = DB::table($this->table)
            ->where('id', $id)
            ->delete();

        if ($this->cache_enabled && $deleted) {
            Cache::forget('redirects.all');
        }

        return (bool) $deleted;
    }

    public function exists(string $source, ?string $excludeId = null): bool
    {
        $normalizedSource = $this->normalizeUrl($source);

        $query = DB::table($this->table)
            ->where('source', $normalizedSource);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}

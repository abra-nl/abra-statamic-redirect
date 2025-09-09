<?php

namespace Abra\AbraStatamicRedirect\Repository;

use Abra\AbraStatamicRedirect\Concerns\ConvertsWildcardPatterns;
use Abra\AbraStatamicRedirect\Interfaces\RedirectRepository;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Statamic\Facades\YAML;

class FileRedirectRepository implements RedirectRepository
{
    use ConvertsWildcardPatterns;

    protected string $path;

    protected bool $cache_enabled;

    protected int $cache_expiry;

    public function __construct()
    {
        $this->path = Config::string('redirects.file_path');
        $this->cache_enabled = Config::boolean('redirects.cache_enabled', false);
        $this->cache_expiry = Config::integer('redirects.cache_expiry', 60);

        if (! File::exists(dirname($this->path))) {
            File::makeDirectory(dirname($this->path), 0755, true);
        }

        if (! File::exists($this->path)) {
            File::put($this->path, "# Redirects\n");
        }
    }

    /**
     * @return array<int, array{id: string, source: string, destination: string, status_code: int, created_at: string, updated_at: string}>
     */
    public function all(): array
    {
        if ($this->cache_enabled && Cache::has('redirects.all')) {
            return Cache::get('redirects.all');
        }

        $redirects = $this->getRedirects();

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
        // Normalize the source URL for matching
        $normalizedSource = $this->normalizeUrl($source);

        $all = $this->all();

        foreach ($all as $redirect) {
            if ($this->normalizeUrl($redirect['source']) === $normalizedSource) {
                return $redirect;
            }
        }

        foreach ($all as $redirect) {
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
        $redirects = $this->all();

        $id = Str::uuid()->toString();
        $redirect = [
            'id' => $id,
            'source' => $data['source'],
            'destination' => $data['destination'],
            'status_code' => $data['status_code'] ?? 301,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];

        $redirects[] = $redirect;

        $this->saveRedirects($redirects);

        return $redirect;
    }

    /**
     * @param  array{source?: string, destination?: string, status_code?: int}  $data
     * @return array{id: string, source: string, destination: string, status_code: int, created_at: string, updated_at: string}
     */
    public function update(string $id, array $data): array
    {
        $redirects = $this->all();

        foreach ($redirects as $key => $redirect) {
            if ($redirect['id'] === $id) {
                $redirects[$key] = array_merge($redirect, [
                    'source' => $data['source'] ?? $redirect['source'],
                    'destination' => $data['destination'] ?? $redirect['destination'],
                    'status_code' => $data['status_code'] ?? $redirect['status_code'],
                    'updated_at' => now()->toIso8601String(),
                ]);

                $this->saveRedirects($redirects);

                return $redirects[$key];
            }
        }

        throw new Exception("Redirect with ID {$id} not found");
    }

    public function delete(string $id): bool
    {
        $redirects = $this->all();

        foreach ($redirects as $key => $redirect) {
            if ($redirect['id'] === $id) {
                unset($redirects[$key]);
                $this->saveRedirects(array_values($redirects));

                return true;
            }
        }

        return false;
    }

    public function exists(string $source, ?string $excludeId = null): bool
    {
        $normalizedSource = $this->normalizeUrl($source);

        foreach ($this->all() as $redirect) {
            if ($redirect['id'] !== $excludeId && $this->normalizeUrl($redirect['source']) === $normalizedSource) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array{id: string, source: string, destination: string, status_code: int, created_at: string, updated_at: string}>
     */
    protected function getRedirects(): array
    {
        try {
            return YAML::parse(File::get($this->path)) ?: [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Save redirects to YAML file
     *
     * @param  array<int, array{id: string, source: string, destination: string, status_code: int, created_at: string, updated_at: string}>  $redirects
     */
    protected function saveRedirects(array $redirects): bool
    {
        if ($this->cache_enabled) {
            Cache::forget('redirects.all');
        }

        return File::put($this->path, YAML::dump($redirects));
    }
}

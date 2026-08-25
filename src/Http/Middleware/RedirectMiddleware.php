<?php

namespace Abra\AbraStatamicRedirect\Http\Middleware;

use Abra\AbraStatamicRedirect\Interfaces\RedirectRepository;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RedirectMiddleware
{
    protected bool $cache_enabled;

    protected int $cache_expiry;

    public function __construct(protected RedirectRepository $redirects)
    {
        /** @var bool $cacheEnabled */
        $cacheEnabled = config('redirects.cache.enabled', false);
        /** @var int $cacheExpiry */
        $cacheExpiry = config('redirects.cache.expiry', 60);
        $this->cache_enabled = $cacheEnabled;
        $this->cache_expiry = $cacheExpiry;
    }

    public function handle(Request $request, Closure $next): mixed
    {
        /** @var string $cpRoute */
        $cpRoute = config('statamic.cp.route', 'cp');

        // Skip for CP requests
        if ($request->is($cpRoute.'*')) {
            return $next($request);
        }

        $requestPath = $this->normalizePath($request->path());
        $requestHost = $request->getHost();

        // Check if there's a redirect for this path
        if ($redirect = $this->findRedirectForPath($requestPath, $requestHost)) {
            // Handle query parameters if needed
            $destination = $redirect['destination'];
            if ($request->getQueryString()) {
                $destination = $this->appendQueryString($destination, $request->getQueryString());
            }

            return redirect()->to($destination, $redirect['status_code']);
        }

        return $next($request);
    }

    /**
     * Find a redirect for the given path and host
     *
     * @return array{id: string, host: string, source: string, destination: string, status_code: int, created_at: string, updated_at: string}|null
     */
    protected function findRedirectForPath(string $path, string $host): ?array
    {
        $cacheKey = 'redirect_for_path_'.md5($host.'|'.$path);

        if ($this->cache_enabled && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $redirect = $this->redirects->find($path, $host);

        if ($this->cache_enabled && $redirect) {
            Cache::put($cacheKey, $redirect, $this->cache_expiry);
        }

        return $redirect;
    }

    /**
     * Normalize the request path for matching
     */
    protected function normalizePath(string $path): string
    {
        // If root path, return /
        if ($path === '' || $path === '0') {
            return '/';
        }

        // Otherwise remove trailing slashes
        return '/'.ltrim($path, '/');
    }

    /**
     * Append query string to destination URL
     */
    protected function appendQueryString(string $url, string $queryString): string
    {
        // If destination already has query params, append with &
        if (str_contains($url, '?')) {
            return $url.'&'.$queryString;
        }

        // Otherwise append with ?
        return $url.'?'.$queryString;
    }
}

<?php

namespace Abra\AbraStatamicRedirect\Concerns;

use Illuminate\Support\Str;

trait ConvertsWildcardPatterns
{
    /**
     * Convert a wildcard pattern to a regex pattern
     */
    protected function wildcardToRegex(string $wildcardPattern): string
    {
        // Normalize the wildcard pattern
        $normalizedPattern = $this->normalizeUrl($wildcardPattern);

        // Handle special case for patterns ending with /*
        if (Str::endsWith($normalizedPattern, '/*')) {
            // Create the base path (without the /* suffix)
            $basePath = substr((string) $normalizedPattern, 0, -2);

            // Escape special regex characters in the base path
            $escapedBasePath = preg_quote($basePath, '/');

            // Create a pattern that matches:
            // 1. Exactly the base path, or
            // 2. The base path followed by / and anything
            return '/^'.$escapedBasePath.'(\/.*)?$/';
        }

        // Handle patterns with wildcards in the middle or beginning
        // Escape special regex characters except for asterisks
        $pattern = preg_quote((string) $normalizedPattern, '/');

        // Replace asterisks with regex pattern to match any characters
        $pattern = str_replace('\*', '.*', $pattern);

        // Add start and end anchors
        return '/^'.$pattern.'$/';
    }

    /**
     * Normalize URL for consistent matching
     */
    protected function normalizeUrl(string $url): string
    {
        // Remove trailing slashes, except for root URL
        $url = rtrim($url, '/');
        if ($url === '' || $url === '0') {
            return '/';
        }

        return $url;
    }
}

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
     * Apply wildcard substitution to destination URL
     */
    protected function applyWildcardSubstitution(string $source, string $pattern, string $destination): string
    {
        // Normalize inputs
        $normalizedSource = $this->normalizeUrl($source);
        $normalizedPattern = $this->normalizeUrl($pattern);

        // If destination doesn't contain wildcards, return as-is
        if (! Str::contains($destination, '*')) {
            return $destination;
        }

        // Get the regex pattern for matching
        $regexPattern = $this->wildcardToRegex($normalizedPattern);

        // Extract captured groups from the source
        if (! preg_match($regexPattern, (string) $normalizedSource, $matches)) {
            return $destination;
        }

        // Build array of wildcard captures
        $captures = [];

        // Special handling for patterns ending with /*
        if (Str::endsWith($normalizedPattern, '/*')) {
            // The first capture group contains everything after the base path
            $captures[] = isset($matches[1]) && $matches[1] !== '' ? ltrim($matches[1], '/') : '';
        } else {
            // For other patterns, extract wildcards in order
            $patternParts = explode('*', (string) $normalizedPattern);
            $sourceCopy = $normalizedSource;

            foreach ($patternParts as $i => $part) {
                if ($i === count($patternParts) - 1) {
                    break; // Last part, no wildcard after it
                }

                // Find position after this literal part
                $pos = $part !== '' ? strpos((string) $sourceCopy, $part) : 0;
                if ($pos === false) {
                    continue;
                }

                // Move past the literal part
                $sourceCopy = substr((string) $sourceCopy, $pos + strlen($part));

                // Find where the next literal part starts
                $nextPart = $patternParts[$i + 1] ?? '';
                if ($nextPart !== '') {
                    $nextPos = strpos($sourceCopy, $nextPart);
                    if ($nextPos !== false) {
                        $capture = substr($sourceCopy, 0, $nextPos);
                        // Remove trailing slash from capture if nextPart starts with slash
                        if (Str::startsWith($nextPart, '/') && Str::endsWith($capture, '/')) {
                            $capture = rtrim($capture, '/');
                        }
                        $captures[] = $capture;

                        continue;
                    }
                }

                // If no next part or not found, capture the rest
                $captures[] = $sourceCopy;
            }
        }

        // Replace wildcards in destination with captured values
        $result = $destination;
        foreach ($captures as $capture) {
            $result = preg_replace('/\*/', $capture, (string) $result, 1);
        }

        return $result;
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

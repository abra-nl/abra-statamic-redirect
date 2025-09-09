<?php

use Abra\AbraStatamicRedirect\Concerns\ConvertsWildcardPatterns;

// Create a test class that uses the trait
class TestClassWithWildcardPatterns
{
    use ConvertsWildcardPatterns;

    // Make protected methods public for testing
    public function testWildcardToRegex(string $wildcardPattern): string
    {
        return $this->wildcardToRegex($wildcardPattern);
    }

    public function testNormalizeUrl(string $url): string
    {
        return $this->normalizeUrl($url);
    }
}

beforeEach(function (): void {
    $this->testClass = new TestClassWithWildcardPatterns;
});

describe('ConvertsWildcardPatterns', function (): void {
    describe('normalizeUrl', function (): void {
        test('removes trailing slashes from regular URLs', function (): void {
            expect($this->testClass->testNormalizeUrl('/test/'))->toBe('/test');
            expect($this->testClass->testNormalizeUrl('/test/path/'))->toBe('/test/path');
            expect($this->testClass->testNormalizeUrl('/test/path///'))->toBe('/test/path');
        });

        test('preserves root URL as single slash', function (): void {
            expect($this->testClass->testNormalizeUrl('/'))->toBe('/');
            expect($this->testClass->testNormalizeUrl('//'))->toBe('/');
            expect($this->testClass->testNormalizeUrl(''))->toBe('/');
        });

        test('handles URLs without trailing slashes', function (): void {
            expect($this->testClass->testNormalizeUrl('/test'))->toBe('/test');
            expect($this->testClass->testNormalizeUrl('/test/path'))->toBe('/test/path');
        });

        test('handles URLs with multiple segments', function (): void {
            expect($this->testClass->testNormalizeUrl('/blog/posts/2023/'))->toBe('/blog/posts/2023');
            expect($this->testClass->testNormalizeUrl('/api/v1/users/'))->toBe('/api/v1/users');
        });
    });

    describe('wildcardToRegex', function (): void {
        describe('patterns ending with /*', function (): void {
            test('creates regex for simple wildcard patterns', function (): void {
                $regex = $this->testClass->testWildcardToRegex('/blog/*');
                expect($regex)->toBe('/^\/blog(\/.*)?$/');
            });

            test('matches paths exactly and with suffixes', function (): void {
                $regex = $this->testClass->testWildcardToRegex('/blog/*');

                // Should match the base path
                expect(preg_match($regex, '/blog'))->toBe(1);

                // Should match paths with suffixes
                expect(preg_match($regex, '/blog/post-1'))->toBe(1);
                expect(preg_match($regex, '/blog/2023/january'))->toBe(1);
                expect(preg_match($regex, '/blog/'))->toBe(1);

                // Should not match different paths
                expect(preg_match($regex, '/news'))->toBe(0);
                expect(preg_match($regex, '/blog-archive'))->toBe(0);
                expect(preg_match($regex, '/other/blog'))->toBe(0);
            });

            test('handles nested wildcard patterns', function (): void {
                $regex = $this->testClass->testWildcardToRegex('/api/v1/*');
                expect($regex)->toBe('/^\/api\/v1(\/.*)?$/');

                expect(preg_match($regex, '/api/v1'))->toBe(1);
                expect(preg_match($regex, '/api/v1/users'))->toBe(1);
                expect(preg_match($regex, '/api/v1/users/123'))->toBe(1);
                expect(preg_match($regex, '/api/v2/users'))->toBe(0);
            });

            test('escapes special regex characters in base path', function (): void {
                $regex = $this->testClass->testWildcardToRegex('/test.path/*');
                expect($regex)->toBe('/^\/test\.path(\/.*)?$/');

                expect(preg_match($regex, '/test.path'))->toBe(1);
                expect(preg_match($regex, '/test.path/file'))->toBe(1);
                expect(preg_match($regex, '/testXpath'))->toBe(0);
            });

            test('handles root wildcard pattern', function (): void {
                $regex = $this->testClass->testWildcardToRegex('/*');
                expect($regex)->toBe('/^(\/.*)?$/');

                expect(preg_match($regex, ''))->toBe(1);
                expect(preg_match($regex, '/'))->toBe(1);
                expect(preg_match($regex, '/anything'))->toBe(1);
                expect(preg_match($regex, '/deep/nested/path'))->toBe(1);
            });
        });

        describe('patterns with wildcards in middle or beginning', function (): void {
            test('handles wildcards in the middle of patterns', function (): void {
                $regex = $this->testClass->testWildcardToRegex('/blog/*/comments');
                expect($regex)->toBe('/^\/blog\/.*\/comments$/');

                expect(preg_match($regex, '/blog/post-1/comments'))->toBe(1);
                expect(preg_match($regex, '/blog/2023-01-01/comments'))->toBe(1);
                expect(preg_match($regex, '/blog/any-post-title/comments'))->toBe(1);
                expect(preg_match($regex, '/blog/comments'))->toBe(0);
                expect(preg_match($regex, '/blog/post-1/replies'))->toBe(0);
            });

            test('handles wildcards at the beginning of patterns', function (): void {
                $regex = $this->testClass->testWildcardToRegex('*/admin');
                expect($regex)->toBe('/^.*\/admin$/');

                expect(preg_match($regex, '/admin'))->toBe(1);
                expect(preg_match($regex, 'site/admin'))->toBe(1);
                expect(preg_match($regex, 'multi/level/admin'))->toBe(1);
                expect(preg_match($regex, '/admin/dashboard'))->toBe(0);
            });

            test('handles multiple wildcards in pattern', function (): void {
                $regex = $this->testClass->testWildcardToRegex('/*/api/*/data');
                expect($regex)->toBe('/^\/.*\/api\/.*\/data$/');

                expect(preg_match($regex, '/v1/api/users/data'))->toBe(1);
                expect(preg_match($regex, '/beta/api/posts/data'))->toBe(1);
                expect(preg_match($regex, '/any/api/any/data'))->toBe(1);
                expect(preg_match($regex, '/v1/api/data'))->toBe(0);
                expect(preg_match($regex, '/api/users/data'))->toBe(0);
            });

            test('escapes regex special characters with wildcards', function (): void {
                $regex = $this->testClass->testWildcardToRegex('/test.*/file.txt');
                expect($regex)->toBe('/^\/test\..*\/file\.txt$/');

                expect(preg_match($regex, '/test.anything/file.txt'))->toBe(1);
                expect(preg_match($regex, '/test.123/file.txt'))->toBe(1);
                expect(preg_match($regex, '/testXanything/file.txt'))->toBe(0);
            });
        });

        describe('patterns without wildcards', function (): void {
            test('creates exact match regex for patterns without wildcards', function (): void {
                $regex = $this->testClass->testWildcardToRegex('/exact/path');
                expect($regex)->toBe('/^\/exact\/path$/');

                expect(preg_match($regex, '/exact/path'))->toBe(1);
                expect(preg_match($regex, '/exact/path/'))->toBe(0);
                expect(preg_match($regex, '/exact/path/more'))->toBe(0);
                expect(preg_match($regex, '/different/path'))->toBe(0);
            });

            test('escapes special regex characters in exact patterns', function (): void {
                $regex = $this->testClass->testWildcardToRegex('/test.path/file.txt');
                expect($regex)->toBe('/^\/test\.path\/file\.txt$/');

                expect(preg_match($regex, '/test.path/file.txt'))->toBe(1);
                expect(preg_match($regex, '/testXpath/fileXtxt'))->toBe(0);
            });

            test('handles root path pattern', function (): void {
                $regex = $this->testClass->testWildcardToRegex('/');
                expect($regex)->toBe('/^\/$/');

                expect(preg_match($regex, '/'))->toBe(1);
                expect(preg_match($regex, '/home'))->toBe(0);
                expect(preg_match($regex, ''))->toBe(0);
            });
        });

        describe('normalization and edge cases', function (): void {
            test('normalizes trailing slashes before processing', function (): void {
                $regexWithSlash = $this->testClass->testWildcardToRegex('/blog/*/');
                $regexWithoutSlash = $this->testClass->testWildcardToRegex('/blog/*');

                expect($regexWithSlash)->toBe($regexWithoutSlash);
                expect($regexWithSlash)->toBe('/^\/blog(\/.*)?$/');
            });

            test('handles empty string pattern', function (): void {
                $regex = $this->testClass->testWildcardToRegex('');
                expect($regex)->toBe('/^\/$/');

                expect(preg_match($regex, '/'))->toBe(1);
                expect(preg_match($regex, ''))->toBe(0);
            });

            test('handles complex regex characters', function (): void {
                $regex = $this->testClass->testWildcardToRegex('/test[0-9]+path/*');
                expect($regex)->toBe('/^\/test\[0\-9\]\+path(\/.*)?$/');

                expect(preg_match($regex, '/test[0-9]+path'))->toBe(1);
                expect(preg_match($regex, '/test[0-9]+path/file'))->toBe(1);
                expect(preg_match($regex, '/test123path'))->toBe(0);
            });

            test('handles patterns with query parameters and fragments', function (): void {
                $regex = $this->testClass->testWildcardToRegex('/search?q=*');
                expect($regex)->toBe('/^\/search\?q\=.*$/');

                expect(preg_match($regex, '/search?q=test'))->toBe(1);
                expect(preg_match($regex, '/search?q=anything'))->toBe(1);
                expect(preg_match($regex, '/search?q='))->toBe(1);
            });
        });

        describe('real-world use cases', function (): void {
            test('handles common blog patterns', function (): void {
                $regex = $this->testClass->testWildcardToRegex('/blog/*/');

                expect(preg_match($regex, '/blog/my-first-post'))->toBe(1);
                expect(preg_match($regex, '/blog/2023-review'))->toBe(1);
                expect(preg_match($regex, '/blog/category/tech'))->toBe(1);
            });

            test('handles API versioning patterns', function (): void {
                $regex = $this->testClass->testWildcardToRegex('/api/v*/users');

                expect(preg_match($regex, '/api/v1/users'))->toBe(1);
                expect(preg_match($regex, '/api/v2/users'))->toBe(1);
                expect(preg_match($regex, '/api/v1.2/users'))->toBe(1);
                expect(preg_match($regex, '/api/users'))->toBe(0);
            });

            test('handles file extension patterns', function (): void {
                $regex = $this->testClass->testWildcardToRegex('/uploads/*.jpg');

                expect(preg_match($regex, '/uploads/photo.jpg'))->toBe(1);
                expect(preg_match($regex, '/uploads/image-1.jpg'))->toBe(1);
                expect(preg_match($regex, '/uploads/photo.png'))->toBe(0);
            });

            test('handles date-based URL patterns', function (): void {
                $regex = $this->testClass->testWildcardToRegex('/archive/*/posts');

                expect(preg_match($regex, '/archive/2023/posts'))->toBe(1);
                expect(preg_match($regex, '/archive/2023-01/posts'))->toBe(1);
                expect(preg_match($regex, '/archive/january-2023/posts'))->toBe(1);
            });

            test('handles user profile patterns', function (): void {
                $regex = $this->testClass->testWildcardToRegex('/users/*/profile');

                expect(preg_match($regex, '/users/john-doe/profile'))->toBe(1);
                expect(preg_match($regex, '/users/123/profile'))->toBe(1);
                expect(preg_match($regex, '/users/admin/profile'))->toBe(1);
                expect(preg_match($regex, '/users/profile'))->toBe(0);
            });
        });
    });
});

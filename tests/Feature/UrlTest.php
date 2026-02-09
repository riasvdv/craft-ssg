<?php

declare(strict_types=1);

use rias\ssg\Url;

it('generates a path for a simple URL', function () {
    $url = new Url('https://example.com/about', '/tmp/static');

    expect($url->path())->toBe('/tmp/static/about.html');
});

it('generates an index path for root URL', function () {
    $url = new Url('https://example.com/', '/tmp/static');

    expect($url->path())->toBe('/tmp/static/index.html');
});

it('generates an index path for trailing slash URL', function () {
    $url = new Url('https://example.com/blog/', '/tmp/static');

    expect($url->path())->toBe('/tmp/static/blog/index.html');
});

it('generates directory index paths when directoryIndex is enabled', function () {
    $url = new Url('https://example.com/about', '/tmp/static', directoryIndex: true);

    expect($url->path())->toBe('/tmp/static/about/index.html');
});

it('generates directory index paths for nested URLs', function () {
    $url = new Url('https://example.com/blog/my-post', '/tmp/static', directoryIndex: true);

    expect($url->path())->toBe('/tmp/static/blog/my-post/index.html');
});

it('ensures trailing slash on destination in path', function () {
    $url = new Url('https://example.com/about', '/tmp/static/');

    expect($url->path())->toBe('/tmp/static/about.html');
});

it('returns the last segment of the URL', function () {
    $url = new Url('https://example.com/blog/my-post', '/tmp/static');

    expect($url->lastSegment())->toBe('/my-post');
});

it('ensures last segment starts with a slash', function () {
    $url = new Url('https://example.com/about', '/tmp/static');

    expect($url->lastSegment())->toBe('/about');
});

it('converts to string using the original URL', function () {
    $url = new Url('https://example.com/about', '/tmp/static');

    expect((string) $url)->toBe('https://example.com/about');
});

it('handles deeply nested URL paths', function () {
    $url = new Url('https://example.com/blog/2024/01/my-post', '/tmp/static');

    expect($url->path())->toBe('/tmp/static/blog/2024/01/my-post.html');
});

it('handles deeply nested URL paths with directory index', function () {
    $url = new Url('https://example.com/blog/2024/01/my-post', '/tmp/static', directoryIndex: true);

    expect($url->path())->toBe('/tmp/static/blog/2024/01/my-post/index.html');
});

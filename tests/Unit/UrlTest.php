<?php

declare(strict_types=1);

use rias\ssg\Url;

beforeEach(function () {
    if (! class_exists('craft\helpers\UrlHelper')) {
        $this->markTestSkipped('Craft CMS is not available.');
    }
});

it('can be cast to string', function () {
    $url = new Url('https://example.com/about', '/tmp/static');

    expect((string) $url)->toBe('https://example.com/about');
});

it('generates correct path for a simple url', function () {
    $url = new Url('https://example.com/about', '/tmp/static');

    expect($url->path())->toBe('/tmp/static//about.html');
});

it('generates index.html for root url', function () {
    $url = new Url('https://example.com/', '/tmp/static');

    expect($url->path())->toBe('/tmp/static//index.html');
});

it('generates correct path with directory index enabled', function () {
    $url = new Url('https://example.com/about', '/tmp/static', directoryIndex: true);

    expect($url->path())->toBe('/tmp/static//about/index.html');
});

it('returns the last segment of the url', function () {
    $url = new Url('https://example.com/blog/my-post', '/tmp/static');

    expect($url->lastSegment())->toBe('/my-post');
});

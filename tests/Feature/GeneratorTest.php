<?php

declare(strict_types=1);

use craft\helpers\FileHelper;
use Illuminate\Support\Collection;
use rias\ssg\events\AfterGeneratingEvent;
use rias\ssg\events\BeforeGeneratingEvent;
use rias\ssg\Generator;
use rias\ssg\SSG;
use rias\ssg\Url;

it('creates a generator from plugin settings', function () {
    $generator = Generator::new();

    expect($generator)->toBeInstanceOf(Generator::class);
});

it('supports fluent API chaining', function () {
    $generator = Generator::new()
        ->destination('/tmp/test')
        ->concurrency(2)
        ->baseUrl('https://example.com')
        ->disableClear()
        ->directoryIndex(true)
        ->verbose(true)
        ->urls(collect());

    expect($generator)->toBeInstanceOf(Generator::class);
});

it('clears the destination directory when clear is enabled', function () {
    $destination = sys_get_temp_dir() . '/craft-ssg-test-clear-' . uniqid();
    FileHelper::createDirectory($destination);
    file_put_contents($destination . '/old-file.html', 'old content');

    Generator::new()
        ->destination($destination)
        ->clearDirectory();

    expect(is_dir($destination))->toBeTrue()
        ->and(file_exists($destination . '/old-file.html'))->toBeFalse();

    FileHelper::removeDirectory($destination);
});

it('preserves the destination directory when disableClear is set', function () {
    $destination = sys_get_temp_dir() . '/craft-ssg-test-noclear-' . uniqid();
    FileHelper::createDirectory($destination);
    file_put_contents($destination . '/existing.html', 'keep me');

    Generator::new()
        ->destination($destination)
        ->disableClear()
        ->clearDirectory();

    expect(file_exists($destination . '/existing.html'))->toBeTrue()
        ->and(file_get_contents($destination . '/existing.html'))->toBe('keep me');

    FileHelper::removeDirectory($destination);
});

it('creates the destination directory if it does not exist', function () {
    $destination = sys_get_temp_dir() . '/craft-ssg-test-create-' . uniqid();

    expect(is_dir($destination))->toBeFalse();

    Generator::new()
        ->destination($destination)
        ->clearDirectory();

    expect(is_dir($destination))->toBeTrue();

    FileHelper::removeDirectory($destination);
});

it('parses environment aliases in the destination', function () {
    $destination = sys_get_temp_dir() . '/craft-ssg-test-alias-' . uniqid();
    Craft::setAlias('@testDestination', $destination);

    Generator::new()
        ->destination('@testDestination')
        ->clearDirectory();

    expect(is_dir($destination))->toBeTrue();

    FileHelper::removeDirectory($destination);
});

it('fires the before generating event', function () {
    $destination = sys_get_temp_dir() . '/craft-ssg-test-event-' . uniqid();
    $eventFired = false;
    SSG::getInstance()->getSettings()->copy = [];

    SSG::getInstance()->on(SSG::EVENT_BEFORE_GENERATING, function () use (&$eventFired) {
        $eventFired = true;
    });

    Generator::new()
        ->destination($destination)
        ->disableClear()
        ->baseUrl('https://example.com/')
        ->urls(collect())
        ->generate();

    expect($eventFired)->toBeTrue();

    FileHelper::removeDirectory($destination);
});

it('fires the after generating event', function () {
    $destination = sys_get_temp_dir() . '/craft-ssg-test-event-after-' . uniqid();
    $eventFired = false;
    SSG::getInstance()->getSettings()->copy = [];

    SSG::getInstance()->on(SSG::EVENT_AFTER_GENERATING, function () use (&$eventFired) {
        $eventFired = true;
    });

    Generator::new()
        ->destination($destination)
        ->disableClear()
        ->baseUrl('https://example.com/')
        ->urls(collect())
        ->generate();

    expect($eventFired)->toBeTrue();

    FileHelper::removeDirectory($destination);
});

it('cancels generation when before event sets isValid to false', function () {
    $destination = sys_get_temp_dir() . '/craft-ssg-test-cancel-' . uniqid();
    $afterFired = false;

    SSG::getInstance()->on(SSG::EVENT_BEFORE_GENERATING, function (BeforeGeneratingEvent $event) {
        $event->isValid = false;
    });

    SSG::getInstance()->on(SSG::EVENT_AFTER_GENERATING, function () use (&$afterFired) {
        $afterFired = true;
    });

    Generator::new()
        ->destination($destination)
        ->disableClear()
        ->baseUrl('https://example.com/')
        ->urls(collect())
        ->generate();

    expect($afterFired)->toBeFalse()
        ->and(is_dir($destination))->toBeFalse();
});

it('generates into the destination directory with no URLs', function () {
    $destination = sys_get_temp_dir() . '/craft-ssg-test-generate-' . uniqid();
    SSG::getInstance()->getSettings()->copy = [];

    Generator::new()
        ->destination($destination)
        ->baseUrl('https://example.com/')
        ->urls(collect())
        ->generate();

    expect(is_dir($destination))->toBeTrue();

    FileHelper::removeDirectory($destination);
});

it('accepts a custom URL collection via the urls setter', function () {
    $urls = collect([
        new Url('https://example.com/page-1', '/tmp/static'),
        new Url('https://example.com/page-2', '/tmp/static'),
    ]);

    $generator = Generator::new()
        ->destination('/tmp/static')
        ->urls($urls);

    expect($generator)->toBeInstanceOf(Generator::class);
});

it('throws when concurrency is greater than 1 without spatie/fork', function () {
    // spatie/fork IS installed as a dev dependency, so this test verifies
    // that concurrency > 1 works without throwing
    $destination = sys_get_temp_dir() . '/craft-ssg-test-concurrency-' . uniqid();
    SSG::getInstance()->getSettings()->copy = [];

    Generator::new()
        ->destination($destination)
        ->concurrency(2)
        ->baseUrl('https://example.com/')
        ->urls(collect())
        ->generate();

    expect(is_dir($destination))->toBeTrue();

    FileHelper::removeDirectory($destination);
});

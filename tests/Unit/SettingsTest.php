<?php

declare(strict_types=1);

use rias\ssg\models\Settings;

it('has expected default values', function () {
    $settings = new Settings();

    expect($settings->baseUrl)->toBe('$DEFAULT_SITE_URL')
        ->and($settings->destination)->toBe('@storage/static')
        ->and($settings->concurrency)->toBe(1)
        ->and($settings->clear)->toBeTrue()
        ->and($settings->directoryIndex)->toBeFalse()
        ->and($settings->copy)->toBe([
            ['@webroot/cpresources', 'cpresources'],
        ]);
});

it('extends the craft base model', function () {
    expect(Settings::class)->toExtend(craft\base\Model::class);
});

it('defines validation rules for required fields', function () {
    $settings = new Settings();
    $rules = $settings->rules();

    $requiredRule = collect($rules)->first(fn ($rule) => in_array('required', $rule, true));

    expect($requiredRule)->not->toBeNull()
        ->and($requiredRule[0])->toContain('baseUrl', 'destination');
});

it('defines validation rules for string max length', function () {
    $settings = new Settings();
    $rules = $settings->rules();

    $stringRule = collect($rules)->first(fn ($rule) => in_array('string', $rule, true));

    expect($stringRule)->not->toBeNull()
        ->and($stringRule[0])->toContain('baseUrl', 'destination')
        ->and($stringRule['max'])->toBe(255);
});

it('allows overriding default settings via properties', function () {
    $settings = new Settings();
    $settings->baseUrl = 'https://example.com';
    $settings->destination = '/var/www/static';
    $settings->concurrency = 4;
    $settings->clear = false;
    $settings->directoryIndex = true;

    expect($settings->baseUrl)->toBe('https://example.com')
        ->and($settings->destination)->toBe('/var/www/static')
        ->and($settings->concurrency)->toBe(4)
        ->and($settings->clear)->toBeFalse()
        ->and($settings->directoryIndex)->toBeTrue();
});

it('allows configuring copy paths', function () {
    $settings = new Settings();
    $settings->copy = [
        ['@webroot/assets', 'assets'],
        ['@webroot/images', 'images'],
    ];

    expect($settings->copy)->toHaveCount(2)
        ->and($settings->copy[0])->toBe(['@webroot/assets', 'assets'])
        ->and($settings->copy[1])->toBe(['@webroot/images', 'images']);
});

<?php

declare(strict_types=1);

use craft\helpers\App;
use rias\ssg\Generator;
use rias\ssg\SSG;

it('has a Craft application available', function () {
    expect(Craft::$app)->not->toBeNull();
});

it('can create a generator', function () {
    $generator = Generator::new();

    expect($generator)->toBeInstanceOf(Generator::class);
});

it('can create a generator with a destination', function () {
    $destination = sys_get_temp_dir() . '/craft-ssg-test';

    $generator = Generator::new()
        ->destination($destination);

    expect($generator)->toBeInstanceOf(Generator::class);
});

it('can create a generator with concurrency', function () {
    $generator = Generator::new()
        ->concurrency(4);

    expect($generator)->toBeInstanceOf(Generator::class);
});

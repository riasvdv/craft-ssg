<?php

declare(strict_types=1);

use craft\helpers\ArrayHelper;
use craft\test\TestSetup;
use rias\ssg\SSG;

/**
 * Create a Craft application for testing without Codeception.
 *
 * This replicates what TestSetup::warmCraft() and createTestCraftObjectConfig()
 * do, but without the CraftTest (Codeception module) dependency.
 */
function warmCraft(): void
{
    $_SERVER['REMOTE_ADDR'] = '1.1.1.1';
    $_SERVER['REMOTE_PORT'] = 654321;

    $craftPath = dirname(__DIR__) . '/vendor/craftcms/cms';
    $srcPath = $craftPath . '/src';

    $configService = TestSetup::createConfigService();

    $config = ArrayHelper::merge(
        [
            'components' => [
                'config' => $configService,
            ],
        ],
        require $srcPath . '/config/app.php',
        require $srcPath . '/config/app.console.php',
        $configService->getConfigFromFile('app'),
        $configService->getConfigFromFile('app.console'),
    );

    $config['vendorPath'] = CRAFT_VENDOR_PATH;

    $config = ArrayHelper::merge($config, [
        'class' => craft\console\Application::class,
        'id' => 'craft-test',
        'env' => 'test',
        'basePath' => $srcPath,
    ]);

    $config['isInstalled'] = false;

    Craft::createObject($config);

    // Register the SSG plugin so SSG::getInstance() works
    $plugin = new SSG('ssg');
    $plugin->init();
}

function tearDownCraft(): void
{
    // Restore PHP's error/exception handlers before Craft teardown,
    // so PHPUnit doesn't flag tests as risky for leaving Yii's handlers.
    restore_error_handler();
    restore_exception_handler();

    TestSetup::tearDownCraft();
}

uses()
    ->beforeEach(fn () => warmCraft())
    ->afterEach(fn () => tearDownCraft())
    ->in('Feature');

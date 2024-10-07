<?php

use craft\web\View;

[$_, $url, $oldBaseUrl, $newBaseUrl, $root, $webroot] = $argv;

$https = parse_url($url, PHP_URL_SCHEME) === 'https';

/**
 * Mock a web server request
 *
 * @see \craft\test\Craft::recreateClient
 */
$_SERVER = array_merge($_SERVER, [
    'HTTPS' => $https ? 1 : 0,
    'REQUEST_URI' => parse_url($url, PHP_URL_PATH),
    'SERVER_NAME' => parse_url($url, PHP_URL_HOST),
    'SERVER_PORT' => $https ? '443' : '80',
    'SCRIPT_FILENAME' => $webroot . '/index.php',
    'SCRIPT_NAME' => '/index.php',
]);

require $root . '/bootstrap.php';

ob_start();

/** @var craft\web\Application $app */
$app = require $root . '/vendor/craftcms/cms/bootstrap/web.php';
Craft::$app->getRequest()->setIsConsoleRequest(false);
Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_SITE);
$app->run();

$contents = ob_get_clean();

if ($newBaseUrl && $oldBaseUrl !== $newBaseUrl) {
    $contents = str_replace($oldBaseUrl, $newBaseUrl, $contents);
}

echo $contents;

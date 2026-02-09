<?php

declare(strict_types=1);

use craft\web\View;

[$_, $url, $siteBaseUrlsJson, $newBaseUrl, $root, $webroot] = $argv;
$siteBaseUrls = json_decode($siteBaseUrlsJson, true);

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

if ($newBaseUrl && $siteBaseUrls) {
    foreach ($siteBaseUrls as $siteBaseUrl) {
        if ($siteBaseUrl === $newBaseUrl) {
            continue;
        }

        // Determine the relative path of this site from the primary site
        $sitePath = parse_url($siteBaseUrl, PHP_URL_PATH) ?? '/';
        $newSiteBaseUrl = rtrim($newBaseUrl, '/') . $sitePath;

        $contents = str_replace($siteBaseUrl, $newSiteBaseUrl, $contents);
    }
}

echo $contents;

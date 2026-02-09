<?php

declare(strict_types=1);

/**
 * SSG config.php
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'ssg.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

use craft\helpers\App;

return [
    '*' => [
        /*
         * This informs the generator where the static site will eventually be hosted.
         * It should be an absolute URL, for example: "https://craftcms.com"
         */
        'baseUrl' => App::env('PRIMARY_SITE_URL'),

        /**
         * This option defines where the static files will be saved.
         */
        'destination' => '@storage/static',

        /**
         * The concurrency at which to generate static pages.
         * If you want to increase this, make sure the
         * spatie/fork composer package is installed.
         */
        'concurrency' => 1,

        /**
         * Use folder-based structure with index.html files.
         * When true: /about generates about/index.html
         * When false: /about generates about.html
         */
        'directoryIndex' => false,

        /**
         * Define a set of directories and files to be copied along
         * with the generated files. For example, you may want to link your CSS,
         * JavaScript, static images, and perhaps any uploaded assets.
         * Each array consists of the "from" in the first index
         * and the "to" in the second index.
         */
        'copy' => [
            ['@webroot/cpresources', 'cpresources'],
            // ['@webroot/build', 'build'],
        ],
    ],
];

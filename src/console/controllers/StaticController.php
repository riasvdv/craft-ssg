<?php

namespace rias\ssg\console\controllers;

use Craft;
use craft\console\Controller;
use craft\helpers\Console;
use rias\ssg\Generator;
use rias\ssg\SSG;
use yii\console\ExitCode;

/**
 * Static site generation for Craft CMS
 */
class StaticController extends Controller
{
    public $defaultAction = 'generate';

    /**
     * @var ?string The destination to generate content to.
     */
    public ?string $destination = null;

    /**
     * @var ?int Speed up site generation by installing spatie/fork and using multiple workers
     */
    public ?int $concurrency = null;

    /**
     * @var ?bool Disable clearing the target folder.
     */
    public ?bool $disableClear = null;

    /**
     * @inheritDoc
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);

        if ($actionID === 'generate') {
            $options[] = 'destination';
            $options[] = 'concurrency';
            $options[] = 'disableClear';
        }

        return $options;
    }

    /**
     * Generate the static site
     * @return int
     */
    public function actionGenerate(): int
    {
        $settings = SSG::getInstance()->getSettings();

        Generator::new()
            ->destination($this->destination ?? $settings->destination ?? '@storage/static')
            ->concurrency($this->concurrency ?? $settings->concurrency)
            ->baseUrl($settings->baseUrl)
            ->disableClear($this->disableClear ?? !$settings->clear)
            ->generate();

        return ExitCode::OK;
    }

    /**
     * Clear the static site contents
     * @return int
     */
    public function actionClear(): int
    {
        Generator::new()
            ->disableClear(false)
            ->clearDirectory();

        Console::output('Cleared the static storage directory.');

        return ExitCode::OK;
    }
}

<?php

declare(strict_types=1);

namespace rias\ssg;

use Composer\InstalledVersions;
use Craft;
use craft\base\Model;
use craft\base\Plugin;
use rias\ssg\models\Settings;

/**
 * ssg plugin
 *
 * @method static SSG getInstance()
 * @method Settings getSettings()
 * @author Rias <hey@rias.be>
 * @copyright Rias
 * @license MIT
 */
class SSG extends Plugin
{
    /**
     * @event SSGEvent The event that is triggered before generating a site.
     */
    public const EVENT_BEFORE_GENERATING = 'beforeGenerating';

    /**
     * @event SSGEvent The event that is triggered after generating a site.
     */
    public const EVENT_AFTER_GENERATING = 'afterGenerating';

    public bool $hasCpSettings = true;

    public function init(): void
    {
        $this->name = 'Static Site Generation';
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        // Get and pre-validate the settings
        $settings = $this->getSettings();
        $settings->validate();

        // Get the settings that are being defined by the config file
        $overrides = Craft::$app->getConfig()->getConfigFromFile(strtolower($this->handle));

        return Craft::$app->view->renderTemplate('ssg/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
            'overrides' => array_keys($overrides),
            'forkInstalled' => InstalledVersions::isInstalled('spatie/fork'),
        ]);
    }
}

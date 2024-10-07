<?php

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

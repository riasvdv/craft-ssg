<img width="100" src="./src/icon.svg">

# Static Site Generation

Static Site Generation for Craft CMS

## Requirements

This plugin requires Craft CMS 5.4.0 or later, and PHP 8.2 or later.

## Installation

You can install this plugin from the Plugin Store or with Composer.

#### From the Plugin Store

Go to the Plugin Store in your project’s Control Panel and search for “ssg”. Then press “Install”.

#### With Composer

Open your terminal and run the following commands:

```bash
# go to the project directory
cd /path/to/my-project.test

# tell Composer to load the plugin
composer require rias/craft-ssg

# tell Craft to install the plugin
./craft plugin/install ssg
```

## Usage

You can configure how SSG generates your static site through the plugin settings or the config file.

Generate your static site by running:

```shell
php craft ssg/static/generate
```

## Events

You can listen to events and add additional functionality like this:

```php
use craft\base\Event;
use rias\ssg\SSG;
use rias\ssg\events\BeforeGeneratingEvent;

Event::on(
    SSG::class,
    SSG::EVENT_BEFORE_GENERATING,
    function (BeforeGeneratingEvent $event) {
        // Your code here
    }
);
```

A list of events can be found below.

### `SSG::EVENT_BEFORE_GENERATING`

This event is fired before generating a site. You can set `->isValid` to `false` on the event to cancel the generation.

### `SSG::EVENT_AFTER_GENERATING`

This event is fired after generating a site.

## Demo

You can find a demo of the [europa museum demo statically generated here](https://craft-ssg.pages.dev/).

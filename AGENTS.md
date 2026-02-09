# AGENTS.md — Craft SSG Plugin

## Project Overview

Craft CMS 5.4+ plugin (PHP 8.2+) that generates static HTML sites. Built on Yii2, uses Laravel Collections, Symfony Process, and optionally Spatie Fork for parallel page generation.

**Namespace:** `rias\ssg`
**Package:** `rias/craft-ssg`

## Commands

```bash
# Code style check (Craft CMS standards via ECS)
composer check-cs

# Auto-fix code style
composer fix-cs

# Static analysis (PHPStan level 4)
composer phpstan

# Plugin CLI commands (run inside a Craft project)
php craft ssg/static/generate --destination=/path --concurrency=4 --disableClear --verbose
php craft ssg/static/clear
```

### Testing

No test suite exists yet. If adding tests, use Craft's recommended setup with `craftcms/phpunit` or Pest.

## Architecture

```
src/
├── console/controllers/     # CLI commands (Yii2 console controllers)
│   └── StaticController.php # generate & clear actions
├── events/                  # Event classes for extensibility
│   ├── AfterGeneratingEvent.php
│   └── BeforeGeneratingEvent.php
├── models/
│   └── Settings.php         # Plugin settings model with validation
├── templates/
│   └── _settings.twig       # CP settings UI
├── config.php               # Default configuration
├── generate-page.php        # Worker script for parallel page generation
├── Generator.php            # Core generation logic (fluent API)
├── SSG.php                  # Main plugin class (extends craft\base\Plugin)
└── Url.php                  # URL value object
```

**Key patterns:**
- `Generator` uses a fluent builder: `Generator::new()->destination(...)->concurrency(...)->generate()`
- Parallel processing via Spatie Fork spawning `generate-page.php` as worker processes
- Event system: `EVENT_BEFORE_GENERATING` (cancelable) and `EVENT_AFTER_GENERATING`
- Errors are collected into a `Collection`, not thrown mid-process

## Code Style

### Enforced by tooling
- **ECS** with `craft\ecs\SetList::CRAFT_CMS_4` — run `composer check-cs` before committing
- **PHPStan** level 4 with `craftcms/phpstan` rules — run `composer phpstan`

### Strict types — always

Every PHP file starts with:
```php
<?php

declare(strict_types=1);
```

### Imports

- One class per `use` statement, no aliases unless name collisions
- Grouped loosely: core PHP classes, then Craft/Yii, then plugin namespace, then third-party
- Alphabetical within groups

```php
use Closure;
use Craft;
use craft\elements\Entry;
use craft\helpers\Console;
use Illuminate\Support\Collection;
use rias\ssg\events\BeforeGeneratingEvent;
use Spatie\Fork\Fork;
```

### Naming Conventions

| Element       | Convention          | Example                              |
|---------------|---------------------|--------------------------------------|
| Namespace     | lowercase           | `rias\ssg\models`                    |
| Class         | PascalCase          | `StaticController`, `Generator`      |
| Method        | camelCase           | `generate()`, `clearDirectory()`     |
| Property      | camelCase           | `$concurrency`, `$baseUrl`           |
| Constant      | SCREAMING_SNAKE     | `EVENT_BEFORE_GENERATING`            |
| Action method | `action` + PascalCase | `actionGenerate()` (Yii2 convention) |

### Type Hints

- **All** method parameters and return types must be typed
- Use nullable types (`?string`) over union with null
- Use constructor property promotion for simple classes
- PHPDoc only when types can't express it (generics, magic methods, arrays of specific types)

```php
// Constructor promotion
public function __construct(
    private string $url,
    private string $destination,
) {
}

// Fluent setters return self
public function concurrency(int $concurrency): self
{
    $this->concurrency = $concurrency;
    return $this;
}
```

### Error Handling

- Collect errors into `Collection` for batch reporting — don't throw mid-iteration
- Use `match(true)` with `str_contains()` for pattern-based error classification
- Throw exceptions only for unrecoverable setup errors (missing dependencies, invalid config)
- Console output uses emoji prefixes: `✅` success, `❌` error, `ℹ️` info

```php
// Collect errors
$this->errors = collect($results)->flatten();
$this->errors->each(fn(string $error) => Console::output("❌  {$error}"));

// Unrecoverable
throw new Exception("You must install spatie/fork to use concurrency > 1.");
```

### PHP Features in Use

- PHP 8.2+ required
- Constructor property promotion
- Named arguments: `->after(parent: function() { ... })`
- `match` expressions for branching
- Arrow functions (`fn() =>`) for short closures
- Laravel Collections (`collect()`, `->map()`, `->filter()`, `->unique()`)
- Craft helpers: `App::parseEnv()`, `App::env()`, `Console::output()`

## Configuration

Plugin settings are in `src/models/Settings.php` with these defaults (from `src/config.php`):

```php
'destination' => '@storage/static'  // Supports Craft aliases
'concurrency' => 1
'baseUrl'     => App::env('PRIMARY_SITE_URL')
'clear'       => true
```

Environment variables are parsed via `App::parseEnv()`.

## Dependencies

**Runtime:** `craftcms/cms ^5.4.0`, `php >=8.2`
**Dev:** `craftcms/ecs`, `craftcms/phpstan`, `spatie/fork ^1.2`
**Implicit (via Craft):** Laravel Collections, Symfony Process, Yii2

## CI/CD

Only a release workflow exists (`.github/workflows/create-release.yml`) — triggered by the Craft Plugin Store on new releases.

No CI for code quality. Always run locally before pushing:
```bash
composer check-cs && composer phpstan
```

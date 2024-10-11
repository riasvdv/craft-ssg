<?php

namespace rias\ssg;

use Closure;
use Composer\InstalledVersions;
use Craft;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\User;
use craft\helpers\App;
use craft\helpers\Console;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use Illuminate\Support\Collection;
use rias\ssg\events\AfterGeneratingEvent;
use rias\ssg\events\BeforeGeneratingEvent;
use Spatie\Fork\Fork;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use yii\console\Exception;

class Generator
{
    private int $concurrency = 1;

    private ?string $baseUrl = null;

    private bool $disableClear = false;

    private string $destination = '@storage/static';

    private string $phpExecutable;

    private int $count = 0;

    private Collection $errors;

    private function __construct()
    {
        $this->phpExecutable = (new PhpExecutableFinder())->find();
        $this->errors = collect();
    }

    public static function new(): self
    {
        $settings = SSG::getInstance()->getSettings();

        return (new self())
            ->destination($settings->destination)
            ->concurrency($settings->concurrency)
            ->baseUrl($settings->baseUrl)
            ->disableClear(!$settings->clear);
    }

    public function concurrency(int $concurrency): self
    {
        $this->concurrency = $concurrency;

        return $this;
    }

    public function baseUrl(string $baseUrl): self
    {
        $this->baseUrl = StringHelper::ensureRight($baseUrl, '/');

        return $this;
    }

    public function disableClear(bool $disableClear = true): self
    {
        $this->disableClear = $disableClear;

        return $this;
    }

    public function destination(string $destination): self
    {
        $this->destination = $destination;

        return $this;
    }

    public function generate(): void
    {
        if ($this->concurrency > 1 && !InstalledVersions::isInstalled('spatie/fork')) {
            throw new Exception("You must install **spatie/fork** to use concurrency more than 1.");
        }

        if (SSG::getInstance()->hasEventHandlers(SSG::EVENT_BEFORE_GENERATING)) {
            $event = new BeforeGeneratingEvent();
            SSG::getInstance()->trigger(SSG::EVENT_BEFORE_GENERATING, $event);
            if (! $event->isValid) {
                Console::outputWarning("Generating was cancelled by beforeGenerating event.");

                return;
            }
        }

        $this->destination = App::parseEnv($this->destination);
        $this->baseUrl = App::parseEnv($this->baseUrl);

        $this->clearDirectory()
            ->createContent()
            ->copyFiles()
            ->outputSummary();

        if (SSG::getInstance()->hasEventHandlers(SSG::EVENT_AFTER_GENERATING)) {
            SSG::getInstance()->trigger(SSG::EVENT_AFTER_GENERATING, new AfterGeneratingEvent());
        }
    }

    public function clearDirectory(): self
    {
        if (!$this->disableClear) {
            FileHelper::removeDirectory($this->destination);
        }

        FileHelper::createDirectory($this->destination);

        return $this;
    }

    private function createContent(): self
    {
        Console::output("ℹ️ Gathering content...");

        $urls = $this->getUrls()->map(function(Url $url) {
            return function() use ($url) {
                return $this->generatePage($url);
            };
        });
        $total = $urls->count();
        $count = 1;
        $prefix = "ℹ️ Generating {$total} content files: ";

        Console::startProgress($count, $total, $prefix);

        $this->errors = (match (true) {
            InstalledVersions::isInstalled('spatie/fork') => collect((new Fork())
                ->concurrent($this->concurrency)
                ->after(parent: function() use (&$count, $total, $prefix) {
                    Console::updateProgress($count++, $total, $prefix);
                })
                ->run(...$urls)),
            default => collect($urls)->map(function(Closure $closure) use ($prefix, $total, &$count) {
                Console::updateProgress($count++, $total, $prefix);

                return $closure();
            }
            )
        })->flatten();

        Console::endProgress();

        $this->count = $urls->count() - $this->errors->count();

        Console::output("✅  Generated {$this->count} content files");

        $this->errors->each(fn(string $error) => Console::output("❌  {$error}"));

        return $this;
    }

    private function copyFiles(): self
    {
        $files = SSG::getInstance()->getSettings()->copy ?? [];

        foreach ($files as $item) {
            [$source, $dest] = $item;

            $sourcePath = App::parseEnv($source);
            $dest = App::parseEnv($dest);

            $destPath = $this->destination . '/' . $dest;

            if (is_file($sourcePath)) {
                copy($sourcePath, $destPath);
            } else {
                FileHelper::copyDirectory($sourcePath, $destPath);
            }

            Console::output("✅  $source copied to $dest");
        }

        return $this;
    }

    private function outputSummary(): void
    {
        Console::output('');
        Console::output('✅  Static site generated into ' . $this->destination);

        if ($this->errors->count()) {
            Console::outputWarning("⚠️  {$this->errors->count()}/{$this->count} pages not generated");
        }
    }

    private function getUrls(): Collection
    {
        return collect()
            ->merge(collect(Entry::findAll())->map(fn(Entry $entry) => $entry->getUrl()))
            ->merge(collect(Category::findAll())->map(fn(Category $category) => $category->getUrl()))
            ->merge(collect(User::findAll())->map(fn(User $user) => $user->getUrl()))
            ->filter()
            ->map(fn(string $url) => new Url($url, $this->destination));
    }

    private function generatePage(Url $url): array
    {
        $process = new Process([
            $this->phpExecutable,
            __DIR__ . '/generate-page.php',
            (string) $url,
            Craft::$app->getSites()->getPrimarySite()->baseUrl,
            $this->baseUrl ?? Craft::$app->getSites()->getPrimarySite()->baseUrl,
            Craft::getAlias('@root'),
            Craft::getAlias('@webroot'),
        ]);

        $process->run();
        $content = $process->getOutput();
        $errors = [];

        if ($process->isSuccessful()) {
            FileHelper::writeToFile($url->path(), $content);

            return array_merge(
                $errors,
                $this->generatePaginatedUrls($url, $content)
            );
        }

        $message = match (true) {
            str_contains($content, 'yii\web\NotFoundHttpException: Template not found') => '404 - Template not found',
            str_contains($content, 'yii\web\NotFoundHttpException') => '404 - Page not found',
            str_contains($content, 'Twig\Error\RuntimeError') => '500 - Twig Runtime Error',
            default => 'Unknown error, visit the page to see any exceptions',
        };

        $errors[] = "Error: {$url}: {$message}";

        return $errors;
    }

    private function generatePaginatedUrls(Url $url, string $content): array
    {
        $pathParam = Craft::$app->getConfig()->getGeneral()->pathParam;
        $pathParam = StringHelper::ensureLeft($pathParam, '/');

        /** Match a string like /p10 to indicate the 10th page from a collection */
        $pattern = "#(({$pathParam})\d+)#";

        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        return collect($matches)
            ->filter(fn(array $match) => isset($match[1]))
            ->filter(fn(array $match) => $match[1] !== "{$pathParam}0")
            ->flatMap(function(array $match) use ($url, $pattern) {
                preg_match($pattern, $url->lastSegment(), $segmentMatches);

                $nextUrl = !empty($segmentMatches)
                    ? preg_replace("#{$match[2]}\d+#", $match[1], $url)
                    : $url . $match[1];

                $nextUrl = new Url($nextUrl, $this->destination);

                if (!file_exists($nextUrl->path())) {
                    return $this->generatePage($nextUrl);
                }

                return [];
            })->all();
    }
}

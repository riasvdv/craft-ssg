<?php

declare(strict_types=1);

namespace rias\ssg\models;

use craft\base\Model;

class Settings extends Model
{
    public string $baseUrl = '$DEFAULT_SITE_URL';

    public string $destination = '@storage/static';

    public int $concurrency = 1;

    public bool $clear = true;

    public bool $directoryIndex = false;

    /** @var array<int, string[]>|null */
    public ?array $copy = [
        ['@webroot/cpresources', 'cpresources'],
    ];

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return [
            [['baseUrl', 'destination'], 'required'],
            [['baseUrl', 'destination'], 'string', 'max' => 255],
        ];
    }
}

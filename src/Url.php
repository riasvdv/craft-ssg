<?php

declare(strict_types=1);

namespace rias\ssg;

use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;

class Url
{
    public function __construct(
        private string $url,
        private string $destination,
        private bool $directoryIndex = false,
    ) {
    }

    public function path(): string
    {
        $path = UrlHelper::rootRelativeUrl($this->url);

        if ($this->directoryIndex) {
            $path = StringHelper::ensureRight($path, '/') . 'index';
        } elseif (str_ends_with($path, '/')) {
            $path .= 'index';
        }

        return StringHelper::ensureRight($this->destination, '/') . $path . '.html';
    }

    public function lastSegment(): string
    {
        return StringHelper::ensureLeft(last(explode('/', $this->url)), '/');
    }

    public function __toString(): string
    {
        return $this->url;
    }
}

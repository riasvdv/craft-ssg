<?php

namespace rias\ssg;

use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;

class Url
{
    public function __construct(
        private string $url,
        private string $destination,
    ) {
    }

    public function path(): string
    {
        $path = UrlHelper::rootRelativeUrl($this->url);

        if ($path === '/') {
            $path = '/index';
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

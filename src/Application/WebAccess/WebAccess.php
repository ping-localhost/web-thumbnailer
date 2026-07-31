<?php

declare(strict_types=1);

namespace WebThumbnailer\Application\WebAccess;

/**
 * GET an HTTP URL (or local path) and return headers + body.
 */
interface WebAccess
{
    /**
     * @param string   $url      URL to get (http://...) or absolute local path
     * @param int|null $timeout  network timeout (seconds)
     * @param int|null $maxBytes maximum downloaded bytes
     *
     * @return array{0: array<int|string, mixed>, 1: string|false}
     *         [0] = headers (status line at index 0), [1] = body or false
     */
    public function getContent(string $url, ?int $timeout = null, ?int $maxBytes = null): array;
}

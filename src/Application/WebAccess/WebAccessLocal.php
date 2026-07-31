<?php

declare(strict_types=1);

namespace WebThumbnailer\Application\WebAccess;

/**
 * Read local files for tests / filesystem paths.
 */
class WebAccessLocal implements WebAccess
{
    #[\Override]
    public function getContent(string $url, ?int $timeout = null, ?int $maxBytes = null): array
    {
        unset($timeout, $maxBytes);

        $content = @file_get_contents($url);
        if ($content === false) {
            return [[0 => 'HTTP/1.1 404'], false];
        }

        return [['HTTP/1.1 200'], $content];
    }
}

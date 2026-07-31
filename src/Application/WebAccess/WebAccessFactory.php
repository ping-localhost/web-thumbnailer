<?php

declare(strict_types=1);

namespace WebThumbnailer\Application\WebAccess;

/**
 * Create WebAccess instances for local paths or remote URLs.
 */
class WebAccessFactory
{
    /**
     * @param string|null $url URL or absolute filesystem path
     */
    public static function getWebAccess(?string $url = null): WebAccess
    {
        if ($url !== null && $url !== '' && $url[0] === '/') {
            return new WebAccessLocal();
        }

        return new WebAccessHttpClient();
    }
}

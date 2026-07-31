<?php

declare(strict_types=1);

namespace WebThumbnailer\Finder;

use WebThumbnailer\Application\ConfigManager;
use WebThumbnailer\Application\WebAccess\WebAccess;
use WebThumbnailer\Application\WebAccess\WebAccessFactory;
use WebThumbnailer\Utils\ImageUtils;
use WebThumbnailer\Utils\UrlUtils;

/**
 * This finder isn't linked to any domain.
 * It will return the resource if it is an image (by extension, or by content).
 * Otherwise, it'll try to retrieve an OpenGraph resource.
 */
class DefaultFinder extends FinderCommon
{
    /** @var WebAccess instance. */
    protected $webAccess;

    /**
     * @inheritdoc
     * @param mixed[]|null $rules   All existing rules loaded from JSON files.
     * @param mixed[]|null $options Options provided by the user to retrieve a thumbnail.
     */
    public function __construct(string $domain, string $url, ?array $rules, ?array $options)
    {
        $this->webAccess = WebAccessFactory::getWebAccess($url);
        $this->url = $url;
        $this->domain = $domain;
    }

    /**
     * Generic finder.
     *
     * @inheritdoc
     */
    public function find()
    {
        if (ImageUtils::isImageExtension(UrlUtils::getUrlFileExtension($this->url))) {
            return $this->url;
        }

        list($headers, $content) = $this->webAccess->getContent(
            $this->url,
            (int) ConfigManager::get('settings.default.timeout', 30),
            (int) ConfigManager::get('settings.default.max_img_dl', 16777216)
        );

        if (!empty($content) && ImageUtils::isImageString($content)) {
            return $this->url;
        }

        $contentType = $this->headerValue($headers, 'content-type');
        if (
            $contentType !== ''
            && str_contains(strtolower($contentType), 'image/')
            && !str_contains(strtolower($contentType), 'application/octet-stream')
        ) {
            return $this->url;
        }

        if (!empty($headers) && strpos((string) $headers[0], '200') === false) {
            return false;
        }

        return !empty($content) ? static::extractMetaTag($content) : false;
    }

    /**
     * @param array<int|string, mixed> $headers
     */
    private function headerValue(array $headers, string $name): string
    {
        foreach ($headers as $key => $value) {
            if (!is_string($key) || strcasecmp($key, $name) !== 0) {
                continue;
            }

            return is_array($value) ? (string) ($value[0] ?? '') : (string) $value;
        }

        return '';
    }

    /**
     * Applies the regexp on the HTML $content to extract the thumb URL.
     *
     * Supports OpenGraph structured image properties (og:image:url / og:image:secure_url)
     * as well as the root og:image. See https://ogp.me/#structured
     *
     * @param string $content Downloaded HTML content
     *
     * @return string|false Extracted thumb URL or false if not found.
     */
    public static function extractMetaTag(string $content)
    {
        $propertiesKey = ['property', 'name', 'itemprop'];
        $properties = implode('|', $propertiesKey);

        // Prefer HTTPS when available, then root og:image, then og:image:url.
        foreach (['og:image:secure_url', 'og:image', 'og:image:url'] as $prop) {
            $quoted = preg_quote($prop, '#');
            // Exact property match — og:image must not swallow og:image:width etc.
            $ogRegex = '#<meta[^>]+(?:' . $properties . ')=["\']?' . $quoted
                . '["\'\s][^>]*content=["\']?(.*?)["\'\s>]#i';
            $ogRegexReverse = '#<meta[^>]+content=["\']?(.*?)["\'\s][^>]+(?:' . $properties
                . ')=["\']?' . $quoted . '["\'\s/>]#i';

            if (
                preg_match($ogRegex, $content, $matches) > 0
                || preg_match($ogRegexReverse, $content, $matches) > 0
            ) {
                return $matches[1];
            }
        }

        return false;
    }

    /** @inheritdoc */
    public function isHotlinkAllowed(): bool
    {
        return true;
    }

    /** @inheritdoc */
    public function checkRules(?array $rules): bool
    {
        return true;
    }

    /** @inheritdoc */
    public function loadRules(?array $rules): void
    {
    }

    /** @inheritdoc */
    public function getName(): string
    {
        return 'default';
    }
}

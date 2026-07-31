<?php

declare(strict_types=1);

namespace WebThumbnailer\Finder;

use WebThumbnailer\Application\ConfigManager;
use WebThumbnailer\Application\WebAccess\WebAccess;
use WebThumbnailer\Application\WebAccess\WebAccessFactory;
use WebThumbnailer\Exception\BadRulesException;
use WebThumbnailer\Utils\FinderUtils;

/**
 * Generic Finder using regex rules on remote web content.
 * It will use regex rules to resolve a thumbnail in web a page.
 *
 * Mandatory rules:
 *   - image_regex
 *   - thumbnail_url
 *
 * Example:
 *   1. `http://domain.tld/page` content will be downloaded.
 *   2. `image_regex` will be apply on the content
 *   3. Matches will be use to generate `thumbnail_url`.
 */
class QueryRegexFinder extends FinderCommon
{
    /** @var WebAccess instance. */
    protected $webAccess;

    /** @var string thumbnail_url rule. */
    protected $thumbnailUrlFormat;

    /** @var string Regex to apply on provided URL. */
    protected $urlRegex;

    /**
     * @inheritdoc
     * @param mixed[]|null $rules   All existing rules loaded from JSON files.
     * @param mixed[]|null $options Options provided by the user to retrieve a thumbnail.
     *
     * @throws BadRulesException
     */
    public function __construct(string $domain, string $url, ?array $rules, ?array $options)
    {
        $this->webAccess = WebAccessFactory::getWebAccess($url);
        $this->url = $url;
        $this->domain = $domain;
        $this->loadRules($rules);
        $this->finderOptions = $options;
    }

    /**
     * This finder downloads target URL page, and apply the regex given in rules on its content
     * to extract the thumbnail image.
     * The thumb URL must include ${number} to be replaced from the regex match.
     * Also replace eventual URL options.
     *
     * @inheritdoc
     *
     * @throws BadRulesException
     */
    public function find()
    {
        list($headers, $content) = $this->webAccess->getContent(
            $this->url,
            (int) ConfigManager::get('settings.default.timeout', 30),
            (int) ConfigManager::get('settings.default.max_img_dl', 16777216)
        );
        if (
            empty($content)
            || empty($headers)
            || strpos((string) $headers[0], '200') === false
        ) {
            return false;
        }

        return $this->extractThumbContent($content);
    }

    /**
     * @param string $content to extract thumb from
     *
     * @return string|false Thumbnail URL or false if not found
     *
     * @throws BadRulesException
     */
    public function extractThumbContent(string $content)
    {
        $thumbnailUrl = $this->thumbnailUrlFormat;
        if (preg_match($this->urlRegex, $content, $matches) !== 0) {
            $total = count($matches);
            for ($i = 1; $i < $total; $i++) {
                $thumbnailUrl = str_replace('${' . $i . '}', $matches[$i], $thumbnailUrl);
            }

            // Match only options (not ${number})
            if (preg_match_all('/\${((?!\d)\w+?)}/', $thumbnailUrl, $optionsMatch, PREG_PATTERN_ORDER)) {
                foreach ($optionsMatch[1] as $value) {
                    $thumbnailUrl = $this->replaceOption($thumbnailUrl, $value);
                }
            }
            return $thumbnailUrl;
        }

        return false;
    }

    /** @inheritdoc */
    public function checkRules(?array $rules): bool
    {
        if (count($rules ?? []) > 0 && !FinderUtils::checkMandatoryRules($rules, ['image_regex', 'thumbnail_url'])) {
            throw new BadRulesException();
        }

        return true;
    }

    /**
     * @inheritdoc
     *
     * @throws BadRulesException
     */
    public function loadRules(?array $rules): void
    {
        $this->checkRules($rules);
        $this->urlRegex = FinderUtils::buildRegex($rules['image_regex'], 'im');
        $this->thumbnailUrlFormat = $rules['thumbnail_url'];
    }

    /** @inheritdoc */
    public function getName(): string
    {
        return 'Query Regex';
    }
}

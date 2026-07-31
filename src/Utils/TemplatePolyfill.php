<?php

declare(strict_types=1);

namespace WebThumbnailer\Utils;

/**
 * Tiny {placeholder} renderer for .htaccess templates.
 * Replaces the old phpunit/php-text-template production dependency.
 */
class TemplatePolyfill
{
    /**
     * @param array<string, string> $values
     */
    public static function render(string $file, array $values): string
    {
        $template = file_get_contents($file);
        if ($template === false) {
            throw new \RuntimeException(sprintf('Unable to read template "%s"', $file));
        }

        $replace = [];
        foreach ($values as $key => $value) {
            $replace['{' . $key . '}'] = $value;
        }

        return strtr($template, $replace);
    }
}

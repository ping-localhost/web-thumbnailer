# Web Thumbnailer

PHP library that resolves a thumbnail URL for almost any link — site-specific rules (Imgur, YouTube, Instagram, Flickr, …), [Open Graph](https://ogp.me/) metadata (including structured `og:image:*` properties), or a direct image URL.

Fork of [ArthurHoaro/web-thumbnailer](https://github.com/ArthurHoaro/web-thumbnailer), maintained for PHP 8.4+ and modern tooling.

## Requirements

- PHP **8.4+**
- Extensions: `gd`, `json`, `mbstring` (and `curl` recommended for Symfony HttpClient)
- `symfony/http-client` ^7 or ^8

## Installation

```bash
composer require ping-localhost/web-thumbnailer
```

If Packagist is not used yet, add the VCS repository:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/ping-localhost/web-thumbnailer.git"
        }
    ],
    "require": {
        "ping-localhost/web-thumbnailer": "dev-master"
    }
}
```

The package `replace`s `arthurhoaro/web-thumbnailer`, so existing code using the `WebThumbnailer\` namespace keeps working.

## Features

- Site rules: Imgur, Flickr, YouTube, Instagram (post `/media/` hotlink), XKCD, Giphy, and more (`src/resources/rules.json`)
- Open Graph `og:image`, `og:image:url`, and `og:image:secure_url`
- Direct image URLs (by extension or content type)
- Download mode with local cache, resize/crop; or hotlink / hotlink-strict

## Usage

```php
<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

use WebThumbnailer\WebThumbnailer;

$wt = new WebThumbnailer();

// Basic — download + cache (default)
$thumb = $wt->thumbnail('https://www.youtube.com/watch?v=dQw4w9WgXcQ');

// Sized + cropped
$thumb2 = $wt
    ->maxHeight(180)
    ->maxWidth(320)
    ->crop(true)
    ->thumbnail('https://i.imgur.com/abc123.jpg');

// Hotlink when the provider supports it (no local download)
$hot = $wt
    ->modeHotlink()
    ->thumbnail('https://www.instagram.com/p/BL3FRY7F0DJ/');
// → https://www.instagram.com/p/BL3FRY7F0DJ/media/?size=l

// false when nothing can be resolved (unless debug is on)
$wt->thumbnail('not-a-url');
```

Array config overrides fluent helpers for a single call:

```php
$wt->thumbnail('https://github.com/ping-localhost', [
    WebThumbnailer::MAX_HEIGHT => 320,
    WebThumbnailer::MAX_WIDTH => 320,
    WebThumbnailer::CROP => true,
]);
```

## Download modes

Only one mode is active at a time:

| Mode | Helper | Behaviour |
|------|--------|-----------|
| Download (default) | `modeDownload()` | Fetch, resize, store under cache |
| Hotlink | `modeHotlink()` | Prefer provider hotlink URL; download if not allowed |
| Hotlink strict | `modeHotlinkStrict()` | Hotlink only; fail otherwise |

Hotlinked images are **not** resized by this library.

## Image size & crop

In download mode:

- Max width and/or height resize to the first limit hit
- Crop requires **both** max width and max height (center crop)
- Providers with size APIs (Imgur, Flickr, …) pick the smallest matching remote size

```php
$wt->maxHeight(180)->maxWidth(320)->crop(true);
// or
$conf = [
    WebThumbnailer::MAX_HEIGHT => 180,
    WebThumbnailer::MAX_WIDTH => 320,
    WebThumbnailer::CROP => true,
];
```

## Resize mode

- `resample()` / `WebThumbnailer::RESAMPLE` — `imagecopyresampled` (default, better quality)
- `resize()` / `WebThumbnailer::RESIZE` — `imagecopyresized` (faster)

## Other options

| Option | Helper | Meaning |
|--------|--------|---------|
| `NOCACHE` | `noCache(true)` | Bypass cache |
| `DEBUG` | `debug(true)` | Throw instead of returning `false` |
| `VERBOSE` | `verbose(true)` | Log failures |
| `DOWNLOAD_TIMEOUT` | `downloadTimeout(30)` | Seconds |
| `DOWNLOAD_MAX_SIZE` | `downloadMaxSize(4194304)` | Bytes |

## Settings file

Defaults live in `src/resources/settings.json`. Override with:

```php
use WebThumbnailer\Application\ConfigManager;

ConfigManager::addFile('conf/mysettings.json');
```

Useful keys: `default.download_mode`, `default.timeout`, `default.max_img_dl`, `default.max_width`, `default.max_height`, `default.cache_duration`, `path.cache`, `apache_version` (`2.2` / `2.4`).

Cache paths are relative to the process entry point unless you set an absolute `path.cache`.

## Development

```bash
composer install
composer check   # phpcs + phpstan + phpunit
```

Individual scripts: `composer cs`, `composer cs-fix`, `composer phpstan`, `composer test`.

## Contributing

Add domains in `src/resources/rules.json` with an existing Finder, or implement a new Finder under `src/Finder/`. Please open an issue for bugs.

## License

MIT — see [LICENSE.md](LICENSE.md). Original work © Arthur Hoaro; this fork © contributors to [ping-localhost/web-thumbnailer](https://github.com/ping-localhost/web-thumbnailer).

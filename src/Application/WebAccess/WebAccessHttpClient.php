<?php

declare(strict_types=1);

namespace WebThumbnailer\Application\WebAccess;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use WebThumbnailer\Application\ConfigManager;

/**
 * Remote downloads via Symfony HttpClient (curl or native transport).
 */
class WebAccessHttpClient implements WebAccess
{
    public function __construct(private readonly ?HttpClientInterface $client = null)
    {
    }

    #[\Override]
    public function getContent(string $url, ?int $timeout = null, ?int $maxBytes = null): array
    {
        $timeout ??= (int) ConfigManager::get('settings.default.timeout', 30);
        $maxBytes ??= (int) ConfigManager::get('settings.default.max_img_dl', 16777216);

        $locale = setlocale(LC_COLLATE, '0') ?: 'en';
        $acceptLanguage = substr($locale, 0, 2) . ',en-US;q=0.7,en;q=0.3';
        $userAgent = 'Mozilla/5.0 (X11; Linux x86_64; rv:45.0; WebThumbnailer) Gecko/20100101 Firefox/45.0';

        $client = $this->client ?? HttpClient::create([
            'timeout' => $timeout,
            'max_redirects' => 6,
            'headers' => [
                'User-Agent' => $userAgent,
                'Accept-Language' => $acceptLanguage,
            ],
        ]);

        try {
            $response = $client->request('GET', $url, [
                'timeout' => $timeout,
                'max_duration' => $timeout,
            ]);

            $statusCode = $response->getStatusCode();
            $headers = [0 => sprintf('HTTP/1.1 %d', $statusCode)];
            foreach ($response->getHeaders(false) as $name => $values) {
                // Preserve legacy shape: single value as string, multiples as list
                $headers[$name] = count($values) === 1 ? $values[0] : $values;
            }

            $content = '';
            foreach ($client->stream($response) as $chunk) {
                $content .= $chunk->getContent();
                if (strlen($content) >= $maxBytes) {
                    $content = substr($content, 0, $maxBytes);
                    $response->cancel();
                    break;
                }
            }

            return [$headers, $content];
        } catch (TransportExceptionInterface $e) {
            return [[0 => 'http_client error: ' . $e->getMessage()], false];
        }
    }
}

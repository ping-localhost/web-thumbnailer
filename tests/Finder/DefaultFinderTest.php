<?php

declare(strict_types=1);

namespace WebThumbnailer\Finder;

use WebThumbnailer\TestCase;

class DefaultFinderTest extends TestCase
{
    /**
     * PHP builtin local server URL.
     */
    protected const LOCAL_SERVER = 'http://localhost:8081/';

    /**
     * Test the default finder with URL which match an image (.png).
     */
    public function testDefaultFinderImage(): void
    {
        $url = 'http://domains.tld/image.png';
        $finder = new DefaultFinder('', $url, [], []);
        $this->assertEquals($url, $finder->find());

        $url = 'http://domains.tld/image.JPG';
        $finder = new DefaultFinder('', $url, [], []);
        $this->assertEquals($url, $finder->find());

        $url = 'http://domains.tld/image.svg';
        $finder = new DefaultFinder('', $url, [], []);
        $this->assertEquals($url, $finder->find());
    }

    /**
     * Test the default finder with URL which does NOT match an image.
     */
    public function testDefaultFinderNotImage(): void
    {
        $file = __DIR__ . '/../workdir/nope';
        touch($file);
        $finder = new DefaultFinder('', $file, [], []);
        $this->assertFalse($finder->find());
        @unlink($file);
    }

    /**
     * Test the default finder downloading an image without extension.
     */
    public function testDefautFinderRemoteImage(): void
    {
        $file = __DIR__ . '/../workdir/image';
        // From http://php.net/imagecreatefromstring
        $data = 'iVBORw0KGgoAAAANSUhEUgAAABwAAAASCAMAAAB/2U7WAAAABl'
            . 'BMVEUAAAD///+l2Z/dAAAASUlEQVR4XqWQUQoAIAxC2/0vXZDr'
            . 'EX4IJTRkb7lobNUStXsB0jIXIAMSsQnWlsV+wULF4Avk9fLq2r'
            . '8a5HSE35Q3eO2XP1A1wQkZSgETvDtKdQAAAABJRU5ErkJggg==';
        file_put_contents($file, base64_decode($data));
        $finder = new DefaultFinder('', $file, null, null);
        $this->assertEquals($file, $finder->find());
        @unlink($file);
    }

    /**
     * Test the default finder trying to find an open graph link.
     */
    public function testDefaultFinderOpenGraph(): void
    {
        $url = __DIR__ . '/../resources/default/le-monde.html';
        $expected = 'https://img.lemde.fr/2016/10/21/107/0/1132/566/1440/720/60/0/fe3b107_3522-d2olbw.y93o25u3di.jpg';
        $finder = new DefaultFinder('', $url, null, null);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Structured property only (og:image:url) — https://github.com/ArthurHoaro/web-thumbnailer/issues/25
     */
    public function testExtractMetaTagOgImageUrl(): void
    {
        $html = '<html><head>'
            . '<meta property="og:image:width" content="1200" />'
            . '<meta property="og:image:url" content="https://example.com/structured.jpg?x=1&y=2" />'
            . '<meta property="og:image:height" content="630" />'
            . '</head></html>';

        $this->assertSame(
            'https://example.com/structured.jpg?x=1&y=2',
            DefaultFinder::extractMetaTag($html)
        );
    }

    /**
     * Prefer og:image:secure_url over http og:image.
     */
    public function testExtractMetaTagPrefersSecureUrl(): void
    {
        $html = '<html><head>'
            . '<meta property="og:image" content="http://example.com/img.jpg" />'
            . '<meta property="og:image:secure_url" content="https://secure.example.com/img.jpg" />'
            . '</head></html>';

        $this->assertSame(
            'https://secure.example.com/img.jpg',
            DefaultFinder::extractMetaTag($html)
        );
    }

    /**
     * og:image:width must not be mistaken for an image URL.
     */
    public function testExtractMetaTagIgnoresNonUrlStructuredProps(): void
    {
        $html = '<html><head>'
            . '<meta property="og:image:width" content="1200" />'
            . '<meta property="og:image:height" content="630" />'
            . '<meta property="og:image" content="https://example.com/root.jpg" />'
            . '</head></html>';

        $this->assertSame('https://example.com/root.jpg', DefaultFinder::extractMetaTag($html));
    }

    /**
     * Test the default finder trying to find an open graph link.
     */
    public function testDefaultFinderOpenGraphRemote(): void
    {
        $url = self::LOCAL_SERVER . 'default/le-monde.html';
        $expected = 'https://img.lemde.fr/2016/10/21/107/0/1132/566/1440/720/60/0/fe3b107_3522-d2olbw.y93o25u3di.jpg';
        $finder = new DefaultFinder('', $url, null, null);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Test the default finder trying to find an image mime-type.
     */
    public function testDefaultFinderImageMimetype(): void
    {
        $url = self::LOCAL_SERVER . 'default/image-mimetype.php';
        $expected = $url;
        $finder = new DefaultFinder('', $url, null, null);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Test the default finder finding a non 200 status code.
     */
    public function testDefaultFinderStatusError(): void
    {
        $url = self::LOCAL_SERVER . 'default/status-ko.php';
        $finder = new DefaultFinder('', $url, null, null);
        $this->assertFalse($finder->find());
    }

    /**
     * Test getName().
     */
    public function testGetName(): void
    {
        $finder = new DefaultFinder('', '', [], []);
        $this->assertEquals('default', $finder->getName());
    }
}

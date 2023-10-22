<?php

namespace Photobooth\Tests\Unit\Utility;

use Photobooth\Utility\PathUtility;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PathUtilityTest extends TestCase
{
    public function testGetRootPath(): void
    {
        $expected = realpath(__DIR__ . '/../../../');
        $this->assertSame($expected, PathUtility::getRootPath());
    }

    #[DataProvider('providerGetAbsolutePath')]
    public function testGetAbsolutePath(string $path, string $expected): void
    {
        $this->assertSame($expected, PathUtility::getAbsolutePath($path));
    }

    public static function providerGetAbsolutePath(): array
    {
        $rootPath = realpath(__DIR__ . '/../../../');

        return [
            ['data', $rootPath . DIRECTORY_SEPARATOR . 'data'],
            ['template/classic.template.php', $rootPath . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'classic.template.php'],
            ['/images', $rootPath . DIRECTORY_SEPARATOR . 'images'],
            ['/invalid-path', $rootPath . DIRECTORY_SEPARATOR . 'invalid-path']
        ];
    }

    #[DataProvider('providerIsUrl')]
    public function testIsUrl(string $url, bool $expected): void
    {
        $this->assertSame($expected, PathUtility::isUrl($url));
    }

    public static function providerIsUrl(): array
    {
        return [
            ['https://example.com', true],
            ['http://example.com', true],
            ['ftp://example.com', false],
            ['localhost', false],
        ];
    }

    #[DataProvider('providerGetPublicPath')]
    public function testGetPublicPath(string $path, string $expected): void
    {
        $this->assertSame($expected, PathUtility::getPublicPath($path));
    }

    public static function providerGetPublicPath(): array
    {
        $rootPath = realpath(__DIR__ . '/../../../');

        return [
            ['https://example.com', 'https://example.com'],
            ['http://example.com', 'http://example.com'],
            ['localhost', '/localhost'],
            ['data/images/20231001_094317.jpg', '/data/images/20231001_094317.jpg'],
            [$rootPath . '/data/images/20231001_094317.jpg', '/data/images/20231001_094317.jpg'],
            [$rootPath . '/resources/css/framework.css', '/resources/css/framework.css'],
        ];
    }

    #[DataProvider('providerGetPublicPathAbsolute')]
    public function testGetPublicPathAbsolute(string $path, string $expected): void
    {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_NAME'] = 'photoboothproject.github.io';
        $this->assertSame($expected, PathUtility::getPublicPath($path, true));
    }

    public static function providerGetPublicPathAbsolute(): array
    {
        $rootPath = realpath(__DIR__ . '/../../../');

        return [
            ['https://example.com', 'https://example.com'],
            ['http://example.com', 'http://example.com'],
            ['localhost', 'https://photoboothproject.github.io/localhost'],
            ['data/images/20231001_094317.jpg', 'https://photoboothproject.github.io/data/images/20231001_094317.jpg'],
            [$rootPath . '/data/images/20231001_094317.jpg', 'https://photoboothproject.github.io/data/images/20231001_094317.jpg'],
            [$rootPath . '/resources/css/framework.css', 'https://photoboothproject.github.io/resources/css/framework.css'],
        ];
    }

    #[DataProvider('providerGetBaseUrl')]
    public function testGetBaseUrl(string $documentRoot, string $expected): void
    {
        $_SERVER['DOCUMENT_ROOT'] = $documentRoot;
        $this->assertSame($expected, PathUtility::getBaseUrl());
    }

    public static function providerGetBaseUrl(): array
    {
        return [
            [realpath(__DIR__ . '/../../../../'), '/' . basename(realpath(__DIR__ . '/../../../')) . '/'],
            [realpath(__DIR__ . '/../../../'), '/'],
        ];
    }

    #[DataProvider('providerFixFilePath')]
    public function testFixFilePath(string $path, string $expected): void
    {
        $this->assertSame($expected, PathUtility::fixFilePath($path));
    }

    public static function providerFixFilePath(): array
    {
        return [
            ['/var/www/html/', '/var/www/html/'],
            ['//var//www//html//', '/var/www/html/'],
            ['/var/www//html/', '/var/www/html/'],
            ['C:\\xampp\\htdocs\\photobooth\\data\\tmp', 'C:/xampp/htdocs/photobooth/data/tmp'],
        ];
    }
}

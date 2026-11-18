<?php

namespace Photobooth\Tests\Unit\Service;

use Photobooth\Configuration\PhotoboothConfiguration;
use Photobooth\Service\ConfigurationService;
use Photobooth\Service\RemoteStorageService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationMigrationTest extends TestCase
{
    private function migrate(array $ftp): array
    {
        $service = new class () extends ConfigurationService {
            public function __construct()
            {
            }

            public function migrate(array $config): array
            {
                return $this->processMigration($config);
            }
        };
        $migrated = $service->migrate(['ftp' => $ftp]);
        self::assertSame($migrated, $service->migrate($migrated));

        return (new Processor())->processConfiguration(new PhotoboothConfiguration(), [$migrated])['ftp'];
    }

    public function testLegacyConnectionPathsAndCustomQrUrlArePreserved(): void
    {
        $ftp = $this->migrate([
            'enabled' => true,
            'baseURL' => 'ftp.example.org',
            'baseFolder' => '/www/',
            'folder' => 'Party Photos',
            'title' => 'My Event',
            'username' => 'user',
            'password' => 'secret',
            'useForQr' => true,
            'website' => 'https://example.org',
            'urlTemplate' => '%website%/custom/%folder%/%title%',
        ]);
        self::assertSame('ftp.example.org', $ftp['host']);
        self::assertSame('user', $ftp['username']);
        self::assertSame('secret', $ftp['password']);
        $storage = new RemoteStorageService($ftp);
        self::assertSame('www/party-photos/my-event', $storage->getStorageFolder());
        self::assertSame('https://example.org/custom/Party Photos/my-event/images/photo.jpg', $storage->getPhotoUrl('photo.jpg'));
        self::assertTrue($storage->hasPublicUrl());
    }

    public function testLegacyQrOptOutAndDefaultTemplate(): void
    {
        $legacy = ['baseURL' => 'ftp.example.org', 'website' => 'https://example.org', 'folder' => 'photos'];
        self::assertSame('', $this->migrate($legacy)['url']);
        self::assertSame('https://example.org/photos/%title%', $this->migrate($legacy + ['useForQr' => true])['url']);
    }

    public function testNewSettingsTakePrecedenceAndAreNotMigrated(): void
    {
        $new = ['host' => 'new.example.org', 'path' => 'custom/%title%', 'url' => ''];
        $ftp = $this->migrate($new + ['baseURL' => 'old.example.org', 'folder' => 'old', 'useForQr' => true, 'website' => 'https://old.example.org']);
        foreach ($new as $key => $value) {
            self::assertSame($value, $ftp[$key]);
            self::assertSame($value, $this->migrate($new)[$key]);
        }
    }
}

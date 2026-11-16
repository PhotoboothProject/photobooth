<?php

namespace Photobooth\Tests\Unit\Service;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Ftp\UnableToAuthenticate as FtpAuthenticationFailure;
use League\Flysystem\PhpseclibV3\UnableToAuthenticate as SftpAuthenticationFailure;
use League\Flysystem\PhpseclibV3\UnableToConnectToSftpHost;
use League\Flysystem\PhpseclibV3\UnableToEstablishAuthenticityOfHost;
use League\Flysystem\UnableToWriteFile;
use Photobooth\Enum\RemoteStorageTypeEnum;
use Photobooth\Logger\NamedLogger;
use Photobooth\Service\ConfigurationService;
use Photobooth\Service\RemoteStorageService;
use PHPUnit\Framework\TestCase;

class RemoteStorageServiceTest extends TestCase
{
    private function config(): array
    {
        return [
            'type' => RemoteStorageTypeEnum::FTP,
            'baseURL' => 'storage.example',
            'port' => 21,
            'username' => 'booth',
            'password' => 'synthetic-password',
            'baseFolder' => 'uploads',
            'folder' => 'events',
            'title' => 'Party',
        ];
    }

    private function service(array $config): RemoteStorageService
    {
        $service = (new \ReflectionClass(RemoteStorageService::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(RemoteStorageService::class, 'config'))->setValue($service, $config);
        (new \ReflectionProperty(RemoteStorageService::class, 'logger'))->setValue($service, $this->createStub(NamedLogger::class));
        return $service;
    }

    public function testIdentityTracksEffectiveDestinationOnly(): void
    {
        $config = $this->config();
        $identity = $this->service($config)->getDestinationIdentity();
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $identity);
        foreach ([
            'type' => RemoteStorageTypeEnum::SFTP,
            'baseURL' => 'other.example',
            'port' => 22,
            'username' => 'other',
            'baseFolder' => 'other',
            'folder' => 'other',
            'title' => 'Other party',
        ] as $key => $value) {
            self::assertNotSame($identity, $this->service(array_replace($config, [$key => $value]))->getDestinationIdentity(), $key);
        }
        $equivalent = array_replace($config, [
            'baseURL' => 'STORAGE.EXAMPLE',
            'port' => '21',
            'baseFolder' => '/uploads//',
            'password' => 'rotated',
            'website' => 'https://gallery.example',
            'create_webpage' => true,
        ]);
        self::assertSame($identity, $this->service($equivalent)->getDestinationIdentity());
    }

    public function testNestedConnectionAndAuthenticationFailuresStopWithoutProbing(): void
    {
        $service = $this->getMockBuilder(RemoteStorageService::class)
            ->disableOriginalConstructor()->onlyMethods(['isConnectionAlive'])->getMock();
        $service->expects(self::never())->method('isConnectionAlive');
        foreach ([
            new FtpAuthenticationFailure(),
            UnableToConnectToSftpHost::atHostname('synthetic.example'),
            SftpAuthenticationFailure::withPassword(),
            UnableToEstablishAuthenticityOfHost::becauseTheAuthenticityCantBeEstablished('synthetic.example'),
        ] as $cause) {
            $wrapped = new \RuntimeException('nested adapter failure', 0, $cause);
            self::assertTrue($service->shouldStopAfterUploadFailure(UnableToWriteFile::atLocation('photo.jpg', '', $wrapped)));
        }
    }

    public function testSameGenericWriteFailureContinuesOnlyWithConfirmedLiveConnection(): void
    {
        $error = UnableToWriteFile::atLocation('photo.jpg');
        foreach ([true, false, null] as $alive) {
            $service = $this->getMockBuilder(RemoteStorageService::class)
                ->disableOriginalConstructor()->onlyMethods(['isConnectionAlive'])->getMock();
            (new \ReflectionProperty(RemoteStorageService::class, 'logger'))->setValue($service, $this->createStub(NamedLogger::class));
            $probe = $service->expects(self::once())->method('isConnectionAlive');
            if ($alive === null) {
                $probe->willThrowException(new \RuntimeException('connection lost'));
            } else {
                $probe->willReturn($alive);
            }
            self::assertSame($alive !== true, $service->shouldStopAfterUploadFailure($error));
        }
    }

    public function testMissingConnectionStopsConservatively(): void
    {
        self::assertTrue($this->service($this->config())->shouldStopAfterUploadFailure(UnableToWriteFile::atLocation('photo.jpg')));
    }

    public function testMissingGalleryTemplateFailsInsteadOfCompletingTheJob(): void
    {
        $service = $this->getMockBuilder(RemoteStorageService::class)
            ->disableOriginalConstructor()->onlyMethods(['fileExists'])->getMock();
        $service->expects(self::once())->method('fileExists')->willReturn(false);
        $config = $this->config();
        $config['template_location'] = 'tests/fixtures/missing-gallery-' . bin2hex(random_bytes(8)) . '.php';
        (new \ReflectionProperty(RemoteStorageService::class, 'config'))->setValue($service, $config);
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Remote gallery template is missing or unreadable.');
        $service->createWebpage();
    }

    public function testSftpProbeChecksOriginalConnectionWithoutReconnecting(): void
    {
        foreach ([true, false] as $alive) {
            $service = $this->service($this->config());
            (new \ReflectionMethod(RemoteStorageService::class, 'getAdapterForSftp'))->invoke($service, $this->config());
            $probe = (new \ReflectionProperty(RemoteStorageService::class, 'connectionProbe'))->getValue($service);
            $provider = (new \ReflectionFunction($probe))->getStaticVariables()['provider'];
            $connection = $this->createMock(\phpseclib3\Net\SFTP::class);
            $levels = [];
            $connection->expects(self::exactly(2))->method('isConnected')->willReturnCallback(static function (int $level = 0) use (&$levels, $alive): bool {
                $levels[] = $level;
                return $level === 0 || $alive;
            });
            $connection->expects(self::never())->method('ping');
            $connection->expects(self::never())->method('login');
            $provider->lastConnection = $connection;
            self::assertSame(!$alive, $service->shouldStopAfterUploadFailure(UnableToWriteFile::atLocation('photo.jpg')));
            self::assertSame([0, 2], $levels);
        }
    }

    public function testRefreshPreservesAdapterUntilConfigurationChanges(): void
    {
        $previous = $GLOBALS[ConfigurationService::class] ?? null;
        $config = $this->config();
        $configuration = $this->createStub(ConfigurationService::class);
        $configuration->method('getConfiguration')->willReturnCallback(static function () use (&$config): array {
            return ['ftp' => $config];
        });
        $GLOBALS[ConfigurationService::class] = $configuration;
        try {
            $service = $this->getMockBuilder(RemoteStorageService::class)
                ->disableOriginalConstructor()->onlyMethods(['getAdapter'])->getMock();
            $service->expects(self::exactly(2))->method('getAdapter')->willReturn($this->createStub(FilesystemAdapter::class));
            $service->refreshConfiguration();
            $identity = $service->getDestinationIdentity();
            $service->refreshConfiguration();
            $config['password'] = 'rotated';
            $service->refreshConfiguration();
            self::assertSame($identity, $service->getDestinationIdentity());
        } finally {
            if ($previous === null) {
                unset($GLOBALS[ConfigurationService::class]);
            } else {
                $GLOBALS[ConfigurationService::class] = $previous;
            }
        }
    }
}

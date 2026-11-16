<?php

namespace Photobooth\Service;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\Ftp\FtpConnectionException;
use League\Flysystem\Ftp\FtpConnectionProvider;
use League\Flysystem\Ftp\NoopCommandConnectivityChecker;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\UnableToListContents;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use Photobooth\Enum\RemoteStorageTypeEnum;
use Photobooth\Logger\NamedLogger;
use Photobooth\Utility\ArrayUtility;
use Photobooth\Utility\PathUtility;
use Photobooth\Utility\SlugUtility;

class RemoteStorageService
{
    protected array $config;
    protected NamedLogger $logger;
    protected Filesystem $filesystem;
    protected \Closure $connectionProbe;

    public function __construct()
    {
        $this->logger = LoggerService::getInstance()->getLogger('remotestorage');
        $this->refreshConfiguration();
    }

    public function refreshConfiguration(): void
    {
        $config = ConfigurationService::getInstance()->getConfiguration()['ftp'];
        if (!isset($this->config) || $this->config !== $config) {
            $this->config = $config;
            $this->filesystem = new Filesystem($this->getAdapter($config));
        }
    }

    public function getDestinationIdentity(): string
    {
        return hash('sha256', serialize([
            $this->config['type']->value,
            strtolower(trim($this->config['baseURL'])),
            (int) $this->config['port'],
            $this->config['username'],
            trim((string) preg_replace('~/+~', '/', $this->config['baseFolder']), '/'),
            $this->getStorageFolder(),
        ]));
    }

    public function shouldStopAfterUploadFailure(\Throwable $error): bool
    {
        do {
            if ($error instanceof FtpConnectionException
                || $error instanceof \League\Flysystem\PhpseclibV3\UnableToConnectToSftpHost
                || $error instanceof \League\Flysystem\PhpseclibV3\UnableToAuthenticate
                || $error instanceof \League\Flysystem\PhpseclibV3\UnableToEstablishAuthenticityOfHost
                || $error instanceof \League\Flysystem\PhpseclibV3\UnableToLoadPrivateKey) {
                return true;
            }
            $error = $error->getPrevious();
        } while ($error !== null);

        try {
            if ($this->isConnectionAlive()) {
                return false;
            }
        } catch (\Throwable) {
            // An unconfirmed connection must not trigger the remaining uploads.
        }
        $this->logger->warning('Upload failed and the remote connection could not be confirmed.');
        return true;
    }

    protected function isConnectionAlive(): bool
    {
        return isset($this->connectionProbe) && ($this->connectionProbe)();
    }

    public function createWebpage(): void
    {
        if ($this->fileExists($this->getStorageFolder() . '/index.php') && $this->fileExists($this->getStorageFolder() . '/config.inc.php')) {
            return;
        }

        $templateLocation = PathUtility::getAbsolutePath($this->config['template_location']);
        $template = @file_get_contents($templateLocation);
        if ($template === false) {
            throw new \UnexpectedValueException('Remote gallery template is missing or unreadable.');
        }

        $config = ConfigurationService::getInstance()->getConfiguration();
        $languageService = LanguageService::getInstance();

        $parameters = [
            'meta' => [
                'sitename' => 'Photobooth',
                'lang' => $config['ui']['language'],
                'title' => htmlentities($config['ftp']['title']),
                'max-age' => 60,
            ],
            'paths' => [
                'images' => 'images',
                'thumbs' => 'thumbs',
            ],
            'files' => [
                'download_prefix' => SlugUtility::create($config['ftp']['title']),
            ],
            'labels' => [
                'close' => $languageService->translate('close'),
                'share' => $languageService->translate('shareMessage'),
                'download' => $languageService->translate('download'),
                'download_confirmation_images' => $languageService->translate('download_confirmation_images'),
            ],
            'theme' => [
                '--primary-color' => $config['colors']['primary'],
                '--secondary-color' => $config['colors']['secondary'],
                '--button-font-color' => $config['colors']['button_font'],
                '--font-color' => $config['colors']['font'],
            ]
        ];

        $this->write($this->getStorageFolder() . '/config.inc.php', "<?php\n\nreturn " . ArrayUtility::export($parameters) . ";\n");
        $this->write($this->getStorageFolder() . '/index.php', $template);
    }

    public function getWebpageUri(): string
    {
        $template = (string) $this->config['urlTemplate'];
        $template = str_replace('%website%', $this->config['website'], $template);
        $template = str_replace('%folder%', $this->config['folder'], $template);
        $template = str_replace('%title%', SlugUtility::create($this->config['title']), $template);

        return $template;
    }

    public function getStorageFolder(): string
    {
        $storageFolder = SlugUtility::create($this->config['folder']) . '/' . SlugUtility::create($this->config['title']);

        return $storageFolder;
    }

    public function fileExists(string $location): bool
    {
        return $this->filesystem->fileExists($location);
    }

    public function write(string $location, string $contents): void
    {
        $this->logger->debug('Uploading...', [$location]);
        $this->filesystem->write($location, $contents);
    }

    public function delete(string $location): void
    {
        $this->logger->debug('Deleting...', [$location]);
        if ($this->fileExists($location)) {
            $this->filesystem->delete($location);
        }
    }

    public function testConnection(): bool
    {
        $this->logger->debug('Testing upload connection.');
        try {
            $files = [];
            $contents = $this->filesystem->listContents('/', false);
            foreach ($contents as $object) {
                $files[] = $object->path();
            }
            $this->logger->debug('Connection established.', [$files]);
            return true;
        } catch (FilesystemException | UnableToListContents | UnableToReadFile $exception) {
            $this->logger->error('Connection failed.', ['exception' => $exception->getMessage()]);
        }

        return false;
    }

    protected function getAdapter(array $config): FilesystemAdapter
    {
        /** @var RemoteStorageTypeEnum $type */
        $type = $config['type'];

        return match ($type) {
            RemoteStorageTypeEnum::FTP => $this->getAdapterForFtp($config),
            RemoteStorageTypeEnum::SFTP => $this->getAdapterForSftp($config),
        };
    }

    protected function getAdapterForFtp(array $config): FtpAdapter
    {
        $provider = new class () extends FtpConnectionProvider {
            public mixed $lastConnection = null;

            public function createConnection(FtpConnectionOptions $options)
            {
                return $this->lastConnection = parent::createConnection($options);
            }
        };
        $this->connectionProbe = static fn (): bool => $provider->lastConnection !== null
            && (new NoopCommandConnectivityChecker())->isConnected($provider->lastConnection);

        return new FtpAdapter(
            FtpConnectionOptions::fromArray([
                'host' => $config['baseURL'],
                'root' => '/' . $config['baseFolder'],
                'username' => $config['username'],
                'password' => $config['password'],
                'port' => $config['port'],
            ]),
            $provider
        );
    }

    protected function getAdapterForSftp(array $config): SftpAdapter
    {
        $provider = new class ($config['baseURL'], $config['username'], $config['password'], port: $config['port']) extends SftpConnectionProvider {
            public ?\phpseclib3\Net\SFTP $lastConnection = null;

            public function provideConnection(): \phpseclib3\Net\SFTP
            {
                return $this->lastConnection = parent::provideConnection();
            }
        };
        // ping() reconnects; check the connection used by the failed upload instead.
        $this->connectionProbe = static fn (): bool => $provider->lastConnection !== null
            && $provider->lastConnection->isConnected()
            && $provider->lastConnection->isConnected(2);

        return new SftpAdapter(
            $provider,
            '/' . $config['baseFolder'],
            PortableVisibilityConverter::fromArray([
                'file' => [
                    'public' => 0664,
                    'private' => 0664,
                ],
                'dir' => [
                    'public' => 0775,
                    'private' => 0775,
                ],
            ])
        );
    }

    public static function getInstance(): self
    {
        if (!isset($GLOBALS[self::class])) {
            $GLOBALS[self::class] = new self();
        }

        return $GLOBALS[self::class];
    }
}

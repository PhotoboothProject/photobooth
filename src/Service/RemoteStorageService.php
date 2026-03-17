<?php

namespace Photobooth\Service;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
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

    public function __construct()
    {
        $this->config = ConfigurationService::getInstance()->getConfiguration()['ftp'];
        $this->logger = LoggerService::getInstance()->getLogger('remotestorage');
        $this->filesystem = new Filesystem($this->getAdapter($this->config));
    }

    public function createWebpage(): void
    {
        $templateLocation = PathUtility::getAbsolutePath($this->config['template_location']);
        if (!file_exists($templateLocation)) {
            return;
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
            'gallery_enabled' => (bool) $config['ftp']['create_webpage'],
            'labels' => [
                'close' => $languageService->translate('close'),
                'share' => $languageService->translate('shareMessage'),
                'download' => $languageService->translate('download'),
                'download_confirmation_images' => $languageService->translate('download_confirmation_images'),
                'image_uploading' => $languageService->translate('ftp:image_uploading'),
            ],
            'theme' => [
                '--primary-color' => $config['colors']['primary'],
                '--secondary-color' => $config['colors']['secondary'],
                '--button-font-color' => $config['fonts']['button_font_color'] ?? $config['colors']['font'],
                '--font-color' => $config['colors']['font'],
            ],
        ];

        // Always update config and template
        $this->write($this->getStoragePath('config.inc.php'), "<?php\n\nreturn " . ArrayUtility::export($parameters) . ";\n");
        $this->write($this->getStoragePath('index.php'), (string) file_get_contents($templateLocation));

        // Remove legacy .htaccess files that cause 403 on hosts without AllowOverride Options
        $this->delete($this->getStoragePath('images/.htaccess'));
        $this->delete($this->getStoragePath('thumbs/.htaccess'));

        // Prevent directory listing without relying on AllowOverride Options
        $indexGuard = "<?php header('Location: ../'); exit;\n";
        $this->write($this->getStoragePath('images/index.php'), $indexGuard);
        $this->write($this->getStoragePath('thumbs/index.php'), $indexGuard);
    }

    public function getWebpageUri(): string
    {
        $website = rtrim((string) $this->config['website'], '/');
        $baseFolder = trim((string) $this->config['baseFolder'], '/');

        if ($baseFolder !== '') {
            return $website . '/' . $baseFolder;
        }

        return $website;
    }

    public function getStoragePath(string $relativePath): string
    {
        return $relativePath;
    }

    public function ensureDirectoriesExist(): void
    {
        $directories = [
            $this->getStoragePath('images'),
            $this->getStoragePath('thumbs'),
        ];

        foreach ($directories as $directory) {
            try {
                $this->filesystem->createDirectory($directory);
            } catch (FilesystemException $e) {
                $this->logger->debug('Directory may already exist, continuing.', [$directory, $e->getMessage()]);
            }
        }
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
        $this->logger->info('Testing upload connection.');
        try {
            $files = [];
            $contents = $this->filesystem->listContents('/', false);
            foreach ($contents as $object) {
                $files[] = $object->path();
            }
            $this->logger->info('Connection established.', [$files]);
        } catch (\Throwable $exception) {
            $this->logger->error('Connection failed.', ['exception' => $exception->getMessage()]);

            return false;
        }

        try {
            $this->ensureDirectoriesExist();
            $this->createWebpage();
            $this->logger->info('Remote setup completed.');
        } catch (\Throwable $exception) {
            $this->logger->error('Remote setup failed.', ['exception' => $exception->getMessage()]);
        }

        return true;
    }

    /**
     * Create a temporary Filesystem instance from raw config values (not saved config).
     * Used for folder browsing before config is saved.
     *
     * @param array{type: string, baseURL: string, port: int, username: string, password: string} $config
     */
    public static function createTemporaryFilesystem(array $config, string $root = '/'): Filesystem
    {
        $ip = gethostbyname($config['baseURL']);
        $host = $ip !== $config['baseURL'] ? $ip : $config['baseURL'];
        $type = RemoteStorageTypeEnum::from($config['type']);

        $adapter = match ($type) {
            RemoteStorageTypeEnum::FTP => new FtpAdapter(
                FtpConnectionOptions::fromArray([
                    'host' => $host,
                    'root' => $root,
                    'username' => $config['username'],
                    'password' => $config['password'],
                    'port' => (int) $config['port'],
                ])
            ),
            RemoteStorageTypeEnum::SFTP => new SftpAdapter(
                SftpConnectionProvider::fromArray([
                    'host' => $host,
                    'username' => $config['username'],
                    'password' => $config['password'],
                    'port' => (int) $config['port'],
                ]),
                $root,
                PortableVisibilityConverter::fromArray([
                    'file' => ['public' => 0664, 'private' => 0664],
                    'dir' => ['public' => 0775, 'private' => 0775],
                ])
            ),
        };

        return new Filesystem($adapter);
    }

    /**
     * List folders at the given path using provided connection config.
     *
     * @param array{type: string, baseURL: string, port: int, username: string, password: string} $config
     * @return string[]
     */
    public static function listFolders(array $config, string $path = '/'): array
    {
        $filesystem = self::createTemporaryFilesystem($config);
        $folders = [];

        foreach ($filesystem->listContents($path, false) as $item) {
            if ($item->isDir()) {
                $folders[] = $item->path();
            }
        }

        sort($folders);

        return $folders;
    }

    /**
     * Resolve hostname to IPv4 address to avoid IPv6 connection issues.
     */
    protected function resolveHostToIPv4(string $host): string
    {
        $ip = gethostbyname($host);

        return $ip !== $host ? $ip : $host;
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
        return new FtpAdapter(
            FtpConnectionOptions::fromArray([
                'host' => $this->resolveHostToIPv4($config['baseURL']),
                'root' => '/' . $config['baseFolder'],
                'username' => $config['username'],
                'password' => $config['password'],
                'port' => $config['port'],
            ])
        );
    }

    protected function getAdapterForSftp(array $config): SftpAdapter
    {
        return new SftpAdapter(
            SftpConnectionProvider::fromArray([
                'host' => $this->resolveHostToIPv4($config['baseURL']),
                'username' => $config['username'],
                'password' => $config['password'],
                'port' => $config['port']
            ]),
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

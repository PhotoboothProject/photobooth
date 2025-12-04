<?php

namespace Photobooth\Service;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\UnableToListContents;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;
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
    protected ?string $lastError = null;

    public function __construct()
    {
        $this->config = ConfigurationService::getInstance()->getConfiguration()['ftp'];
        $this->logger = LoggerService::getInstance()->getLogger('remotestorage');
        $this->filesystem = new Filesystem($this->getAdapter($this->config));
    }

    public function createWebpage(): void
    {
        if ($this->fileExists($this->getStorageFolder() . '/index.php') && $this->fileExists($this->getStorageFolder() . '/config.inc.php')) {
            return;
        }

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
        $this->write($this->getStorageFolder() . '/index.php', (string) file_get_contents($templateLocation));
    }

    public function getWebpageUri(): string
    {
        $template = (string) $this->config['urlTemplate'];

        if (!empty($this->config['baseFolder']) && !str_contains($template, '%baseFolder%')) {
            $template = str_replace('%folder%', '%baseFolder%/%folder%', $template);
        }

        $template = str_replace('%website%', $this->config['website'], $template);
        $template = str_replace('%folder%', $this->config['folder'], $template);
        $template = str_replace('%title%', SlugUtility::create($this->config['title']), $template);
        $template = str_replace('%baseFolder%', $this->config['baseFolder'], $template);
        $template = str_replace('%date%', date('Y/m/d'), $template);

        return $template;
    }

    public function getStorageFolder(): string
    {
        $storageFolder = SlugUtility::create($this->config['folder']) . '/' . SlugUtility::create($this->config['title']);

        if (!empty($this->config['baseFolder'])) {
            $baseFolder = rtrim($this->config['baseFolder'], '/');
            $storageFolder = $baseFolder . '/' . $storageFolder;
        }

        return $storageFolder;
    }

    public function fileExists(string $location): bool
    {
        return $this->filesystem->fileExists($location);
    }

    public function write(string $location, string $contents): bool
    {
        $this->logger->debug('Uploading...', [$location]);
        try {
            $this->filesystem->write($location, $contents);
            return true;
        } catch (FilesystemException | UnableToWriteFile $exception) {
            $this->logger->error('Upload failed.', ['exception' => $exception->getMessage()]);
            return false;
        }
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
        $this->lastError = null;
        try {
            $files = [];
            $contents = $this->filesystem->listContents('', false);
            foreach ($contents as $object) {
                $files[] = $object->path();
            }
            $this->logger->debug('Connection established.', [$files]);
            return true;
        } catch (FilesystemException | UnableToListContents | UnableToReadFile $exception) {
            $this->lastError = $exception->getMessage();
            $this->logger->error('Connection failed.', ['exception' => $exception->getMessage()]);
        }

        return false;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
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
                'host' => $config['baseURL'],
                'root' => '',
                'username' => $config['username'],
                'password' => $config['password'],
                'port' => $config['port'],
                'ssl' => $config['ssl'] ?? false,
                'passive' => $config['passive'] ?? true,
                'timeout' => 10,
            ])
        );
    }

    protected function getAdapterForSftp(array $config): SftpAdapter
    {
        return new SftpAdapter(
            SftpConnectionProvider::fromArray([
                'host' => $config['baseURL'],
                'username' => $config['username'],
                'password' => $config['password'],
                'port' => $config['port']
            ]),
            '/',
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

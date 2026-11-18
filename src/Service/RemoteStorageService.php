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
    protected string $lastError = '';

    /**
     * @param array|null $config Remote storage settings to use instead of the
     *                           saved ones (e.g. to test unsaved form values).
     */
    public function __construct(?array $config = null)
    {
        $this->config = $config ?? ConfigurationService::getInstance()->getConfiguration()['ftp'];
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

        // Allow developers to customize the generated webpage configuration
        // (e.g. for a custom template set via template_location): the file
        // must return an array which is merged over the defaults above.
        $overrideLocation = PathUtility::getAbsolutePath((string) $this->config['template_config_location']);
        if (is_file($overrideLocation)) {
            $overrides = include $overrideLocation;
            if (is_array($overrides)) {
                $parameters = array_replace_recursive($parameters, $overrides);
            } else {
                $this->logger->warning('Template config override did not return an array, ignoring.', [$overrideLocation]);
            }
        }

        $this->write($this->getStorageFolder() . '/config.inc.php', "<?php\n\nreturn " . ArrayUtility::export($parameters) . ";\n");
        $this->write($this->getStorageFolder() . '/index.php', (string) file_get_contents($templateLocation));
    }

    /**
     * Whether a public URL is configured, i.e. QR codes should point to the
     * remote storage instead of the local Photobooth.
     */
    public function hasPublicUrl(): bool
    {
        return (bool) $this->config['enabled'] && trim((string) $this->config['url']) !== '';
    }

    /**
     * Public URL of the storage folder (the remote gallery), without trailing slash.
     */
    public function getWebpageUri(): string
    {
        return rtrim($this->replacePlaceholders(trim((string) $this->config['url'])), '/');
    }

    /**
     * Public link to a single uploaded photo, as encoded in the QR code:
     * the gallery URL followed by the "qr_link" setting, where %file% is
     * replaced by the filename. An empty "qr_link" links to the gallery.
     */
    public function getPhotoUrl(string $filename): string
    {
        $link = trim((string) $this->config['qr_link']);

        return $this->getWebpageUri() . str_replace('%file%', rawurlencode($filename), $link);
    }

    /**
     * Folder on the remote server the files are uploaded to, relative to
     * the server root. Written exactly as configured in "path"; only the
     * %title% placeholder is replaced (by the slug of the title).
     */
    public function getStorageFolder(): string
    {
        $path = str_replace('\\', '/', $this->replacePlaceholders((string) $this->config['path']));
        $segments = array_filter(array_map('trim', explode('/', $path)), fn (string $segment): bool => $segment !== '');

        return implode('/', $segments);
    }

    protected function replacePlaceholders(string $value): string
    {
        return str_replace('%title%', SlugUtility::create((string) $this->config['title']), $value);
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
            $this->lastError = self::describeException($exception);
            $this->logger->error('Connection failed.', ['exception' => $this->lastError]);
        }

        return false;
    }

    /**
     * Reason of the last failed testConnection() call.
     */
    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * Flysystem wraps the actual cause (e.g. a failed login) into generic
     * exceptions like "creating parent directory failed": append the
     * messages of the previous exceptions so the real reason is visible.
     */
    public static function describeException(\Throwable $exception): string
    {
        $messages = [];
        for ($e = $exception; $e !== null; $e = $e->getPrevious()) {
            $message = trim((string) preg_replace('/\s+/', ' ', $e->getMessage()));
            if ($message !== '' && !in_array($message, $messages, true)) {
                $messages[] = $message;
            }
        }

        return implode(' <- ', $messages);
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
                'host' => $config['host'],
                'root' => '/',
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
                'host' => $config['host'],
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

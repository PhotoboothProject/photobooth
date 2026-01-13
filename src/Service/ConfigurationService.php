<?php

namespace Photobooth\Service;

use Photobooth\Config\Loader\PhpArrayLoader;
use Photobooth\Configuration\PhotoboothConfiguration;
use Photobooth\Environment;
use Photobooth\Helper;
use Photobooth\Utility\ArrayUtility;
use Photobooth\Utility\PathUtility;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Config\Loader\DelegatingLoader;

class ConfigurationService
{
    protected array $defaultConfiguration;
    protected array $configuration;
    protected array $processedConfiguration;

    public function __construct()
    {
        $this->load();
    }

    public function load(): void
    {
        $fileLocator = new FileLocator(PathUtility::getAbsolutePath('config'));
        $loaderResolver = new LoaderResolver([
            new PhpArrayLoader($fileLocator),
        ]);
        $loader = new DelegatingLoader($loaderResolver);

        // default configuration
        $this->defaultConfiguration = (new Processor())->processConfiguration(new PhotoboothConfiguration(), [[]]);

        // configuration
        $userConfiguration = [];
        if (file_exists(PathUtility::getAbsolutePath('config/my.config.inc.php'))) {
            $userConfiguration = $loader->load('my.config.inc.php', 'php_array');
            $userConfiguration = $this->processMigration($userConfiguration);
        }
        $configuration = (new Processor())->processConfiguration(new PhotoboothConfiguration(), [$userConfiguration]);
        $configuration = $this->addDefaults($configuration);
        $this->configuration = $configuration;
    }

    public function update(array $data): void
    {
        $data = (new Processor())->processConfiguration(new PhotoboothConfiguration(), [$data]);
        $content = "<?php\n\nreturn " . ArrayUtility::export(ArrayUtility::diffRecursive($data, $this->defaultConfiguration)) . ";\n";
        $userConfigurationFile = PathUtility::getAbsolutePath('config/my.config.inc.php');
        if (file_put_contents($userConfigurationFile, $content)) {
            Helper::clearCache($userConfigurationFile);
            return;
        }

        throw new \RuntimeException('Config can not be saved!');
    }

    protected function addDefaults(array $config): array
    {
        $default_font  = 'resources/fonts/GreatVibes-Regular.ttf';
        $default_frame = 'resources/img/frames/frame.png';
        $random_frame  = 'api/randomImg.php?dir=demoframes';

        if (empty($config['picture']['frame'])) {
            $config['picture']['frame'] = $random_frame;
        }

        if (empty($config['textonpicture']['font'])) {
            $config['textonpicture']['font'] = $default_font;
        }

        if (empty($config['collage']['frame'])) {
            $config['collage']['frame'] = $default_frame;
        }

        if (empty($config['collage']['placeholderpath'])) {
            $config['collage']['placeholderpath'] = 'resources/img/background/01.jpg';
        }

        if (empty($config['textoncollage']['font'])) {
            $config['textoncollage']['font'] = $default_font;
        }

        if (empty($config['print']['frame'])) {
            $config['print']['frame'] = $default_frame;
        }

        if (empty($config['textonprint']['font'])) {
            $config['textonprint']['font'] = $default_font;
        }

        if (empty($config['collage']['limit'])) {
            $config['collage']['limit'] = 4;
        }

        $bg_path = 'resources/img/background.png';
        $logo_url = 'resources/img/logo/logo-qrcode-text.png';
        if (empty($config['logo']['path'])) {
            $config['logo']['path'] = $logo_url;
        }

        if (empty($config['background']['defaults'])) {
            $config['background']['defaults'] = $bg_path;
        }

        if (empty($config['background']['admin'])) {
            $config['background']['admin'] = $bg_path;
        }

        if (empty($config['background']['chroma'])) {
            $config['background']['chroma'] = $bg_path;
        }

        if (empty($config['remotebuzzer']['serverip'])) {
            $config['remotebuzzer']['serverip'] = Environment::getIp();
        }

        if (empty($config['qr']['url'])) {
            $config['qr']['url'] = 'view.php?image=';
        }

        return $config;
    }

    protected function processMigration(array $config): array
    {
        // Normalize legacy paths that may contain absolute URLs or subfolder prefixes (e.g. /photobooth/)
        $baseUrl  = PathUtility::getBaseUrl();
        $hostBase = '';
        if (isset($_SERVER['HTTP_HOST'])) {
            $scheme   = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https' : 'http';
            $hostBase = $scheme . '://' . $_SERVER['HTTP_HOST'] . $baseUrl;
        }

        $normalizePath = static function (?string $path) use ($baseUrl, $hostBase): ?string {
            if ($path === null || $path === '') {
                return $path;
            }

            $value = (string)$path;

            // Strip surrounding url("...") / url('...') / url(...)
            if (substr($value, 0, 4) === 'url(' && substr($value, -1) === ')') {
                $value = trim(substr($value, 4, -1), '"\'');
            }

            // Strip project root based absolute paths
            try {
                $rootPath = PathUtility::getRootPath();
                if (str_starts_with($value, $rootPath)) {
                    $value = substr($value, strlen($rootPath));
                }
            } catch (\InvalidArgumentException) {
                // ignore if root path can't be resolved in this context
            }

            // Remove full host+base prefix, e.g. "https://host/photobooth/"
            if ($hostBase !== '' && str_starts_with($value, $hostBase)) {
                $value = substr($value, strlen($hostBase));
            }

            // Remove base URL prefix, e.g. "/photobooth/"
            if ($baseUrl !== '' && str_starts_with($value, $baseUrl)) {
                $value = substr($value, strlen($baseUrl));
            }

            // If path still contains a known project-relative marker, strip everything before it
            foreach (['/private/', '/resources/'] as $marker) {
                $pos = strpos($value, $marker);
                if ($pos !== false) {
                    $value = substr($value, $pos);
                    break;
                }
            }

            return $value;
        };

        // Migrate Commands
        $commands = [
            'take_picture',
            'take_custom',
            'take_video',
            'print',
            'exiftool',
            'preview',
            'nodebin',
            'pre_photo',
            'post_photo',
            'reboot',
            'shutdown',
        ];
        foreach ($commands as $command) {
            if (isset($config[$command]['cmd'])) {
                $config['commands'][$command] = $config[$command]['cmd'];
                unset($config[$command]['cmd']);
                if (count($config[$command]) === 0) {
                    unset($config[$command]);
                }
            }
        }
        if (isset($config['preview']['killcmd']) && trim($config['preview']['killcmd']) !== '') {
            $config['commands']['preview_kill'] = trim($config['preview']['killcmd']);
        }

        // Migrate Preview Mode
        if (isset($config['preview']['mode']) && $config['preview']['mode'] === 'gphoto') {
            $config['preview']['mode'] = 'device_cam';
        }

        // Migrate Preview URL, remove surrounding url("...")
        if (isset($config['preview']['url']) && substr($config['preview']['url'], 0, 4) === 'url(' && substr($config['preview']['url'], -1) === ')') {
            $config['preview']['url'] = trim(substr($config['preview']['url'], 4, -1), '"\'');
        }

        // Migrate screensaver switch interval from minutes to seconds
        if (isset($config['screensaver'])) {
            if (isset($config['screensaver']['switch_minutes']) && !isset($config['screensaver']['switch_seconds'])) {
                $config['screensaver']['switch_seconds'] = (int)$config['screensaver']['switch_minutes'] * 60;
            }
            unset($config['screensaver']['switch_minutes']);
        }

        // Migrate button font color from old colors config
        if (!empty($config['colors']['button_font']) && empty($config['fonts']['button_font_color'])) {
            $config['fonts']['button_font_color'] = $config['colors']['button_font'];
        }

        // Migrate countdown color from colors to fonts section
        if (!empty($config['colors']['countdown']) && empty($config['fonts']['countdown_text_color'])) {
            $config['fonts']['countdown_text_color'] = $config['colors']['countdown'];
        }

        // Migrate start font color to new font slots if empty
        if (!empty($config['colors']['start_font'])) {
            if (empty($config['fonts']['start_screen_title_color'])) {
                $config['fonts']['start_screen_title_color'] = $config['colors']['start_font'];
            }
            if (empty($config['fonts']['event_text_color'])) {
                $config['fonts']['event_text_color'] = $config['colors']['start_font'];
            }
        }

        // Migrate general font color to default font color
        if (!empty($config['colors']['font']) && empty($config['fonts']['default_color'])) {
            $config['fonts']['default_color'] = $config['colors']['font'];
        }

        // Migrate Background URLs
        if (isset($config['background']) && is_array($config['background'])) {
            $baseUrl = PathUtility::getBaseUrl();
            foreach (['defaults', 'admin', 'chroma'] as $backgroundKey) {
                if (!isset($config['background'][$backgroundKey]) || $config['background'][$backgroundKey] === '') {
                    continue;
                }

                $value = (string)$config['background'][$backgroundKey];

                // Strip surrounding url("...") / url('...') / url(...)
                if (substr($value, 0, 4) === 'url(' && substr($value, -1) === ')') {
                    $value = trim(substr($value, 4, -1), '"\'');
                }

                // Strip document root based absolute paths
                if (isset($_SERVER['DOCUMENT_ROOT']) && str_starts_with($value, $_SERVER['DOCUMENT_ROOT'])) {
                    $value = substr($value, strlen($_SERVER['DOCUMENT_ROOT']));
                }

                // Strip leading base URL so only a relative path is stored
                if ($baseUrl !== '' && str_starts_with($value, $baseUrl)) {
                    $value = substr($value, strlen($baseUrl));
                }

                // Normalize leading slash
                $value = ltrim($value, '/');

                $config['background'][$backgroundKey] = $value;
            }
        }

        // Normalize various media and font paths to be project-relative
        $config['logo']['path']               = $normalizePath($config['logo']['path'] ?? null);
        $config['ui']['shutter_cheese_img']   = $normalizePath($config['ui']['shutter_cheese_img'] ?? null);
        $config['picture']['frame']           = $normalizePath($config['picture']['frame'] ?? null);
        $config['collage']['frame']           = $normalizePath($config['collage']['frame'] ?? null);
        $config['collage']['placeholderpath'] = $normalizePath($config['collage']['placeholderpath'] ?? null);
        $config['background']['defaults']     = $normalizePath($config['background']['defaults'] ?? null);
        $config['background']['admin']        = $normalizePath($config['background']['admin'] ?? null);
        $config['background']['chroma']       = $normalizePath($config['background']['chroma'] ?? null);
        $config['textonpicture']['font']      = $normalizePath($config['textonpicture']['font'] ?? null);
        $config['textoncollage']['font']      = $normalizePath($config['textoncollage']['font'] ?? null);
        $config['textonprint']['font']        = $normalizePath($config['textonprint']['font'] ?? null);
        $config['print']['frame']             = $normalizePath($config['print']['frame'] ?? null);

        // Hash legacy plain-text login pins
        $hashPinIfNeeded = static function (?string $pin): ?string {
            if ($pin === null || $pin === '') {
                return $pin;
            }
            $info = password_get_info($pin);
            if (($info['algo'] ?? 0) !== 0) {
                return $pin;
            }

            return password_hash($pin, PASSWORD_DEFAULT);
        };

        if (array_key_exists('login', $config)) {
            if (array_key_exists('pin', $config['login'])) {
                $config['login']['pin'] = $hashPinIfNeeded($config['login']['pin']);
            }
            if (array_key_exists('rental_pin', $config['login'])) {
                $config['login']['rental_pin'] = $hashPinIfNeeded($config['login']['rental_pin']);
            }
        }

        return $config;
    }

    public function getDefaultConfiguration(): array
    {
        return $this->defaultConfiguration;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public static function getInstance(): self
    {
        if (!isset($GLOBALS[self::class])) {
            $GLOBALS[self::class] = new self();
        }

        return $GLOBALS[self::class];
    }
}

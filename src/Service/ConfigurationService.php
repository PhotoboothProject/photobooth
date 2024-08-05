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
        $default_font = PathUtility::getPublicPath('resources/fonts/GreatVibes-Regular.ttf');
        $default_frame = PathUtility::getPublicPath('resources/img/frames/frame.png');
        $random_frame = PathUtility::getPublicPath('api/randomImg.php?dir=demoframes');

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
            $config['collage']['placeholderpath'] = PathUtility::getPublicPath('resources/img/background/01.jpg');
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

        $bg_url = PathUtility::getPublicPath('resources/img/background.png');
        $logo_url = PathUtility::getPublicPath('resources/img/logo/logo-qrcode-text.png');

        if (empty($config['logo']['path'])) {
            $config['logo']['path'] = $logo_url;
        }

        if (empty($config['background']['defaults'])) {
            $config['background']['defaults'] = 'url(' . $bg_url . ')';
        }

        if (empty($config['background']['admin'])) {
            $config['background']['admin'] = 'url(' . $bg_url . ')';
        }

        if (empty($config['background']['chroma'])) {
            $config['background']['chroma'] = 'url(' . $bg_url . ')';
        }

        if (empty($config['remotebuzzer']['serverip'])) {
            $config['remotebuzzer']['serverip'] = Environment::getIp();
        }

        if (empty($config['qr']['url'])) {
            $config['qr']['url'] = PathUtility::getPublicPath('api/download.php?image=');
        }

        return $config;
    }

    protected function processMigration(array $config): array
    {
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

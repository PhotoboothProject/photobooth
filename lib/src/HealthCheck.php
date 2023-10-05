<?php

namespace Photobooth;

use Photobooth\Service\LanguageService;

/**
 * Class HealthCheck
 *
 * Performs a health check on the PHP environment.
 */
class HealthCheck
{
    public const MINIMUM_PHP_VERSION = '8.2.0';
    public const REQUIRED_PHP_EXTENSIONS = [
        'dom',
        'gd',
        'mbstring',
        'zip'
    ];

    public bool $healthStatus = true;

    private LanguageService $languageService;

    public function __construct()
    {
        if (version_compare(self::phpVersion(), self::MINIMUM_PHP_VERSION, '<')) {
            $this->healthStatus = false;
        }
        foreach (self::REQUIRED_PHP_EXTENSIONS as $extension) {
            if (!extension_loaded('mbstring')) {
                $this->healthStatus = false;
            }
        }

        $this->languageService = LanguageService::getInstance();
    }

    public static function phpVersion(): string
    {
        return \PHP_VERSION;
    }

    public function renderReport(): string
    {
        $content = [];

        $content[] = '<div class="w-full p-5 mx-auto mt-2 rounded-lg ' . ($this->healthStatus ? 'bg-green-500' : 'bg-red-500') . ' text-white text-center">';
        $content[] = '<h3 class="font-bold uppercase underline pb-2">' . $this->languageService->translate('healthStatus') . '</h3>';
        $content[] = '<p>';
        $content[] = $this->languageService->translate('currentPhpVersion') . ' ' . self::phpVersion() . '<br>';
        if (version_compare(self::phpVersion(), self::MINIMUM_PHP_VERSION, '>=')) {
            $content[] = '<i class="fa fa-check mr-2"></i> ' . $this->languageService->translate('phpVersionOk');
        } else {
            $content[] = '<i class="fa fa-times mr-2"></i> ' . $this->languageService->translate('phpVersionError') . '<br>';
            $content[] = $this->languageService->translate('phpVersionWarning');
            $content[] = $this->languageService->translate('phpUpdateRequired');
        }
        $content[] = '</p>';
        foreach (self::REQUIRED_PHP_EXTENSIONS as $extension) {
            $content[] = '<p>';
            $content[] = extension_loaded($extension)
                ? '<i class="fa fa-check mr-2"></i> ' . sprintf($this->languageService->translate('php-extension-enabled'), $extension)
                : '<i class="fa fa-times mr-2"></i> ' . sprintf($this->languageService->translate('php-extension-disabled'), $extension);
            $content[] = '</p>';
        }
        $content[] = '<p><b>' . ($this->healthStatus ? $this->languageService->translate('healthGood') : $this->languageService->translate('healthError')) . '</b></p>';
        $content[] = '</div>';

        return implode('', $content);
    }
}

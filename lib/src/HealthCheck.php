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
    public const MINIMUM_PHP_MAJOR = 8;
    public const MINIMUM_PHP_MINOR = 2;

    public bool $healthStatus = false;
    public int $phpMajor = 0;
    public int $phpMinor = 0;
    public bool $gdEnabled = false;
    public bool $zipEnabled = false;
    public bool $mbstringEnabled = false;

    private LanguageService $languageService;

    public function __construct()
    {
        list($this->phpMajor, $this->phpMinor) = $this->phpVersion();
        $this->gdEnabled = extension_loaded('gd');
        $this->zipEnabled = extension_loaded('zip');
        $this->mbstringEnabled = extension_loaded('mbstring');
        if (
            ($this->phpMajor >= self::MINIMUM_PHP_MAJOR && $this->phpMinor >= self::MINIMUM_PHP_MINOR) &&
            $this->gdEnabled &&
            $this->zipEnabled &&
            $this->mbstringEnabled
        ) {
            $this->healthStatus = true;
        }

        $this->languageService = LanguageService::getInstance();
    }

    /**
     * @return int[]
     */
    public static function phpVersion(): array
    {
        try {
            return [\PHP_MAJOR_VERSION, \PHP_MINOR_VERSION];
        } catch (\Exception $e) {
            return [0, 0];
        }
    }

    public function renderReport(): string
    {
        $content = [];

        $content[] = '<div class="w-full p-5 mx-auto mt-2 rounded-lg ' . ($this->healthStatus ? 'bg-green-500' : 'bg-red-500') . ' text-white text-center">';
        $content[] = '<h3 class="font-bold uppercase underline pb-2">' . $this->languageService->translate('healthStatus') . '</h3>';
        $content[] = '<p>';
        $content[] = $this->languageService->translate('currentPhpVersion') . ' ' . $this->phpMajor . '.' . $this->phpMinor . '<br>';
        if ($this->phpMajor >= self::MINIMUM_PHP_MAJOR && $this->phpMinor >= self::MINIMUM_PHP_MINOR) {
            $content[] = '<i class="fa fa-check mr-2"></i> ' . $this->languageService->translate('phpVersionOk');
        } else {
            $content[] = '<i class="fa fa-times mr-2"></i> ' . $this->languageService->translate('phpVersionError') . '<br>';
            $content[] = $this->languageService->translate('phpVersionWarning');
            $content[] = $this->languageService->translate('phpUpdateRequired');
        }
        $content[] = '</p>';
        $content[] = '<p>';
        $content[] = $this->gdEnabled ? '<i class="fa fa-check mr-2"></i> ' . $this->languageService->translate('phpGdEnabled') : '<i class="fa fa-times mr-2"></i> ' . $this->languageService->translate('phpGdDisabled');
        $content[] = '</p>';
        $content[] = '<p>';
        $content[] = $this->zipEnabled ? '<i class="fa fa-check mr-2"></i> ' . $this->languageService->translate('phpZipEnabled') : '<i class="fa fa-times mr-2"></i> ' . $this->languageService->translate('phpZipDisabled');
        $content[] = '</p>';
        $content[] = '<p>';
        $content[] = $this->mbstringEnabled ? '<i class="fa fa-check mr-2"></i> ' . $this->languageService->translate('phpMbstringEnabled') : '<i class="fa fa-times mr-2"></i> ' . $this->languageService->translate('phpMbstringDisabled');
        $content[] = '</p>';
        $content[] = '<p><b>' . ($this->healthStatus ? $this->languageService->translate('healthGood') : $this->languageService->translate('healthError')) . '</b></p>';
        $content[] = '</div>';

        return implode('', $content);
    }
}

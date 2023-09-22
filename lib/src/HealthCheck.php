<?php

namespace Photobooth;

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
        $content[] = '<h3 class="font-bold uppercase underline pb-2"><span data-i18n="healthStatus"></span></h3>';
        $content[] = '<p>';
        $content[] = '<span data-i18n="currentPhpVersion"></span> ' . $this->phpMajor . '.' . $this->phpMinor . '<br>';
        if ($this->phpMajor >= self::MINIMUM_PHP_MAJOR && $this->phpMinor >= self::MINIMUM_PHP_MINOR) {
            $content[] = '<i class="fa fa-check mr-2"></i><span data-i18n="phpVersionOk"></span>';
        } else {
            $content[] = '<i class="fa fa-times mr-2"></i><span data-i18n="phpVersionError"></span><br>';
            $content[] = '<span data-i18n="phpVersionWarning"></span>';
            $content[] = '<span data-i18n="phpUpdateRequired"></span>';
        }
        $content[] = '</p>';
        $content[] = '<p>';
        $content[] = $this->gdEnabled ? '<i class="fa fa-check mr-2"></i><span data-i18n="phpGdEnabled"></span>' : '<i class="fa fa-times mr-2"></i><span data-i18n="phpGdDisabled"></span>';
        $content[] = '</p>';
        $content[] = '<p>';
        $content[] = $this->zipEnabled ? '<i class="fa fa-check mr-2"></i><span data-i18n="phpZipEnabled"></span>' : '<i class="fa fa-times mr-2"></i><span data-i18n="phpZipDisabled"></span>';
        $content[] = '</p>';
        $content[] = '<p>';
        $content[] = $this->mbstringEnabled ? '<i class="fa fa-check mr-2"></i><span data-i18n="phpMbstringEnabled"></span>' : '<i class="fa fa-times mr-2"></i><span data-i18n="phpMbstringDisabled"></span>';
        $content[] = '</p>';
        $content[] = '<p><b>' . ($this->healthStatus ? '<span data-i18n="healthGood"></span>' : '<span data-i18n="healthError"></span>') . '</b></p>';
        $content[] = '</div>';

        return implode('', $content);
    }
}

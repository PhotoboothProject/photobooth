<?php

namespace Photobooth\Service;

use Photobooth\Utility\PathUtility;

class ApplicationService
{
    protected string $version;

    public function __construct()
    {
        $this->version = $this->calculatePhotoboothVersion();
    }

    public function getTitle(): string
    {
        return 'Photobooth ' . $this->getVersion();
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    protected function calculatePhotoboothVersion(): string
    {
        $packageJsonPath = PathUtility::getRootPath() . DIRECTORY_SEPARATOR . '/package.json';
        if (!is_file($packageJsonPath)) {
            throw new \Exception('Package file not found.');
        }
        $packageContent = file_get_contents($packageJsonPath);
        if ($packageContent === false) {
            throw new \Exception('Error loading package file: ' . $packageJsonPath);
        }
        $package = json_decode($packageContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Error decoding package file: ' . json_last_error_msg());
        }
        return $package['version'] ?? 'unknown';
    }

    public function getLatestRelease(): string
    {
        $gh = 'PhotoboothProject';
        $url = 'https://api.github.com/repos/' . $gh . '/photobooth/releases/latest';
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: $gh/photobooth\r\n",
            ],
        ];

        $context = stream_context_create($options);
        $content = file_get_contents($url, false, $context);
        if ($content === false) {
            throw new \Exception('Failed to fetch latest release from GitHub API');
        }

        $data = json_decode($content, true);
        if (!$data || !isset($data['tag_name'])) {
            throw new \Exception('Invalid data returned from GitHub API');
        }

        $remoteVersion = substr($data['tag_name'], 1);
        return $remoteVersion;
    }

    /**
     * Check whether an update to the photobooth software is available.
     */
    public function checkUpdate(): bool
    {
        try {
            $remoteVersion = $this->getLatestRelease();
            $localVersion = $this->getVersion();
            $updateAvailable = version_compare($localVersion, $remoteVersion, '<');

            return $updateAvailable;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function getInstance(): self
    {
        if (!isset($GLOBALS[self::class])) {
            $GLOBALS[self::class] = new self();
        }

        return $GLOBALS[self::class];
    }
}

<?php

namespace Photobooth\Asset\VersionStrategy;

use DateTime;
use Photobooth\Utility\PathUtility;
use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;

class AutoVersionStrategy implements VersionStrategyInterface
{
    private DateTime $requestTime;
    private array $revisionData = [];
    private ?string $packageLockHash = null;

    public function __construct()
    {
        $this->requestTime = new DateTime();

        // revisions file is generated through the gulp
        // build process and stores hash values of all
        // files within the resoures folder
        $manifestPath = PathUtility::getAbsolutePath('resources/revisions.json');
        if (is_file($manifestPath)) {
            try {
                $this->revisionData = json_decode(file_get_contents($manifestPath), true, flags: \JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                // if no revisions file exists we do not want
                // the application to fail, in getVersion it
                // will simply fall back to unversioned files
            }
        }

        // package-lock.json hash
        $packageLockPath = PathUtility::getAbsolutePath('package-lock.json');
        if (is_file($packageLockPath)) {
            $this->packageLockHash = sha1(file_get_contents($packageLockPath));
        }
    }

    public function getVersion(string $path): string
    {
        // all api calls should not be cached at all
        if (str_starts_with($path, 'api')) {
            return $this->requestTime->format('YmdHis');
        }

        // node modules should only be updated when
        // the locked packages are changed
        if (str_starts_with($path, 'node_modules') && $this->packageLockHash !== null) {
            return $this->packageLockHash;
        }

        // always check private files for changed
        // content to avoid caching issues
        if (str_starts_with($path, 'private')) {
            $absolutePath = PathUtility::getAbsolutePath($path);
            if (file_exists($absolutePath)) {
                return sha1(file_get_contents($absolutePath));
            }
        }

        // revision data contains a hash of the
        // current file contents, this one is
        // applied when a hash exists.
        if ($this->revisionData[$path] ?? null) {
            return $this->revisionData[$path];
        }

        // all other resources are considered not
        // versioned, no version will be generated
        return '';
    }

    public function applyVersion(string $path): string
    {
        $version = $this->getVersion($path);
        $path = PathUtility::getPublicPath($path);
        if ($version !== '') {
            return sprintf('%s?v=%s', $path, $version);
        }

        return $path;
    }
}

<?php

namespace Photobooth\Service;

use Photobooth\Asset\VersionStrategy\AutoVersionStrategy;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\Packages;

class AssetService
{
    protected Packages $packages;

    public function __construct()
    {
        $defaultPackage = new Package(new AutoVersionStrategy());
        $this->packages = new Packages($defaultPackage, []);
    }

    public function getUrl(string $path): string
    {
        return $this->packages->getUrl($path);
    }

    public static function getInstance(): self
    {
        if (!isset($GLOBALS[self::class])) {
            throw new \Exception(self::class . ' instance does not exist in $GLOBALS.');
        }

        return $GLOBALS[self::class];
    }
}

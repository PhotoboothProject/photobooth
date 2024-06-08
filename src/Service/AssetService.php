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
            $GLOBALS[self::class] = new self();
        }

        return $GLOBALS[self::class];
    }
}

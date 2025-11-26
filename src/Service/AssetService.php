<?php

namespace Photobooth\Service;

use Photobooth\Asset\VersionStrategy\AutoVersionStrategy;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\Packages;

class AssetService
{
    protected Packages $packages;
    protected ?string $extraVersion = null;

    public function __construct()
    {
        $defaultPackage = new Package(new AutoVersionStrategy());
        $this->packages = new Packages($defaultPackage, []);
    }

    public function getUrl(string $path): string
    {
        $url = $this->packages->getUrl($path);

        if ($this->extraVersion !== null && $this->extraVersion !== '') {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . 'r=' . rawurlencode($this->extraVersion);
        }

        return $url;
    }

    public function setExtraVersion(?string $version): void
    {
        $this->extraVersion = $version;
    }

    public static function getInstance(): self
    {
        if (!isset($GLOBALS[self::class])) {
            $GLOBALS[self::class] = new self();
        }

        return $GLOBALS[self::class];
    }
}

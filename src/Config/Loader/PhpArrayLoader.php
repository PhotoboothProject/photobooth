<?php

namespace Photobooth\Config\Loader;

use Symfony\Component\Config\Loader\FileLoader;

class PhpArrayLoader extends FileLoader
{
    public function load(mixed $resource, ?string $type = null): mixed
    {
        $data = require $this->locator->locate($resource);
        if (!isset($data) || !is_array($data)) {
            // Fallback for $config style
            if (!isset($config) || !is_array($config)) {
                throw new \RuntimeException('Configuration file must return an array.');
            } else {
                $data = $config;
            }
        }

        return $data;
    }

    public function supports($resource, ?string $type = null): bool
    {
        return is_string($resource) && 'php_array' === $type && is_file($this->locator->locate($resource));
    }
}

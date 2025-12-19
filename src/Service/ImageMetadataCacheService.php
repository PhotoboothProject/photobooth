<?php

namespace Photobooth\Service;

use Photobooth\Enum\FolderEnum;

class ImageMetadataCacheService
{
    private const CACHE_FILE = 'image_metadata_cache.json';

    /** @var array<string, array{width:int,height:int}> */
    private array $cache = [];

    private bool $dirty = false;

    public function __construct()
    {
        $cacheFile = $this->getCacheFilePath();
        if (is_file($cacheFile)) {
            $data = file_get_contents($cacheFile);
            if ($data !== false) {
                $decoded = json_decode($data, true);
                if (is_array($decoded)) {
                    $this->cache = array_reduce(
                        array_keys($decoded),
                        function (array $carry, string $key) use ($decoded): array {
                            $value = $decoded[$key];
                            if (
                                is_array($value)
                                && isset($value['width'], $value['height'])
                                && is_int($value['width'])
                                && is_int($value['height'])
                            ) {
                                $carry[$key] = [
                                    'width' => $value['width'],
                                    'height' => $value['height'],
                                ];
                            }
                            return $carry;
                        },
                        []
                    );
                }
            }
        }
    }

    public function __destruct()
    {
        $this->flush();
    }

    private function getCacheFilePath(): string
    {
        return FolderEnum::DATA->absolute() . DIRECTORY_SEPARATOR . self::CACHE_FILE;
    }

    public function get(string $path): ?array
    {
        return $this->cache[$path] ?? null;
    }

    public function set(string $path, int $width, int $height): void
    {
        $existing = $this->cache[$path] ?? null;
        if ($existing !== null && $existing['width'] === $width && $existing['height'] === $height) {
            return;
        }

        $this->cache[$path] = [
            'width' => $width,
            'height' => $height,
        ];
        $this->dirty = true;
    }

    /**
     * Remove a single entry from the cache and persist change.
     */
    public function remove(string $path): void
    {
        if (!isset($this->cache[$path])) {
            return;
        }

        unset($this->cache[$path]);
        $this->dirty = true;
        $this->flush();
    }

    /**
     * Clear the whole cache file and in-memory cache.
     */
    public function clear(): void
    {
        $this->cache = [];
        $this->dirty = false;

        $cacheFile = $this->getCacheFilePath();
        if (is_file($cacheFile)) {
            @unlink($cacheFile);
        }
    }

    private function flush(): void
    {
        if (!$this->dirty) {
            return;
        }

        $cacheFile = $this->getCacheFilePath();
        $encoded = json_encode($this->cache);
        if ($encoded === false) {
            return;
        }

        @file_put_contents($cacheFile, $encoded);
        $this->dirty = false;
    }

    public static function getInstance(): self
    {
        if (!isset($GLOBALS[self::class])) {
            $GLOBALS[self::class] = new self();
        }

        return $GLOBALS[self::class];
    }
}

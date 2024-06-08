<?php

namespace Photobooth\Service;

use Photobooth\Utility\PathUtility;
use Symfony\Component\Finder\Finder;

class SoundService
{
    private string $voice;
    private string $locale;
    private bool $fallback;

    private array $files = [
        'counter-1' => null,
        'counter-2' => null,
        'counter-3' => null,
        'counter-4' => null,
        'counter-5' => null,
        'counter-6' => null,
        'counter-7' => null,
        'counter-8' => null,
        'counter-9' => null,
        'counter-10' => null,
        'cheese' => null,
    ];

    public function __construct()
    {
        $config = ConfigurationService::getInstance()->getConfiguration();
        $this->locale = $config['ui']['language'] ?? 'en';
        $this->voice = $config['sound']['voice'] ?? 'man';
        $this->fallback =  $config['sound']['fallback_enabled'] ?? true;

        $this->files['counter-1'] = $this->findSoundFile('counter-1');
        $this->files['counter-2'] = $this->findSoundFile('counter-2');
        $this->files['counter-3'] = $this->findSoundFile('counter-3');
        $this->files['counter-4'] = $this->findSoundFile('counter-4');
        $this->files['counter-5'] = $this->findSoundFile('counter-5');
        $this->files['counter-6'] = $this->findSoundFile('counter-6');
        $this->files['counter-7'] = $this->findSoundFile('counter-7');
        $this->files['counter-8'] = $this->findSoundFile('counter-8');
        $this->files['counter-9'] = $this->findSoundFile('counter-9');
        $this->files['counter-10'] = $this->findSoundFile('counter-10');
        $this->files['cheese'] = $this->findSoundFile('cheese');
    }

    public function all(bool $absolute = false): array
    {
        if ($absolute) {
            return $this->files;
        }

        return array_map(function ($file) {
            return $file !== null ? PathUtility::getPublicPath($file) : null;
        }, $this->files);
    }

    protected function findSoundFile(string $name): ?string
    {
        $directories = [];
        if ($this->voice === 'custom') {
            $directories[] = PathUtility::getAbsolutePath('private/sounds/');
        } else {
            $directories[] = PathUtility::getAbsolutePath('resources/sounds/' . $this->voice . '/' . $this->locale);
            if ($this->fallback) {
                $directories[] = PathUtility::getAbsolutePath('resources/sounds/' . $this->voice . '/en/');
            }
        }
        if ($this->fallback) {
            $directories[] = PathUtility::getAbsolutePath('resources/sounds/man/en/');
        }

        $directories = array_filter($directories, function ($directory) {
            return file_exists($directory);
        });
        if (count($directories) === 0) {
            return null;
        }

        $finder = (new Finder())
            ->files()
            ->in($directories)->name([$name . '.mp3']);
        if ($finder->count() === 0) {
            return null;
        }

        $files = array_values(iterator_to_array($finder));
        $file = $files[0];

        return $file->getPathname();
    }

    public static function getInstance(): self
    {
        if (!isset($GLOBALS[self::class])) {
            $GLOBALS[self::class] = new self();
        }

        return $GLOBALS[self::class];
    }
}

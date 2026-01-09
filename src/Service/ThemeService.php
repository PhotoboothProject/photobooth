<?php

namespace Photobooth\Service;

use Photobooth\Utility\PathUtility;
use ZipArchive;

class ThemeService
{
    private string $themeDirectory;
    /** @var string[] */
    private array $assetExtensions = [
        'png',
        'jpg',
        'jpeg',
        'gif',
        'webp',
        'svg',
        'avif',
        'heic',
        'bmp',
        // video assets (e.g. screensaver video)
        'mp4',
        'webm',
        'm4v',
        'mov',
        // font assets
        'ttf',
        'otf',
        'woff',
        'woff2',
    ];

    public function __construct()
    {
        $this->themeDirectory = PathUtility::getAbsolutePath('private/themes');

        if (!is_dir($this->themeDirectory)) {
            @mkdir($this->themeDirectory, 0775, true);
        }
    }

    public static function getInstance(): self
    {
        if (!isset($GLOBALS[self::class])) {
            $GLOBALS[self::class] = new self();
        }

        return $GLOBALS[self::class];
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public function getAll(): array
    {
        $directory = $this->themeDirectory;
        $themes = [];
        if (!is_dir($directory)) {
            return $themes;
        }

        $files = glob($directory . DIRECTORY_SEPARATOR . '*.theme.config.json');
        if ($files === false) {
            return $themes;
        }

        foreach ($files as $file) {
            $name = basename($file, '.theme.config.json');
            if (isset($themes[$name])) {
                continue;
            }

            $raw = @file_get_contents($file);
            if ($raw === false) {
                continue;
            }

            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                continue;
            }

            $themes[$name] = $decoded;
        }

        return $themes;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function get(string $name): ?array
    {
        if ($name === '') {
            return null;
        }

        $file = $this->getFilePath($name);
        if (is_file($file)) {
            $raw = @file_get_contents($file);
            if ($raw !== false) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        return null;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function save(string $name, array $data): void
    {
        if ($name === '') {
            return;
        }

        $file = $this->getFilePath($name);

        if (!is_dir($this->themeDirectory)) {
            @mkdir($this->themeDirectory, 0775, true);
        }

        @file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function delete(string $name): void
    {
        if ($name === '') {
            return;
        }

        $file = $this->getFilePath($name);
        if (is_file($file)) {
            @unlink($file);
        }

        $this->removeAssetsDirectory($name);
    }

    /**
     * @return array{success:bool,message?:string,file?:string,downloadName?:string}
     */
    public function exportTheme(string $name): array
    {
        $theme = $this->get($name);
        if ($theme === null) {
            return [
                'success' => false,
                'message' => 'Theme not found',
            ];
        }

        $safeName = $this->getSafeName($name);
        $tempZip = tempnam(sys_get_temp_dir(), 'theme_export_');
        if ($tempZip === false) {
            return [
                'success' => false,
                'message' => 'Unable to create temporary file',
            ];
        }

        $zip = new ZipArchive();
        if ($zip->open($tempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return [
                'success' => false,
                'message' => 'Unable to open archive',
            ];
        }

        // add theme config file at its project-relative location
        $themeRelativePath = 'private/themes/' . $safeName . '.theme.config.json';
        $themeJson = json_encode($theme, JSON_PRETTY_PRINT);
        if ($themeJson === false) {
            return [
                'success' => false,
                'message' => 'Unable to encode theme',
            ];
        }
        $zip->addFromString($themeRelativePath, $themeJson);

        // add referenced assets using their project-relative paths
        $assets = $this->collectAssetCandidates($theme);
        foreach ($assets as $asset) {
            $absolutePath = $asset['absolute'];
            $relativePath = $asset['relative'];

            if (!is_readable($absolutePath)) {
                continue;
            }

            $zip->addFile($absolutePath, $relativePath);
        }

        $zip->close();

        $downloadName = sprintf(
            'photobooth_theme_%s_%s.zip',
            $safeName,
            date('Ymd_His')
        );

        return [
            'success' => true,
            'file' => $tempZip,
            'downloadName' => $downloadName,
        ];
    }

    /**
     * @return array{success:bool,message?:string,name?:string,theme?:array<string,mixed>}
     */
    public function importTheme(string $zipPath, ?string $targetName = null): array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return [
                'success' => false,
                'message' => 'Could not open archive',
            ];
        }

        // Find theme config in zip
        $themeFileIndex = null;
        $themeFilename = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (!is_array($stat)) {
                continue;
            }
            /** @var array{name:string} $stat */
            $nameInZip = $stat['name'];
            if (str_ends_with($nameInZip, '.theme.config.json')) {
                $themeFileIndex = $i;
                $themeFilename = $nameInZip;
                break;
            }
        }

        if ($themeFileIndex === null || $themeFilename === null) {
            $zip->close();
            return [
                'success' => false,
                'message' => 'Archive does not contain a theme config',
            ];
        }

        $themeContent = $zip->getFromIndex($themeFileIndex);
        if ($themeContent === false) {
            $zip->close();
            return [
                'success' => false,
                'message' => 'Unable to read theme config',
            ];
        }

        $themeData = json_decode($themeContent, true);
        if (!is_array($themeData)) {
            $zip->close();
            return [
                'success' => false,
                'message' => 'Invalid theme config JSON',
            ];
        }

        $themeName = basename($themeFilename, '.theme.config.json');
        $safeName = $this->getSafeName($themeName);

        // extract allowed files preserving structure
        $allowedPrefixes = ['private/'];
        $root = PathUtility::getRootPath();
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (!is_array($stat)) {
                continue;
            }
            /** @var array{name:string} $stat */
            $entryName = $stat['name'];
            // Normalize
            $entryName = PathUtility::fixFilePath($entryName);

            $isAllowed = false;
            foreach ($allowedPrefixes as $prefix) {
                if (str_starts_with($entryName, $prefix)) {
                    $isAllowed = true;
                    break;
                }
            }

            if (!$isAllowed) {
                continue;
            }

            $targetPath = $root . ltrim($entryName, '/');
            $targetPath = PathUtility::fixFilePath($targetPath);

            // guard against directory traversal
            if (!str_starts_with($targetPath, $root)) {
                continue;
            }

            if (str_ends_with($entryName, '/')) {
                if (!is_dir($targetPath)) {
                    @mkdir($targetPath, 0775, true);
                }
                continue;
            }

            $dir = dirname($targetPath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }

            $stream = $zip->getStream($stat['name']);
            if ($stream === false) {
                continue;
            }
            $content = stream_get_contents($stream);
            fclose($stream);
            if ($content === false) {
                continue;
            }
            @file_put_contents($targetPath, $content);
        }

        $this->save($safeName, $themeData);

        $zip->close();

        return [
            'success' => true,
            'name' => $safeName,
            'theme' => $themeData,
        ];
    }

    private function getFilePath(string $name): string
    {
        $safeName = $this->getSafeName($name);

        return $this->themeDirectory . DIRECTORY_SEPARATOR . $safeName . '.theme.config.json';
    }

    private function getSafeName(string $name): string
    {
        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name);
        if ($safeName === null) {
            $safeName = '';
        }
        if ($safeName === '') {
            $safeName = 'theme';
        }

        return $safeName;
    }

    /**
     * @param array<string,mixed> $theme
     * @return array<int,array{original:string,absolute:string,relative:string}>
     */
    private function collectAssetCandidates(array $theme): array
    {
        $results = [];
        $this->walkThemeValues($theme, function (string $value) use (&$results) {
            $trimmed = trim($value);
            $pathPart = explode('?', $trimmed)[0];
            $extension = strtolower(pathinfo($pathPart, PATHINFO_EXTENSION));
            if ($extension === '' || !in_array($extension, $this->assetExtensions, true)) {
                return;
            }

            if (PathUtility::isUrl($pathPart)) {
                return;
            }

            try {
                $absolute = PathUtility::resolveFilePath($pathPart);
                $rootPath = PathUtility::getRootPath();
                if (!is_readable($absolute) || !str_starts_with($absolute, $rootPath)) {
                    return;
                }
            } catch (\Throwable) {
                return;
            }

            foreach ($results as $existing) {
                if ($existing['original'] === $value) {
                    return;
                }
            }

            $projectRelative = PathUtility::toProjectRelative($absolute);

            // Skip generated assets living in resources/ per export policy
            if (str_starts_with($projectRelative, 'resources/')) {
                return;
            }

            $results[] = [
                'original' => $value,
                'absolute' => $absolute,
                'relative' => $projectRelative,
            ];
        });

        return $results;
    }

    private function getAssetsDirectory(string $name): string
    {
        $safeName = $this->getSafeName($name);

        return $this->themeDirectory . DIRECTORY_SEPARATOR . $safeName . DIRECTORY_SEPARATOR . 'assets';
    }

    private function removeAssetsDirectory(string $name): void
    {
        $assetsDir = $this->getAssetsDirectory($name);
        if (!is_dir($assetsDir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($assetsDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            if ($fileinfo->isDir()) {
                @rmdir($fileinfo->getRealPath());
            } else {
                @unlink($fileinfo->getRealPath());
            }
        }

        @rmdir($assetsDir);
        $themeDir = dirname($assetsDir);
        $children = glob($themeDir . DIRECTORY_SEPARATOR . '*');
        if (is_dir($themeDir) && ($children === false || count($children) === 0)) {
            @rmdir($themeDir);
        }
    }

    /**
     * Walk nested theme array and call callback for each string value.
     *
     * @param array<string,mixed> $data
     * @param callable(string):void $callback
     */
    private function walkThemeValues(array $data, callable $callback): void
    {
        foreach ($data as $value) {
            if (is_array($value)) {
                $this->walkThemeValues($value, $callback);
                continue;
            }

            if (is_string($value)) {
                $callback($value);
            }
        }
    }
}

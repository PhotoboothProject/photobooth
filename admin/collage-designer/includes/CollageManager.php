<?php
namespace Photobooth\CollageDesigner\Includes;

use Photobooth\Utility\PathUtility;

class CollageManager
{
    private string $designsPath;
    private string $indexFile;

    public function __construct()
    {
        $this->designsPath = PathUtility::getAbsolutePath('private/collages/');
        $this->indexFile = $this->designsPath . 'designs_index.json';
        $this->ensureDesignsDirectoryExists();
    }

    private function ensureDesignsDirectoryExists(): void
    {
        if (!is_dir($this->designsPath)) {
            mkdir($this->designsPath, 0777, true); // Erstelle Verzeichnis, wenn nicht vorhanden
        }
        if (!file_exists($this->indexFile)) {
            file_put_contents($this->indexFile, json_encode([])); // Leere Index-Datei erstellen
        }
    }

    public function getAvailableDesigns(): array
    {
        if (!file_exists($this->indexFile)) {
            return [];
        }
        $content = file_get_contents($this->indexFile);
        return json_decode($content, true) ?: [];
    }

    public function loadDesign(string $filename): ?array
    {
        $filePath = $this->designsPath . $filename;
        if (file_exists($filePath)) {
            return json_decode(file_get_contents($filePath), true);
        }
        return null;
    }

    public function saveDesign(string $name, array $data, ?string $originalFilename = null): string
    {
        $designs = $this->getAvailableDesigns();
        $filename = $originalFilename ?: $this->generateUniqueFilename($name);
        $filePath = $this->designsPath . $filename;

        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));

        // Update index
        $found = false;
        foreach ($designs as &$design) {
            if ($design['filename'] === $filename) {
                $design['name'] = $name;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $designs[] = ['name' => $name, 'filename' => $filename];
        }
        file_put_contents($this->indexFile, json_encode($designs, JSON_PRETTY_PRINT));

        return $filename;
    }

    public function deleteDesign(string $filename): bool
    {
        $filePath = $this->designsPath . $filename;
        if (file_exists($filePath)) {
            unlink($filePath);

            // Update index
            $designs = $this->getAvailableDesigns();
            $designs = array_filter($designs, fn($design) => $design['filename'] !== $filename);
            file_put_contents($this->indexFile, json_encode(array_values($designs), JSON_PRETTY_PRINT)); // array_values um Indizes zurückzusetzen

            return true;
        }
        return false;
    }

    private function generateUniqueFilename(string $name): string
    {
        $base = strtolower(preg_replace('/[^a-z0-9]/', '-', $name));
        $filename = $base . '.json';
        $counter = 1;
        while (file_exists($this->designsPath . $filename)) {
            $filename = $base . '-' . $counter++ . '.json';
        }
        return $filename;
    }
}

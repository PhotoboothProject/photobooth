<?php

namespace Photobooth\Utility;

use Photobooth\Utility\PathUtility;
use Photobooth\Service\LanguageService;

class CollageLayoutScanner
{
    /**
     * Scans predefined directories for collage layout JSON files and groups them.
     *
     * @return array An associative array of grouped collage layouts.
     *               Example: ['Standard-Layouts' => ['Portrait-Layouts' => [...]], 'Eigene Layouts' => ['Community-Layouts' => [...]]]
     */
    public static function scanLayouts(): array
    {
        $layoutFiles = [];

        // Define the main base directories for grouping (e.g., 'template', 'private')
        // Use simple keys ('template', 'private') for logical grouping, map to actual paths.
        $mainBaseDirs = [
            'template' => 'template/collage', // Standard layouts path
            'private' => 'private/collage',   // User-defined/community layouts path
        ];

        foreach ($mainBaseDirs as $mainGroupKey => $baseDirRelativePath) {
            $absoluteBaseDir = PathUtility::getAbsolutePath($baseDirRelativePath);

            // Initialize the main group key in $layoutFiles early
            $layoutFiles[$mainGroupKey] = [];
            
            // Ensure the base directory exists, create if it's a 'private' one and missing
            if (!is_dir($absoluteBaseDir)) {
                if ($mainGroupKey === 'private') { 
                    try {
                        mkdir($absoluteBaseDir, 0777, true);
                    } catch (\Exception $e) {
                        error_log('CollageLayoutScanner: Failed to create base directory: ' . $absoluteBaseDir . ' - ' . $e->getMessage());
                        continue; 
                    }
                } else {
                    continue; // Skip if 'template' base dir doesn't exist (expected to be present)
                }
            }

            // --- Scan subdirectories for specific groups (e.g., 'portrait', 'landscape', 'community') ---
            $subDirNames = ['portrait', 'landscape', 'community']; // Extend as needed

            foreach ($subDirNames as $subGroupName) {
                $subDirPath = $absoluteBaseDir . DIRECTORY_SEPARATOR . $subGroupName;

                // Ensure the subdirectory exists, create if it's in a 'private' context and missing
                if (!is_dir($subDirPath)) {
                    if ($mainGroupKey === 'private') { 
                        try {
                            mkdir($subDirPath, 0777, true);
                        } catch (\Exception $e) {
                            error_log('CollageLayoutScanner: Failed to create subdirectory: ' . $subDirPath . ' - ' . $e->getMessage());
                            continue; 
                        }
                    } else {
                        continue; // Skip if 'template' subdir doesn't exist (expected to be present)
                    }
                }
                
                // If directory exists (or was created), scan it
                // Pass the mainGroupKey AND the subGroupName to build the nested structure
                self::scanDirectory($subDirPath, $layoutFiles[$mainGroupKey], $subGroupName);
            }
        }

        return self::groupAndTranslateLayouts($layoutFiles);
    }

    /**
     * Scans a given directory for JSON files and extracts relevant layout data.
     *
     * @param string $directory The absolute path to the directory.
     * @param array  $layoutFiles Reference to the array to store found layouts for the current main group.
     * @param string $subGroupKey The key for the subgroup (e.g., 'landscape', 'community', 'square').
     */
    private static function scanDirectory(string $directory, array &$layoutFiles, string $subGroupKey): void
    {
        $files = glob($directory . DIRECTORY_SEPARATOR . '*.json');
        foreach ($files as $filePath) {
            $fileContent = file_get_contents($filePath);
            if ($fileContent === false) {
                error_log('CollageLayoutScanner: Could not read file: ' . $filePath);
                continue;
            }

            $layoutConfig = json_decode($fileContent, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($layoutConfig)) {
                error_log('CollageLayoutScanner: Malformed JSON in file: ' . $filePath);
                continue;
            }

            $layoutId = basename($filePath, '.json'); 
            
            $layoutName = $layoutConfig['name'] ?? $layoutId; 

            // Group by the provided $subGroupKey within the main group
            // $layoutFiles is passed by reference and already represents $layoutFiles[$mainGroupKey] from scanLayouts
            $layoutFiles[$subGroupKey][$layoutId] = [
                'id' => $layoutId,
                'name' => $layoutName,
                'description' => $layoutConfig['description'] ?? '', 
                'file_path' => $filePath, 
                'author' => $layoutConfig['author'] ?? 'Unknown',
                'aspect_ratio' => $layoutConfig['aspect_ratio'] ?? '',
                'width' => $layoutConfig['width'] ?? '',
                'height' => $layoutConfig['height'] ?? '',
            ];
        }
    }

    /**
     * Groups and translates the found layouts for display without explicit sorting.
     *
     * @param array $rawLayoutFiles The raw array of found layouts, grouped by main group and subgroup key.
     * @return array The grouped layouts, with translated group titles.
     */
    private static function groupAndTranslateLayouts(array $rawLayoutFiles): array
    {
        $groupedLayouts = [];
        $languageService = LanguageService::getInstance();

        // Define a desired order and translation keys for the main groups (template, private)
        $mainGroupTranslationKeys = [
            'template' => 'standard_layouts', // e.g., "Standard Layouts"
            'private' => 'custom_layouts',    // e.g., "Eigene Layouts"
        ];

        // Define a desired order and translation keys for the subgroups (portrait, landscape, community)
        $subGroupTranslationKeys = [
            'portrait' => 'portrait',
            'landscape' => 'landscape',
            'community' => 'community_layouts',
            // Add other subdir names here
        ];

        foreach ($mainGroupTranslationKeys as $mainGroupKey => $mainTransKey) {
            $translatedMainGroupTitle = $languageService->translate($mainTransKey);
            $groupedLayouts[$translatedMainGroupTitle] = []; // Initialize main group

            if (isset($rawLayoutFiles[$mainGroupKey])) {
                foreach ($subGroupTranslationKeys as $subGroupKey => $subTransKey) {
                    if (isset($rawLayoutFiles[$mainGroupKey][$subGroupKey])) {
                        $translatedSubGroupTitle = $languageService->translate($subTransKey);
                        // Add directly, no sorting
                        $groupedLayouts[$translatedMainGroupTitle][$translatedSubGroupTitle] = $rawLayoutFiles[$mainGroupKey][$subGroupKey];
                    }
                }
                // Handle any subgroups not explicitly defined in $subGroupTranslationKeys (e.g., new custom folder)
                foreach ($rawLayoutFiles[$mainGroupKey] as $subGroupKey => $layouts) {
                    if (!array_key_exists($subGroupKey, $subGroupTranslationKeys)) {
                        $translatedSubGroupTitle = $languageService->translate($subGroupKey); // Try to translate, fallback to key
                        $groupedLayouts[$translatedMainGroupTitle][$translatedSubGroupTitle] = $layouts;
                    }
                }
            }
        }
        
        return $groupedLayouts;
    }
}
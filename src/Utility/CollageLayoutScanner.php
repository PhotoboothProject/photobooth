<?php

namespace Photobooth\Utility;

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
                self::scanDirectory($subDirPath, $layoutFiles[$mainGroupKey], $subGroupName, $mainGroupKey);
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
     * @param string $mainGroupKey The key for the main group (e.g., 'template', 'private').
     */
    private static function scanDirectory(string $directory, array &$layoutFiles, string $subGroupKey, string $mainGroupKey): void
    {
        $files = glob($directory . DIRECTORY_SEPARATOR . '*.json');
        if ($files === false) {
            return;
        }

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

            $refFilePath = $mainGroupKey . '/collage/' . $subGroupKey . '/' . $layoutId;

            // Group by the provided $subGroupKey within the main group
            // $layoutFiles is passed by reference and already represents $layoutFiles[$mainGroupKey] from scanLayouts
            $layoutFiles[$subGroupKey][$layoutId] = [
                'id' => $layoutId,
                'name' => $layoutName,
                'description' => $layoutConfig['description'] ?? '',
                'author' => $layoutConfig['author'] ?? 'Unknown',
                'ref_file_path' => $refFilePath,
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

    /**
     * Retrieves full layout data by its logical reference path.
     * This method loads the actual JSON content from the file.
     *
     * @param string $logicalReferencePath The unique logical path (e.g., "template/portrait/my-layout-id").
     * @return array|null The complete layout data array including the 'layout' content, or null if not found/invalid.
     */
    public static function getLayoutData(string $logicalReferencePath): ?array
    {
        $AbsFilePath = self::getCollageConfigPath($logicalReferencePath);

        if ($AbsFilePath === null) {
            return null;
        }

        $fileContent = file_get_contents($AbsFilePath);

        if ($fileContent === false) {
            error_log('CollageLayoutScanner: Could not read file: ' . $AbsFilePath);
            return [];
        }

        $layoutConfig = json_decode($fileContent, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($layoutConfig)) {
            error_log('CollageLayoutScanner: Malformed JSON in file: ' . $AbsFilePath);
            return [];
        }

        $layoutId = basename($AbsFilePath, '.json');

        $layoutName = $layoutConfig['name'] ?? $layoutId;

        $layoutData = [
                'id' => $layoutId,
                'name' => $layoutName,
                'ref_file_path' => $logicalReferencePath,
            ];

        $layoutData = array_merge($layoutConfig, $layoutData);

        return $layoutData;
    }

    /**
     * Scans for collage layouts and returns them formatted for an HTML select dropdown.
     * Each option's value will be the ref_file_path and its label the display name.
     *
     * @param string|null $currentSelectedPath The currently selected ref_file_path to mark an option as 'selected'.
     * @return string HTML string of <option> and <optgroup> elements.
     */
    public static function getLayoutSelectOptionsHtml(?string $currentSelectedPath = null): string
    {
        $designes = self::scanLayouts();

        $optionsHtml = '';

        foreach ($designes as $mainGroupTitle => $subGroups) {
            $optionsHtml .= '<optgroup label="' . htmlspecialchars($mainGroupTitle, ENT_QUOTES) . '">';

            // Sort subgroups by their translated titles to ensure consistent order
            ksort($subGroups);

            foreach ($subGroups as $subGroupTitle => $layouts) {
                // Add a disabled option as a heading for the subgroup, if not empty
                if (!empty($subGroupTitle)) {
                    $optionsHtml .= '<option disabled>' . str_repeat('&nbsp;', 4) . '--- ' . htmlspecialchars($subGroupTitle, ENT_QUOTES) . ' ---</option>';
                }

                // Sort the layouts within the subgroup by their name
                uasort($layouts, function ($a, $b) {
                    return strcmp($a['name'] ?? $a['id'], $b['name'] ?? $b['id']);
                });

                foreach ($layouts as $layoutId => $layoutData) {
                    $selected = ($layoutData['ref_file_path'] === $currentSelectedPath) ? ' selected="selected"' : '';

                    $displayName = htmlspecialchars($layoutData['name'] ?? $layoutData['id'] ?? '', ENT_QUOTES);

                    $optionsHtml .= '<option value="' . htmlspecialchars($layoutData['ref_file_path'], ENT_QUOTES) . '"' . $selected . '>';
                    $optionsHtml .= str_repeat('&nbsp;', 8);
                    $optionsHtml .= $displayName . '</option>';
                }
            }
            $optionsHtml .= '</optgroup>';
        }
        return $optionsHtml;
    }

    /**
     * Helper to get the absolute path for a logical collage layout reference path.
     * Checks if the file exists and returns the path or null.
     * This method is also used for validation in CollageConfiguration.
     *
     * @param string $logicalReferencePath e.g., 'template/collage/landscape/1+2-1'
     * @return string|null Absolute file path to the JSON file, or null if not found.
     */
    public static function getCollageConfigPath(string $logicalReferencePath): ?string
    {
        // Add the .json extension
        $fullPathWithExtension = $logicalReferencePath . '.json';

        // Let PathUtility build the absolute path
        $absolutePath = PathUtility::getAbsolutePath($fullPathWithExtension);

        if (file_exists($absolutePath)) {
            return $absolutePath;
        }

        // Log, falls die Datei nicht gefunden wird, hilfreich für Debugging
        error_log('DEBUG: CollageLayoutScanner::getCollageConfigPath - Layout JSON file not found at: ' . $absolutePath);
        return null;
    }
}

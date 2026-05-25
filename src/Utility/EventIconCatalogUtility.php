<?php

namespace Photobooth\Utility;

/**
 * Builds a combined icon catalog for event-focused symbol selection.
 *
 * Sources:
 * - Lucide (full local icon set with lucide.dev metadata)
 * - Iconify (event keyword search across monochrome UI/material sets)
 * - Legacy Font Awesome icons are appended separately by AdminInput.
 * - Custom uploaded symbol images are appended dynamically.
 */
final class EventIconCatalogUtility
{
    private const CACHE_VERSION = 3;
    private const CACHE_FILE = 'private/cache/event-icons/catalog.json';
    private const CACHE_TTL_SECONDS = 86400;
    private const HTTP_TIMEOUT_SECONDS = 12;

    private const LUCIDE_CATEGORIES_URL = 'https://lucide.dev/api/categories';
    private const LUCIDE_TAGS_URL = 'https://lucide.dev/api/tags';

    private const ICONIFY_SEARCH_URL = 'https://api.iconify.design/search';
    private const ICONIFY_LIMIT = 999;
    private const ICONIFY_MAX_PER_PREFIX = 40;
    private const ICONIFY_MAX_RENDERABLE_POOL = 4500;

    /**
     * Keep event-relevant Lucide categories and skip pure technical ones.
     *
     * @var string[]
     */
    private const LUCIDE_ALLOWED_CATEGORIES = [
        'account',
        'animals',
        'buildings',
        'emoji',
        'finance',
        'food-beverage',
        'home',
        'medical',
        'multimedia',
        'nature',
        'navigation',
        'notifications',
        'people',
        'photography',
        'seasons',
        'security',
        'shapes',
        'shopping',
        'social',
        'sports',
        'sustainability',
        'time',
        'tools',
        'transportation',
        'travel',
        'weather',
    ];

    /**
     * Keywords to suppress obviously technical/navigation-heavy icons.
     *
     * @var string[]
     */
    private const BLOCKED_KEYWORDS = [
        'arrow',
        'chevron',
        'wifi',
        'wlan',
        'bluetooth',
        'signal',
        'router',
        'server',
        'database',
        'terminal',
        'code',
        'binary',
        'cpu',
        'laptop',
        'monitor',
        'webhook',
        'antenna',
        'satellite',
        'radar',
        'barcode',
        'qr',
    ];

    /**
     * Additional event-related matching terms for Lucide tags/names.
     *
     * @var string[]
     */
    private const EVENT_KEYWORDS = [
        'camera',
        'photo',
        'video',
        'party',
        'balloon',
        'cake',
        'gift',
        'heart',
        'wedding',
        'ring',
        'bbq',
        'grill',
        'beer',
        'wine',
        'cocktail',
        'sun',
        'beach',
        'flower',
        'rose',
        'tree',
        'leaf',
        'music',
        'dance',
        'ticket',
        'sparkle',
        'confetti',
        'calendar',
        'clock',
        'location',
        'travel',
        'map',
        'pin',
        'smile',
        'star',
        'crown',
        'fire',
        'flame',
        'snow',
        'food',
        'drink',
        'settings',
        'gear',
        'cog',
    ];

    /**
     * Unified category set used by the picker (independent of icon source).
     *
     * @var array<string,array{title:string,source:string}>
     */
    private const CATEGORY_DEFINITIONS = [
        'all' => ['title' => 'Alle', 'source' => 'system'],
        'event' => ['title' => 'Event/Party', 'source' => 'mixed'],
        'photo' => ['title' => 'Foto/Media', 'source' => 'mixed'],
        'love' => ['title' => 'Liebe', 'source' => 'mixed'],
        'food' => ['title' => 'Food/Drink', 'source' => 'mixed'],
        'nature' => ['title' => 'Natur/Wetter', 'source' => 'mixed'],
        'music' => ['title' => 'Musik', 'source' => 'mixed'],
        'people' => ['title' => 'Menschen', 'source' => 'mixed'],
        'time' => ['title' => 'Zeit/Ort', 'source' => 'mixed'],
        'tools' => ['title' => 'Tools', 'source' => 'mixed'],
        'legacy' => ['title' => 'Klassisch (FA)', 'source' => 'legacy'],
        'custom-images' => ['title' => 'Eigene Bilder', 'source' => 'custom'],
    ];

    /**
     * Map Lucide categories to unified picker categories.
     *
     * @var array<string,string[]>
     */
    private const LUCIDE_CATEGORY_TO_GROUPS = [
        'account' => ['people'],
        'animals' => ['nature'],
        'buildings' => ['time'],
        'emoji' => ['event', 'people'],
        'finance' => ['event'],
        'food-beverage' => ['food'],
        'home' => ['event'],
        'medical' => ['event'],
        'multimedia' => ['photo', 'music'],
        'nature' => ['nature'],
        'navigation' => ['time'],
        'notifications' => ['event'],
        'people' => ['people'],
        'photography' => ['photo'],
        'seasons' => ['nature'],
        'security' => ['tools'],
        'shapes' => ['tools'],
        'shopping' => ['event'],
        'social' => ['people', 'love'],
        'sports' => ['event'],
        'sustainability' => ['nature'],
        'time' => ['time'],
        'tools' => ['tools'],
        'transportation' => ['time'],
        'travel' => ['time'],
        'weather' => ['nature'],
    ];

    /**
     * Keyword fallback mapping for both Lucide and Iconify items.
     *
     * @var array<string,string[]>
     */
    private const CATEGORY_KEYWORDS = [
        'event' => ['party', 'balloon', 'confetti', 'sparkle', 'ticket', 'celebration', 'gift', 'crown', 'firework'],
        'photo' => ['camera', 'photo', 'video', 'selfie', 'flash', 'image', 'gallery', 'film'],
        'love' => ['heart', 'love', 'wedding', 'ring', 'rose', 'kiss', 'romance'],
        'food' => ['bbq', 'grill', 'cake', 'beer', 'wine', 'cocktail', 'food', 'drink', 'coffee', 'tea', 'pizza', 'burger'],
        'nature' => ['sun', 'beach', 'flower', 'tree', 'leaf', 'snow', 'nature', 'cloud', 'rain', 'weather', 'mountain'],
        'music' => ['music', 'dance', 'microphone', 'speaker', 'guitar', 'dj', 'headphone', 'note'],
        'people' => ['people', 'person', 'user', 'group', 'family', 'smile', 'friends'],
        'time' => ['calendar', 'clock', 'location', 'map', 'travel', 'pin', 'route', 'compass', 'time'],
        'tools' => ['settings', 'gear', 'cog', 'wrench', 'hammer', 'tool'],
    ];

    /**
     * Iconify query buckets mapped to unified category IDs.
     *
     * @var array<string,string[]>
     */
    private const ICONIFY_BUCKETS = [
        'photo' => ['camera', 'photo', 'video', 'selfie', 'flash'],
        'event' => ['party', 'balloon', 'confetti', 'sparkle', 'ticket', 'celebration'],
        'love' => ['heart', 'wedding', 'ring', 'rose', 'kiss'],
        'food' => ['bbq', 'grill', 'cake', 'beer', 'wine', 'cocktail', 'food'],
        'nature' => ['sun', 'beach', 'flower', 'tree', 'leaf', 'snow'],
        'music' => ['music', 'dance', 'microphone', 'speaker'],
        'people' => ['people', 'user', 'group', 'smile'],
        'time' => ['calendar', 'clock', 'location', 'map', 'travel'],
        'tools' => ['settings', 'gear', 'cog'],
    ];

    /**
     * @return array{generatedAt:string,categories:array<int,array{id:string,title:string,source:string}>,icons:array<int,array{provider:string,value:string,label:string,categories:array<int,string>,search:string}>}
     */
    public static function getCatalog(): array
    {
        $cachePath = PathUtility::getAbsolutePath(self::CACHE_FILE);
        $cached = self::readCache($cachePath);

        if ($cached !== null && self::isCacheFresh($cachePath)) {
            return self::withCustomImages($cached);
        }

        $fresh = self::buildCatalog();
        if ($fresh !== null) {
            self::writeCache($cachePath, $fresh);
            return self::withCustomImages($fresh);
        }

        if ($cached !== null) {
            return self::withCustomImages($cached);
        }

        return self::withCustomImages(self::fallbackCatalog());
    }

    public static function invalidateCache(): void
    {
        $cachePath = PathUtility::getAbsolutePath(self::CACHE_FILE);
        if (is_file($cachePath)) {
            @unlink($cachePath);
        }
    }

    public static function getCustomImageDirectoryRelative(): string
    {
        return EventSymbolUtility::getCustomImageDirectory();
    }

    public static function getCustomImageDirectoryAbsolute(): string
    {
        return PathUtility::getAbsolutePath(self::getCustomImageDirectoryRelative());
    }

    public static function isAllowedCustomImageExtension(string $extension): bool
    {
        return in_array(strtolower($extension), EventSymbolUtility::getAllowedCustomImageExtensions(), true);
    }

    /**
     * @return array{provider:string,value:string,label:string,categories:array<int,string>,search:string}|null
     */
    public static function buildCustomImageEntry(string $relativePath): ?array
    {
        $normalized = EventSymbolUtility::normalizeCustomImageValue('image:' . ltrim($relativePath, '/'));
        if ($normalized === '') {
            return null;
        }

        $path = EventSymbolUtility::getCustomImagePath($normalized);
        if ($path === '') {
            return null;
        }

        $baseName = pathinfo($path, PATHINFO_FILENAME);
        $label = self::humanizeIconifyName((string) $baseName);
        if ($label === '') {
            $label = basename($path);
        }

        return [
            'provider' => 'image',
            'value' => $normalized,
            'label' => $label,
            'categories' => ['custom-images'],
            'search' => strtolower($label . ' ' . $path . ' custom image bild'),
        ];
    }

    /**
     * @return array{generatedAt:string,categories:array<int,array{id:string,title:string,source:string}>,icons:array<int,array{provider:string,value:string,label:string,categories:array<int,string>,search:string}>}|null
     */
    private static function buildCatalog(): ?array
    {
        $lucideIconNames = LucideIconUtility::getIconNames();
        if ($lucideIconNames === []) {
            return null;
        }

        $lucideCategoriesByIcon = self::fetchJson(self::LUCIDE_CATEGORIES_URL);
        $lucideTagsByIcon = self::fetchJson(self::LUCIDE_TAGS_URL);
        if (!is_array($lucideCategoriesByIcon) || !is_array($lucideTagsByIcon)) {
            return null;
        }

        $lucideEntries = self::buildLucideEntries(
            $lucideIconNames,
            $lucideCategoriesByIcon,
            $lucideTagsByIcon,
        );

        $iconifyEntries = self::buildIconifyEntries();
        $icons = array_merge($lucideEntries, $iconifyEntries);

        usort($icons, static function (array $a, array $b): int {
            if ($a['provider'] !== $b['provider']) {
                return strcmp($a['provider'], $b['provider']);
            }
            return strcmp($a['label'], $b['label']);
        });

        return [
            'generatedAt' => gmdate(DATE_ATOM),
            'categories' => self::buildBaseCategories(),
            'icons' => $icons,
        ];
    }

    /**
     * @param string[]                $iconNames
     * @param array<string, mixed>    $categoriesByIcon
     * @param array<string, mixed>    $tagsByIcon
     *
     * @return array<int, array{provider:string,value:string,label:string,categories:array<int,string>,search:string}>
     */
    private static function buildLucideEntries(
        array $iconNames,
        array $categoriesByIcon,
        array $tagsByIcon,
    ): array {
        $allowedCategories = array_fill_keys(self::LUCIDE_ALLOWED_CATEGORIES, true);
        $entries = [];

        foreach ($iconNames as $iconName) {
            $name = EventSymbolUtility::normalizeLucideName($iconName);
            if ($name === '') {
                continue;
            }

            $iconCategoriesRaw = $categoriesByIcon[$name] ?? [];
            $iconCategories = [];
            if (is_array($iconCategoriesRaw)) {
                foreach ($iconCategoriesRaw as $category) {
                    $category = EventSymbolUtility::normalizeLucideName((string) $category);
                    if ($category === '' || !isset($allowedCategories[$category])) {
                        continue;
                    }
                    $iconCategories[$category] = true;
                }
            }

            $iconTagsRaw = $tagsByIcon[$name] ?? [];
            $tags = [];
            if (is_array($iconTagsRaw)) {
                foreach ($iconTagsRaw as $tag) {
                    $tag = trim(strtolower((string) $tag));
                    if ($tag !== '') {
                        $tags[] = $tag;
                    }
                }
            }

            if (!self::isRelevantIcon($name, array_keys($iconCategories), $tags)) {
                continue;
            }

            $categoryIds = self::resolveUnifiedCategories($name, array_keys($iconCategories), $tags);
            if ($categoryIds === []) {
                $categoryIds = ['event'];
            }

            $label = self::humanizeSlug($name);
            $searchParts = array_merge([$name, strtolower($label)], $categoryIds, array_keys($iconCategories), $tags);
            $entries[] = [
                'provider' => 'lucide',
                'value' => $name,
                'label' => $label,
                'categories' => $categoryIds,
                'search' => implode(' ', array_values(array_unique(array_filter($searchParts, static fn (string $value): bool => $value !== '')))),
            ];
        }

        return $entries;
    }

    /**
     * @return array<int, array{provider:string,value:string,label:string,categories:array<int,string>,search:string}>
     */
    private static function buildIconifyEntries(): array
    {
        $entries = [];
        $seen = [];
        $prefixQuota = [];

        foreach (self::ICONIFY_BUCKETS as $categoryId => $queries) {
            foreach ($queries as $query) {
                $data = self::fetchIconifySearch($query);
                if ($data === null) {
                    continue;
                }

                $collections = $data['collections'];
                foreach ($data['icons'] as $iconName) {
                    $parts = explode(':', $iconName, 2);
                    if (count($parts) !== 2) {
                        continue;
                    }

                    $prefix = strtolower($parts[0]);
                    $name = strtolower($parts[1]);
                    $fullName = $prefix . ':' . $name;

                    if (!isset($collections[$prefix])) {
                        continue;
                    }

                    $collectionInfo = $collections[$prefix];
                    if (($collectionInfo['palette'] ?? true) === true || !self::isAllowedIconifyCollection($collectionInfo)) {
                        continue;
                    }

                    if (self::isBlockedIconName($name) || self::isBlockedIconName($fullName)) {
                        continue;
                    }

                    if (isset($prefixQuota[$prefix]) && $prefixQuota[$prefix] >= self::ICONIFY_MAX_PER_PREFIX) {
                        continue;
                    }

                    if (!isset($seen[$fullName])) {
                        $label = self::humanizeIconifyName($name);
                        $seen[$fullName] = [
                            'provider' => 'iconify',
                            'value' => 'iconify:' . $fullName,
                            'label' => $label,
                            'categories' => [],
                            'search' => trim($fullName . ' ' . $label . ' ' . $query),
                        ];

                        $prefixQuota[$prefix] = ($prefixQuota[$prefix] ?? 0) + 1;
                    }

                    if (!in_array($categoryId, $seen[$fullName]['categories'], true)) {
                        $seen[$fullName]['categories'][] = $categoryId;
                    }

                    if (count($seen) >= self::ICONIFY_MAX_RENDERABLE_POOL) {
                        break 3;
                    }
                }
            }
        }

        foreach ($seen as $entry) {
            $categoryIds = self::resolveUnifiedCategories(
                strtolower((string) ($entry['value'] ?? '')),
                $entry['categories'],
                [strtolower((string) ($entry['label'] ?? ''))],
            );
            if ($categoryIds === []) {
                $categoryIds = ['event'];
            }

            $entry['categories'] = $categoryIds;
            $entries[] = $entry;
        }

        usort($entries, static function (array $a, array $b): int {
            return strcmp($a['label'], $b['label']);
        });

        return $entries;
    }

    /**
     * Keep only consistent UI/Material collections to avoid low-quality previews.
     *
     * @param array<string,mixed> $collectionInfo
     */
    private static function isAllowedIconifyCollection(array $collectionInfo): bool
    {
        $category = strtolower(trim((string) ($collectionInfo['category'] ?? '')));
        if ($category === '') {
            return false;
        }

        if (!str_starts_with($category, 'ui') && !str_starts_with($category, 'material')) {
            return false;
        }

        foreach (['logo', 'emoji', 'flag', 'archive', 'thematic'] as $blockedCategoryTerm) {
            if (str_contains($category, $blockedCategoryTerm)) {
                return false;
            }
        }

        foreach (['other', 'mixed grid'] as $blockedUiTerm) {
            if (str_contains($category, $blockedUiTerm)) {
                return false;
            }
        }

        $height = isset($collectionInfo['height']) ? (int) $collectionInfo['height'] : 0;
        if (!in_array($height, [16, 20, 24, 32], true)) {
            return false;
        }

        return true;
    }

    /**
     * @return array{icons:string[],collections:array<string,array<string,mixed>>}|null
     */
    private static function fetchIconifySearch(string $query): ?array
    {
        $url = self::ICONIFY_SEARCH_URL . '?query=' . rawurlencode($query) . '&limit=' . self::ICONIFY_LIMIT;
        $response = self::fetchJson($url);
        if (!is_array($response) || !isset($response['icons']) || !is_array($response['icons'])) {
            return null;
        }

        $icons = [];
        foreach ($response['icons'] as $icon) {
            $icon = strtolower(trim((string) $icon));
            if ($icon !== '' && preg_match('/^[a-z0-9-]+:[a-z0-9._-]+$/', $icon) === 1) {
                $icons[] = $icon;
            }
        }

        $collections = [];
        if (isset($response['collections']) && is_array($response['collections'])) {
            foreach ($response['collections'] as $prefix => $info) {
                if (!is_array($info)) {
                    continue;
                }
                $collections[strtolower((string) $prefix)] = $info;
            }
        }

        return [
            'icons' => $icons,
            'collections' => $collections,
        ];
    }

    /**
     * @param string[] $categories
     * @param string[] $tags
     */
    private static function isRelevantIcon(string $name, array $categories, array $tags): bool
    {
        $haystack = strtolower($name . ' ' . implode(' ', $categories) . ' ' . implode(' ', $tags));

        if (self::isBlockedIconName($haystack)) {
            return false;
        }

        foreach (self::EVENT_KEYWORDS as $keyword) {
            if (str_contains($haystack, $keyword)) {
                return true;
            }
        }

        return count($categories) > 0;
    }

    private static function isBlockedIconName(string $value): bool
    {
        $value = strtolower($value);
        foreach (self::BLOCKED_KEYWORDS as $keyword) {
            if (str_contains($value, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private static function humanizeSlug(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if ($value === 'food-beverage') {
            return 'Food & Beverage';
        }

        $parts = explode('-', $value);
        $parts = array_map(static fn (string $part): string => ucfirst($part), $parts);
        return implode(' ', $parts);
    }

    private static function humanizeIconifyName(string $value): string
    {
        $value = str_replace(['_', '.'], '-', trim($value));
        return self::humanizeSlug($value);
    }

    /**
     * @param string[] $headers
     */
    private static function fetchJson(string $url, array $headers = []): mixed
    {
        $headers = array_merge([
            'User-Agent: Photobooth/icon-catalog',
            'Accept: application/json',
        ], $headers);

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers) . "\r\n",
                'timeout' => self::HTTP_TIMEOUT_SECONDS,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $payload = @file_get_contents($url, false, $context);
        if ($payload === false) {
            return null;
        }

        $decoded = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $decoded;
    }

    /**
     * @return array{generatedAt:string,categories:array<int,array{id:string,title:string,source:string}>,icons:array<int,array{provider:string,value:string,label:string,categories:array<int,string>,search:string}>}|null
     */
    private static function readCache(string $cachePath): ?array
    {
        if (!is_file($cachePath)) {
            return null;
        }

        $raw = @file_get_contents($cachePath);
        if ($raw === false || trim($raw) === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }

        if (!isset($decoded['categories']) || !is_array($decoded['categories'])) {
            return null;
        }

        if (!isset($decoded['icons']) || !is_array($decoded['icons'])) {
            return null;
        }

        if (!isset($decoded['generatedAt']) || !is_string($decoded['generatedAt'])) {
            $decoded['generatedAt'] = gmdate(DATE_ATOM, (int) filemtime($cachePath));
        }

        if (!isset($decoded['version']) || (int) $decoded['version'] !== self::CACHE_VERSION) {
            return null;
        }

        return $decoded;
    }

    /**
     * @param array{generatedAt:string,categories:array<int,array{id:string,title:string,source:string}>,icons:array<int,array{provider:string,value:string,label:string,categories:array<int,string>,search:string}>} $catalog
     */
    private static function writeCache(string $cachePath, array $catalog): void
    {
        $catalog['version'] = self::CACHE_VERSION;
        $directory = dirname($cachePath);
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        $json = json_encode($catalog, JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return;
        }

        @file_put_contents($cachePath, $json, LOCK_EX);
    }

    private static function isCacheFresh(string $cachePath): bool
    {
        if (!is_file($cachePath)) {
            return false;
        }

        $mtime = (int) filemtime($cachePath);
        return $mtime > 0 && (time() - $mtime) < self::CACHE_TTL_SECONDS;
    }

    /**
     * @return array{generatedAt:string,categories:array<int,array{id:string,title:string,source:string}>,icons:array<int,array{provider:string,value:string,label:string,categories:array<int,string>,search:string}>}
     */
    private static function fallbackCatalog(): array
    {
        return [
            'generatedAt' => gmdate(DATE_ATOM),
            'categories' => self::buildBaseCategories(),
            'icons' => [
                [
                    'provider' => 'lucide',
                    'value' => 'camera',
                    'label' => 'Camera',
                    'categories' => ['photo', 'event'],
                    'search' => 'camera photo event',
                ],
                [
                    'provider' => 'lucide',
                    'value' => 'heart',
                    'label' => 'Heart',
                    'categories' => ['love', 'event'],
                    'search' => 'heart love event',
                ],
                [
                    'provider' => 'lucide',
                    'value' => 'cake',
                    'label' => 'Cake',
                    'categories' => ['food', 'event'],
                    'search' => 'cake party event',
                ],
                [
                    'provider' => 'lucide',
                    'value' => 'sun',
                    'label' => 'Sun',
                    'categories' => ['nature', 'event'],
                    'search' => 'sun weather event',
                ],
                [
                    'provider' => 'lucide',
                    'value' => 'cog',
                    'label' => 'Cog',
                    'categories' => ['tools', 'event'],
                    'search' => 'cog settings event',
                ],
            ],
        ];
    }

    /**
     * @param array{generatedAt:string,categories:array<int,array{id:string,title:string,source:string}>,icons:array<int,array{provider:string,value:string,label:string,categories:array<int,string>,search:string}>} $catalog
     *
     * @return array{generatedAt:string,categories:array<int,array{id:string,title:string,source:string}>,icons:array<int,array{provider:string,value:string,label:string,categories:array<int,string>,search:string}>}
     */
    private static function withCustomImages(array $catalog): array
    {
        $customEntries = self::getCustomImageEntries();
        if ($customEntries === []) {
            return $catalog;
        }

        $seenValues = [];
        foreach ($catalog['icons'] as $icon) {
            if (!is_array($icon)) {
                continue;
            }
            $value = (string) ($icon['value'] ?? '');
            if ($value !== '') {
                $seenValues[$value] = true;
            }
        }

        foreach ($customEntries as $entry) {
            if (!isset($seenValues[$entry['value']])) {
                $catalog['icons'][] = $entry;
                $seenValues[$entry['value']] = true;
            }
        }

        usort($catalog['icons'], static function (array $a, array $b): int {
            if ($a['provider'] !== $b['provider']) {
                return strcmp($a['provider'], $b['provider']);
            }
            return strcmp($a['label'], $b['label']);
        });

        return $catalog;
    }

    /**
     * @return array<int,array{provider:string,value:string,label:string,categories:array<int,string>,search:string}>
     */
    private static function getCustomImageEntries(): array
    {
        $directory = self::getCustomImageDirectoryAbsolute();
        if (!is_dir($directory)) {
            return [];
        }

        $entries = [];
        $files = scandir($directory);
        if (!is_array($files)) {
            return [];
        }

        $relativeDirectory = self::getCustomImageDirectoryRelative();
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $absolutePath = $directory . DIRECTORY_SEPARATOR . $file;
            if (!is_file($absolutePath)) {
                continue;
            }

            $relativePath = $relativeDirectory . '/' . $file;
            $entry = self::buildCustomImageEntry($relativePath);
            if ($entry !== null) {
                $entries[] = $entry;
            }
        }

        usort($entries, static function (array $a, array $b): int {
            return strcmp($a['label'], $b['label']);
        });

        return $entries;
    }

    /**
     * @param string[] $lucideCategories
     * @param string[] $tags
     *
     * @return string[]
     */
    private static function resolveUnifiedCategories(string $name, array $lucideCategories, array $tags): array
    {
        $categoryMap = [];

        foreach ($lucideCategories as $category) {
            $category = strtolower(trim((string) $category));
            if ($category === '') {
                continue;
            }

            if (isset(self::LUCIDE_CATEGORY_TO_GROUPS[$category])) {
                foreach (self::LUCIDE_CATEGORY_TO_GROUPS[$category] as $mappedCategory) {
                    self::addCategoryIfValid($categoryMap, $mappedCategory);
                }
            }
        }

        $haystack = strtolower($name . ' ' . implode(' ', $lucideCategories) . ' ' . implode(' ', $tags));
        foreach (self::CATEGORY_KEYWORDS as $categoryId => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($haystack, $keyword)) {
                    self::addCategoryIfValid($categoryMap, $categoryId);
                    break;
                }
            }
        }

        if ($categoryMap === []) {
            self::addCategoryIfValid($categoryMap, 'event');
        }

        return array_keys($categoryMap);
    }

    /**
     * @param array<string,bool> $categoryMap
     */
    private static function addCategoryIfValid(array &$categoryMap, string $categoryId): void
    {
        if ($categoryId === 'all') {
            return;
        }

        if (!isset(self::CATEGORY_DEFINITIONS[$categoryId])) {
            return;
        }

        $categoryMap[$categoryId] = true;
    }

    /**
     * @return array<int,array{id:string,title:string,source:string}>
     */
    private static function buildBaseCategories(): array
    {
        $result = [];
        foreach (self::CATEGORY_DEFINITIONS as $id => $definition) {
            $result[] = [
                'id' => $id,
                'title' => $definition['title'],
                'source' => $definition['source'],
            ];
        }

        return $result;
    }
}

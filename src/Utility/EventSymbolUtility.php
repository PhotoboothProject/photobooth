<?php

namespace Photobooth\Utility;

final class EventSymbolUtility
{
    /**
     * Keep the historical Font Awesome options so existing setups stay selectable.
     *
     * @var array<string, string>
     */
    private const LEGACY_FONT_AWESOME_ICONS = [
        'fa-camera' => 'Camera',
        'fa-camera-retro' => 'Camera Retro',
        'fa-birthday-cake' => 'Birthday Cake',
        'fa-gift' => 'Gift',
        'fa-tree' => 'Tree',
        'fa-snowflake' => 'Snowflake',
        'fa-heart-o' => 'Heart (Outline)',
        'fa-regular fa-heart' => 'Heart',
        'fa-solid fa-heart' => 'Heart (Filled)',
        'fa-solid fa-heart-pulse' => 'Heartbeat',
        'fa-brands fa-apple' => 'Apple',
        'fa-anchor' => 'Anchor',
        'fa-light fa-champagne-glasses' => 'Champagne Glasses',
        'fa-gears' => 'Gears',
        'fa-users' => 'People',
    ];

    /**
     * Lucide fallbacks used only for dual rendering contexts.
     *
     * @var array<string, string>
     */
    private const LEGACY_TO_LUCIDE_MAP = [
        'fa-camera' => 'camera',
        'fa-camera-retro' => 'camera',
        'fa-birthday-cake' => 'cake',
        'fa-gift' => 'gift',
        'fa-tree' => 'tree-pine',
        'fa-snowflake' => 'snowflake',
        'fa-heart-o' => 'heart',
        'fa-regular fa-heart' => 'heart',
        'fa-solid fa-heart' => 'heart',
        'fa-solid fa-heart-pulse' => 'heart-pulse',
        'fa-brands fa-apple' => 'apple',
        'fa-anchor' => 'anchor',
        'fa-light fa-champagne-glasses' => 'party-popper',
        'fa-champagne-glasses' => 'party-popper',
        'fa-gears' => 'cog',
        'fa-cogs' => 'cog',
        'fa-users' => 'users',
    ];

    private const CUSTOM_IMAGE_PREFIX = 'image:';
    private const CUSTOM_IMAGE_DIRECTORY = 'private/images/event-symbols/';

    /**
     * @var string[]
     */
    private const CUSTOM_IMAGE_EXTENSIONS = ['svg', 'png', 'jpg', 'jpeg', 'webp', 'gif', 'avif'];

    public static function normalize(?string $value): string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return 'camera';
        }

        $lower = strtolower($value);

        if (str_starts_with($lower, self::CUSTOM_IMAGE_PREFIX) || self::looksLikeCustomImagePath($value)) {
            $customImage = self::normalizeCustomImageValue($value);
            return $customImage !== '' ? $customImage : 'camera';
        }

        if (str_starts_with($lower, 'lucide:')) {
            $value = substr($value, 7);
            $lower = strtolower($value);
        } elseif (str_starts_with($lower, 'fa:')) {
            $value = substr($value, 3);
            $lower = strtolower($value);
        } elseif (str_starts_with($lower, 'iconify:')) {
            $value = substr($value, 8);
            $lower = strtolower($value);
        }

        if (self::isFontAwesomeSymbol($lower)) {
            $faClasses = self::sanitizeFontAwesomeClasses($lower);
            return $faClasses !== '' ? $faClasses : 'fa-camera';
        }

        if (self::isIconifySymbol($lower)) {
            $iconify = self::normalizeIconifyName($lower);
            return $iconify !== '' ? 'iconify:' . $iconify : 'iconify:mdi:camera';
        }

        $lucide = self::normalizeLucideName($value);
        return $lucide !== '' ? $lucide : 'camera';
    }

    public static function isFontAwesomeSymbol(?string $value): bool
    {
        $value = strtolower(trim((string)$value));
        if ($value === '') {
            return false;
        }

        if (isset(self::LEGACY_FONT_AWESOME_ICONS[$value])) {
            return true;
        }

        return preg_match('/(^|\\s)fa($|\\s)|fa-[a-z0-9-]+/', $value) === 1;
    }

    public static function sanitizeFontAwesomeClasses(?string $value): string
    {
        $value = strtolower(trim((string)$value));
        if ($value === '') {
            return '';
        }

        $tokens = preg_split('/\\s+/', $value) ?: [];
        $classes = [];
        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token === '' || (!str_starts_with($token, 'fa-') && $token !== 'fa')) {
                continue;
            }
            $classes[$token] = true;
        }

        if (!isset($classes['fa']) && count($classes) > 0) {
            $classes = ['fa' => true] + $classes;
        }

        // Must contain at least one icon class, not only style wrappers.
        $hasIconClass = false;
        foreach (array_keys($classes) as $className) {
            if (str_starts_with($className, 'fa-') && !in_array($className, ['fa-solid', 'fa-regular', 'fa-brands', 'fa-light', 'fa-thin', 'fa-sharp', 'fa-classic'], true)) {
                $hasIconClass = true;
                break;
            }
        }

        if (!$hasIconClass) {
            return '';
        }

        return implode(' ', array_keys($classes));
    }

    public static function normalizeLucideName(?string $value): string
    {
        $value = strtolower(trim((string)$value));
        $value = preg_replace('/[^a-z0-9-]+/', '-', $value);
        $value = preg_replace('/-+/', '-', (string)$value);
        return trim((string)$value, '-');
    }

    public static function isIconifySymbol(?string $value): bool
    {
        $value = strtolower(trim((string)$value));
        if ($value === '') {
            return false;
        }

        if (str_starts_with($value, 'iconify:')) {
            $value = substr($value, 8);
        }

        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*:[a-z0-9][a-z0-9._-]*$/', $value) === 1;
    }

    public static function normalizeIconifyName(?string $value): string
    {
        $value = strtolower(trim((string)$value));
        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, 'iconify:')) {
            $value = substr($value, 8);
        }

        if (strpos($value, ':') === false) {
            return '';
        }

        [$prefix, $icon] = explode(':', $value, 2);
        $prefix = preg_replace('/[^a-z0-9-]+/', '', $prefix);
        $prefix = preg_replace('/-+/', '-', (string)$prefix);
        $prefix = trim((string)$prefix, '-');

        $icon = preg_replace('/[^a-z0-9._-]+/', '-', $icon);
        $icon = preg_replace('/-+/', '-', (string)$icon);
        $icon = trim((string)$icon, '-._');

        if ($prefix === '' || $icon === '') {
            return '';
        }

        return $prefix . ':' . $icon;
    }

    public static function normalizeCustomImageValue(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $lower = strtolower($value);
        if (str_starts_with($lower, self::CUSTOM_IMAGE_PREFIX)) {
            $value = substr($value, strlen(self::CUSTOM_IMAGE_PREFIX));
        }

        $value = str_replace('\\', '/', $value);
        $value = ltrim($value, '/');
        $value = preg_replace('#/+#', '/', $value);
        $value = (string) $value;

        if ($value === '' || str_contains($value, "\0") || str_contains($value, '..')) {
            return '';
        }

        if (preg_match('/^[A-Za-z0-9._\/-]+$/', $value) !== 1) {
            return '';
        }

        if (!str_starts_with(strtolower($value), self::CUSTOM_IMAGE_DIRECTORY)) {
            return '';
        }

        $extension = strtolower(pathinfo($value, PATHINFO_EXTENSION));
        if ($extension === '' || !in_array($extension, self::CUSTOM_IMAGE_EXTENSIONS, true)) {
            return '';
        }

        return self::CUSTOM_IMAGE_PREFIX . $value;
    }

    public static function isCustomImageSymbol(?string $value): bool
    {
        if ($value === null || trim($value) === '') {
            return false;
        }

        if (self::normalizeCustomImageValue($value) !== '') {
            return true;
        }

        $normalized = self::normalize($value);
        return str_starts_with(strtolower($normalized), self::CUSTOM_IMAGE_PREFIX);
    }

    public static function getCustomImagePath(?string $value): string
    {
        $normalizedCustom = self::normalizeCustomImageValue($value);
        if ($normalizedCustom === '') {
            $normalizedCustom = self::normalize($value);
        }

        if (!str_starts_with(strtolower($normalizedCustom), self::CUSTOM_IMAGE_PREFIX)) {
            return '';
        }

        return substr($normalizedCustom, strlen(self::CUSTOM_IMAGE_PREFIX));
    }

    public static function getCustomImagePublicPath(?string $value): string
    {
        $path = self::getCustomImagePath($value);
        if ($path === '') {
            return '';
        }

        return PathUtility::getPublicPath($path);
    }

    public static function getCustomImageDirectory(): string
    {
        return rtrim(self::CUSTOM_IMAGE_DIRECTORY, '/');
    }

    /**
     * @return string[]
     */
    public static function getAllowedCustomImageExtensions(): array
    {
        return self::CUSTOM_IMAGE_EXTENSIONS;
    }

    public static function getSymbolType(?string $value): string
    {
        $normalized = self::normalize($value);

        if (self::isCustomImageSymbol($normalized)) {
            return 'image';
        }

        if (self::isFontAwesomeSymbol($normalized)) {
            return 'fa';
        }

        $normalized = strtolower(trim($normalized));
        if (str_starts_with($normalized, 'iconify:') || self::isIconifySymbol($normalized)) {
            return 'iconify';
        }

        return 'lucide';
    }

    public static function getLucideFallback(?string $value): string
    {
        $normalized = self::normalize($value);
        $lower = strtolower($normalized);

        if (self::isCustomImageSymbol($lower)) {
            return 'camera';
        }

        if (!self::isFontAwesomeSymbol($lower)) {
            return $lower;
        }

        if (isset(self::LEGACY_TO_LUCIDE_MAP[$lower])) {
            return self::LEGACY_TO_LUCIDE_MAP[$lower];
        }

        $tokens = preg_split('/\\s+/', $lower);
        if (!is_array($tokens)) {
            return 'camera';
        }

        foreach ($tokens as $token) {
            if (isset(self::LEGACY_TO_LUCIDE_MAP[$token])) {
                return self::LEGACY_TO_LUCIDE_MAP[$token];
            }
        }

        return 'camera';
    }

    public static function getFontAwesomeClasses(?string $value): string
    {
        $normalized = self::normalize($value);
        return self::sanitizeFontAwesomeClasses($normalized);
    }

    public static function getIconifyName(?string $value): string
    {
        $normalized = self::normalize($value);
        $lower = strtolower(trim($normalized));

        if (str_starts_with($lower, 'iconify:')) {
            $name = self::normalizeIconifyName(substr($lower, 8));
            return $name !== '' ? $name : 'mdi:camera';
        }

        if (self::isIconifySymbol($lower)) {
            $name = self::normalizeIconifyName($lower);
            return $name !== '' ? $name : 'mdi:camera';
        }

        return 'mdi:camera';
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    public static function getLegacyIconList(): array
    {
        $result = [];
        foreach (self::LEGACY_FONT_AWESOME_ICONS as $value => $label) {
            $result[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return $result;
    }

    private static function looksLikeCustomImagePath(string $value): bool
    {
        $normalized = strtolower(str_replace('\\', '/', ltrim(trim($value), '/')));
        return str_starts_with($normalized, self::CUSTOM_IMAGE_DIRECTORY);
    }
}

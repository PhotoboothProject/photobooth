<?php

namespace Photobooth\Utility;

final class LucideIconUtility
{
    private static ?array $iconNames = null;

    /**
     * @return string[]
     */
    public static function getIconNames(): array
    {
        if (self::$iconNames !== null) {
            return self::$iconNames;
        }

        $iconsPath = PathUtility::getAbsolutePath('node_modules/lucide/dist/esm/icons');
        if (!is_dir($iconsPath)) {
            self::$iconNames = self::getFallbackIcons();
            return self::$iconNames;
        }

        $icons = [];
        $files = scandir($iconsPath);
        if (is_array($files)) {
            foreach ($files as $file) {
                if (!str_ends_with($file, '.mjs')) {
                    continue;
                }

                $name = substr($file, 0, -4);
                if ($name !== '') {
                    $icons[] = $name;
                }
            }
        }

        if (count($icons) === 0) {
            self::$iconNames = self::getFallbackIcons();
            return self::$iconNames;
        }

        sort($icons, SORT_STRING);
        self::$iconNames = array_values(array_unique($icons));
        return self::$iconNames;
    }

    private static function getFallbackIcons(): array
    {
        return [
            'camera',
            'camera-off',
            'heart',
            'heart-pulse',
            'party-popper',
            'gift',
            'cake',
            'tree-pine',
            'snowflake',
            'users',
            'anchor',
            'apple',
            'cog',
        ];
    }
}

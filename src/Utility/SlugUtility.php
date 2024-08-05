<?php

namespace Photobooth\Utility;

class SlugUtility
{
    public static function create(string $string): string
    {
        $divider = '-';

        // replace non letter or digits by divider
        $string = preg_replace('~[^\pL\d]+~u', $divider, $string);

        // transliterate
        $string = iconv('utf-8', 'us-ascii//TRANSLIT', (string)$string);
        if ($string === false) {
            return 'n-a';
        }

        // remove unwanted characters
        $string = preg_replace('~[^-\w]+~', '', (string)$string);

        // trim
        $string = trim((string)$string, $divider);

        // remove duplicate divider
        $string = preg_replace('~-+~', $divider, (string)$string);

        // lowercase
        $string = strtolower((string)$string);

        return empty($string) ? 'n-a' : $string;
    }
}

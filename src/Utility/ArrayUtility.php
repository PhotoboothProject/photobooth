<?php

namespace Photobooth\Utility;

class ArrayUtility
{
    public static function mergeRecursive(array $array, array $overrule): array
    {
        foreach ($overrule as $key => $_) {
            if (isset($array[$key]) && is_array($array[$key])) {
                if (is_array($overrule[$key])) {
                    $array[$key] = self::mergeRecursive($array[$key], $overrule[$key]);
                }
            } else {
                $array[$key] = $overrule[$key];
            }
        }
        reset($array);

        return $array;
    }

    public static function setValueByPath(array $array, string $path, mixed $value): array
    {
        $delimiter = '/';
        if ($path === '') {
            throw new \RuntimeException('Path must not be empty', 1695053538);
        }

        /** @var string[] */
        $data = str_getcsv($path, $delimiter);
        $pointer = &$array;
        foreach ($data as $segment) {
            if ($segment === '') {
                throw new \RuntimeException('Invalid path segment specified', 1341406846);
            }
            if (is_array($pointer) && !array_key_exists($segment, $pointer)) {
                $pointer[$segment] = [];
            }
            if (!is_array($pointer)) {
                $pointer = [];
            }
            $pointer = &$pointer[$segment];
        }
        $pointer = $value;

        return $array;
    }

    public static function getValueByPath(array $array, string $path): mixed
    {
        $delimiter = '/';
        if ($path === '') {
            throw new \RuntimeException('Path must not be empty', 1695053537);
        }

        /** @var string[] */
        $data = str_getcsv($path, $delimiter);
        $value = $array;
        foreach ($data as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                throw new \RuntimeException('Segment ' . $segment . ' of path ' . implode($delimiter, $data) . ' does not exist in array', 1695053538);
            }
        }

        return $value;
    }

    public static function diffRecursive(array $array1, array $array2): array
    {
        $differenceArray = [];
        foreach ($array1 as $key => $value) {
            if (!array_key_exists($key, $array2) || (!is_array($value) && $value !== $array2[$key])) {
                $differenceArray[$key] = $value;
            } elseif (is_array($value)) {
                if (is_array($array2[$key])) {
                    $recursiveResult = self::diffRecursive($value, $array2[$key]);
                    if (!empty($recursiveResult)) {
                        $differenceArray[$key] = $recursiveResult;
                    }
                }
            }
        }
        return $differenceArray;
    }
}

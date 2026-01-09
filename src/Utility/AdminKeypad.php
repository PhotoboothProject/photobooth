<?php

namespace Photobooth\Utility;

class AdminKeypad
{
    public static function login(string $userPin, array $login): bool
    {
        if (self::isValidPin($userPin, $login['pin'] ?? null)) {
            session_regenerate_id(true);
            $_SESSION['auth'] = true;
            return true;
        } elseif (($login['rental_keypad'] ?? false) && self::isValidPin($userPin, $login['rental_pin'] ?? null)) {
            session_regenerate_id(true);
            $_SESSION['rental'] = true;
            return true;
        }

        return false;
    }

    /**
     * Validate user PIN against stored (plain or hashed) value.
     */
    protected static function isValidPin(string $userPin, ?string $storedPin): bool
    {
        if ($storedPin === null || $storedPin === '') {
            return false;
        }

        if (self::isHashedPin($storedPin)) {
            return password_verify($userPin, $storedPin);
        }

        return hash_equals($storedPin, $userPin);
    }

    public static function render(): string
    {
        $content = [];
        $content[] = '<div class="grid grid-cols-3">';
        $content[] = self::renderKey(1);
        $content[] = self::renderKey(2);
        $content[] = self::renderKey(3);
        $content[] = self::renderKey(4);
        $content[] = self::renderKey(5);
        $content[] = self::renderKey(6);
        $content[] = self::renderKey(7);
        $content[] = self::renderKey(8);
        $content[] = self::renderKey(9);
        $content[] = self::renderKey('remove');
        $content[] = self::renderKey(0);
        $content[] = self::renderKey('home');
        $content[] = '</div>';

        return implode(PHP_EOL, $content);
    }

    protected static function renderKey(null|int|string $key = null): string
    {
        $containerClass = 'keypad_key peer flex items-center justify-center p-2 hover:text-brand-1 transition-all';
        $keyClass = '
                flex items-center justify-center w-16 h-16 transition-all
                text-gray-500 text-lg cursor-pointer font-bold
                border border-solid border-gray-200 rounded-full
                hover:border-brand-1 hover:text-brand-1 hover:scale-110
                active:border-brand-1 active:bg-brand-1 active:text-white
                outline-hidden focus:outline-hidden focus:ring-2 focus:ring-brand-1 active:ring-2 active:ring-brand-1 active:outline-hidden
            ';

        $content = [];
        if (isset($key)) {
            if (is_numeric($key)) {
                $content[] = '<div class="' . $containerClass . '">';
                $content[] = '<span class="' . $keyClass . '" onclick="keypadAdd(' . $key . ');">' . $key . '</span>';
                $content[] = '</div>';
            } elseif ($key  === 'remove') {
                $content[] = '<div class="' . $containerClass . ' cursor-pointer" onclick="keypadRemoveLastValue();"><span class="fa fa-chevron-left"></span></div>';
            } elseif ($key  === 'home') {
                $content[] = '<a href="' . PathUtility::getPublicPath() . '" class="text-2xl ' . $containerClass . ' cursor-pointer"><span class="fa fa-home"></span></a>';
            }
        } else {
            $content[] = '<div class="' . $containerClass . '"></div>';
        }

        return implode(PHP_EOL, $content);
    }

    public static function renderIndicator(int $length): string
    {
        $containerClass = '
            keypad_keybox
            flex items-center justify-center w-10 h-14
            border border-solid border-gray-200 bg-gray-50 rounded-sm m-2
            [&.active]:border-brand-1
            [&.error]:animate-error [&.error]:border-red-500/70
        ';
        $dotClass = '
            keypad_key
            w-3 h-3 rounded-full bg-gray-400
            [&.active]:border-2 [&.active]:border-solid [&.active]:border-brand-1 [&.active]:bg-transparent
            [&.checked]:bg-brand-1
            [&.error]:bg-red-500/70
        ';

        $content = [];
        $content[] = '<div class="pinIndicator flex items-center justify-center">';
        for ($x = 0; $x <= $length - 1; $x++) {
            $activeClass = '';
            if ($x == 0) {
                $activeClass = 'active';
            }
            $content[] = '
                <div class="' . $containerClass . ' ' . $activeClass . '">
                    <span class="' . $dotClass . ' ' . $activeClass . '"></span>
                </div>
            ';
        }
        $content[] = '</div>';

        return implode(PHP_EOL, $content);
    }

    /**
     * Detects if a PIN string is password_hash output.
     */
    public static function isHashedPin(?string $storedPin): bool
    {
        if ($storedPin === null || $storedPin === '') {
            return false;
        }

        $info = password_get_info($storedPin);
        return ($info['algo'] ?? 0) !== 0;
    }

    /**
     * Return display length: real length for plain PIN, 4 for hashed.
     */
    public static function pinLength(?string $storedPin): int
    {
        if ($storedPin === null || $storedPin === '') {
            return 0;
        }

        return self::isHashedPin($storedPin) ? 4 : strlen($storedPin);
    }
}

<?php

namespace Photobooth\Utility;

final class StartpageTextPosition
{
    private const DEFAULT_POSITION = 'bottom';

    /**
     * @var string[]
     */
    private const ALLOWED_POSITIONS = [
        'top',
        'center',
        'bottom',
        'left-top',
        'left-center',
        'left-bottom',
        'right-top',
        'right-center',
        'right-bottom',
    ];

    public static function normalize(mixed $position): string
    {
        if (!is_string($position)) {
            return self::DEFAULT_POSITION;
        }

        $position = strtolower(trim($position));
        $position = str_replace(['_', ' '], '-', $position);

        return in_array($position, self::ALLOWED_POSITIONS, true) ? $position : self::DEFAULT_POSITION;
    }

    public static function resolve(mixed $textPosition, mixed $logoPosition, mixed $logoEnabled = true): string
    {
        $isLogoEnabled = $logoEnabled === true || $logoEnabled === 'true';

        if ($isLogoEnabled && $logoPosition === 'center') {
            return self::DEFAULT_POSITION;
        }

        return self::normalize($textPosition);
    }
}

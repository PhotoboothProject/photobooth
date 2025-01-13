<?php

declare(strict_types=1);

namespace Photobooth\Enum;

use Photobooth\Enum\Interface\LabelInterface;
use Photobooth\Utility\PathUtility;

enum CollageLayoutEnum: string implements LabelInterface
{
    case TWO_PLUS_TWO_1 = '2+2-1';
    case TWO_PLUS_TWO_2 = '2+2-2';
    case ONE_PLUS_THREE_1 = '1+3-1';
    case ONE_PLUS_THREE_2 = '1+3-2';
    case THREE_PLUS_ONE = '3+1';
    case ONE_PLUS_TWO = '1+2';
    case TWO_PLUS_ONE = '2+1';
    case TWO_X_FOUR_1 = '2x4-1';
    case TWO_X_FOUR_2 = '2x4-2';
    case TWO_X_FOUR_3 = '2x4-3';
    case TWO_X_FOUR_4 = '2x4-4';
    case TWO_X_THREE_1 = '2x3-1';
    case TWO_X_THREE_2 = '2x3-2';
    case COLLAGE_JSON = 'collage.json';

    public function label(): string
    {
        return match($this) {
            self::TWO_PLUS_TWO_1 => '2+2 Layout (Option 1)',
            self::TWO_PLUS_TWO_2 => '2+2 Layout (Option 2)',
            self::ONE_PLUS_THREE_1 => '1+3 Layout (Option 1)',
            self::ONE_PLUS_THREE_2 => '1+3 Layout (Option 2)',
            self::THREE_PLUS_ONE => '3+1 Layout',
            self::ONE_PLUS_TWO => '1+2 Layout',
            self::TWO_PLUS_ONE => '2+1 Layout',
            self::TWO_X_FOUR_1 => '2x4 Layout (Option 1)',
            self::TWO_X_FOUR_2 => '2x4 Layout (Option 2)',
            self::TWO_X_FOUR_3 => '2x4 Layout (Option 3)',
            self::TWO_X_FOUR_4 => '2x4 Layout (Option 4)',
            self::TWO_X_THREE_1 => '2x3 Layout (Option 1)',
            self::TWO_X_THREE_2 => '2x3 Layout (Option 2)',
            self::COLLAGE_JSON => 'Collage JSON Configuration',
        };
    }

    public function limit(): int
    {
        return match($this) {
            self::TWO_PLUS_TWO_1,
            self::TWO_PLUS_TWO_2,
            self::ONE_PLUS_THREE_1,
            self::ONE_PLUS_THREE_2,
            self::THREE_PLUS_ONE,
            self::TWO_X_FOUR_1,
            self::TWO_X_FOUR_2,
            self::TWO_X_FOUR_3,
            self::TWO_X_FOUR_4 => 4,

            self::ONE_PLUS_TWO,
            self::TWO_PLUS_ONE,
            self::TWO_X_THREE_1,
            self::TWO_X_THREE_2 => 3,

            self::COLLAGE_JSON => self::getCollageJsonLimit(),
        };
    }

    private static function getCollageJsonLimit(): int
    {
        $collageConfigFilePath = PathUtility::getAbsolutePath('private/collage.json');
        $fallbackLimit = 0;

        if (!file_exists($collageConfigFilePath)) {
            return $fallbackLimit;
        }

        $collageConfig = json_decode((string)file_get_contents($collageConfigFilePath), true);

        if (is_array($collageConfig)) {
            return array_key_exists('layout', $collageConfig)
                ? count($collageConfig['layout'])
                : count($collageConfig);
        }

        return $fallbackLimit;
    }

    public static function getLimitByValue(string $value): int
    {
        $case = self::tryFrom($value);

        return $case?->limit() ?? 0;
    }
}

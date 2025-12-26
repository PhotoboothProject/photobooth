<?php

declare(strict_types=1);

namespace Photobooth\Enum;

use Photobooth\Enum\Interface\LabelInterface;

enum CollageLayoutEnum: string implements LabelInterface
{
    case TWO_PLUS_TWO_1 = '2+2-1';
    case TWO_PLUS_TWO_2 = '2+2-2';
    case ONE_PLUS_THREE_1 = '1+3-1';
    case ONE_PLUS_THREE_2 = '1+3-2';
    case THREE_PLUS_ONE_1 = '3+1-1';
    case ONE_PLUS_TWO_1 = '1+2-1';
    case TWO_PLUS_ONE_1 = '2+1-1';
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
            self::THREE_PLUS_ONE_1 => '3+1 Layout',
            self::ONE_PLUS_TWO_1 => '1+2 Layout',
            self::TWO_PLUS_ONE_1 => '2+1 Layout',
            self::TWO_X_FOUR_1 => '2x4 Layout (Option 1)',
            self::TWO_X_FOUR_2 => '2x4 Layout (Option 2)',
            self::TWO_X_FOUR_3 => '2x4 Layout (Option 3)',
            self::TWO_X_FOUR_4 => '2x4 Layout (Option 4)',
            self::TWO_X_THREE_1 => '2x3 Layout (Option 1)',
            self::TWO_X_THREE_2 => '2x3 Layout (Option 2)',
            self::COLLAGE_JSON => 'Collage JSON Configuration',
        };
    }
}

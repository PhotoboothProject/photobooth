<?php

declare(strict_types=1);

namespace Photobooth\Enum;

use Photobooth\Enum\Interface\LabelInterface;

enum ImageFilterEnum: string implements LabelInterface
{
    case PLAIN = 'plain';
    case ANTIQUE = 'antique';
    case AQUA = 'aqua';
    case BLUE = 'blue';
    case BLUR = 'blur';
    case COLOR = 'color';
    case COOL = 'cool';
    case EDGE = 'edge';
    case EMBOSS = 'emboss';
    case EVERGLOW = 'everglow';
    case GRAYSCALE = 'grayscale';
    case GREEN = 'green';
    case MEAN = 'mean';
    case NEGATE = 'negate';
    case PINK = 'pink';
    case PIXELATE = 'pixelate';
    case RED = 'red';
    case RETRO = 'retro';
    case SELECTIVE_BLUR = 'selective-blur';
    case SEPIA_LIGHT = 'sepia-light';
    case SEPIA_DARK = 'sepia-dark';
    case SMOOTH = 'smooth';
    case SUMMER = 'summer';
    case VINTAGE = 'vintage';
    case WASHED = 'washed';
    case YELLOW = 'yellow';

    public function label(): string
    {
        return match($this) {
            ImageFilterEnum::PLAIN => 'None',
            ImageFilterEnum::ANTIQUE => 'Antique',
            ImageFilterEnum::AQUA => 'Aqua',
            ImageFilterEnum::BLUE => 'Blue',
            ImageFilterEnum::BLUR => 'Blur',
            ImageFilterEnum::COLOR => 'Color',
            ImageFilterEnum::COOL => 'Cool',
            ImageFilterEnum::EDGE => 'Edge',
            ImageFilterEnum::EMBOSS => 'Emboss',
            ImageFilterEnum::EVERGLOW => 'Everglow',
            ImageFilterEnum::GRAYSCALE => 'Grayscale',
            ImageFilterEnum::GREEN => 'Green',
            ImageFilterEnum::MEAN => 'Mean',
            ImageFilterEnum::NEGATE => 'Negate',
            ImageFilterEnum::PINK => 'Pink',
            ImageFilterEnum::PIXELATE => 'Pixelate',
            ImageFilterEnum::RED => 'Red',
            ImageFilterEnum::RETRO => 'Retro',
            ImageFilterEnum::SELECTIVE_BLUR => 'Selective blur',
            ImageFilterEnum::SEPIA_LIGHT => 'Sepia light',
            ImageFilterEnum::SEPIA_DARK => 'Sepia dark',
            ImageFilterEnum::SMOOTH => 'Smooth',
            ImageFilterEnum::SUMMER => 'Summer',
            ImageFilterEnum::VINTAGE => 'Vintage',
            ImageFilterEnum::WASHED => 'Washed',
            ImageFilterEnum::YELLOW => 'Yellow',
        };
    }
}

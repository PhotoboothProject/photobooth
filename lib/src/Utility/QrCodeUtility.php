<?php

namespace Photobooth\Utility;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\Margin\Margin;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Writer\Result\ResultInterface;

class QrCodeUtility
{
    public static function create(string $text, string $labelText = '', int $size = 300, int $margin = 15): ResultInterface
    {
        $builder = Builder::create()
            ->writer(new SvgWriter())
            ->writerOptions([])
            ->data($text)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::Medium)
            ->size($size - (2 * $margin))
            ->margin($margin)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin);

        if ($labelText !== '') {
            $builder
                ->labelText($labelText)
                ->labelMargin(new Margin(0, $margin, $margin, $margin));
        }

        $result = $builder
            ->validateResult(false)
            ->build();

        return $result;
    }
}

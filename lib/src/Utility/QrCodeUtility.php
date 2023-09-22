<?php

namespace Photobooth\Utility;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium;
use Endroid\QrCode\Label\Margin\Margin;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\Result\ResultInterface;

class QrCodeUtility
{
    public static function create(string $text, string $labelText = '', int $size = 300, int $margin = 15): ResultInterface
    {
        $builder = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($text)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelMedium())
            ->size($size - (2 * $margin))
            ->margin($margin)
            ->roundBlockSizeMode(new RoundBlockSizeModeMargin());

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

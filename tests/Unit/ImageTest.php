<?php

namespace Photobooth\Tests\Unit;

use Photobooth\Image;
use PHPUnit\Framework\TestCase;

final class ImageTest extends TestCase
{
    public function testRelativeQrPlacementUsesFinalCropDimensionsWithoutChangingRenderOrder(): void
    {
        $image = new Image();
        $image->debugLevel = 3;
        $image->qrPosition = 'bottomRight';
        $image->qrPositionMode = 'relative';
        $image->qrRelativeSize = 0.08;
        $image->qrRelativeOffset = 0.02;
        $image->qrCropWidth = 500;
        $image->qrCropHeight = 250;

        $source = imagecreatetruecolor(1000, 750);
        $this->assertInstanceOf(\GdImage::class, $source);
        $white = imagecolorallocate($source, 255, 255, 255);
        $this->assertNotFalse($white);
        $this->assertTrue(imagefill($source, 0, 0, $white));

        $qrCode = imagecreatetruecolor(100, 100);
        $this->assertInstanceOf(\GdImage::class, $qrCode);
        imagesavealpha($qrCode, true);
        imagealphablending($qrCode, false);
        $black = imagecolorallocate($qrCode, 0, 0, 0);
        $this->assertNotFalse($black);
        $this->assertTrue(imagefill($qrCode, 0, 0, $black));

        $withQr = $image->applyQr($qrCode, $source);
        $cropped = $image->resizeCropImage($withQr, 500, 250);

        [$minX, $minY, $maxX, $maxY] = $this->findNonWhiteBounds($cropped);

        $this->assertSame(40, $maxX - $minX + 1, 'QR width should be 8% of the final 500px print width.');
        $this->assertSame(40, $maxY - $minY + 1, 'QR height should match the resized square QR code.');
        $this->assertSame(10, 500 - 1 - $maxX, 'QR right offset should be 2% of the final 500px print width.');
        $this->assertSame(10, 250 - 1 - $maxY, 'QR bottom offset should be 2% of the final 500px print width.');
    }

    public function testCenteredRelativeQrPlacementDoesNotTriggerFloatToIntDeprecation(): void
    {
        $image = new Image();
        $image->debugLevel = 3;
        $image->qrPosition = 'top';
        $image->qrPositionMode = 'relative';
        $image->qrRelativeSize = 0.09;
        $image->qrRelativeOffset = 0.02;
        $image->qrCropWidth = 500;
        $image->qrCropHeight = 500;

        $source = imagecreatetruecolor(1000, 500);
        $this->assertInstanceOf(\GdImage::class, $source);

        $qrCode = imagecreatetruecolor(100, 100);
        $this->assertInstanceOf(\GdImage::class, $qrCode);
        imagesavealpha($qrCode, true);
        imagealphablending($qrCode, false);
        $black = imagecolorallocate($qrCode, 0, 0, 0);
        $this->assertNotFalse($black);
        $this->assertTrue(imagefill($qrCode, 0, 0, $black));

        set_error_handler(
            static function (int $severity, string $message): never {
                throw new \ErrorException($message, 0, $severity);
            },
            E_DEPRECATED
        );

        try {
            $result = $image->applyQr($qrCode, $source);
        } finally {
            restore_error_handler();
        }

        $this->assertInstanceOf(\GdImage::class, $result);
    }

    /**
     * @return array{int,int,int,int}
     */
    private function findNonWhiteBounds(\GdImage $image): array
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $minX = $width;
        $minY = $height;
        $maxX = -1;
        $maxY = -1;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($image, $x, $y) & 0xFFFFFF;
                if ($rgb !== 0xFFFFFF) {
                    $minX = min($minX, $x);
                    $minY = min($minY, $y);
                    $maxX = max($maxX, $x);
                    $maxY = max($maxY, $y);
                }
            }
        }

        $this->assertNotSame(-1, $maxX, 'Expected QR pixels to be present on the cropped image.');

        return [$minX, $minY, $maxX, $maxY];
    }
}

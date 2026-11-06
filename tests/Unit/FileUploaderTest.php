<?php

namespace Photobooth\Tests\Unit;

use Photobooth\FileUploader;
use Photobooth\Logger\NamedLogger;
use Photobooth\Utility\ImageUtility;
use Photobooth\Utility\VideoUtility;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class FileUploaderTest extends TestCase
{
    public function testScreensaverFolderAcceptsImagesAndVideos(): void
    {
        $uploader = new FileUploader('private/screensavers', [], new NamedLogger('file-uploader-test', 0));
        $reflection = new ReflectionClass($uploader);
        $typeChecker = $reflection->getProperty('typeChecker')->getValue($uploader);

        $this->assertEqualsCanonicalizing(
            array_merge(ImageUtility::supportedMimeTypesSelect, VideoUtility::supportedMimeTypesSelect),
            $typeChecker['private/screensavers'],
        );
    }
}

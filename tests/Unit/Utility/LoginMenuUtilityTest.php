<?php

namespace Photobooth\Tests\Unit\Utility;

use Photobooth\Utility\LoginMenuUtility;
use PHPUnit\Framework\TestCase;

final class LoginMenuUtilityTest extends TestCase
{
    public function testAdminSessionIsMenuSession(): void
    {
        $this->assertTrue(LoginMenuUtility::isMenuSessionActive(['auth' => true]));
    }

    public function testRentalSessionIsMenuSession(): void
    {
        $this->assertTrue(LoginMenuUtility::isMenuSessionActive(['rental' => true]));
    }

    public function testAnonymousSessionIsNotMenuSession(): void
    {
        $this->assertFalse(LoginMenuUtility::isMenuSessionActive([]));
    }

    public function testChromaMenuVisibilityIsFalseWhenChromaCaptureIsDisabled(): void
    {
        $config = [
            'chromaCapture' => ['enabled' => false],
            'protect' => [
                'index' => false,
                'localhost_index' => false,
            ],
            'login' => ['enabled' => false],
        ];

        $this->assertFalse(LoginMenuUtility::shouldShowChromaMenuEntry($config, ['auth' => true], []));
    }
}

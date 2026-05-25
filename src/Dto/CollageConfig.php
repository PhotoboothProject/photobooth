<?php

namespace Photobooth\Dto;

class CollageConfig
{
    public string $collageLayout;
    public string $collageOrientation;
    public int $collageResolution;
    public string $collageBackgroundColor;
    public string $collageFrame;
    public string $collageTakeFrame;
    public string $collagePlaceholder;
    public int $collagePlaceholderPosition;
    public string $collagePlaceholderPath;
    public string $collageBackground;
    public string $collageBackgroundLandscape;
    public string $collageBackgroundPortrait;
    public string $collageBackgroundStrip;
    public string $collageBackgroundRenderMode = 'behind_images';
    public string $collageDashedLineColor;
    public int $collageLimit;
    public string $pictureFlip;
    public int $pictureRotation;
    public string $collagePolaroidEffect;
    public int $collagePolaroidRotation;
    public string $textOnCollageEnabled;
    public string $textOnCollageLine1;
    public string $textOnCollageLine2;
    public string $textOnCollageLine3;
    public int $textOnCollageLocationX;
    public int $textOnCollageLocationY;
    public int $textOnCollageRotation;
    public string $textOnCollageFont;
    public string $textOnCollageFontColor;
    public int $textOnCollageFontSize;
    public int $textOnCollageLinespace;
    public bool $collageAllowSelection;

    // Zone-based text alignment properties (when template uses text_alignment.mode = "zone")
    public bool $textZoneMode = false;
    public float $textZoneX = 0;
    public float $textZoneY = 0;
    public float $textZoneW = 0;
    public float $textZoneH = 0;
    public float $textZonePadding = 0;
    public string $textZoneAlign = 'center';
    public string $textZoneValign = 'middle';
    public int $textZoneRotation = 0;
}

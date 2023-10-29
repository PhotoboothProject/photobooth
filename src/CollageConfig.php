<?php

namespace Photobooth;

class CollageConfig
{
    public $collageLayout;
    public $collageResolution;
    public $collageBackgroundColor;
    public $collageFrame;
    public $collageTakeFrame;
    public $collagePlaceholder;
    public $collagePlaceholderPosition;
    public $collagePlaceholderPath;
    public $collageBackground;
    public $collageDashedLineColor;
    public $collageLimit;
    public $pictureFlip;
    public $pictureRotation;
    public $picturePolaroidEffect;
    public $picturePolaroidRotation;
    public $textOnCollageEnabled;
    public $textOnCollageLine1;
    public $textOnCollageLine2;
    public $textOnCollageLine3;
    public $textOnCollageLocationX;
    public $textOnCollageLocationY;
    public $textOnCollageRotation;
    public $textOnCollageFont;
    public $textOnCollageFontColor;
    public $textOnCollageFontSize;
    public $textOnCollageLinespace;

    public function __construct(
        $collageLayout = COLLAGE_LAYOUT,
        $collageResolution = COLLAGE_RESOLUTION,
        $collageBackgroundColor = COLLAGE_BACKGROUND_COLOR,
        $collageFrame = COLLAGE_FRAME,
        $collageTakeFrame = COLLAGE_TAKE_FRAME,
        $collagePlaceholder = COLLAGE_PLACEHOLDER,
        $collagePlaceholderPosition = COLLAGE_PLACEHOLDER_POSITION,
        $collagePlaceholderPath = COLLAGE_PLACEHOLDER_PATH,
        $collageBackground = COLLAGE_BACKGROUND,
        $collageDashedLineColor = COLLAGE_DASHEDLINE_COLOR,
        $collageLimit = COLLAGE_LIMIT,
        $pictureFlip = PICTURE_FLIP,
        $pictureRotation = PICTURE_ROTATION,
        $picturePolaroidEffect = PICTURE_POLAROID_EFFECT,
        $picturePolaroidRotation = PICTURE_POLAROID_ROTATION,
        $textOnCollageEnabled = TEXTONCOLLAGE_ENABLED,
        $textOnCollageLine1 = TEXTONCOLLAGE_LINE1,
        $textOnCollageLine2 = TEXTONCOLLAGE_LINE2,
        $textOnCollageLine3 = TEXTONCOLLAGE_LINE3,
        $textOnCollageLocationX = TEXTONCOLLAGE_LOCATIONX,
        $textOnCollageLocationY = TEXTONCOLLAGE_LOCATIONY,
        $textOnCollageRotation = TEXTONCOLLAGE_ROTATION,
        $textOnCollageFont = TEXTONCOLLAGE_FONT,
        $textOnCollageFontColor = TEXTONCOLLAGE_FONT_COLOR,
        $textOnCollageFontSize = TEXTONCOLLAGE_FONT_SIZE,
        $textOnCollageLinespace = TEXTONCOLLAGE_LINESPACE
    ) {
        $this->collageLayout = $collageLayout;
        $this->collageResolution = $collageResolution;
        $this->collageBackgroundColor = $collageBackgroundColor;
        $this->collageFrame = $collageFrame;
        $this->collageTakeFrame = $collageTakeFrame;
        $this->collagePlaceholder = $collagePlaceholder;
        $this->collagePlaceholderPosition = $collagePlaceholderPosition;
        $this->collagePlaceholderPath = $collagePlaceholderPath;
        $this->collageBackground = $collageBackground;
        $this->collageDashedLineColor = $collageDashedLineColor;
        $this->collageLimit = $collageLimit;
        $this->pictureFlip = $pictureFlip;
        $this->pictureRotation = $pictureRotation;
        $this->picturePolaroidEffect = $picturePolaroidEffect;
        $this->picturePolaroidRotation = $picturePolaroidRotation;
        $this->textOnCollageEnabled = $textOnCollageEnabled;
        $this->textOnCollageLine1 = $textOnCollageLine1;
        $this->textOnCollageLine2 = $textOnCollageLine2;
        $this->textOnCollageLine3 = $textOnCollageLine3;
        $this->textOnCollageLocationX = $textOnCollageLocationX;
        $this->textOnCollageLocationY = $textOnCollageLocationY;
        $this->textOnCollageRotation = $textOnCollageRotation;
        $this->textOnCollageFont = $textOnCollageFont;
        $this->textOnCollageFontColor = $textOnCollageFontColor;
        $this->textOnCollageFontSize = $textOnCollageFontSize;
        $this->textOnCollageLinespace = $textOnCollageLinespace;
    }
}

<?php

require_once __DIR__ . '/config.php';

define('COLLAGE_LAYOUT', $config['collage']['layout']);
define('COLLAGE_RESOLUTION', (int) substr($config['collage']['resolution'], 0, -3));
define('COLLAGE_BACKGROUND_COLOR', $config['collage']['background_color']);
define('COLLAGE_FRAME', str_starts_with($config['collage']['frame'], 'http') ? $config['collage']['frame'] : $_SERVER['DOCUMENT_ROOT'] . $config['collage']['frame']);
define(
    'COLLAGE_BACKGROUND',
    (empty($config['collage']['background'])
            ? ''
            : str_starts_with($config['collage']['background'], 'http'))
        ? $config['collage']['background']
        : $_SERVER['DOCUMENT_ROOT'] . $config['collage']['background']
);
define('COLLAGE_TAKE_FRAME', $config['collage']['take_frame']);
define('COLLAGE_PLACEHOLDER', $config['collage']['placeholder']);
// If a placeholder is set, decrease the value by 1 in order to reflect array counting at 0
define('COLLAGE_PLACEHOLDER_POSITION', (int) $config['collage']['placeholderposition'] - 1);
define(
    'COLLAGE_PLACEHOLDER_PATH',
    str_starts_with($config['collage']['placeholderpath'], 'http') ? $config['collage']['placeholderpath'] : $_SERVER['DOCUMENT_ROOT'] . $config['collage']['placeholderpath']
);
define('COLLAGE_DASHEDLINE_COLOR', $config['collage']['dashedline_color']);
// If a placholder image should be used, we need to increase the limit here in order to count the images correct
define('COLLAGE_LIMIT', $config['collage']['placeholder'] ? $config['collage']['limit'] + 1 : $config['collage']['limit']);
define('PICTURE_FLIP', $config['picture']['flip']);
define('PICTURE_ROTATION', $config['picture']['rotation']);
define('PICTURE_POLAROID_EFFECT', $config['picture']['polaroid_effect'] === true ? 'enabled' : 'disabled');
define('PICTURE_POLAROID_ROTATION', $config['picture']['polaroid_rotation']);
define('TEXTONCOLLAGE_ENABLED', $config['textoncollage']['enabled'] === true ? 'enabled' : 'disabled');
define('TEXTONCOLLAGE_LINE1', $config['textoncollage']['line1']);
define('TEXTONCOLLAGE_LINE2', $config['textoncollage']['line2']);
define('TEXTONCOLLAGE_LINE3', $config['textoncollage']['line3']);
define('TEXTONCOLLAGE_LOCATIONX', $config['textoncollage']['locationx']);
define('TEXTONCOLLAGE_LOCATIONY', $config['textoncollage']['locationy']);
define('TEXTONCOLLAGE_ROTATION', $config['textoncollage']['rotation']);
define('TEXTONCOLLAGE_FONT', $config['textoncollage']['font']);
define('TEXTONCOLLAGE_FONT_COLOR', $config['textoncollage']['font_color']);
define('TEXTONCOLLAGE_FONT_SIZE', $config['textoncollage']['font_size']);
define('TEXTONCOLLAGE_LINESPACE', $config['textoncollage']['linespace']);

class CollageConfig {
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

    function __construct(
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

<?php

namespace Photobooth\Factory;

use Photobooth\Dto\CollageConfig;
use Photobooth\Utility\PathUtility;

class CollageConfigFactory
{
    public static function fromConfig(array $config): CollageConfig
    {
        $collageConfig = new CollageConfig();
        $layout = $config['collage']['layout'];
        $collageConfig->collageLayout = $layout instanceof \BackedEnum
            ? (string) $layout->value
            : (string) $layout;
        $collageConfig->collageOrientation = $config['collage']['orientation'];
        $collageConfig->collageBackgroundColor = $config['collage']['background_color'];
        $collageConfig->collageFrame = $config['collage']['frame'];
        $collageConfig->collageTakeFrame = $config['collage']['take_frame'];
        $collageConfig->collagePlaceholder = $config['collage']['placeholder'];
        // If a placeholder is set, decrease the value by 1 in order to reflect array counting at 0
        $collageConfig->collagePlaceholderPosition = (int) $config['collage']['placeholderposition'] - 1;
        $collageConfig->collagePlaceholderPath = $config['collage']['placeholderpath'];
        $collageConfig->collageBackground = $config['collage']['background'] ?? '';
        $collageConfig->collageBackgroundLandscape = $config['collage']['background_landscape'] ?? '';
        $collageConfig->collageBackgroundPortrait = $config['collage']['background_portrait'] ?? '';
        $collageConfig->collageBackgroundStrip = $config['collage']['background_strip'] ?? '';
        $collageConfig->collageBackgroundRenderMode = $config['collage']['background_render_mode'] ?? 'behind_images';
        $collageConfig->collageDashedLineColor = $config['collage']['dashedline_color'];
        // If a placholder image should be used, we need to increase the limit here in order to count the images correct
        $collageConfig->collageLimit = (int) ($config['collage']['placeholder'] ? $config['collage']['limit'] + 1 : $config['collage']['limit']);
        $collageConfig->pictureFlip = $config['picture']['flip'];
        $collageConfig->pictureRotation = (int) $config['picture']['rotation'];
        $collageConfig->collagePolaroidEffect = $config['collage']['polaroid_effect'] === true ? 'enabled' : 'disabled';
        $collageConfig->collagePolaroidRotation = (int) $config['collage']['polaroid_rotation'];
        $collageConfig->textOnCollageEnabled = $config['textoncollage']['enabled'] === true ? 'enabled' : 'disabled';
        $collageConfig->textOnCollageLine1 = $config['textoncollage']['line1'];
        $collageConfig->textOnCollageLine2 = $config['textoncollage']['line2'];
        $collageConfig->textOnCollageLine3 = $config['textoncollage']['line3'];
        $collageConfig->textOnCollageLocationX = (int) $config['textoncollage']['locationx'];
        $collageConfig->textOnCollageLocationY = (int) $config['textoncollage']['locationy'];
        $collageConfig->textOnCollageRotation = (int) $config['textoncollage']['rotation'];
        $collageConfig->textOnCollageFont = PathUtility::getAbsolutePath($config['textoncollage']['font']);
        $collageConfig->textOnCollageFontColor = $config['textoncollage']['font_color'];
        $collageConfig->textOnCollageFontSize = (int) $config['textoncollage']['font_size'];
        $collageConfig->textOnCollageLinespace = (int) $config['textoncollage']['linespace'];
        $collageConfig->collageAllowSelection = $config['collage']['allow_selection'] === true;

        return $collageConfig;
    }
}

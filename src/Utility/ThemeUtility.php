<?php

namespace Photobooth\Utility;

class ThemeUtility
{
    public static function renderCustomUserStyle(array $config): string
    {
        $properties = [
            '--primary-color' => $config['colors']['primary'] ?? '__UNSET__',
            '--primary-light-color' => $config['colors']['primary_light'] ?? '__UNSET__',
            '--secondary-color' => $config['colors']['secondary'] ?? '__UNSET__',
            '--highlight-color' => $config['colors']['highlight'] ?? '__UNSET__',
            '--secondary-font-color' => $config['colors']['font_secondary'] ?? '__UNSET__',
            '--button-font-color' => $config['colors']['button_font'] ?? '__UNSET__',
            '--start-font-color' => $config['colors']['start_font'] ?? '__UNSET__',
            '--countdown-color' => $config['colors']['countdown'] ?? '__UNSET__',
            '--background-countdown-color' => $config['colors']['background_countdown'] ?? '__UNSET__',
            '--cheese-color' => $config['colors']['cheese'] ?? '__UNSET__',
            '--panel-color' => $config['colors']['panel'] ?? '__UNSET__',
            '--border-color' => $config['colors']['border'] ?? '__UNSET__',
            '--box-color' => $config['colors']['box'] ?? '__UNSET__',
            '--gallery-button-color' => $config['colors']['gallery_button'] ?? '__UNSET__',
            '--background-default' => $config['background']['defaults'] ?? '__UNSET__',
            '--background-chroma' => $config['background']['chroma'] ?? '__UNSET__',
            '--background-preview' => $config['preview']['url'] ?? '__UNSET__',
            '--font-size' => $config['ui']['font_size'] ?? '__UNSET__',
            '--font-color' => $config['colors']['font'] ?? '__UNSET__',
            '--preview-rotation' => $config['preview']['rotation'] ?? '__UNSET__',
        ];

        $output = '';
        $output .= '<style>' . PHP_EOL;
        $output .= ':root {' . PHP_EOL;
        foreach ($properties as $key => $value) {
            $value = trim($value);
            if ($value === '__UNSET__' || $value === '') {
                continue;
            }
            $output .= '  ' . $key . ': ' . $value . ';' . PHP_EOL;
        }
        $output .= '}' . PHP_EOL;
        $output .= '</style>' . PHP_EOL;

        return $output;
    }
}

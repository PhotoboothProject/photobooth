<?php

namespace Photobooth\Utility;

class ThemeUtility
{
    public static function renderCustomUserStyle(array $config): string
    {
        $backgroundDefault = $config['background']['defaults'] ?? '';
        $backgroundChroma  = $config['background']['chroma'] ?? '';

        $backgroundDefaultCss = self::buildBackgroundCssValue($backgroundDefault);
        $backgroundChromaCss  = self::buildBackgroundCssValue($backgroundChroma);

        $properties = [
            '--ui-scale' => $config['ui']['scale'] ? $config['ui']['scale'] . '%' : '__UNSET__',
            '--primary-color' => $config['colors']['primary'] ?? '__UNSET__',
            '--primary-light-color' => $config['colors']['primary_light'] ?? '__UNSET__',
            '--secondary-color' => $config['colors']['secondary'] ?? '__UNSET__',
            '--highlight-color' => $config['colors']['highlight'] ?? '__UNSET__',
            '--secondary-font-color' => $config['colors']['font_secondary'] ?? '__UNSET__',
            '--background-countdown-color' => $config['colors']['background_countdown'] ?? '__UNSET__',
            '--cheese-color' => $config['colors']['cheese'] ?? '__UNSET__',
            '--panel-color' => $config['colors']['panel'] ?? '__UNSET__',
            '--border-color' => $config['colors']['border'] ?? '__UNSET__',
            '--box-color' => $config['colors']['box'] ?? '__UNSET__',
            '--gallery-button-color' => $config['colors']['gallery_button'] ?? '__UNSET__',
            '--background-default' => $backgroundDefaultCss,
            '--background-chroma'  => $backgroundChromaCss,
            '--background-preview' => $config['preview']['url'] ?? '__UNSET__',
            '--preview-rotation' => $config['preview']['rotation'] ?? '__UNSET__',
            '--ui-scale-result' => $config['ui']['scale_resultImage'] ? 'auto ' . $config['ui']['scale_resultImage'] . '%' : '__UNSET__',
        ];

        $fontCss = '';

        $loadedFontUrls = [];

        $appendFontFace = static function (string $family, string $path) use (&$fontCss, &$loadedFontUrls): string {
            $url = PathUtility::getPublicPath($path);

            // Avoid emitting duplicate @font-face rules when the same font file is used in multiple slots.
            if (!isset($loadedFontUrls[$url])) {
                $loadedFontUrls[$url] = $family;
                $fontCss              .= "@font-face {font-family:'{$family}';src:url('{$url}') format('truetype');font-display:swap;}\n";
            }

            return $loadedFontUrls[$url];
        };

        // Default font
        if (!empty($config['fonts']['default'])) {
            $fontFamily                          = $appendFontFace('DefaultFont', $config['fonts']['default']);
            $properties['--font-family-default'] = $fontFamily;
        }
        if (!empty($config['fonts']['default_color'])) {
            $properties['--font-color'] = $config['fonts']['default_color'];
        }
        $properties['--font-weight'] = !empty($config['fonts']['default_bold']) ? '700' : '400';
        $properties['--font-style']  = !empty($config['fonts']['default_italic']) ? 'italic' : 'normal';

        // Start screen font
        if (!empty($config['fonts']['start_screen_title'])) {
            $fontFamily                             = $appendFontFace('StartScreenFont', $config['fonts']['start_screen_title']);
            $properties['--font-family-start-text'] = $fontFamily;
        }
        $properties['--start-text-color']  = $config['fonts']['start_screen_title_color'] ?? '__UNSET__';
        $properties['--start-text-weight'] = !empty($config['fonts']['start_screen_title_bold']) ? '700' : '400';
        $properties['--start-text-style']  = !empty($config['fonts']['start_screen_title_italic']) ? 'italic' : 'normal';

        // Event font
        if (!empty($config['fonts']['event_text'])) {
            $fontFamily                             = $appendFontFace('EventFont', $config['fonts']['event_text']);
            $properties['--font-family-event-text'] = $fontFamily;
        }
        $properties['--event-text-color']  = $config['fonts']['event_text_color'] ?? '__UNSET__';
        $properties['--event-text-weight'] = !empty($config['fonts']['event_text_bold']) ? '700' : '400';
        $properties['--event-text-style']  = !empty($config['fonts']['event_text_italic']) ? 'italic' : 'normal';

        // Countdown font
        if (!empty($config['fonts']['countdown_text'])) {
            $fontFamily                            = $appendFontFace('CountdownFont', $config['fonts']['countdown_text']);
            $properties['--font-family-countdown'] = $fontFamily;
        }
        $properties['--countdown-font-weight'] = !empty($config['fonts']['countdown_text_bold']) ? '700' : '400';
        $properties['--countdown-font-style']  = !empty($config['fonts']['countdown_text_italic']) ? 'italic' : 'normal';
        $properties['--countdown-font-color']  = $config['fonts']['countdown_text_color'] ?? '__UNSET__';

        // Gallery title font
        if (!empty($config['fonts']['gallery_title'])) {
            $fontFamily                                = $appendFontFace('GalleryFont', $config['fonts']['gallery_title']);
            $properties['--font-family-gallery-title'] = $fontFamily;
        }
        $properties['--gallery-title-color']  = $config['fonts']['gallery_title_color'] ?? '__UNSET__';
        $properties['--gallery-title-weight'] = !empty($config['fonts']['gallery_title_bold']) ? '700' : '400';
        $properties['--gallery-title-style']  = !empty($config['fonts']['gallery_title_italic']) ? 'italic' : 'normal';

        // Screensaver font
        if (!empty($config['fonts']['screensaver_text'])) {
            $fontFamily                                   = $appendFontFace('ScreensaverFont', $config['fonts']['screensaver_text']);
            $properties['--font-family-screensaver-text'] = $fontFamily;
        }
        $properties['--screensaver-text-color'] = $config['fonts']['screensaver_text_color'] ?? '__UNSET__';
        $properties['--screensaver-text-weight'] = !empty($config['fonts']['screensaver_text_bold']) ? '700' : '400';
        $properties['--screensaver-text-style']  = !empty($config['fonts']['screensaver_text_italic']) ? 'italic' : 'normal';

        // Font variables (button)
        if (!empty($config['fonts']['button_font'])) {
            $fontFamily                         = $appendFontFace('ButtonFont', $config['fonts']['button_font']);
            $properties['--font-family-button'] = $fontFamily;
        }
        $properties['--button-font-color']  = $config['fonts']['button_font_color'] ?? '__UNSET__';
        $properties['--button-font-weight'] = !empty($config['fonts']['button_font_bold']) ? '700' : '400';
        $properties['--button-font-style']  = !empty($config['fonts']['button_font_italic']) ? 'italic' : 'normal';

        // Font variables (buzzer message)
        if (!empty($config['fonts']['button_buzzer_message_font'])) {
            $fontFamily                                        = $appendFontFace('BuzzerMessageFont', $config['fonts']['button_buzzer_message_font']);
            $properties['--font-family-button_buzzer_message'] = $fontFamily;
        }
        $properties['--buzzer-message-font-color']  = $config['fonts']['button_buzzer_message_font_color'] ?? '__UNSET__';
        $properties['--buzzer-message-font-weight'] = !empty($config['fonts']['button_buzzer_message_font_bold']) ? '700' : '400';
        $properties['--buzzer-message-font-style']  = !empty($config['fonts']['button_buzzer_message_font_italic']) ? 'italic' : 'normal';

        $output = '';
        $output .= '<style>' . PHP_EOL;
        if ($fontCss !== '') {
            $output .= $fontCss;
        }
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

    protected static function buildBackgroundCssValue(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '__UNSET__';
        }

        // Keep already wrapped values for backwards compatibility
        if (str_starts_with($value, 'url(')) {
            return $value;
        }

        // Build a full public URL from a relative or absolute path
        $publicPath = PathUtility::getPublicPath($value);

        return 'url(' . $publicPath . ')';
    }
}

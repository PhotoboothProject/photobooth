<?php

use Photobooth\Service\LanguageService;
use Photobooth\Utility\AdminInput;

$languageService = LanguageService::getInstance();

foreach ($configsetup as $section => $fields) {

    // hidden
    $hiddenSection = 'visible';
    $sectionId = 'id="' . $section . '"';
    if (isElementHidden('setting_section ', $fields) == 'hidden') {
        $sectionId = '';
        $hiddenSection = 'hidden';
    }

    // section container
    echo '<div class="adminSection mb-8 ' . $hiddenSection . '" ' . $sectionId . '>';

    // headline
    echo '<h1 class="text-brand-1 text-xl font-bold pt-4 px-4 lg:pt-8 lg:px-8 mb-4">' . $languageService->translate($section) . '</h1>';

    // grid
    echo '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 lg:gap-8 px-4 lg:px-8">';

    $col = 0;
    foreach ($fields as $key => $setting) {
        if (in_array($key, ['platform', 'view'])) {
            continue;
        }

        $hidden = '';
        if (isElementHidden('setting_element', $setting) == 'hidden') {
            $hidden = 'hidden';
        }

        $i18ntag = $section . ':' . $key;

        echo '<!-- ' . strtoupper($setting['type']) . ' ' . strtoupper($setting['name']) . ' -->';
        echo '<div class="flex flex-col rounded-xl p-3 shadow-xl bg-white ' . $hidden . '" id="' . $i18ntag . '">';

        // Populate setting elements by type

        switch ($setting['type']) {
            case 'icon':
                echo AdminInput::renderIcon($setting, $i18ntag);
                break;
            case 'input':
            case 'number':
                echo AdminInput::renderInput($setting, $i18ntag);
                break;
            case 'range':
                echo AdminInput::renderRange($setting, $i18ntag);
                break;
            case 'color':
                echo AdminInput::renderColor($setting, $i18ntag);
                break;
            case 'hidden':
                echo AdminInput::renderHidden($setting);
                break;
            case 'checkbox':
                echo AdminInput::renderCheckbox($setting, $i18ntag);
                break;
            case 'multi-select':
            case 'select':
                echo AdminInput::renderSelect($setting, $i18ntag);
                break;
            case 'button':
                echo AdminInput::renderButton($setting, $i18ntag, $key, $config);
                break;
            case 'image':
                echo AdminInput::renderImageSelect($setting, $i18ntag);
                break;
        }

        echo '</div>';
    }
    echo '</div>';
    echo '</div>';
}

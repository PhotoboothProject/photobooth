<?php

use Photobooth\Enum\Interface\LabelInterface;

function getSelect($setting, $i18ntag)
{
    $className = $setting['type'] === 'multi-select' ? 'min-h-[30px] h-32 resize-y ' : '';
    $className .= 'w-full h-10 border-2 border-solid border-gray-300 focus:border-brand-1 rounded-md px-2 mt-auto';
    $settingName = $setting['name'] . '' . ($setting['type'] === 'multi-select' ? '[]' : '');
    $options = '';

    foreach ($setting['options'] as $value => $option) {
        $label = $option;
        $value = $value;
        if ($option instanceof \UnitEnum) {
            $label = ($option instanceof LabelInterface) ? $option->label() : $option->name;
            $value = $option->value;
        }

        $selected = '';
        if ((is_array($setting['value']) && in_array($value, $setting['value'])) || $value === $setting['value']) {
            $selected = ' selected="selected"';
        }
        $options .= '<option ' . $selected . ' value="' . $value . '">' . $label . '</option>';
    }

    return getHeadline($i18ntag) . '
        <select
            class="' . $className . '"
            name="' . $settingName . '"
            ' . ($setting['type'] === 'multi-select' ? ' multiple="multiple"' : '') . '
        >
            ' . $options . '
        </select>
    ';
}

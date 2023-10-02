<?php

namespace Photobooth\Utility;

class AdminInput
{
    public static function renderIcon($setting, $label)
    {
        return getHeadline($label) . '
            ' . ($setting['value'] !== '' ? '<div class="text-center mb-3 p-3 border-2 border-solid border-gray-300 rounded-md"><i class="' . $setting['value'] . '"></i></div>' : '') . '
            <input
                class="w-full h-10 border-2 border-solid border-gray-300 focus:border-brand-1 rounded-md px-3 mt-auto"
                type="' . ($setting['type'] === 'number' ? 'number' : 'text') . '"
                name="' . $setting['name'] . '"
                value="' . $setting['value'] . '"
                placeholder="' . $setting['placeholder'] . '"
            />
        ';
    }
}

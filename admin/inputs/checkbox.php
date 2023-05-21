<?php
    function getCheckbox($setting, $i18ntag) {
        $checkboxClasses = "w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600";
        $init = $setting['value'];
        
        return (
            getHeadline($i18ntag) .'
            <label class="adminCheckbox relative inline-flex items-center cursor-pointer mt-auto">
                <input class="hidden peer" type="checkbox" '.(($setting['value'] == 'true')?' checked="checked"':'').' name="'.$setting['name'].'" value="true"/>
                <div class="'.$checkboxClasses.'"></div>
                <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-300">
                    <label class="adminCheckbox-true '. ($init == "true" ? "" : "hidden") .'" data-i18n="adminpanel_toggletextON"></label>
                    <label class="adminCheckbox-false '. ($init != "true" ? "" : "hidden") .'" data-i18n="adminpanel_toggletextOFF"></label>
                </span>
            </label>'
        );
    }
?>


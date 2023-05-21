<?php
    function getRangeInput($setting, $i18ntag) {
        $inputClass = "adminRangeInput w-full h-2 mb-1 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700";
        
        return (
            getHeadline($i18ntag) .'
                <div class="w-full flex flex-col mt-auto">
                    <label id="'.$setting['name'].'-value" for="'.$setting['name'].'" class="block mb-3 text-sm font-bold text-gray-900 dark:text-white">
                        <span class="mr-1">'.$setting['value'].'</span>'.(($setting['unit'] == 'empty')?'': '<span data-i18n="'.$setting['unit'].'">'.$setting['unit'].'</span>').'
                    </label>
                    <input type="range" name="'.$setting['name'].'" class="'. $inputClass .'" value="'.$setting['value'].'" min="'.$setting['range_min'].'" max="'.$setting['range_max'].'" step="'.$setting['range_step'].'" placeholder="'.$setting['placeholder'].'"/>
                    <div class="w-full flex text-gray-300">
                        <span>'.$setting['range_min'].'</span>
                        <span class="ml-auto">'.$setting['range_max'].'</span>
                    </div>
                </div>
            '
        );
    }
?>
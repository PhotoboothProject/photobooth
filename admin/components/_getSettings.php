<?php
    foreach($configsetup as $section => $fields)
    {
        html_src_indent($indent);
        html_src_indent($indent++);

        // hidden
        $hiddenSection = "visible";
        $sectionId = 'id="'.$section.'"';
        if(isElementHidden('setting_section ',$fields) == "hidden") {
                $sectionId = "";
                $hiddenSection = "hidden";
        }

        // section container
        echo '<div class="adminSection mb-8 '. $hiddenSection .'" '.$sectionId.'>';

        html_src_indent($indent);
        
        // headline
        echo '<h1 class="text-brand-1 text-xl font-bold pt-4 px-4 lg:pt-8 lg:px-8 mb-4"> <span data-i18n="'.$section.'">'.$section.'</span></h1>';

        // grid
        echo '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 lg:gap-8 px-4 lg:px-8">';

        $col = 0;
        foreach($fields as $key => $setting)
        {
            if (in_array($key,array("platform", "view"))) {
                    continue;
            };

            $hidden = "";
            if(isElementHidden('setting_element', $setting) == "hidden") {
                $hidden = "hidden";
            }

            $i18ntag = $section.':'.$key;

            html_src_indent($indent++);

            echo '<!-- '.strtoupper($setting['type']).' '.strtoupper($setting['name']).' -->';
            echo '<div class="flex flex-col rounded-xl p-3 shadow-xl bg-white '. $hidden .'" id="'.$i18ntag.'">';
            
            /************************************
            ** Populate setting elements by type
            ************************************/
            
            switch($setting['type']) {
                    case 'input':
                    case 'number':
                            echo getTextInput($setting, $i18ntag);
                            break;
                    case 'range':
                            echo getRangeInput($setting, $i18ntag);
                            break;
                    case 'color':
                            echo getColorInput($setting, $i18ntag);
                            break;
                    case 'hidden':
                            echo '<input type="hidden" name="'.$setting['name'].'" value="'.$setting['value'].'"/>';
                            break;
                    case 'checkbox':
                            echo getCheckbox($setting, $i18ntag);
                            break;
                    case 'multi-select':
                    case 'select':
                            echo getSelect($setting, $i18ntag);
                            break;
                    case 'button':
                            echo getInputButton($setting, $i18ntag, $key, $config);
                            break;
            }

            echo '</div>';
            --$indent;
        }
        echo '</div>';

        html_src_indent(--$indent);

        echo '</div>';
    }
?>
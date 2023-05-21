<?php
    function isElementHidden($element_class, $setting)
    {
            global $config;
            if (empty($setting['view'])) {
                $setting['view'] = $config['adminpanel']['view_default'];
            };

            switch ($setting['view'])
            {
                    case 'experimental':
                         if (!$config['adminpanel']['experimental_settings']) { $element_class = 'hidden'; };
                         break;
                    case 'expert':
                         if ($config['adminpanel']['view'] == 'advanced') { $element_class = 'hidden'; };
                         if ($config['adminpanel']['view'] == 'basic') { $element_class = 'hidden'; };
                         break;
                    case 'advanced':
                         if ($config['adminpanel']['view'] == 'basic') { $element_class = 'hidden'; };
                         break;
                    case 'basic':
                         break;
            };
            
            if (isset($fields['platform']) && $fields['platform'] != 'all' && $fields['platform'] != $os) {
                $setting['type'] = $element_class = 'hidden';
            };
            
            if (isset($setting['type']) && $setting['type'] == 'hidden') {
                $element_class = 'hidden';
            };

            return $element_class;
    }
?>
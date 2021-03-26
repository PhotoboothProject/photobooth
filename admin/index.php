<?php
session_start();
require_once '../lib/config.php';

// Login / Authentication check
if (
    !$config['login']['enabled'] ||
    (!$config['protect']['localhost_admin'] && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) ||
    (isset($_SESSION['auth']) && $_SESSION['auth'] === true) ||
    !$config['protect']['admin']
) {
    require_once '../lib/configsetup.inc.php';
} else {
    header('location: ../login');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0 user-scalable=no">
        <meta name="msapplication-TileColor" content="<?=$config['colors']['primary']?>">
        <meta name="theme-color" content="<?=$config['colors']['primary']?>">

        <!-- Favicon + Android/iPhone Icons -->
        <link rel="apple-touch-icon" sizes="180x180" href="../resources/img/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="../resources/img/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="../resources/img/favicon-16x16.png">
        <link rel="manifest" href="../resources/img/site.webmanifest">
        <link rel="mask-icon" href="../resources/img/safari-pinned-tab.svg" color="#5bbad5">

        <link rel="stylesheet" type="text/css" href="../node_modules/normalize.css/normalize.css">
        <link rel="stylesheet" type="text/css" href="../node_modules/font-awesome/css/font-awesome.css">
        <link rel="stylesheet" type="text/css" href="../node_modules/selectize/dist/css/selectize.css">
        <link rel="stylesheet" type="text/css" href="../resources/css/admin.css">
</head>
<body>
<!-- NavBar content -->
<?php
                /***********************
                ** PHP helper functions
                ***********************/

                function html_src_indent($num)
                {
                        echo "\n".str_repeat("\t",$num);
                }

                function isElementHidden($element_class, $setting)
                {
                        global $config;
                        /*
                        ** check for admin panel view settings
                        */
        
                        if (empty($setting['view'])) {
                           $setting['view'] = $config['adminpanel']['view_default'];
                        };
        
                        switch ($setting['view'])
                        {
                                case 'expert':
                                     if ($config['adminpanel']['view'] == 'advanced') { $element_class = 'hidden'; };
                                case 'advanced':
                                     if ($config['adminpanel']['view'] == 'basic') { $element_class = 'hidden'; };
                                case 'basic':
                                     break;
                        };
                                
                        /*
                        ** check for  platform compatibility
                        */
                        if (isset($fields['platform']) && $fields['platform'] != 'all' && $fields['platform'] != $os) {
                           $setting['type'] = $element_class = 'hidden';
                        };
        
                        /*
                        ** Check if actual setting type is hidden
                        */
                        if (isset($setting['type']) && $setting['type'] == 'hidden') {
                           $element_class = 'hidden';
                        };
        
                        return $element_class;
                }
        
        
                $indent = 2;

                /********************
                * Create topnav bar *
                *********************/
                html_src_indent($indent++);
                echo '<div class="admintopnavbar">';
                html_src_indent($indent);
                echo '<i class="fa fa-long-arrow-left fa-3x" id="admintopnavbarback"></i>';
                if(isset($_SESSION['auth']) && $_SESSION['auth'] === true)
                {
                        html_src_indent($indent);
                        echo '     <i class="fa fa-sign-out fa-3x" id="admintopnavbarlogout"></i>';
                }
                html_src_indent($indent);
                echo '     <i class="fa fa-bars fa-3x" id="admintopnavbarmenutoggle"></i>';
                html_src_indent(--$indent);
                echo '</div>';

                /*********************
                * Create sidenav bar *
                *********************/
                html_src_indent($indent++);
                echo '<div>';
                html_src_indent($indent);
                echo '<div class="adminsidebar" id="adminsidebar">';
                html_src_indent(++$indent);
                echo '<ul class="adminnavlist" id="navlist">';

                html_src_indent(++$indent);
        
        
                foreach($configsetup as $section => $fields)
                {
                        html_src_indent($indent);
        
                        /*
                        ** check for admin panel view settings
                        */
                        
        
                        echo '<li><a class="'.isElementHidden('adminnavlistelement',$fields).'" href="#'.$section.'" id="nav-'.$section.'"><div><span data-i18n="'.$section.'">'.$section.'</span></div></a></li>';
        
                }
        
                html_src_indent(--$indent);
                echo '</ul>';
        ?>
</div>
<!-- Settings page content -->
<form  autocomplete="off">

        <div class="admincontent" id="admincontentpage">
                    <button class="save-btn" id="save-btn">
                     <span class="save"><span data-i18n="save"></span></span>
                     <span class="saving"><i class="fa fa-circle-o-notch fa-spin fa-fw"></i><span data-i18n="saving"></span></span>
                     <span class="success"><i class="fa fa-check"></i><span data-i18n="success"></span></span>
                     <span class="error"><i class="fa fa-times"></i><span data-i18n="saveerror"></span></span>
             </button>
        <?php
        
                /************************
                * Create settings panel *
                ************************/
        
                foreach($configsetup as $section => $fields)
                {
                        html_src_indent($indent);
                        html_src_indent($indent++);
        
                        echo '<!-- SECTION '.$section.'-->';
                        echo '<div class="'.isElementHidden('setting_section',$fields).'" id="'.$section.'">';
        
                        html_src_indent($indent);
                        echo '<h1 class="setting_section_heading"> <span data-i18n="'.$section.'">'.$section.'</span></h1>';
        
                        $col = 0;
                        foreach($fields as $key => $setting)
                        {
                                if (in_array($key,array("platform", "view"))) {
                                        continue;
                                };
        
                                $i18ntag = $section.':'.$key;
        
                                html_src_indent($indent++);
        
                                echo '<!-- '.strtoupper($setting['type']).' '.strtoupper($setting['name']).' -->';
                                echo '<div class="'.isElementHidden('setting_element', $setting).'" id="'.$i18ntag.'">';
                                
                                /************************************
                                ** Populate setting elements by type
                                ************************************/
                                
                                switch($setting['type']) {
                                        case 'input':
                                                echo '<div class="tooltip">';
                                                echo '<label class="settinglabel" data-i18n="'.$i18ntag.'">'.$i18ntag.'</label>';
                                                echo '<span class="tooltiptext" data-i18n="manual:'.$i18ntag.'">manual:'.$i18ntag.'</span></div>';
                                                echo '<input class="settinginput" type="text" name="'.$setting['name'].'" value="'.$setting['value'].'" placeholder="'.$setting['placeholder'].'"/>';
                                                break;
                                        case 'range':
                                                echo '<div class="tooltip">';
                                                echo '<label class="settinglabel" data-i18n="'.$i18ntag.'">'.$i18ntag.'</label>';
                                                echo '<span class="tooltiptext" data-i18n="manual:'.$i18ntag.'">manual:'.$i18ntag.'</span></div>';
                                                echo '<label class="floatright" id="'.$setting['name'].'-value"><span>'.$setting['value'].'</span>'.(($setting['unit'] == 'empty')?'': '<span data-i18n="'.$setting['unit'].'">'.$setting['unit'].'</span>').'</label>';
                                                echo '<input type="range" name="'.$setting['name'].'" class="configslider settinginput" value="'.$setting['value'].'" min="'.$setting['range_min'].'" max="'.$setting['range_max'].'" step="'.$setting['range_step'].'" placeholder="'.$setting['placeholder'].'"/>';
                                                echo '<label>'.$setting['range_min'].'</label><label class="floatright">'.$setting['range_max'].'</label>';
                                                break;
                                        case 'color':
                                                echo '<label class="settinglabel" data-i18n="'.$i18ntag.'"> '.$i18ntag.'</label>';
                                                echo '<input class="settinginput color" type="color" name="'.$setting['name'].'" value="'.$setting['value'].'" placeholder="'.$setting['placeholder'].'"/>';
                                                break;
                                        case 'hidden':
                                                echo '<input type="hidden" name="'.$setting['name'].'" value="'.$setting['value'].'"/>';
                                                break;
                                        case 'checkbox':
                                                echo '<div class="tooltip"><label class="settinglabel"><span data-i18n="'.$i18ntag.'">'.$i18ntag.'</span></label>';
                                                echo '<span class="tooltiptext" data-i18n="manual:'.$i18ntag.'">manual:'.$i18ntag.'</span></div>';                                      
                                                echo '<label class="toggle settinginput"> <input type="checkbox" '.(($setting['value'] == 'true')?' checked="checked"':'').' name="'.$setting['name'].'" value="true"/>';
                                                echo '<span class="slider">';
                                                if ($setting['value'] == 'true')
                                                {
                                                        echo '<label class="toggleTextON" data-i18n="adminpanel_toggletextON"></label><label class="toggleTextOFF hidden" data-i18n="adminpanel_toggletextOFF"></label>';
                                                } else
                                                {
                                                        echo '<label class="toggleTextON hidden" data-i18n="adminpanel_toggletextON"></label><label class="toggleTextOFF" data-i18n="adminpanel_toggletextOFF"></label>';
                                                }
                                                echo '</span></label>';
                                                break;
                                        case 'multi-select':
                                        case 'select':
                                                echo '<div class="tooltip">';
                                                echo '<label class="settinglabel" data-i18n="'.$i18ntag.'">'.$i18ntag.'</label>';
                                                echo '<span class="tooltiptext" data-i18n="manual:'.$i18ntag.'">manual:'.$i18ntag.'</span></div>';                                      
                                                echo '<select class="settinginput'.($setting['type'] === 'multi-select' ? ' multi-select' : '');
                                                echo '" name="'.$setting['name'] . ($setting['type'] === 'multi-select' ? '[]' : '');
                                                echo '"' . ($setting['type'] === 'multi-select' ? ' multiple="multiple"' : '') . '>';
                                                        foreach($setting['options'] as $val => $option) {
                                                                $selected = '';
                                                                if ((is_array($setting['value']) && in_array($val, $setting['value'])) || ($val === $setting['value'])) {
                                                                        $selected = ' selected="selected"';
                                                                }
                                                                echo '<option '.$selected.' value="'.$val.'">'.$option.'</option>';
                                                        }
                                                echo '</select>';
                                                break;
                                        case 'button':
                                             echo '<div class="tooltip">';
                                             echo '<label class="settinglabel" data-i18n="'.$i18ntag.'">'.$i18ntag.'</label>';
                                             echo '<span class="tooltiptext" data-i18n="manual:'.$i18ntag.'">manual:'.$i18ntag.'</span></div>';
                                             echo '<div><button class="adminpanel-setting-btn" id="'.$setting['value'].'">';
                                             switch ($key) {
                                                    case 'reset_button':
                                                         echo '<span class="save"><span data-i18n="reset"></span></span>';
                                                         echo '<span class="saving"><i class="fa fa-circle-o-notch fa-spin fa-fw"></i><span data-i18n="saving"></span></span>';
                                                         echo '<span class="success"><i class="fa fa-check"></i><span data-i18n="success"></span></span>';
                                                         echo '<span class="error"><i class="fa fa-times"></i><span data-i18n="saveerror"></span></span>';
                                                         break;
                                                    case 'database_rebuild':
                                                    case 'check_version':
                                                         echo '<span class="saving"><i class="fa fa-circle-o-notch fa-spin fa-fw"></i><span data-i18n="busy"></span></span>';
                                                         echo '<span class="success"><i class="fa fa-check"></i><span data-i18n="success"></span></span>';
                                                         echo '<span class="error"><i class="fa fa-times"></i><span data-i18n="saveerror"></span></span>';
                                                         echo '<span class="text" data-i18n="'.$setting['placeholder'].'"></span>';
                                                         break;
                                                    default:
                                                         echo '<span class="text" data-i18n="'.$setting['placeholder'].'"></span>';
                                                         break;
                                                         }
                                             echo '</button>';
                                             echo '</div>';

                                             switch ($key) {
                                                    case 'check_version':
                                                         echo '<table id="version_text_table"><tr><td><span id="current_version_text"></span></td><td><span id="current_version"></span></td></tr><tr><td><span id="available_version_text"></span></td><td></span><span id="available_version"></td></tr></table>';
                                                         break;
                                                    default:
                                                        break;
                                                }

                                             break;
                                }
        
                                echo '</div>';
                                --$indent;
                        }
        
                        html_src_indent(--$indent);
        
                        echo '</div>';
                }
?>
</div>
</form>
        <script src="../node_modules/whatwg-fetch/dist/fetch.umd.js"></script>
        <script type="text/javascript" src="../api/config.php"></script>
        <script type="text/javascript" src="../node_modules/jquery/dist/jquery.min.js"></script>
        <script type="text/javascript" src="../node_modules/waypoints/lib/jquery.waypoints.min.js"></script>
        <script type="text/javascript" src="../node_modules/selectize/dist/js/standalone/selectize.min.js"></script>
        <script type="text/javascript" src="../resources/js/theme.js"></script>
        <script type="text/javascript" src="../resources/js/admin.js"></script>
        <script src="../node_modules/@andreasremdt/simple-translator/dist/umd/translator.min.js"></script>
        <script type="text/javascript" src="../resources/js/i18n-sub.js"></script>
 </body>
</html>
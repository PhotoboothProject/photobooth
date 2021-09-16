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

        <!-- do not cache in browser -->
        <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
        <meta http-equiv="Pragma" content="no-cache" />
        <meta http-equiv="Expires" content="0" />

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
	<?php if (is_file("../private/overrides.css")): ?>
	<link rel="stylesheet" href="../private/overrides.css" />
	<?php endif; ?>
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
                        echo '<i class="fa fa-sign-out fa-3x" id="admintopnavbarlogout"></i>';
                }
                html_src_indent($indent);
                echo '<i class="fa fa-bars fa-3x" id="admintopnavbarmenutoggle"></i>';
                html_src_indent($indent);
                echo '<i class="setting_section_heading"><span data-i18n="debugpanel">Debug Panel</span></i>';
                html_src_indent(--$indent);
                echo '<i><div id="debugpanel_autorefresh"><div><label class="settinglabel"><span data-i18n="debugpanel:autorefresh">debugpanel:autorefresh</span></label></div><label class="toggle settinginput"> <input type="checkbox" id="debugpanel_autorefresh" value="true"/><span class="slider"><label class="toggleTextON hidden" data-i18n="adminpanel_toggletextON"></label><label class="toggleTextOFF" data-i18n="adminpanel_toggletextOFF"></label></span></label></div></i>';
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

                echo '<li><a class="adminnavlistelement" href="#myconfig" id="nav-myconfig"><div><span data-i18n="myconfig">myconfig</span></div></a></li>';
                echo '<li><a class="adminnavlistelement" href="#remotebuzzerlog" id="nav-remotebuzzerlog"><div><span data-i18n="remotebuzzer">remotebuzzer</span></div></a></li>';
                echo '<li><a class="adminnavlistelement" href="#synctodrivelog" id="nav-synctodrivelog"><div><span data-i18n="synctodrive">synctodrive</span></div></a></li>';
                echo '<li><a class="adminnavlistelement" href="#cameralog" id="nav-cameralog"><div><span data-i18n="cameralog">cameralog</span></div></a></li>';
                echo '<li><a class="adminnavlistelement" href="#serverprocesses" id="nav-serverprocesses"><div><span data-i18n="serverprocesses">serverprocesses</span></div></a></li>';
                echo '<li><a class="adminnavlistelement" href="#bootconfig" id="nav-bootconfig"><div><span data-i18n="bootconfig">bootconfig</span></div></a></li>';
                echo '<li><a class="adminnavlistelement" href="#githead" id="nav-githead"><div><span data-i18n="githead">githead</span></div></a></li>';
        
                html_src_indent(--$indent);
                echo '</ul>';
        ?>
</div>
<!-- Settings page content -->
<form  autocomplete="off">
<div class="admincontent" id="admincontentpage">
	<div class="debugcontent"> 
	</div>
</div>
        <script src="../node_modules/whatwg-fetch/dist/fetch.umd.js"></script>
        <script type="text/javascript" src="../api/config.php"></script>
        <script type="text/javascript" src="../node_modules/jquery/dist/jquery.min.js"></script>
        <script type="text/javascript" src="../node_modules/selectize/dist/js/standalone/selectize.min.js"></script>
        <script type="text/javascript" src="../resources/js/tools.js"></script>
        <script type="text/javascript" src="../resources/js/theme.js"></script>
        <script type="text/javascript" src="../resources/js/debugpanel.js"></script>
        <script src="../node_modules/@andreasremdt/simple-translator/dist/umd/translator.min.js"></script>
        <script type="text/javascript" src="../resources/js/i18n.js"></script>
</body>
</html>


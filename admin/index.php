<?php
session_start();
require_once('../lib/config.php');
require_once('../lib/configsetup.inc.php');
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
	<link rel="stylesheet" type="text/css" href="../resources/css/admin.css">
</head>
<body>
<!-- NavBar content -->
<div class="adminsidebar" id="adminsidebar">
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
		   $setting['view'] = $config['adminpanel_view_default'];
		};

		switch ($setting['view'])
		{
			case 'expert':
			     if ($config['adminpanel_view'] == 'advanced') { $element_class = 'hidden'; };
			case 'advanced':
			     if ($config['adminpanel_view'] == 'basic') { $element_class = 'hidden'; };
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


	/*******************
	* Create nav panel *
	*******************/
	$indent = 3;
	html_src_indent($indent++);

	echo '<ul class="adminnavlist" id="navlist">';
	echo '<li><a class="adminnavlistelement" href=".." id="nav-ref-main"><i class="fa fa-long-arrow-left fa-2x"></i></a></li>';


	foreach($configsetup as $section => $fields)
	{
		html_src_indent($indent);

		/*
		** check for admin panel view settings
		*/
		

		echo '<li><a class="'.isElementHidden('adminnavlistelement',$fields).'" href="#'.$section.'" id="nav-'.$section.'"><span data-i18n="'.$section.'">'.$section.'</span></a></li>';

	}

	html_src_indent(--$indent);
	echo '</ul>';
?>
</div>

<!-- Settings page content -->
<form  autocomplete="off">

<div class="admincontent" id="admincontentpage">
    <button class="save-btn">
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

		if ($section == 'reset') {
		 	   html_src_indent($indent++);
		   	   echo '<button class="reset-btn">';
  		   	   html_src_indent($indent);
		   	   echo '<span class="save"><span data-i18n="reset"></span></span>';
		   	   echo '<span class="saving"><i class="fa fa-circle-o-notch fa-spin fa-fw"></i><span data-i18n="saving"></span></span>';
		   	   echo '<span class="success"><i class="fa fa-check"></i><span data-i18n="success"></span></span>';
		   	   echo '<span class="error"><i class="fa fa-times"></i><span data-i18n="saveerror"></span></span>';
   		   	   html_src_indent(--$indent);		      
		   	   echo '</button>';
		   }

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
					echo '<label class="floatright" id="'.$setting['name'].'-value"><span>'.$setting['value'].'</span> <span data-i18n="'.$setting['unit'].'">'.$setting['unit'].'</span></label>';
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
					echo '<label class="toggle toggletext settinginput"> <input type="checkbox" '.(($setting['value'] == 'true')?' checked="checked"':'').' name="'.$setting['name'].'" value="true"/><span class="slider"></span></label>';
					break;
				case 'multi-select':
				case 'select':
					echo '<div class="tooltip">';
					echo '<label class="settinglabel" data-i18n="'.$i18ntag.'">'.$i18ntag.'</label>';
					echo '<span class="tooltiptext" data-i18n="manual:'.$i18ntag.'">manual:'.$i18ntag.'</span></div>';					
					echo '<select class="settinginput" name="'.$setting['name'] . ($setting['type'] === 'multi-select' ? '[]' : '') . '"' . ($setting['type'] === 'multi-select' ? ' multiple="multiple" size="2"' : '') . '>';
						foreach($setting['options'] as $val => $option) {
							$selected = '';
							if ((is_array($setting['value']) && in_array($val, $setting['value'])) || ($val === $setting['value'])) {
								$selected = ' selected="selected"';
							}
							echo '<option '.$selected.' value="'.$val.'">'.$option.'</option>';
						}
					echo '</select>';
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
        <script type="text/javascript" src="../resources/js/theme.js"></script>
        <script type="text/javascript" src="../resources/js/admin.js"></script>
        <script src="../node_modules/@andreasremdt/simple-translator/dist/umd/translator.min.js"></script>
        <script type="text/javascript" src="../resources/js/i18n-sub.js"></script>
 </body>
</html>
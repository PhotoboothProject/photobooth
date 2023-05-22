<!DOCTYPE html>
<html>
<head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0 user-scalable=no">
        <meta name="msapplication-TileColor" content="<?=$config['colors']['primary']?>">
        <meta name="theme-color" content="<?=$config['colors']['primary']?>">

        <title><?=$pageTitle ?></title>

        <!-- Favicon + Android/iPhone Icons -->
        <link rel="apple-touch-icon" sizes="180x180" href="<?=$fileRoot?>resources/img/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="<?=$fileRoot?>resources/img/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="<?=$fileRoot?>resources/img/favicon-16x16.png">
        <link rel="manifest" href="<?=$fileRoot?>resources/img/site.webmanifest">
        <link rel="mask-icon" href="<?=$fileRoot?>resources/img/safari-pinned-tab.svg" color="#5bbad5">

        <link rel="stylesheet" type="text/css" href="<?=$fileRoot?>node_modules/normalize.css/normalize.css">
        <link rel="stylesheet" type="text/css" href="<?=$fileRoot?>node_modules/font-awesome/css/font-awesome.css">
        <link rel="stylesheet" type="text/css" href="<?=$fileRoot?>node_modules/material-icons/iconfont/material-icons.css">
        <link rel="stylesheet" type="text/css" href="<?=$fileRoot?>node_modules/material-icons/css/material-icons.css">
        <link rel="stylesheet" type="text/css" href="<?=$fileRoot?>node_modules/selectize/dist/css/selectize.css">

        <!-- tw admin -->
        <link rel="stylesheet" href="<?=$fileRoot?>resources/css/tailwind.admin.css"/>
<style>
:root {
    --brand-1: <?=$config['colors']['panel'];?>;  
}
</style>
</head> 
<body class="w-full h-screen overflow-hidden fixed">
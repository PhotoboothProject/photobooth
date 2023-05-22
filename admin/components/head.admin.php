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

        <!-- tw admin -->
        <link rel="stylesheet" href="<?=$fileRoot?>resources/css/tailwind.admin.css"/>

        <!-- js -->
        <script type="text/javascript" src="<?=$fileRoot?>node_modules/jquery/dist/jquery.min.js"></script>
<style>
:root {
    --brand-1: <?=$config['colors']['panel'];?>;  
}
</style>
</head> 
<body class="w-full h-screen overflow-hidden fixed">
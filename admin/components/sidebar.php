<?php
    include('navItem.php');

    $headline = 'Sidebar';
    if (isset($sidebarHeadline)) {
        $headline = $sidebarHeadline;
    }
?>

<div class="w-full flex md:hidden px-5 pb-5 items-center">
    <div class="w-full flex flex-col">
        <span class="text-2xl text-white"><?=$headline ?></span>
        <span class="text-white text-opacity-60 flex items-center">
            <span class="fa fa-location-arrow text-white text-opacity-60 text-sm flex items-center mr-1"></span>
            <span id="activeTabLabel" class="capitalize">General</span>
        </span>
    </div>
    <div class="w-12 h-12 ml-auto text white cursor-pointer flex items-center justify-center" onclick="toggleAdminNavi()">
        <span class="text-white !text-2xl fa fa-bars"></span>
    </div>
</div>

<div class="adminNavi hidden md:!hidden w-full h-full z-40 fixed top-0 left-0 bg-black bg-opacity-70 cursor-pointer [&.isActive]:flex" onclick="toggleAdminNavi();"></div>
<div class="adminNavi hidden [&.isActive]:flex z-50 bg-brand-1 h-full pb-10 overflow-hidden w-3/4 fixed top-0 right-0 md:w-64 md:flex md:static md:bg-transparent">
    <div class="w-full h-full pl-5 flex flex-col overflow-hidden">
        <div class="flex items-center shrink-0 border-b border-solid border-white border-opacity-20 py-4 mr-4">
            <a href="<?=$fileRoot?>login" class="h-4 mr-4 flex items-center justify-center border-r border-solid border-white border-opacity-20 px-3">
                <span class="fa fa-home text-white text-opacity-60 text-2xl hover:text-opacity-100 transition-all"></span>
            </a>
            <h1 class="text-white font-bold"><?=$headline ?></h1>
            <div class="w-12 h-12 ml-auto text white cursor-pointer flex items-center justify-center md:hidden" onclick="toggleAdminNavi()">
                <span class="text-white !text-2xl fa fa-close"></span>
            </div>
        </div>
        <div class="w-full h-full flex flex-col overflow-hidden">
            <ul class="w-full h-full flex flex-col overflow-x-hidden overflow-y-auto">
                <li class="flex w-full h-6 shrink-0"></li>
                <?php
                    foreach($configsetup as $section => $fields)
                    {
                        echo getNavItem($section, isElementHidden('adminnavlistelement',$fields) );
                    }
                ?>
            </ul>
        </div>
    </div>
</div>

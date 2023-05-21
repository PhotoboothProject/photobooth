<form class="w-full h-full flex flex-col" autocomplete="off">
    <div class="adminContent w-full flex flex-1 flex-col py-5 overflow-x-hidden overflow-y-auto">
        <?php include("_getSettings.php"); ?>
    </div>

    <div class="pt-5 pb-5 mx-4 lg:mx-8">  
        <div class="w-44 ml-auto">
            <?php echo getCtaBtn( "save", "save-admin-btn" ); ?>
        </div>
    </div>
</form>
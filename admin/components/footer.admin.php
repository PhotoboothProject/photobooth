<?php

use Photobooth\Service\AssetService;

$assetService = AssetService::getInstance();

?>
    <div class="pageLoader w-full h-full fixed top-0 left-0 z-50 hidden place-items-center [&.isActive]:grid">
        <div class="w-full h-full left-0 top-0 z-10 absolute bg-black bg-opacity-60"></div>
        <div class="min-w-[115px] px-4 py-6 rounded-md bg-white shadow-md flex flex-col items-center justify-center relative z-20 text-center">
            <?php echo getLoader('sm'); ?>
            <label class="text-xs text-brand-1 mt-4 font-bold"></label>
        </div>
    </div>

    <?php echo getToast(); ?>

    <script src="<?=$assetService->getUrl('api/config.php')?>"></script>
    <script src="<?=$assetService->getUrl('resources/js/main.admin.js')?>"></script>
 </body>
</html>

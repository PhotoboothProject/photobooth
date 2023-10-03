<?php

use Photobooth\Utility\AdminInput;
use Photobooth\Utility\PathUtility;

?>
<form class="w-full h-full flex flex-col" autocomplete="off">
    <div class="adminContent w-full flex flex-1 flex-col py-5 overflow-x-hidden overflow-y-auto">
        <?php include PathUtility::getAbsolutePath('admin/components/_getSettings.php'); ?>
    </div>

    <div class="pt-5 pb-5 mx-4 lg:mx-8">
        <div class="w-44 ml-auto">
            <?php echo AdminInput::renderCta('save', 'save-admin-btn'); ?>
        </div>
    </div>
</form>

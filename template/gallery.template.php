<?php

use Photobooth\Service\LanguageService;
use Photobooth\Utility\PathUtility;

$languageService = LanguageService::getInstance();

?>
<div id="gallery" class="gallery rotarygroup">
    <div class="gallery__inner">
        <div class="gallery__header">
            <h1><?=$languageService->translate('gallery')?></h1>
            <a href="#" class="<?php echo $btnClass; ?> gallery__close close_gal rotaryfocus"><i class="<?php echo $config['icons']['close']; ?>"></i></a>
        </div>
<?php
include PathUtility::getAbsolutePath('template/components/gal.images.php');
if ($GALLERY_FOOTER === true && $config['gallery']['action_footer'] === true) {
    include PathUtility::getAbsolutePath('template/components/gal.btnFooter.php');
}
?>
    </div>
</div>


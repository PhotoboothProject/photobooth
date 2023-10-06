<?php

use Photobooth\Service\LanguageService;
$languageService = LanguageService::getInstance();
?>

<div id="newQrModal" class="w-full h-full fixed top-0 left-0 hidden place-items-center p-4 [&.isOpen]:grid" style="z-index:9999">
    <div class="w-full h-full absolute top-0 left-0 z-10 bg-black/60 cursor-pointer" onclick="closeQrCodeModal();"></div>
    <div class="max-w-lg bg-white px-6 py-8 relative z-20 flex rounded text-lg">

    
        <?php if ($config['qr']['wifi_enabled']) {
            $qrStepOneLong = $config['qr']['custom_text'] ? $config['qr']['text'] : '<span data-i18n="qr_step_one_long"></span>';
            echo '<div class="mr-8 flex flex-col items-center justify-center">
                    <h2 class="flex flex-col text-brand-1 font-bold text-2xl">' . $languageService->translate('qr_step_one') . '</h2>
                    <div class="flex flex-col max-w-[250px] text-center mb-4">' .
                $qrStepOneLong .
                '</div>
                    <div class="flex flex-col mt-auto">
                        <div class="w-52 h-52 border-4 border-solid border-brand-1 rounded-t">
                            <div id="wifiQrCode"></div>
                        </div>
                        <h2 class="w-full bg-brand-1 rounded-b text-white text-center pb-1">' . $languageService->translate('wifi') . '</h2>
                    </div>
                </div>';
        } ?>

        <div class="flex flex-col items-center justify-center">
            <?php if ($config['qr']['wifi_enabled']) {
                $qrStepTwoLong = '<span data-i18n="qr_step_two_long"></span>';
                echo '<h2 class="flex flex-col text-brand-1 font-bold text-2xl">' . $languageService->translate('qr_step_two') . '</h2>';
            } else {
                $qrStepTwoLong = $config['qr']['custom_text'] ? $config['qr']['text'] : $languageService->translate('qr_step_two_long');
            } ?>
            <div class="flex flex-col max-w-[250px] text-center mb-4"><?php echo $qrStepTwoLong; ?></div>
            <div class="flex flex-col mt-auto">
                <div class="w-52 h-52 border-4 border-solid border-brand-1 rounded-t">
                    <div id="imageQrCode"></div>
                </div>
                <?php
                echo '<h2 class="w-full bg-brand-1 rounded-b text-white text-center pb-1">' . $languageService->translate('your_picture') . '</h2>';
                ?>
            </div>
        </div>

    </div>
</div>

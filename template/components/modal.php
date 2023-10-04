<?php

use Photobooth\Service\LanguageService;

$languageService = LanguageService::getInstance();

?>

<div class="modal" id="print_mesg">
    <div class="modal__body"><?=$languageService->translate('printing')?></div>
</div>

<div class="modal" id="modal_mesg"></div>

<div class="modal" id="save_mesg">
    <div class="modal__body" id="save_mesg_text"><?=$languageService->translate('saving')?></div>
</div>


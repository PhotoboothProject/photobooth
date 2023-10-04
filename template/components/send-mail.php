<?php

use Photobooth\Service\LanguageService;

$languageService = LanguageService::getInstance();

?>
<div class="send-mail">
    <i class="<?php echo $config['icons']['mail_close']; ?>" id="send-mail-close"></i>
    <p><?=($config['mail']['send_all_later']) ? $languageService->translate('insertMailToDB') : $languageService->translate('insertMail')?></p>
    <form id="send-mail-form" style="margin: 0;">
        <input class="mail-form-input" size="35" type="email" name="sendTo">
        <input id="mail-form-image" type="hidden" name="image" value="">
        <?php if ($config['mail']['send_all_later']): ?>
        <input type="checkbox" id="mail-form-send-link" name="send-link" checked="checked" value="yes" style="opacity: 0">
        <label for="mail-form-send-link" style="opacity: 0"><?=$languageService->translate('sendAllMail')?></label>
        <button class="mail-form-input rotaryfocus" name="submit" type="submit" value="Send"><?=$languageService->translate('add')?></button>
        <?php else: ?>
        <button class="mail-form-input rotaryfocus" name="submit" type="submit" value="Send"><?=$languageService->translate('send')?></button>
        <?php endif; ?>
    </form>

    <div id="mail-form-message" style="max-width: 75%"></div>
</div>


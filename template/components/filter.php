<?php

use Photobooth\Enum\ImageFilterEnum;
use Photobooth\Service\LanguageService;

$languageService = LanguageService::getInstance();

?>
<div id="filternav" class="sidenav dragscroll rotarygroup">
    <button
        type="button"
        class="sidenav-close rotaryfocus"
        data-command="sidenav-close"
        title="<?=$languageService->translate('close')?>"
        >
        <i class="<?php echo $config['icons']['close']; ?>"></i>
    </button>
    <div class="sidenav-list">
        <?php foreach (ImageFilterEnum::cases() as $filter): ?>
            <?php if (!in_array($filter->value, $config['filters']['disabled'])): ?>
                <button
                    type="button"
                    class="sidenav-list-item<?php echo ImageFilterEnum::tryFrom($config['filters']['defaults']) === $filter ? ' sidenav-list-item--active' : ''; ?> rotaryfocus"
                    data-filter="<?= $filter->value ?>"
                >
                    <?= $filter->label() ?>
                </button>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

<?php

use Photobooth\Enum\ImageFilterEnum;

?>
<div id="mySidenav" class="dragscroll sidenav rotarygroup">
    <a href="#" class="<?php echo $btnClass; ?> closebtn rotaryfocus"><i class="<?php echo $config['icons']['close']; ?>"></i></a>
    <?php foreach (ImageFilterEnum::cases() as $filter): ?>
        <?php if (!in_array($filter->value, $config['filters']['disabled'])): ?>
            <div id="<?= $filter->value ?>" class="filter <?php if ($config['filters']['defaults'] === $filter->value) {
                echo 'activeSidenavBtn';
            } ?>">
                <a class="btn btn--small rotaryfocus" href="#"><?= $filter->label() ?></a>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

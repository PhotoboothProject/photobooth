<div id="mySidenav" class="dragscroll sidenav rotarygroup">
    <a href="#" class="<?php echo $btnClass; ?> closebtn rotaryfocus"><i class="<?php echo $config['icons']['close']; ?>"></i></a>

    <?php foreach (AVAILABLE_FILTERS as $filter => $name): ?>
        <?php if (!in_array($filter, $config['filters']['disabled'])): ?>
            <div id="<?= $filter ?>" class="filter <?php if ($config['filters']['defaults'] === $filter) {
                echo 'activeSidenavBtn';
            } ?>">
                <a class="btn btn--small rotaryfocus" href="#"><?= $name ?></a>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
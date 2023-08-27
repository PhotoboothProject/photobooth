<div id="mySidenav" class="dragscroll sidenav rotarygroup w-full max-w-xs h-full fixed right-0 top-0 z-50 bg-black/80 hidden [&.sidenav--open]:flex flex-col">
    <a href="#" class="w-12 h-12 top-0 right-0 absolute cursor-pointer flex items-center justify-center text-xl closebtn rotaryfocus">
        <i class="text-white <?php echo $config['icons']['close']; ?>"></i>
    </a>

    <div class="w-full pt-12 flex flex-col">
        <?php foreach (AVAILABLE_FILTERS as $filter => $name): ?>
            <?php
            $filterClasses = 'w-full p-4 filter text-white cursor-pointer [&.activeSidenavBtn]:bg-white [&.activeSidenavBtn]:text-black';
            if ($config['filters']['defaults'] === $filter) {
                $filterClasses .= ' activeSidenavBtn';
            }
            if (!in_array($filter, $config['filters']['disabled'])): ?>
                <div id="<?= $filter ?>" class="<?= $filterClasses ?>">
                    <a class="btn btn--small rotaryfocus" href="#"><?= $name ?></a>
                </div>
            <?php endif;
            ?>
        <?php endforeach; ?>
    </div>
</div>
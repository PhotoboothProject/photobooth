
<div class="w-full max-w-xl rounded-lg py-8 bg-white flex flex-col shadow-xl relative">

    <div class="px-4">
        <h1 class="text-2xl font-bold text-center mb-6 border-solid border-b border-gray-200 pb-4 text-brand-1">
            <?php
                if((isset($_SESSION['auth']) && $_SESSION['auth'] === true)) {
                    echo 'Admin -';
                }
            ?>
            <span data-i18n="menu"></span>
        </h1>
    </div>

    <?php if(!$config['protect']['index'] || (!$config['protect']['localhost_index'] && (isset($_SERVER['SERVER_ADDR']) && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR'])) || !$config['login']['enabled'] || (isset($_SESSION['auth']) && $_SESSION['auth'] === true)): ?>
        <div class="w-12 h-12 bg-white absolute right-4 top-4 rounded-b-l-lg shadow-xls flex items-center justify-center text-brand-1 cursor-pointer">
            <a href="<?=$fileRoot?>" >
                <i class="!text-2xl <?php echo $config['icons']['close']; ?>"></i>
                <!-- <span data-i18n="close"></span> -->
            </a>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 px-4 ">
        <?php
            if(!$config['protect']['admin'] || (!$config['protect']['localhost_admin'] && (isset($_SERVER['SERVER_ADDR']) && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR'])) || !$config['login']['enabled'] || (isset($_SESSION['auth']) && $_SESSION['auth'] === true)) {
                echo getMenuBtn($fileRoot . 'admin', 'admin_panel', $config['icons']['admin']);
                echo getMenuBtn($fileRoot . 'test', 'testMenu', $config['icons']['admin']);
            }

            echo getMenuBtn($fileRoot . 'gallery', 'gallery', $config['icons']['gallery']);
            echo getMenuBtn($fileRoot . 'slideshow', 'slideshow', $config['icons']['slideshow']);

            if(!$config['protect']['index'] || (!$config['protect']['localhost_index'] && (isset($_SERVER['SERVER_ADDR']) && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR'])) || !$config['login']['enabled'] || (isset($_SESSION['auth']) && $_SESSION['auth'] === true)) {
                echo getMenuBtn($fileRoot . 'chroma', 'chromaCapture', $config['icons']['chromaCapture']);
            }

            if(!$config['protect']['manual'] || (!$config['protect']['localhost_manual'] && (isset($_SERVER['SERVER_ADDR']) && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR'])) || !$config['login']['enabled'] || (isset($_SESSION['auth']) && $_SESSION['auth'] === true)) {
                echo getMenuBtn($fileRoot . 'faq', 'show_faq', $config['icons']['faq']);
                echo getMenuBtn($fileRoot . 'manual', 'show_manual', $config['icons']['manual']);
                echo getMenuBtn('https://t.me/PhotoboothGroup', 'telegram', $config['icons']['telegram']);
            }

            // echo getMenuBtn("/", "reload", $config['icons']['refresh']);

            // reboot
            if((isset($_SESSION['auth']) && $_SESSION['auth'] === true)) {
                echo getMenuBtn('reboot-btn', 'reboot_button');
            }

            // shutdown
            if((isset($_SESSION['auth']) && $_SESSION['auth'] === true) || isset($_SESSION['rental'])) {
                echo getMenuBtn('shutdown-btn', 'shutdown_button');
            }

            // logout
            if((isset($_SESSION['auth']) && $_SESSION['auth'] === true) || isset($_SESSION['rental'])) {
                echo getMenuBtn($fileRoot . 'login/logout.php', 'logout', $config['icons']['logout']);
            }

            ?>
    </div>
</div>

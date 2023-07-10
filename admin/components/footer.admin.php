    <div class="pageLoader w-full h-full fixed top-0 left-0 z-50 hidden place-items-center [&.isActive]:grid">
        <div class="w-full h-full left-0 top-0 z-10 absolute bg-black bg-opacity-60"></div>
        <div class="min-w-[115px] px-4 py-6 rounded-md bg-white shadow-md flex flex-col items-center justify-center relative z-20 text-center">
            <?php echo getLoader("sm"); ?>
            <label class="text-xs text-brand-1 mt-4 font-bold"></label>
        </div>
    </div>

    <?php echo getToast(); ?>

    <script src="<?=$fileRoot?>node_modules/whatwg-fetch/dist/fetch.umd.js"></script>
    <script type="text/javascript" src="<?=$fileRoot?>api/config.php?v=<?php echo $config['photobooth']['version']; ?>"></script>
    <script src="<?=$fileRoot?>node_modules/@andreasremdt/simple-translator/dist/umd/translator.min.js"></script>
    <script type="text/javascript" src="<?=$fileRoot?>resources/js/i18n.js?v=<?=$config['photobooth']['version']?>"></script>

    <script type="text/javascript" src="<?=$fileRoot?>resources/js/main.admin.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
 </body>
</html>

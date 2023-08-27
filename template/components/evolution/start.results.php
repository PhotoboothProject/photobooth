<div class="w-full h-full bg-black bg-opacity-80 backdrop-blur-lg absolute left-0 top-0 flex flex-col bg-contain bg-center bg-no-repeat" id="result">
    <div class="w-full h-full flex items-end justify-center p-5">
        <?php if ($config['video']['enabled']): ?>
            <?php if ($config['video']['qr']): ?>
                <img src="" id="resultVideoQR" alt="video qr code">
            <?php endif; ?>
            <?php if ($config['video']['gif']) { ?>
                <img id="resultVideo" src="" alt="result gif">
            <?php } else { ?>
                <video id="resultVideo" autoplay loop muted>
                </video>
            <?php } ?>
        <?php endif; ?>
    <?php include __DIR__ . '/../resultsBtn.php'; ?>
    </div>

</div>

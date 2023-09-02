<!-- Start Page -->
<div class="stages <?php echo $uiShape; ?> rotarygroup noborder" id="start">
    <?php include 'components/start.logo.php'; ?>
    <div class="startInner <?php echo $uiShape; ?> noborder">
        <?php if ($config['event']['enabled']): ?>
        <div class="names">
            <?php if ($config['ui']['decore_lines']): ?>
            <hr class="small" />
            <hr>
            <?php endif; ?>
            <div>
                <h1><?= $config['event']['textLeft'] ?>
                    <i class="fa <?= $config['event']['symbol'] ?>" aria-hidden="true"></i>
                    <?= $config['event']['textRight'] ?>
                    <?php if ($config['start_screen']['title_visible']): ?>
                    <br>
                    <?= $config['start_screen']['title'] ?>
                    <?php endif; ?>
                </h1>
                <?php if ($config['start_screen']['subtitle_visible']): ?>
                <h2><?= $config['start_screen']['subtitle'] ?></h2>
                <?php endif; ?>
            </div>
            <?php if ($config['ui']['decore_lines']): ?>
            <hr>
            <hr class="small" />
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="names">
            <?php if ($config['ui']['decore_lines']): ?>
            <hr class="small" />
            <hr>
            <?php endif; ?>
            <div>
                <?php if ($config['start_screen']['title_visible']): ?>
                <h1><?= $config['start_screen']['title'] ?></h1>
                <?php endif; ?>
                <?php if ($config['start_screen']['subtitle_visible']): ?>
                <h2><?= $config['start_screen']['subtitle'] ?></h2>
                <?php endif; ?>
            </div>
            <?php if ($config['ui']['decore_lines']): ?>
            <hr>
            <hr class="small" />
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php include 'components/actionBtn.php'; ?>
    </div>

    <?php if ($config['ui']['show_fork']): ?>
    <a href="https://github.com/<?= $config['ui']['github'] ?>/photobooth" class="github-fork-ribbon" data-ribbon="Fork me on GitHub">Fork me on GitHub</a>
    <?php endif; ?>
</div>


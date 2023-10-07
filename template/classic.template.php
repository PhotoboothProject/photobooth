<?php

use Photobooth\Utility\PathUtility;

?>
<!-- Start Page -->
<div class="stage rotarygroup" data-stage="start">
    <?php include PathUtility::getAbsolutePath('template/components/start.logo.php'); ?>
    <div class="stage-inner">
        <div class="names<?= ($config['ui']['decore_lines']) ? ' names--decoration' : '' ?>">
            <div class="names-inner">
                <?php if ($config['event']['enabled']): ?>
                    <h1>
                        <?= $config['event']['textLeft'] ?>
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
                <?php else: ?>
                    <?php if ($config['start_screen']['title_visible']): ?>
                    <h1><?= $config['start_screen']['title'] ?></h1>
                    <?php endif; ?>
                    <?php if ($config['start_screen']['subtitle_visible']): ?>
                    <h2><?= $config['start_screen']['subtitle'] ?></h2>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php include PathUtility::getAbsolutePath('template/components/actionBtn.php'); ?>
    </div>
    <?php if ($config['ui']['show_fork']): ?>
    <a href="https://github.com/<?= $config['ui']['github'] ?>/photobooth" class="github-fork-ribbon" data-ribbon="Fork me on GitHub">Fork me on GitHub</a>
    <?php endif; ?>
</div>


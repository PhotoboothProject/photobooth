<?php
$bgImage = $config['background']['defaults'];
if (str_contains($bgImage, 'url(')) {
    $bgImage = substr($bgImage, 4, -1);
}
?>

<div class="w-full h-screen flex flex-col items-center relative bg-red-200">
    <!-- bgImage -->
    <div class="w-full h-full absolute left-0 top-0">
        <img src="<?=$bgImage?>" alt="background" class="w-full h-full object-cover">
    </div>

    <!-- logo -->
    <?php include 'components/start.logo.php'; ?>

    <!-- controls -->
    <div class="w-full flex items-center justify-center mb-8 mt-auto rotarygroup">
        <?php include 'components/actionBtn.php'; ?>
    </div>
</div>

<!--  BROKEN AND NEEDS FIX
<?php if ($config['event']['enabled']): ?>
<div class="names">
    <?php if ($config['ui']['decore_lines']): ?>
    <hr class="small" />
    <hr>
    <?php endif; ?>
    <div>
        <h1>
            <?=$config['event']['textLeft']?>
            <i class="fa <?=$config['event']['symbol']?>" aria-hidden="true"></i>
            <?=$config['event']['textRight']?>
            <?php if ($config['start_screen']['title_visible']): ?>
            <br>
            <?=$config['start_screen']['title']?>
            <?php endif; ?>
        </h1>
        <?php if ($config['start_screen']['subtitle_visible']): ?>
        <h2><?=$config['start_screen']['subtitle']?></h2>
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
        <h1><?=$config['start_screen']['title']?></h1>
        <?php endif; ?>
        <?php if ($config['start_screen']['subtitle_visible']): ?>
        <h2><?=$config['start_screen']['subtitle']?></h2>
        <?php endif; ?>
    </div>
    <?php if ($config['ui']['decore_lines']): ?>
    <hr>
    <hr class="small" />
    <?php endif; ?>
</div>
<?php endif; ?>

BROKEN AND NEEDS FIX END -->

<?php if ($config['ui']['show_fork']): ?>
<a href="https://github.com/<?=$config['ui']['github']?>/photobooth" class="github-fork-ribbon" data-ribbon="Fork me on GitHub">Fork me on GitHub</a>
<?php endif; ?>


<div id="gallery" class="gallery rotarygroup">
	<div class="gallery__inner">
		<div class="gallery__header">
			<h1><span data-i18n="gallery"></span></h1>
			<a href="#" class="<?php echo $btnClass; ?> gallery__close close_gal rotaryfocus"><i class="<?php echo $config['icons']['close']; ?>"></i></a>
		</div>

        <?php
        include('components/gal.images.php');

        if($GALLERY_FOOTER === true && $config['gallery']['action_footer'] === true) {
            include('components/gal.btnFooter.php');
        }
        ?>
	</div>
</div>

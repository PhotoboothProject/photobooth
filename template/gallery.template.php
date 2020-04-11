<div id="gallery" class="gallery">
	<div class="gallery__inner">
		<div class="gallery__header">
			<h1><span data-i18n="gallery"></span></h1>
			<a href="#" class="gallery__close close_gal"><i class="fa fa-times"></i></a>
		</div>
		<div class="gallery__body" id="galimages">
			<?php if (empty($imagelist)): ?>
			<h1 style="text-align:center" data-i18n="gallery_no_image"></h1>
			<?php else: ?>
			<?php foreach ($imagelist as $image): ?>
			<?php
            $date = '';
            if ($config['file_format_date'] && $config['show_date']) {
                $dateObject = DateTime::createFromFormat('Ymd_His', substr($image, 0, strlen($image) - 4));

                if ($dateObject) {
                    $date = '<i class="fa fa-clock-o"></i> ' . $dateObject->format($config['gallery']['date_format']);
                }
            }

            $filename_photo = $config['folders']['images'] . DIRECTORY_SEPARATOR . $image;
            $filename_thumb = $config['folders']['thumbs'] . DIRECTORY_SEPARATOR . $image;

            $imageinfo = getimagesize($filename_photo);
            $imageinfoThumb = getimagesize($filename_thumb);
            ?>

			<a href="<?=$filename_photo?>" data-size="<?=$imageinfo[0]?>x<?=$imageinfo[1]?>"
				data-med="<?=$filename_thumb?>" data-med-size="<?=$imageinfoThumb[0]?>x<?=$imageinfoThumb[1]?>">
				<figure>
					<img src="<?=$filename_thumb?>" alt="<?=$image?>" />
					<figcaption><?=$date?></figcaption>
				</figure>
			</a>
				<?php endforeach; ?>
				<?php endif; ?>
		</div>
	</div>
</div>
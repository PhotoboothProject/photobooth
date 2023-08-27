<?php
$closeBtnTarget = '#';
$closeBtnClasses =
    '!w-12 !h-12 relative flex items-center text-lg text-white justify-center !ml-auto p-4 cursor-pointer border border-white rounded-md transition-all hover:bg-white hover:text-black';
$bgImage = $config['background']['defaults'];
if (str_contains($bgImage, 'url(')) {
    $bgImage = substr($bgImage, 4, -1);
}

if (isset($gallery_standalone)) {
    echo '<div class="w-full h-full absolute left-0 top-0"><img src="' . $bgImage . '" alt="background" class="w-full h-full object-cover"></div>';
    $closeBtnTarget = $fileRoot;
} else {
    $closeBtnClasses .= ' gallery__close';
}
?>

<div id="gallery" class="fixed top-0 left-0 z-50 w-full h-screen bg-black bg-opacity-80 hidden flex-col items-center text-white rotarygroup backdrop-blur-md [&.gallery--open]:flex">
        <?php
        $galleryCount = 0;
        if (empty($imagelist)) {
            $gallery = '<h1 style="text-align:center" data-i18n="gallery_no_image"></h1>';
        } else {
            $gallery = '';
            foreach ($imagelist as $image) {
                try {
                    $date = $config['ui']['branding'] . ' Gallery';
                    if ($config['picture']['naming'] === 'dateformatted' && $config['gallery']['show_date']) {
                        if ($config['database']['file'] != 'db') {
                            $db = strlen($config['database']['file']);
                            $name = substr($image, ++$db);
                        } else {
                            $name = $image;
                        }
                        $dateObject = DateTime::createFromFormat('Ymd_His', substr($name, 0, strlen($name) - 4));
                        if ($dateObject) {
                            $date = '<i class="' . $config['icons']['date'] . '"></i> ' . $dateObject->format($config['gallery']['date_format']);
                        }
                    }

                    $filename_photo = $fileRoot . $config['folders']['data'] . DIRECTORY_SEPARATOR . $config['folders']['images'] . DIRECTORY_SEPARATOR . $image;
                    $filename_thumb = $fileRoot . $config['folders']['data'] . DIRECTORY_SEPARATOR . $config['folders']['thumbs'] . DIRECTORY_SEPARATOR . $image;

                    $imageinfo = @getimagesize($filename_photo);
                    $imageinfoThumb = @getimagesize($filename_thumb);

                    if (is_array($imageinfo)) {
                        $galleryCount++;
                        if (!is_array($imageinfoThumb)) {
                            $imageinfoThumb = $imageinfo;
                        }

                        // image date
                        $size = $imageinfo[0] . 'x' . $imageinfo[1] . '" data-pswp-width="' . $imageinfo[0] . '" data-pswp-height="' . $imageinfo[1];
                        $med = $gallery_path . $filename_thumb . '" data-med-size="' . $imageinfoThumb[0] . 'x' . $imageinfoThumb[1];
                        $figcaption = '';
                        if ($config['gallery']['figcaption'] && $dateObject) {
                            $figcaption =
                                '<figcaption class="text-white text-opacity-60 text-xs font-light pt-2"><i class="' .
                                $config['icons']['date'] .
                                '"></i> ' .
                                $dateObject->format($config['gallery']['date_format']) .
                                '</figcaption>';
                        }

                        $gallery .=
                            '
                                <a href="' .
                            $filename_photo .
                            '" class="rotaryfocus" data-size="' .
                            $size .
                            '"data-med="' .
                            $med .
                            '">
                                    <figure class="w-full h-0 pb-[67%] relative">
                                        <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 z-10">' .
                            getLoader('') .
                            '</div>
                                        <img class="w-full h-full absolute left-0 top-0 lazy object-cover z-20" data-src="' .
                            $filename_thumb .
                            '" alt="' .
                            $image .
                            '" >
                                    </figure>
                                    ' .
                            $figcaption .
                            '
                                </a>';
                    }
                } catch (Exception $e) {
                    // Empty catch block
                    // ignore errors for now
                }
            }
        }
        ?>

        <div class="w-full flex flex-col items-center p-4 pb-4">
	        <div class="w-full max-w-7xl flex items-center">
                <h1 class="text-2xl">
                    <span data-i18n="gallery"></span>
                    <span class="ml-2">(<?php echo $galleryCount; ?>)</span>
                </h1>
                <a href="<?= $closeBtnTarget ?>" class="<?= $closeBtnClasses ?>"><i class="<?php echo $config['icons']['close']; ?>"></i></a>
            </div>
		</div>

        <div id="galleryScroll" class="w-full flex flex-col items-center overflow-y-auto pb-28 mb-auto">
            <div class="w-full max-w-7xl flex flex-col">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3" id="galimages">
                    <?php echo $gallery; ?>
                </div>
            </div>
        </div>

        <?php if ($GALLERY_FOOTER === true && $config['gallery']['action_footer'] === true): ?>
            <div class="w-full border-t border-solid border-white border-opacity-30 gap-4 flex items-center justify-center py-2 bg-black/60 fixed bottom-0 left-0 backdrop-blur z-50">
                <?php if ($config['button']['force_buzzer']) {
                    echo '<div id="useBuzzer">
                            <span data-i18n="use_button"></span>
                        </div>';
                } else {
                    if ($config['picture']['enabled']) {
                        echo getBoothButton('takePhoto', $config['icons']['take_picture'], 'takePic', 'xs');
                    }
                    if ($config['custom']['enabled']) {
                        echo getBoothButton('takeCustom', $config['icons']['btn_text'], 'takeCustom', 'xs');
                    }
                    if ($config['collage']['enabled']) {
                        echo getBoothButton('takeCollage', $config['icons']['take_collage'], 'takeCollage', 'xs');
                    }
                    if ($config['video']['enabled']) {
                        echo getBoothButton('takeVideo', $config['icons']['take_video'], 'takeVideo', 'xs');
                    }
                } ?>
            </div>
        <?php endif; ?>
	</div>
</div>

<script>
    $(function($) {
        $("img.lazy").Lazy({ 
            scrollDirection: 'vertical',
            appendScroll: $('#galleryScroll'),
            visibleOnly: true,
            threshold: 0
        });
    });
</script>

<?php
function getImageSelect($setting, $i18ntag) {
    $dir = '../';

    $files = getDirContents($dir);
    $images = '';
    foreach ($files as &$value) {
        if (str_contains($value, 'node_modules') || str_contains($value, 'vendor') || str_contains($value, 'data')) {
            continue;
        }

        if (
            str_contains($value, '.jpg') ||
            str_contains($value, '.png') ||
            str_contains($value, '.jpeg') ||
            str_contains($value, '.gif') ||
            str_contains($value, '.bmp') ||
            str_contains($value, '.webp') ||
            str_contains($value, '.svg')
        ) {
            $imgPath = $value;
            $origin = $value;
            if (str_contains($value, $_SERVER['DOCUMENT_ROOT'])) {
                $origin = substr($value, strlen($_SERVER['DOCUMENT_ROOT']));
                $imgPath = substr($value, strlen($_SERVER['DOCUMENT_ROOT']));
            }
            if (str_contains($setting['value'], 'url(')) {
                $origin = 'url(' . $origin . ')';
            }
            $images .= '<div class="w-full relative h-0 pb-2/3">';
            $images .=
                '<img onclick="adminImageSelect(this, \'' .
                $setting['name'] .
                '\');" data-origin="' .
                $origin .
                '" class="w-full h-full left-0 top-0 absolute object-cover" src="' .
                $imgPath .
                '" title="' .
                $value .
                '"><br>';
            $images .= '</div>';
        }
    }

    $selectedImage = $setting['value'];
    if (str_contains($setting['value'], 'url(')) {
        $selectedImage = substr($setting['value'], 4, -1);
    }
    if (str_contains($setting['value'], $_SERVER['DOCUMENT_ROOT'])) {
        $selectedImage = substr($setting['value'], strlen($_SERVER['DOCUMENT_ROOT']));
    }
    $selectedImage = preg_replace('#/+#', '/', $selectedImage);

    return '
            <div class="adminImageSelection group">

                <div class="w-full flex items-start">
                    <div class="w-24 flex mb-3 mr-3 shrink-0 cursor-pointer" onclick="openAdminImageSelect(this)"><img class="adminImageSelection-preview object-contain" src="' .
        $selectedImage .
        '"></div>
                    <div class="w-full flex flex-col">
                        ' .
        getHeadline($i18ntag) .
        '
                        <div class="text-xs mb-3 -mt-2 break-all">' .
        $setting['value'] .
        '</div>
                        <div class="w-full h-10 bg-brand-1 text-white flex items-center justify-center rounded-full" onclick="openAdminImageSelect(this)"><span data-i18n="choose_image">choose_image</span></div>
                    </div>
                </div>

                <div class="hidden group-[&.isOpen]:grid w-full h-full fixed left-0 top-0 z-50 place-items-center">
                    <div class="w-full h-full left-0 top-0 z-10 absolute bg-black bg-opacity-60 cursor-pointer" onclick="closeAdminImageSelect()"></div>
                    <div class="w-full max-h-3/4 max-w-2xl bg-white p-4 pt-2 rounded relative z-20 flex flex-col overflow-hidden">
                        <div class="w-full flex items-center">
                            <h2 class="flex text-brand-1 font-bold"><span data-i18n="choose_image">choose_image</span></h2>
                            <div class="ml-auto flex items-center justify-center p-3 text-xl fa fa-close" onclick="closeAdminImageSelect()"></div>
                        </div>
                        <div class="flex w-full h-full flex-col overflow-y-auto">
                            <div class="grid grid-cols-3 gap-4">
                                ' .
        $images .
        '
                            </div>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="' .
        $setting['name'] .
        '" value="' .
        $setting['value'] .
        '"/>
            </div>
        ';
}

function getDirContents($dir, &$results = []) {
    $files = scandir($dir);

    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        if (!is_dir($path)) {
            $results[] = $path;
        } elseif ($value != '.' && $value != '..') {
            getDirContents($path, $results);
            $results[] = $path;
        }
    }

    return $results;
}
?>

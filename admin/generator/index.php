<?php
session_start();

$fileRoot = '../../';

require_once $fileRoot . 'lib/config.php';

// Login / Authentication check
if (
    !$config['login']['enabled'] ||
    (!$config['protect']['localhost_admin'] && isset($_SERVER['SERVER_ADDR']) && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) ||
    (isset($_SESSION['auth']) && $_SESSION['auth'] === true) ||
    !$config['protect']['admin']
) {
    // nothing for now
} else {
    header('location: ' . $fileRoot . 'login');
    exit();
}

$pageTitle = 'Collage layout generator';
include '../components/head.admin.php';
include '../helper/index.php';

$error = false;
$success = false;
$labelClass = 'w-full flex flex-col mb-1';
$inputClass = 'w-full h-10 border border-solid border-gray-300 focus:border-brand-1 rounded-md px-3 mt-auto';
$btnClass =
    'w-full h-12 rounded-full bg-brand-1 text-white flex items-center justify-center relative ml-auto border-2 border-solid border-brand-1 hover:bg-white hover:text-brand-1 transition font-bold px-4';
?>


<div class="w-full h-screen bg-brand-2 px-3 md:px-6 py-6 md:py-12 overflow-x-hidden overflow-y-auto">
    <div class="w-full flex items-center justify-center flex-col">
        <div class="w-full h-144 rounded-lg p-4 md:p-8 bg-white flex flex-col shadow-xl place-items-center">
            <div class="w-full text-center flex flex-col items-center justify-center text-2xl font-bold text-brand-1 mb-2">
                <?= $config['ui']['branding'] ?> - Collage Layout Generator
            </div>

            <div class="my-4 md:my-8">
                <span>Choose the background</span>
                <div class="w-[90vw]">
                    <div class="flex flex-row gap-2 overflow-x-auto h-[200px]">
                        <?= getImages('background') ?>
                    </div>
                </div>
            </div>
            <div class="my-4 md:my-8">
                <span>Choose the frame</span>
                <div class="w-[90vw]">
                    <div class="flex flex-row gap-2 overflow-x-auto h-[200px]">
                        <?= getImages('frame') ?>
                    </div>
                </div>
            </div>
            <div class="result_section mt-4 w-full min-h-[50vh] grid grid-cols-[repeat(auto-fit,_minmax(300px,_1fr))]">
                <div class="result_positions h-full p-2 md:p-4">
                    <div class="general_settings">
                        <div>
                            <span class="w-full flex flex-col items-center justify-center text-2md font-bold text-brand-1 mb-2">General Settings</span>
                        </div>
                        <div class="grid gap-2">
                            <div class="grid gap-2 grid-cols-[repeat(auto-fit,_minmax(200px,_1fr))]">
                                <div>
                                    <span>Portrait</span>
                                    <input type="checkbox" name="portrait" />
                                </div>
                                <div>
                                    <span>Rotate after creation</span>
                                    <input type="checkbox" name="rotate_after_creation" />
                                </div>
                            </div>
                            <div class="grid gap-2 grid-cols-[repeat(auto-fit,_minmax(200px,_1fr))]">
                                <div>
                                    <span>Final width</span>
                                    <input id="final_width" onchange="changeGeneralSetting()" class="w-full h-10 border-2 border-solid border-gray-300 focus:border-brand-1 rounded-md px-3 mt-auto" type="number" name="width" value="1500" />
                                </div>
                                <div>
                                    <span>Final height</span>
                                    <input id="final_height" onchange="changeGeneralSetting()" class="w-full h-10 border-2 border-solid border-gray-300 focus:border-brand-1 rounded-md px-3 mt-auto" type="number" name="height" value="1000" />
                                </div>
                            </div>
                            <div>
                                <span>apply_frame</span>
                                <select class="w-full h-10 border-2 border-solid border-gray-300 focus:border-brand-1 rounded-md px-2 mt-auto" name="apply_frame">
                                    <option value="off">Off</option>
                                    <option selected="selected" value="always">Always</option>
                                    <option value="once" selected="selected">Once</option>
                                </select>
                            </div>
                            <div class="grid gap-2 grid-cols-[repeat(auto-fit,_minmax(200px,_1fr))]">
                                <div>
                                    <span>Use background</span>
                                    <input type="checkbox" name="has-background" />
                                </div>
                                <div>
                                    <span>Use frame</span>
                                    <input type="checkbox" name="has-frame" checked />
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="images_settings">
                        <div>
                            <span class="w-full flex flex-col items-center justify-center text-2md font-bold text-brand-1 mb-2">Images Settings</span>
                            <button onclick="addImage()"><i class="fa fa-plus"></i></button>
                        </div>
                        <div id="layout_containers">
                            <?= generateImageLayoutInputs() ?>
                        </div>
                    </div>
                </div>
                <div class="result_images relative">
                    <div id="result_canvas" class="bg-slate-300 relative w-full h-full">
                        <div id="collage_background" class="absolute h-full">
                            <img class="h-full min-w-full" src="" alt="Choose the background">
                        </div>
                        <img id="picture-0" class="absolute object-cover object-left-top rotate-0" src="/resources/img/demo/matty-adame-nLUb9GThIcg-unsplash.jpg">
                        <img id="picture-1" class="absolute object-cover object-left-top rotate-0 hidden" src="/resources/img/demo/matty-adame-nLUb9GThIcg-unsplash.jpg">
                        <img id="picture-2" class="absolute object-cover object-left-top rotate-0 hidden" src="/resources/img/demo/matty-adame-nLUb9GThIcg-unsplash.jpg">
                        <img id="picture-3" class="absolute object-cover object-left-top rotate-0 hidden" src="/resources/img/demo/matty-adame-nLUb9GThIcg-unsplash.jpg">
                        <img id="picture-4" class="absolute object-cover object-left-top rotate-0 hidden" src="/resources/img/demo/matty-adame-nLUb9GThIcg-unsplash.jpg">
                        <div id="collage_frame" class="absolute h-full">
                            <img class="h-full min-w-full" src="" alt="Choose the frame">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full max-w-xl my-12 border-b border-solid border-white border-opacity-20">
        </div>

        <div class="w-full max-w-xl rounded-lg py-8 bg-white flex flex-col shadow-xl relative">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 px-4 ">
                <?php
                echo getMenuBtn($fileRoot . 'admin', 'admin_panel', $config['icons']['admin']);

if (isset($_SESSION['auth']) && $_SESSION['auth'] === true) {
    echo getMenuBtn($fileRoot . 'login/logout.php', 'logout', $config['icons']['logout']);
}
?>
            </div>
        </div>
    </div>
</div>

<?php
include '../components/footer.admin.php';

if ($success) {
    echo '<script>openToast("<span data-i18n=\"upload_success\"></span>");</script>';
}
if ($error !== false) {
    echo '<script>openToast("<span data-i18n=\"upload_error\"></span>", "isError", 5000);</script>';
}

function getImages($element)
{
    $dir = '../../../';

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
            $value = Helper::fixSeperator($value);
            $imgPath = $value;
            $origin = $value;
            $serverRoot = Helper::fixSeperator($_SERVER['DOCUMENT_ROOT']);
            if (str_contains($value, $serverRoot)) {
                $origin = substr($value, strlen($serverRoot));
                $imgPath = substr($value, strlen($serverRoot));
            }

            $images .= '<div class="flex flex-column justify-center w-[200px] shadow-xl shrink-0">';
            $images .=
                '<img loading="lazy" onclick="selectImage(this, \'' .
                $element .
                '\')" data-origin="' .
                $origin .
                '" class="object-cover" src="' .
                $imgPath .
                '" title="' .
                $value .
                '">';
            $images .= '</div>';
        }
    }

    return $images;
}

function getDirContents($dir, &$results = [])
{
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

function generateImageLayoutInputs()
{
    $max_images = 5;
    $dom = '';

    for ($i = 0; $i < $max_images; $i++) {
        $hidden_class = 'hidden';
        if ($i == 0) {
            $hidden_class = '';
        }

        $dom .=
            '<div data-picture="picture-' . $i . '" class="w-full p-3 md:p-5 grid grid-cols-[repeat(auto-fit,_minmax(100px,_1fr))] gap-2 ' .
            $hidden_class .
            '">
            <div class="flex flex-col">
                <span>x position</span>
                <input onchange="changeImageSetting(this, \'left-' .
            $i .
            '\')" class="w-full h-10 border-2 border-solid border-gray-300 focus:border-brand-1 rounded-md px-3 mt-auto" type="text" name="picture-x-position-' .
            $i .
            '" value="0" />
            </div>
            <div class="flex flex-col">
                <span>y position</span>
                <input onchange="changeImageSetting(this, \'top-' .
            $i .
            '\')" class="w-full h-10 border-2 border-solid border-gray-300 focus:border-brand-1 rounded-md px-3 mt-auto" type="text" name="picture-y-position-' .
            $i .
            '" value="0" />
            </div>
            <div class="flex flex-col">
                <span>width</span>
                <input onchange="changeImageSetting(this, \'width-' .
            $i .
            '\')" class="w-full h-10 border-2 border-solid border-gray-300 focus:border-brand-1 rounded-md px-3 mt-auto" type="text" name="picture-width-' .
            $i .
            '" />
            </div>
            <div class="flex flex-col">
                <span>height</span>
                <input onchange="changeImageSetting(this, \'height-' .
            $i .
            '\')" class="w-full h-10 border-2 border-solid border-gray-300 focus:border-brand-1 rounded-md px-3 mt-auto" type="text" name="picture-height-' .
            $i .
            '" />
            </div>
            <div class="flex flex-col">
                <span>rotation</span>
                <input onchange="changeImageSetting(this, \'transform-' .
            $i .
            '\')" class="w-full h-10 border-2 border-solid border-gray-300 focus:border-brand-1 rounded-md px-3 mt-auto" type="number" name="picture-rotation-' .
            $i .
            '" value="0" />
            </div>
        </div>';
    }

    return $dom;
}
?>

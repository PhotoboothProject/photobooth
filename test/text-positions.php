<?php
require_once __DIR__ . '/../lib/boot.php';

use Photobooth\Service\ApplicationService;
use Photobooth\Service\LanguageService;
use Photobooth\Utility\PathUtility;

$languageService = LanguageService::getInstance();
use Photobooth\Collage;
use Photobooth\Helper;

// ---- Config holen (versionssicher) ----
$config = null;

if (isset($config) && is_array($config)) {
    // boot.php hat $config gesetzt
} elseif (isset($GLOBALS['config']) && is_array($GLOBALS['config'])) {
    $config = $GLOBALS['config'];
} elseif (class_exists('\Photobooth\Service\ConfigurationService')) {
    $config = \Photobooth\Service\ConfigurationService::getInstance()->getConfiguration();
} else {
    die('Keine Config-Quelle gefunden.');
}

// ---- Orientation aus Config ----
$orientation = $config['collage']['orientation']
    ?? $config['collage']['format']
    ?? 'landscape';

$orientation = strtolower((string)$orientation);
if (!in_array($orientation, ['landscape', 'portrait'], true)) {
    $orientation = 'landscape';
}

// ---- Text aktivieren (aus Config) ----
$config['textoncollage']['enabled'] = true;

// Sicherstellen dass kein Placeholder aktiv ist (wuerde das Limit aendern)
$config['collage']['placeholder'] = false;

// Font setzen falls nicht vorhanden
$font = __DIR__ . '/../resources/fonts/GreatVibes-Regular.ttf';
if (file_exists($font) && empty($config['textoncollage']['font'])) {
    $config['textoncollage']['font'] = $font;
}

function createTestImage(array $rgb, int $w, int $h, string $name): string
{
    $img = imagecreatetruecolor($w, $h);
    $c = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
    imagefill($img, 0, 0, $c);
    $black = imagecolorallocate($img, 0, 0, 0);
    imagestring($img, 5, (int)($w / 2 - 30), (int)($h / 2 - 8), $name, $black);
    $file = __DIR__ . '/../data/tmp/' . $name . '.jpg';
    imagejpeg($img, $file, 90);
    imagedestroy($img);
    return $file;
}

$colors = [
    [255, 100, 100],
    [100, 255, 100],
    [100, 100, 255],
    [255, 255, 100],
];

// Alle verfuegbaren Layouts (landscape + portrait)
$pageTitle = 'Collage Text Positions - ' . ApplicationService::getInstance()->getTitle();
include PathUtility::getAbsolutePath('admin/components/head.admin.php');
include PathUtility::getAbsolutePath('admin/helper/index.php');

$layouts = [
    '1+3-1', '1+3-2', '3+1-1', '1+2-1', '2+1-1',
    '2+2-1', '2+2-2',
    '2x4-1', '2x4-2', '2x4-3', '2x4-4',
    '2x3-1', '2x3-2',
];

?>
<div class="w-full h-screen absolute bg-brand-2 px-6 py-12 overflow-x-hidden overflow-y-auto">
    <div class="w-full flex items-center justify-center flex-col">
        <div class="w-full max-w-7xl rounded-lg p-8 bg-white flex flex-col shadow-xl mb-8">
            <div class="w-full flex flex-col items-center justify-center text-2xl font-bold text-brand-1 mb-4">
                <?=$languageService->translate('testTextPositions')?>
            </div>
            <div class="text-center mb-4">
                <span class="inline-block px-3 py-1 bg-gray-100 rounded-full text-sm mr-2">
                    <strong>Orientation:</strong> <?=htmlspecialchars($orientation)?>
                </span>
                <span class="inline-block px-3 py-1 bg-purple-100 rounded-full text-sm">
                    <span style="display:inline-block; width:12px; height:12px; background:rgba(255,0,255,0.5); border:1px solid #333; vertical-align:middle; margin-right:4px;"></span>
                    <strong>Text-Zone</strong> (mode="zone")
                </span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

<?php
foreach ($layouts as $layout) {
    $tmp = [];

    // Config fuer dieses Layout vorbereiten
    $testConfig = $config;
    $testConfig['collage']['layout'] = $layout;
    $testConfig['collage']['orientation'] = $orientation;
    $testConfig['collage']['format'] = $orientation;
    $testConfig['collage']['allow_selection'] = true;
    $testConfig['collage']['placeholder'] = false;

    // Template-Pfad pruefen
    $templatePath = Collage::getCollageConfigPath($layout, $orientation);
    if (!$templatePath || !file_exists($templatePath)) {
        echo '<div class="flex flex-col bg-white border border-gray-200 rounded-lg p-4 shadow-sm">';
        echo '<div class="text-lg font-bold text-brand-1 mb-2">' . htmlspecialchars($layout) . '</div>';
        echo '<div style="color:#999">Nicht verfuegbar fuer ' . $orientation . '</div>';
        echo '</div>';
        continue;
    }

    // Korrekte Bildanzahl berechnen (fuer 2x Layouts: count/2)
    $calcResult = Collage::calculateLimit($testConfig['collage']);
    $imageCount = $calcResult['limit'];

    // WICHTIG: limit in Config setzen damit CollageConfigFactory den richtigen Wert verwendet
    $testConfig['collage']['limit'] = $imageCount;

    // Template laden fuer Zone-Visualisierung
    $json = json_decode(file_get_contents($templatePath), true);

    $w = $orientation === 'portrait' ? 600 : 800;
    $h = $orientation === 'portrait' ? 800 : 600;

    for ($i = 0; $i < $imageCount; $i++) {
        $tmp[] = createTestImage($colors[$i % 4], $w, $h, $layout . '_' . $i . '_' . $orientation);
    }

    $dest = __DIR__ . '/../data/images/preview_' . $layout . '_' . $orientation . '.jpg';

    $ok = Collage::createCollage($testConfig, $tmp, $dest);

    // Zone visualisieren falls vorhanden
    $hasZone = false;
    if ($ok && file_exists($dest)) {
        if (isset($json['text_alignment']) && ($json['text_alignment']['mode'] ?? '') === 'zone') {
            $hasZone = true;
            $img = imagecreatefromjpeg($dest);

            if ($img) {
                $width = imagesx($img);
                $height = imagesy($img);
                $ta = $json['text_alignment'];

                // Zone-Koordinaten berechnen
                $rep = ['x' => $width, 'y' => $height];
                $zoneX = isset($ta['x']) ? (int) Helper::doMath(str_replace(array_keys($rep), array_values($rep), (string)$ta['x'])) : 0;
                $zoneY = isset($ta['y']) ? (int) Helper::doMath(str_replace(array_keys($rep), array_values($rep), (string)$ta['y'])) : 0;
                $zoneW = isset($ta['w']) ? (int) Helper::doMath(str_replace(array_keys($rep), array_values($rep), (string)$ta['w'])) : 0;
                $zoneH = isset($ta['h']) ? (int) Helper::doMath(str_replace(array_keys($rep), array_values($rep), (string)$ta['h'])) : 0;

                // Magenta Zone zeichnen
                imagesavealpha($img, true);
                $fill = imagecolorallocatealpha($img, 255, 0, 255, 80);
                $border = imagecolorallocate($img, 255, 255, 255);
                imagefilledrectangle($img, $zoneX, $zoneY, $zoneX + $zoneW, $zoneY + $zoneH, $fill);
                imagerectangle($img, $zoneX, $zoneY, $zoneX + $zoneW, $zoneY + $zoneH, $border);
                imagerectangle($img, $zoneX + 1, $zoneY + 1, $zoneX + $zoneW - 1, $zoneY + $zoneH - 1, $border);

                imagejpeg($img, $dest, 90);
                imagedestroy($img);
            }
        }
    }

    echo '<div class="flex flex-col bg-white border border-gray-200 rounded-lg p-4 shadow-sm">';
    echo '<div class="text-lg font-bold text-brand-1 mb-2">' . htmlspecialchars($layout) . '</div>';
    if ($ok && file_exists($dest)) {
        echo '<img src="/data/images/' . htmlspecialchars(basename($dest)) . '?' . time() . '" class="w-full h-auto rounded border border-gray-300">';
        $size = getimagesize($dest);
        echo '<div class="text-xs text-gray-600 mt-2">' . $size[0] . 'x' . $size[1];
        if ($hasZone) {
            echo ' | <span class="text-purple-600 font-bold">Zone</span>';
        }
        echo '</div>';
    } else {
        echo '<div style="color:red">Fehler beim Erstellen</div>';
    }
    echo '</div>';

    foreach ($tmp as $f) {
        @unlink($f);
    }
}
?>

            </div>
        </div>

        <div class="w-full max-w-xl my-12 border-b border-solid border-white border-opacity-20"></div>

        <div class="w-full max-w-xl rounded-lg py-8 bg-white flex flex-col shadow-xl relative">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 px-4">
            <?php
echo getMenuBtn(PathUtility::getPublicPath('admin'), 'admin_panel', $config['icons']['admin']);
echo getMenuBtn(PathUtility::getPublicPath('test'), 'testMenu', $config['icons']['admin']);
echo getMenuBtn(PathUtility::getPublicPath('admin/generator'), 'layout_generator', $config['icons']['take_collage']);
?>
            </div>
        </div>
    </div>
</div>

<?php
include PathUtility::getAbsolutePath('admin/components/footer.scripts.php');
include PathUtility::getAbsolutePath('admin/components/footer.admin.php');

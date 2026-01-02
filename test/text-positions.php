<!DOCTYPE html>
<html>
<head>
    <title>Text Position Test</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f0f0f0; }
        .layout { display: inline-block; margin: 10px; text-align: center; vertical-align: top; }
        .layout img { max-width: 400px; border: 2px solid #333; box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
        .layout-name { font-weight: bold; margin-top: 5px; }
        h1 { color: #333; }
        .error { color: red; padding: 10px; background: #ffe0e0; margin: 10px; font-size: 12px; }
    </style>
</head>
<body>
    <h1>Text Position Test - Alle Layouts</h1>

    <?php
    // Autoloader zuerst
    require_once __DIR__ . '/../vendor/autoload.php';

    // Config laden
    $configFile = __DIR__ . '/../config/my.config.inc.php';
    if (file_exists($configFile)) {
        $config = require $configFile;
    } else {
        die('Config file not found');
    }

    // Config Setup
    require_once __DIR__ . '/../lib/configsetup.inc.php';

    use Photobooth\Collage;

    // Erstelle 4 Test-Bilder
    $testImages = [];
    for ($i = 1; $i <= 4; $i++) {
        $img = imagecreatetruecolor(800, 600);
        $colors = [
            imagecolorallocate($img, 255, 100, 100),
            imagecolorallocate($img, 100, 255, 100),
            imagecolorallocate($img, 100, 100, 255),
            imagecolorallocate($img, 255, 255, 100),
        ];
        imagefill($img, 0, 0, $colors[$i-1]);
        imagestring($img, 5, 350, 280, "Photo $i", imagecolorallocate($img, 0, 0, 0));

        $filename = __DIR__ . "/../data/tmp/test_$i.jpg";
        imagejpeg($img, $filename);
        imagedestroy($img);
        $testImages[] = $filename;
    }

    // Alle Layouts testen
    $layouts = ['1+3-1', '1+3-2', '3+1-1', '1+2-1', '2+1-1', '2x4-1', '2x4-2', '2x4-3', '2x4-4', '2x3-1', '2x3-2'];

    foreach ($layouts as $layout) {
        try {
            $destFile = __DIR__ . '/../data/images/test_collage_' . str_replace('+', '_', $layout) . '.jpg';

            // Config vorbereiten
            $testConfig = $config;
            $testConfig['collage']['layout'] = $layout;
            $testConfig['collage']['take_frame'] = false;
            $testConfig['textonpicture']['enabled'] = true;
            $testConfig['textonpicture']['line1'] = 'Event Title 2026';
            $testConfig['textonpicture']['line2'] = 'Location Name';
            $testConfig['textonpicture']['line3'] = '';
            $testConfig['textonpicture']['rotation'] = 0;

            $success = Collage::createCollage($testConfig, $testImages, $destFile);

            if ($success) {
                $relPath = 'data/images/' . basename($destFile);
                echo '<div class="layout">';
                echo '<div class="layout-name">' . htmlspecialchars($layout) . '</div>';
                echo '<img src="/' . htmlspecialchars($relPath) . '?' . time() . '">';
                echo '</div>';
            } else {
                echo '<div class="layout">';
                echo '<div class="layout-name">' . htmlspecialchars($layout) . '</div>';
                echo '<div class="error">Error: Failed to create collage</div>';
                echo '</div>';
            }
        } catch (Exception $e) {
            echo '<div class="layout">';
            echo '<div class="layout-name">' . htmlspecialchars($layout) . '</div>';
            echo '<div class="error">Exception: ' . htmlspecialchars($e->getMessage()) . '</div>';
            echo '</div>';
        }
    }

    // Cleanup
    foreach ($testImages as $img) {
        @unlink($img);
    }
    ?>
</body>
</html>

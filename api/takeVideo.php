<?php
header('Content-Type: application/json');

require_once('../lib/config.php');

function isRunning($pid) {
    try {
        $result = shell_exec(
            sprintf("ps %d", $pid)
        );

        if ( count(preg_split("/\n/", $result)) > 2) {
            return true;
        }
    } catch(Exception $e) {

    }

    return false;
}

if ($_POST['play'] === "true" ) {
    $pid = exec('gphoto2 --stdout --capture-movie | ffmpeg -i - -vcodec rawvideo -pix_fmt yuv420p -threads 0 -f v4l2 /dev/video0 > /dev/null 2>&1 & echo $!', $out);
    sleep(3);
    die(json_encode([
        'isRunning' => isRunning($pid),
        'pid' => $pid - 1
    ]));
} elseif($_POST['play'] === "false") {
    exec('kill -15 '.$_POST['pid']);
    die(json_encode([
        'isRunning' => isRunning($_POST['pid']),
        'pid' => $_POST['pid']
    ]));
}

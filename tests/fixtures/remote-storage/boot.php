<?php

declare(strict_types=1);

file_put_contents(\Photobooth\Utility\PathUtility::getRootPath() . 'boot-count', "boot\n", FILE_APPEND | LOCK_EX);

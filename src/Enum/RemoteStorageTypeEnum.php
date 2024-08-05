<?php

declare(strict_types=1);

namespace Photobooth\Enum;

enum RemoteStorageTypeEnum: string
{
    case FTP = 'ftp';
    case SFTP = 'sftp';

    public function identifier(): string
    {
        return match($this) {
            RemoteStorageTypeEnum::FTP => 'FTP - File Transfer Protocol',
            RemoteStorageTypeEnum::SFTP => 'SFTP - SSH File Transfer Protocol',
        };
    }
}

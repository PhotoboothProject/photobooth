<?php

declare(strict_types=1);

namespace Photobooth\Enum;

use Photobooth\Utility\PathUtility;

enum FolderEnum: string
{
    case API = 'api';
    case CHROMA = 'chroma';
    case DATA = 'data';
    case IMAGES = 'data/images';
    case KEYING = 'data/keying';
    case PRINT = 'data/print';
    case QR = 'data/qrcodes';
    case TEST = 'data/test';
    case THUMBS = 'data/thumbs';
    case TEMP = 'data/tmp';
    case LANG = 'resources/lang';
    case PRIVATE = 'private';
    case RESOURCES = 'resources';
    case VAR = 'var';

    public function public(): string
    {
        return PathUtility::getPublicPath($this->value);
    }

    public function absolute(): string
    {
        return PathUtility::getAbsolutePath($this->value);
    }

    public function identifier(): string
    {
        return match($this) {
            FolderEnum::API => 'api',
            FolderEnum::CHROMA => 'chroma',
            FolderEnum::DATA => 'data',
            FolderEnum::IMAGES => 'images',
            FolderEnum::KEYING => 'keying',
            FolderEnum::PRINT => 'print',
            FolderEnum::QR => 'qrcodes',
            FolderEnum::TEST => 'test',
            FolderEnum::THUMBS => 'thumbs',
            FolderEnum::TEMP => 'tmp',
            FolderEnum::LANG => 'lang',
            FolderEnum::PRIVATE => 'private',
            FolderEnum::RESOURCES => 'resources',
            FolderEnum::VAR => 'var',
        };
    }
}

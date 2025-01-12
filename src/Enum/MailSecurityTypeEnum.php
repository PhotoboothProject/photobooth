<?php

declare(strict_types=1);

namespace Photobooth\Enum;

use Photobooth\Enum\Interface\LabelInterface;

enum MailSecurityTypeEnum: string implements LabelInterface
{
    case SSL = 'ssl';
    case TLS = 'tls';

    public function label(): string
    {
        return match($this) {
            self::SSL => 'SSL - Secure Sockets Layer',
            self::TLS => 'TLS - Transport Layer Security',
        };
    }
}

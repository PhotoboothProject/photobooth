<?php

declare(strict_types=1);

namespace Photobooth\Console;

use Photobooth\Command;
use Photobooth\Photobooth;
use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication
{
    protected array $photoboothConfig = [];

    public function __construct(array $photoboothConfig)
    {
        $this->photoboothConfig = $photoboothConfig;
        parent::__construct('Photobooth', (new Photobooth())->getVersion());
        $this->add((new Command\ConfigListCommand())->setPhotoboothConfig($this->photoboothConfig));
    }
}

<?php

declare(strict_types=1);

namespace Photobooth\Console;

use Photobooth\Command;
use Photobooth\Service\ApplicationService;
use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication
{
    protected array $photoboothConfig = [];

    public function __construct(array $photoboothConfig)
    {
        $this->photoboothConfig = $photoboothConfig;
        parent::__construct('Photobooth', ApplicationService::getInstance()->getVersion());
        $this->add((new Command\ConfigListCommand())->setPhotoboothConfig($this->photoboothConfig));
        $this->add((new Command\EnvironmentListCommand()));
    }
}

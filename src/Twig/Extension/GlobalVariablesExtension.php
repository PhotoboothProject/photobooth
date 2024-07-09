<?php

namespace Photobooth\Twig\Extension;

use Photobooth\Service\ApplicationService;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class GlobalVariablesExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * @var array
     */
    protected $config = [];

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function getGlobals(): array
    {
        return [
            '__config' => $this->config,
            '__application' => ApplicationService::getInstance()
        ];
    }

    public function getName(): string
    {
        return 'template_global_variable';
    }
}

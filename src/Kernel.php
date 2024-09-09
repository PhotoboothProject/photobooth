<?php

namespace Photobooth;

use Photobooth\DependencyInjection\PhotoboothExtension;
use Photobooth\Service\ConfigurationService;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function build(ContainerBuilder $container): void
    {
        $container->registerExtension(new PhotoboothExtension());
        $container->loadFromExtension('photobooth', ConfigurationService::getInstance()->getConfiguration());
    }
}

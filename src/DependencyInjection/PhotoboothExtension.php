<?php

namespace Photobooth\DependencyInjection;

use Photobooth\Configuration\PhotoboothConfiguration;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class PhotoboothExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new PhotoboothConfiguration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('photobooth.config', $config);
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new PhotoboothConfiguration();
    }
}

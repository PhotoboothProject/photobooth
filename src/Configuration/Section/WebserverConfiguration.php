<?php

namespace Photobooth\Configuration\Section;

use Photobooth\Environment;
use Photobooth\Service\AssetService;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class WebserverConfiguration
{
    public static function getNode(): NodeDefinition
    {
        $assetService = AssetService::getInstance();
        return (new TreeBuilder('webserver'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->scalarNode('ssid')
                    ->defaultValue('Photobooth')
                    ->end()
                ->scalarNode('url')
                    ->defaultValue('http://' . trim(Environment::getIp()) . $assetService->getUrl(''))
                    ->end()
            ->end();
    }
}

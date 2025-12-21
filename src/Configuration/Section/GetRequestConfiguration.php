<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class GetRequestConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('get_request'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->booleanNode('countdown')->defaultValue(false)->end()
                ->booleanNode('processed')->defaultValue(false)->end()
                ->scalarNode('server')->defaultValue('')->end()
                ->scalarNode('picture')->defaultValue('CNTDWNPHOTO')->end()
                ->scalarNode('collage')->defaultValue('CNTDWNCOLLAGE')->end()
                ->scalarNode('video')->defaultValue('CNTDWNVIDEO')->end()
                ->scalarNode('custom')->defaultValue('CNTDWNCUSTOM')->end()
            ->end();
    }
}

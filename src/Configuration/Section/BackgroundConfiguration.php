<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class BackgroundConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('background'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->scalarNode('defaults')->defaultValue('')->end()
                ->scalarNode('chroma')->defaultValue('')->end()
                ->scalarNode('admin')->defaultValue('')->end()
                ->scalarNode('type')->defaultValue('image')->end()
                ->scalarNode('video')->defaultValue('')->end()
            ->end();
    }
}

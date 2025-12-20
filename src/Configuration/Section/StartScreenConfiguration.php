<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class StartScreenConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('start_screen'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->scalarNode('title')->defaultValue('')->end()
                ->booleanNode('title_visible')->defaultValue(false)->end()
                ->scalarNode('subtitle')->defaultValue('')->end()
                ->booleanNode('subtitle_visible')->defaultValue(false)->end()
            ->end();
    }
}

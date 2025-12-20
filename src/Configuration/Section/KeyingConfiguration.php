<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class KeyingConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('keying'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->booleanNode('enabled')->defaultValue(false)->end()
                ->enumNode('size')
                    ->values(['1000px', '1500px', '2000px', '2500px'])
                    ->defaultValue('1500px')
                    ->end()
                ->enumNode('variant')
                    ->values(['marvinj', 'seriouslyjs'])
                    ->defaultValue('seriouslyjs')
                    ->end()
                ->scalarNode('seriouslyjs_color')->defaultValue('#62af74')->end()
                ->booleanNode('private_backgrounds')->defaultValue(false)->end()
                ->booleanNode('show_all')->defaultValue(false)->end()
            ->end();
    }
}

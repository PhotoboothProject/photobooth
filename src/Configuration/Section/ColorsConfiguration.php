<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class ColorsConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('colors'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->scalarNode('background_countdown')->defaultValue('#8d9fd4')->end()
                ->scalarNode('cheese')->defaultValue('#aa1b3f')->end()
                ->scalarNode('primary')->defaultValue('#1b3faa')->end()
                ->scalarNode('primary_light')->defaultValue('#e8ebf6')->end()
                ->scalarNode('secondary')->defaultValue('#5f78c3')->end()
                ->scalarNode('highlight')->defaultValue('#8d9fd4')->end()
                ->scalarNode('font')->defaultValue('#c9c9c9')->end()
                ->scalarNode('font_secondary')->defaultValue('#333333')->end()
                ->scalarNode('panel')->defaultValue('#1b3faa')->end()
                ->scalarNode('border')->defaultValue('#eeeeee')->end()
                ->scalarNode('box')->defaultValue('#e8ebf6')->end()
                ->scalarNode('status_bar')->defaultValue('#000000')->end()
                ->scalarNode('gallery_button')->defaultValue('#ffffff')->end()
            ->end();
    }
}

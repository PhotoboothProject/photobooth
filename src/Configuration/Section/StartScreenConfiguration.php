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
                ->booleanNode('qr_visible')->defaultValue(false)->end()
                ->scalarNode('qr_url')->defaultValue('')->end()
                ->scalarNode('qr_text')->defaultValue('')->end()
                ->enumNode('qr_position')
                    ->values(['top_right', 'top_left', 'bottom_right', 'bottom_left'])
                    ->defaultValue('top_right')
                    ->end()
            ->end();
    }
}

<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class EventConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('event'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->booleanNode('enabled')->defaultValue(false)->end()
                ->scalarNode('textRight')->defaultValue('')->end()
                ->scalarNode('textLeft')->defaultValue('')->end()
                ->enumNode('symbol')
                    ->values([
                        'fa-camera', 'fa-camera-retro', 'fa-birthday-cake', 'fa-gift', 'fa-tree', 'fa-snowflake',
                        'fa-regular fa-heart', 'fa-solid fa-heart', 'fa-solid fa-heart-pulse', 'fa-brands fa-apple',
                        'fa-anchor', 'fa-light fa-champagne-glasses', 'fa-gears', 'fa-users'
                    ])
                    ->defaultValue('fa-heart-o')
                    ->end()
            ->end();
    }
}

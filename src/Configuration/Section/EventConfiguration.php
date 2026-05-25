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
                ->scalarNode('symbol')->defaultValue('camera')->end()
            ->end();
    }
}

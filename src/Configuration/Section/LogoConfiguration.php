<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class LogoConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('logo'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->booleanNode('enabled')->defaultValue(true)->end()
                ->scalarNode('path')->defaultValue('')->end()
                ->enumNode('position')
                    ->values(['center', 'top_right', 'top_left', 'bottom_right', 'bottom_left'])
                    ->defaultValue('center')
                    ->end()
            ->end();
    }
}

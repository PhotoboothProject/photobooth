<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class ThemeConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('theme'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->scalarNode('current')->defaultValue('')->end()
            ->end();
    }
}

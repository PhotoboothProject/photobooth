<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class DatabaseConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('database'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->booleanNode('enabled')->defaultValue(true)->end()
                ->scalarNode('file')->defaultValue('db')->end()
            ->end();
    }
}

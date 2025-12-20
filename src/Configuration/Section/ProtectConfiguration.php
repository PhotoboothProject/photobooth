<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class ProtectConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('protect'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->booleanNode('admin')->defaultValue(true)->end()
                ->booleanNode('localhost_admin')->defaultValue(true)->end()
                ->booleanNode('index')->defaultValue(false)->end()
                ->booleanNode('localhost_index')->defaultValue(false)->end()
                ->scalarNode('index_redirect')->defaultValue('login')->end()
                ->booleanNode('manual')->defaultValue(false)->end()
                ->booleanNode('localhost_manual')->defaultValue(false)->end()
                ->arrayNode('ip_whitelist')
                    ->prototype('scalar')->end()
                    ->defaultValue([])
                ->end()
            ->end();

    }
}

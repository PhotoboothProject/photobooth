<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class SyncToDriveConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('synctodrive'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->booleanNode('enabled')->defaultValue(false)->end()
                ->scalarNode('target')->defaultValue('photobooth')->end()
                ->integerNode('interval')
                    ->defaultValue(300)
                    ->min(10)
                    ->max(600)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
            ->end();
    }
}

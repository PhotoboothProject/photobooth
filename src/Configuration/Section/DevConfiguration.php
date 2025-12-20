<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class DevConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('dev'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->integerNode('loglevel')
                    ->defaultValue(1)
                    ->min(0)
                    ->max(2)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
                ->booleanNode('demo_images')
                    ->defaultValue(false)
                    ->end()
                ->booleanNode('reload_on_error')
                    ->defaultValue(true)
                    ->end()
            ->end();
    }
}

<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class TextOnPrintConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('textonprint'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->booleanNode('enabled')->defaultValue(false)->end()
                ->scalarNode('line1')->defaultValue('line 1')->end()
                ->scalarNode('line2')->defaultValue('line 2')->end()
                ->scalarNode('line3')->defaultValue('line 3')->end()
                ->integerNode('locationx')
                    ->defaultValue(2250)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
                ->integerNode('locationy')
                    ->defaultValue(1050)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
                ->integerNode('rotation')
                    ->defaultValue(40)
                    ->min(-359)
                    ->max(359)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
                ->scalarNode('font')->defaultValue('')->end()
                ->scalarNode('font_color')->defaultValue('#ffffff')->end()
                ->integerNode('font_size')
                    ->defaultValue(100)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
                ->integerNode('linespace')
                    ->defaultValue(100)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
            ->end();
    }
}

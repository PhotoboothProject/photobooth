<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class TextOnCollageConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('textoncollage'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->booleanNode('enabled')->defaultValue(true)->end()
                ->scalarNode('line1')->defaultValue('Photobooth')->end()
                ->scalarNode('line2')->defaultValue('   we love')->end()
                ->scalarNode('line3')->defaultValue('OpenSource')->end()
                ->integerNode('locationx')
                    ->defaultValue(1470)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
                ->integerNode('locationy')
                    ->defaultValue(250)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
                ->integerNode('rotation')
                    ->defaultValue(0)
                    ->min(-359)
                    ->max(359)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
                ->scalarNode('font')->defaultValue('')->end()
                ->scalarNode('font_color')->defaultValue('#000000')->end()
                ->integerNode('font_size')
                    ->defaultValue(50)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
                ->integerNode('linespace')
                    ->defaultValue(60)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
            ->end();
    }
}

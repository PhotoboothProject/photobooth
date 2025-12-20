<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class QualityConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('jpeg_quality'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->integerNode('image')
                    ->defaultValue(100)
                    ->min(-1)
                    ->max(100)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
                ->integerNode('chroma')
                    ->defaultValue(100)
                    ->min(-1)
                    ->max(100)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
                ->integerNode('thumb')
                    ->defaultValue(60)
                    ->min(-1)
                    ->max(100)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
            ->end();
    }
}

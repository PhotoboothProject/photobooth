<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class SlideshowConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('slideshow'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->integerNode('refreshTime')
                    ->defaultValue(60)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
                ->integerNode('pictureTime')
                    ->defaultValue(3000)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
                ->booleanNode('randomPicture')->defaultValue(true)->end()
                ->booleanNode('use_thumbs')->defaultValue(false)->end()
            ->end();
    }
}

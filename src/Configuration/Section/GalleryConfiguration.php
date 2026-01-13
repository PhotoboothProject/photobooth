<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class GalleryConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('gallery'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->booleanNode('enabled')->defaultValue(true)->end()
                ->booleanNode('newest_first')->defaultValue(true)->end()
                ->booleanNode('use_slideshow')->defaultValue(true)->end()
            ->booleanNode('use_thumb')->defaultValue(false)->end()
            ->integerNode('picture_width')
                ->defaultValue(800)
                ->min(1)
                ->beforeNormalization()
                    ->ifString()
            ->then(static fn (string $value): int => intval($value))
                ->end()
            ->end()
            ->integerNode('picture_height')
                ->defaultValue(600)
                ->min(1)
                ->beforeNormalization()
                    ->ifString()
                    ->then(static fn (string $value): int => intval($value))
                ->end()
            ->end()
                ->integerNode('pictureTime')
                    ->defaultValue(3000)
                    ->min(1000)
                    ->max(10000)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
                ->booleanNode('show_date')->defaultValue(true)->end()
                ->scalarNode('date_format')->defaultValue('d.m.Y - G:i')->end()
                ->booleanNode('db_check_enabled')->defaultValue(true)->end()
                ->integerNode('db_check_time')
                    ->defaultValue(10)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
                ->booleanNode('allow_delete')->defaultValue(true)->end()
                ->booleanNode('scrollbar')->defaultValue(false)->end()
                ->booleanNode('bottom_bar')->defaultValue(true)->end()
                ->booleanNode('figcaption')->defaultValue(true)->end()
            ->end();
    }
}

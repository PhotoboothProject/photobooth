<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class ScreensaverConfiguration
{
    public static function getNode(): NodeDefinition
    {
        // keeps class name for backward compatibility but config node is now "screensaver"
        return (new TreeBuilder('screensaver'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->booleanNode('enabled')->defaultFalse()->end()
                ->enumNode('mode')->values(['image', 'video', 'folder', 'gallery'])->defaultValue('image')->end()
                ->scalarNode('image_source')->defaultValue('')->end()
                ->scalarNode('video_source')->defaultValue('')->end()
                ->scalarNode('text')->defaultValue('')->end()
                ->scalarNode('text_color')->defaultValue('#ffffff')->end()
                ->scalarNode('text_font')->defaultValue('')->end()
                ->scalarNode('text_backdrop_color')->defaultValue('#202020')->end()
                ->floatNode('text_backdrop_opacity')->min(0)->max(1)->defaultValue(0.55)->end()
                ->enumNode('text_position')
                    ->values(['top-center', 'center', 'bottom-center'])
                    ->defaultValue('center')
                ->end()
            ->integerNode('switch_seconds')
                ->min(1)
                ->defaultValue(60)
                ->beforeNormalization()
                    ->ifString()
                        ->then(static fn (string $value): int => intval($value))
                    ->end()
            ->end()
            ->integerNode('gallery_width')
                ->min(1)
                ->defaultValue(800)
                ->beforeNormalization()
                    ->ifString()
                        ->then(static fn (string $value): int => intval($value))
                ->end()
            ->end()
            ->integerNode('timeout_minutes')
                ->min(0)
                ->defaultValue(3)
                ->beforeNormalization()
                    ->ifString()
                        ->then(static fn (string $value): int => intval($value))
                    ->end()
            ->end()
        ->end();
    }
}

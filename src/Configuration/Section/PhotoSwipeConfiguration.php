<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class PhotoSwipeConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('pswp'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->booleanNode('counterEl')->defaultValue(true)->end()
                ->booleanNode('caption')->defaultValue(true)->end()
                ->booleanNode('clickToCloseNonZoomable')->defaultValue(false)->end()
                ->booleanNode('pinchToClose')->defaultValue(true)->end()
                ->booleanNode('closeOnVerticalDrag')->defaultValue(true)->end()
                ->booleanNode('zoomEl')->defaultValue(false)->end()
                ->booleanNode('loop')->defaultValue(true)->end()
                ->floatNode('bgOpacity')
                    ->defaultValue(1.0)
                    ->max(1)
                    ->min(0)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): float { return floatval($value); })
                        ->end()
                    ->end()
                ->scalarNode('imageClickAction')->defaultValue('toggle-controls')->end()
                ->scalarNode('tapAction')->defaultValue('toggle-controls')->end()
                ->scalarNode('doubleTapAction')->defaultValue('zoom')->end()
                ->scalarNode('bgClickAction')->defaultValue('none')->end()
            ->end();
    }
}

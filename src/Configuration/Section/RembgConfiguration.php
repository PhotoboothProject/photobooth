<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class RembgConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('rembg'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->booleanNode('enabled')->defaultValue(false)->end()
                ->scalarNode('background')->defaultValue('')->end()
                ->enumNode('backgroundMode')
                    ->values(['none', 'scale-fit', 'scale-fill', 'crop-center', 'stretch'])
                    ->defaultValue('scale-fill')
                    ->end()
                ->enumNode('model')
                    ->values(['u2net', 'u2netp', 'u2net_cloth_seg', 'u2net_human_seg', 'silueta', 'isnet_general_use', 'isnet_anime'])
                    ->defaultValue('u2net')
                    ->end()
                ->booleanNode('alpha_matting')->defaultValue(true)->end()
                ->integerNode('alpha_matting_foreground_threshold')
                    ->defaultValue(240)
                    ->min(0)
                    ->max(255)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
                ->integerNode('alpha_matting_background_threshold')
                    ->defaultValue(10)
                    ->min(0)
                    ->max(255)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
                ->integerNode('alpha_matting_erode_size')
                    ->defaultValue(10)
                    ->min(0)
                    ->max(255)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
                ->booleanNode('post_processing')->defaultValue(false)->end()
            ->end();
    }
}

<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class VideoConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('video'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->booleanNode('enabled')->defaultValue(false)->end()
                ->integerNode('cntdwn_time')
                    ->defaultValue(3)
                    ->min(0)
                    ->max(10)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
                ->scalarNode('cheese')->defaultValue('Show your moves!')->end()
                ->booleanNode('collage')->defaultValue(false)->end()
                ->booleanNode('collage_keep_images')->defaultValue(false)->end()
                ->booleanNode('collage_only')->defaultValue(false)->end()
                ->enumNode('effects')
                    ->values(['none', 'boomerang'])
                    ->defaultValue('none')
                    ->end()
                ->booleanNode('animation')->defaultValue(true)->end()
                ->booleanNode('gif')->defaultValue(false)->end()
                ->booleanNode('qr')->defaultValue(true)->end()
            ->end();
    }
}

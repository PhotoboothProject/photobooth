<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class QrConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('qr'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->booleanNode('enabled')->defaultValue(true)->end()
                ->scalarNode('url')->defaultValue('')->end()
                ->booleanNode('append_filename')->defaultValue(true)->end()
            ->scalarNode('short_text')->defaultValue('')->end()
                ->booleanNode('custom_text')->defaultValue(false)->end()
                ->scalarNode('text')->defaultValue('')->end()
                ->enumNode('result')
                    ->values([
                        'hidden',
                        'left',
                        'left left--top',
                        'left left--center',
                        'left left--bottom',
                        'right',
                        'right right--top',
                        'right right--center',
                        'right right--bottom',
                    ])
                    ->defaultValue('hidden')
                    ->end()
                ->enumNode('pswp')
                    ->values([
                        'hidden',
                        'left left--top',
                        'left left--center',
                        'left left--bottom',
                        'right right--top',
                        'right right--center',
                        'right right--bottom',
                        'center center--bottom',
                        'center center--top',
                    ])
                    ->defaultValue('hidden')
                    ->end()
            ->end();
    }
}

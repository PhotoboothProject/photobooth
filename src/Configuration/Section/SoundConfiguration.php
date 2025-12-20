<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class SoundConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('sound'))
            ->getRootNode()
            ->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->booleanNode('enabled')->defaultValue(false)->end()
                ->booleanNode('countdown_enabled')->defaultValue(true)->end()
                ->booleanNode('cheese_enabled')->defaultValue(true)->end()
                ->booleanNode('fallback_enabled')->defaultValue(true)->end()
                ->enumNode('voice')
                    ->values(['woman', 'man', 'custom'])
                    ->defaultValue('man')
                ->end()
            ->end();
    }
}

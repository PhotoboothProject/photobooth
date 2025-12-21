<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class ButtonConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('button'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->booleanNode('force_buzzer')->defaultValue(false)->end()
                ->scalarNode('buzzer_message')->defaultValue('Use Buzzer to take a Picture')->end()
                ->booleanNode('show_cups')->defaultValue(false)->end()
                ->booleanNode('show_printUnlock')->defaultValue(false)->end()
                ->booleanNode('homescreen')->defaultValue(true)->end()
                ->booleanNode('reload')->defaultValue(false)->end()
            ->end();
    }
}

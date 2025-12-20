<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class AdminPanelConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('adminpanel'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->enumNode('view')
                    ->values(['basic', 'advanced', 'expert'])
                    ->defaultValue('basic')
                    ->end()
                ->booleanNode('experimental_settings')->defaultValue(false)->end()
            ->end();
    }
}

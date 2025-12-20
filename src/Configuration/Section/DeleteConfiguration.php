<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class DeleteConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('delete'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->booleanNode('no_request')->defaultValue(false)->end()
            ->end();
    }
}

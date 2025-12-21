<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class DownloadConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('download'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->booleanNode('enabled')->defaultValue(true)->end()
                ->booleanNode('thumbs')->defaultValue(false)->end()
            ->end();
    }
}

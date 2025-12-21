<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class ReloadConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('reload'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->scalarNode('key')
                    ->info('specify key id (e.g. 13 is the enter key) to use that key to reload the page, use for example https://keycode.info to get the key code')
                    ->defaultValue('')
                    ->end()
            ->end();
    }
}

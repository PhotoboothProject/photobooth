<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class CustomConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('custom'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->booleanNode('enabled')
                    ->defaultValue(false)
                    ->end()
                ->integerNode('cntdwn_time')
                    ->defaultValue(5)
                    ->min(0)
                    ->max(10)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
                ->scalarNode('key')
                    ->info('specify key id (e.g. 13 is the enter key) to use that key to reload the page, use for example https://keycode.info to get the key code')
                    ->defaultValue('')
                    ->end()
                ->scalarNode('btn_text')
                    ->defaultValue('Custom')
                    ->end()
            ->end();
    }
}

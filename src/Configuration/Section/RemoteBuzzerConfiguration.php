<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class RemoteBuzzerConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('remotebuzzer'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->booleanNode('startserver')->defaultValue(false)->end()
                ->scalarNode('serverip')->defaultValue('')->end()
                ->integerNode('port')
                    ->defaultValue(14711)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
                ->booleanNode('usebuttons')->defaultValue(false)->end()
                ->booleanNode('userotary')->defaultValue(false)->end()
                ->booleanNode('enable_standalonegallery')->defaultValue(false)->end()
                ->booleanNode('picturebutton')->defaultValue(true)->end()
                ->booleanNode('collagebutton')->defaultValue(false)->end()
                ->booleanNode('printbutton')->defaultValue(false)->end()
                ->scalarNode('input_device')->defaultValue('')->end()
                ->booleanNode('shutdownbutton')->defaultValue(false)->end()
                ->booleanNode('videobutton')->defaultValue(false)->end()
                ->booleanNode('rebootbutton')->defaultValue(false)->end()
                ->booleanNode('custombutton')->defaultValue(false)->end()
                ->enumNode('move2usb')
                    ->values(['disabled', 'copy', 'move'])
                    ->defaultValue('disabled')
                    ->end()
            ->end();
    }
}

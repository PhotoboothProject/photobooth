<?php

namespace Photobooth\Configuration\Section;

use Photobooth\Enum\RemoteStorageTypeEnum;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class FtpConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('ftp'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->booleanNode('enabled')->defaultValue(false)->end()
                ->enumNode('type')
                    ->values(RemoteStorageTypeEnum::cases())
                    ->defaultValue(RemoteStorageTypeEnum::FTP)
                    ->beforeNormalization()
                        ->always(function ($value) {
                            if (is_string($value)) {
                                $value = RemoteStorageTypeEnum::from($value);
                            }
                            return $value;
                        })
                        ->end()
                    ->end()
                ->scalarNode('baseURL')->defaultValue('')->end()
                ->integerNode('port')
                    ->defaultValue(21)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
                ->scalarNode('username')->defaultValue('')->end()
                ->scalarNode('password')->defaultValue('')->end()
                ->scalarNode('baseFolder')->defaultValue('')->end()
                ->scalarNode('folder')->defaultValue('')->end()
                ->scalarNode('title')->defaultValue('')->end()
                ->booleanNode('useForQr')->defaultValue(false)->end()
                ->scalarNode('website')->defaultValue('')->end()
                ->scalarNode('urlTemplate')->defaultValue('%website%/%folder%/%title%')->end()
                ->booleanNode('create_webpage')->defaultValue(false)->end()
                ->scalarNode('template_location')->defaultValue('resources/template/index.php')->end()
                ->booleanNode('delete')->defaultValue(false)->end()
            ->end();
    }
}

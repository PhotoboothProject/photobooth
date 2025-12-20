<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class LoginConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('login'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->booleanNode('enabled')->defaultValue(false)->end()
                ->scalarNode('username')->defaultValue('Photo')->end()
                ->scalarNode('password')
                    ->defaultNull()
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): ?string { return strlen(trim($value)) === 0 ? null : $value; })
                        ->end()
                    ->end()
                ->booleanNode('keypad')->defaultValue(false)->end()
                ->scalarNode('pin')
                    ->defaultNull()
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): ?string { return strlen(trim($value)) === 0 ? null : $value; })
                        ->end()
                    ->end()
                ->booleanNode('rental_keypad')->defaultValue(false)->end()
                ->scalarNode('rental_pin')
                    ->defaultNull()
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): ?string { return strlen(trim($value)) === 0 ? null : $value; })
                        ->end()
                    ->end()
            ->end();
    }
}

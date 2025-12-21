<?php

namespace Photobooth\Configuration\Section;

use Photobooth\Enum\MailSecurityTypeEnum;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class MailConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('mail'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->booleanNode('enabled')->defaultValue(false)->end()
                ->booleanNode('send_all_later')->defaultValue(false)->end()
                ->booleanNode('virtualKeyboard')->defaultValue(false)->end()
                ->enumNode('keyboardLayout')
                    ->values(['azerty', 'qwerty', 'qwertz'])
                    ->defaultValue('qwerty')
                    ->end()
                ->scalarNode('subject')->defaultValue('')->end()
                ->scalarNode('text')->defaultValue('')->end()
                ->scalarNode('alt_text')->defaultValue('')->end()
                ->booleanNode('is_html')->defaultValue(false)->end()
                ->scalarNode('host')->defaultValue('smtp.example.com')->end()
                ->scalarNode('username')->defaultValue('photobooth@example.com')->end()
                ->scalarNode('password')->defaultValue('yourpassword')->end()
                ->scalarNode('fromAddress')->defaultValue('photobooth@example.com')->end()
                ->scalarNode('fromName')->defaultValue('Photobooth')->end()
                ->scalarNode('file')->defaultValue('mail-adresses')->end()
                ->enumNode('secure')
                    ->values(MailSecurityTypeEnum::cases())
                    ->defaultValue(MailSecurityTypeEnum::TLS)
                    ->beforeNormalization()
                        ->always(function ($value) {
                            if (is_string($value)) {
                                $value = MailSecurityTypeEnum::from($value);
                            }
                            return $value;
                        })
                        ->end()
                    ->end()
                ->integerNode('port')
                    ->defaultValue(587)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
            ->end();
    }
}

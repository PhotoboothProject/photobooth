<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class PaymentsConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('payments'))->getRootNode()
            ->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->booleanNode('enabled')->defaultValue(false)->end()
                ->enumNode('provider')
                    ->values(['none', 'sumup', 'coin'])
                    ->defaultValue('none')
                ->end()
                ->enumNode('display_mode')
                    ->values(['solo', 'qr', 'both'])
                    ->defaultValue('solo')
                ->end()
                ->scalarNode('webhook_url')->defaultValue('')->end()
                ->integerNode('price_cents')
                    ->defaultValue(100)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int {
                            return intval($value);
                        })
                    ->end()
                ->end()
                ->scalarNode('message')->defaultValue('Bitte zahlen Sie %price% €')->end()
                ->scalarNode('background')->defaultValue('')->end()
                ->integerNode('timeout')
                    ->defaultValue(60)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int {
                            return intval($value);
                        })
                    ->end()
                ->end()
                ->arrayNode('sumup')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('merchant_code')->defaultValue('')->end()
                        ->scalarNode('reader_id')->defaultValue('')->end()
                        ->scalarNode('affiliate_key')->defaultValue('')->end()
                        ->scalarNode('token_file')->defaultValue('/var/www/html/config/sumup_token.txt')->end()
                    ->end()
                ->end()
            ->end();
    }
}

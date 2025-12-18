<?php

namespace Photobooth\Configuration\Section;

use Photobooth\Enum\ImageFilterEnum;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class FilterConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('filters'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->booleanNode('enabled')
                    ->defaultValue(true)
                    ->end()
                ->enumNode('defaults')
                    ->values(ImageFilterEnum::cases())
                    ->defaultValue(ImageFilterEnum::PLAIN)
                    ->beforeNormalization()
                        ->always(function ($value) {
                            if (is_string($value)) {
                                $value = ImageFilterEnum::from($value);
                            }
                            return $value;
                        })
                        ->end()
                    ->end()
                ->integerNode('process_size')
                    ->info('Downscale images to this maximum width/height before applying filters to speed up processing. Set to 0 to disable.')
                    ->defaultValue(0)
                    ->min(0)
                    ->max(5000)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
                ->arrayNode('disabled')
                    ->enumPrototype()
                        ->values(ImageFilterEnum::cases())
                        ->beforeNormalization()
                            ->always(function ($value) {
                                if (is_string($value)) {
                                    $value = ImageFilterEnum::from($value);
                                }
                                return $value;
                            })
                            ->end()
                        ->end()
                    ->end()
            ->end();
    }
}

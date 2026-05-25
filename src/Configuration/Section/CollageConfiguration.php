<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class CollageConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('collage'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->validate()
                ->always(function (array $value): array {
                    $layouts = $value['layouts_enabled'] ?? [];
                    $layouts = is_array($layouts) ? $layouts : [];

                    $layoutValues = array_map(
                        static fn ($l): string => $l instanceof \BackedEnum ? (string) $l->value : (string) $l,
                        $layouts
                    );

                    $uniqueCount = count(array_unique($layoutValues));

                    if (($value['allow_selection'] ?? false) && $uniqueCount < 2) {
                        $value['allow_selection'] = false;
                    }

                    return $value;
                })
                ->end()
            ->children()
                ->booleanNode('enabled')->defaultValue(true)->end()
                ->integerNode('cntdwn_time')
                    ->defaultValue(3)
                    ->min(0)
                    ->max(10)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int {
                            return intval($value);
                        })
                        ->end()
                    ->end()
                ->booleanNode('continuous')->defaultValue(true)->end()
                ->integerNode('continuous_time')
                    ->defaultValue(5)
                    ->min(0)
                    ->max(20)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int {
                            return intval($value);
                        })
                        ->end()
                    ->end()
                ->enumNode('orientation')
                    ->values(['landscape', 'portrait'])
                    ->defaultValue('landscape')
                    ->end()
                ->scalarNode('layout')
                    ->defaultValue('2+2-2')
                    ->beforeNormalization()
                        ->always(function ($value) {
                            if ($value instanceof \BackedEnum) {
                                return (string) $value->value;
                            }
                            return (string) $value;
                        })
                        ->end()
                    ->end()
                ->booleanNode('allow_selection')->defaultValue(false)->end()
                ->arrayNode('layouts_enabled')
                    ->scalarPrototype()
                        ->beforeNormalization()
                            ->always(function ($value) {
                                if ($value instanceof \BackedEnum) {
                                    return (string) $value->value;
                                }
                                return (string) $value;
                            })
                            ->end()
                        ->end()
                    ->end()
                ->integerNode('limit')
                    ->defaultValue(4)
                    ->min(1)
                    ->max(999)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int {
                            return intval($value);
                        })
                        ->end()
                    ->end()
                ->scalarNode('dashedline_color')->defaultValue('#000000')->end()
                ->booleanNode('keep_single_images')->defaultValue(false)->end()
                ->scalarNode('key')
                    ->info('specify key id (e.g. 13 is the enter key) to use that key to reload the page, use for example https://keycode.info to get the key code')
                    ->defaultValue('')
                    ->end()
                ->scalarNode('background_color')->defaultValue('#ffffff')->end()
                ->enumNode('take_frame')
                    ->values(['off', 'always', 'once'])
                    ->defaultValue('off')
                    ->end()
                ->scalarNode('frame')->defaultValue('')->end()
                ->booleanNode('polaroid_effect')->defaultValue(false)->end()
                ->integerNode('polaroid_rotation')
                    ->defaultValue(0)
                    ->min(-359)
                    ->max(359)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int {
                            return intval($value);
                        })
                        ->end()
                    ->end()
                ->booleanNode('placeholder')->defaultValue(false)->end()
                ->integerNode('placeholderposition')
                    ->defaultValue(1)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int {
                            return intval($value);
                        })
                        ->end()
                    ->end()
                ->scalarNode('placeholderpath')->defaultValue('')->end()
                ->scalarNode('background')->defaultValue('')->end()
                ->scalarNode('background_landscape')->defaultValue('')->end()
                ->scalarNode('background_portrait')->defaultValue('')->end()
                ->scalarNode('background_strip')->defaultValue('')->end()
                ->enumNode('background_render_mode')
                    ->values(['behind_images', 'overlay_frame'])
                    ->beforeNormalization()
                        ->ifTrue(static function ($value): bool {
                            return is_bool($value);
                        })
                        ->then(static function (bool $value): string {
                            return $value ? 'overlay_frame' : 'behind_images';
                        })
                        ->end()
                    ->defaultValue('behind_images')
                    ->end()
                ->integerNode('limit')
                    ->defaultValue(4)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int {
                            return intval($value);
                        })
                        ->end()
                    ->end()
            ->end();
    }
}

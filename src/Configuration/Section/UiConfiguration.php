<?php

namespace Photobooth\Configuration\Section;

use Photobooth\Enum\TimezoneEnum;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class UiConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('ui'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->enumNode('language')
                    ->values(['cs', 'de', 'en', 'es', 'fr', 'hr', 'it', 'nl', 'pt', 'tr'])
                    ->defaultValue('en')
                    ->end()
                ->enumNode('local_timezone')
                    ->values(TimezoneEnum::cases())
                    ->defaultValue(TimezoneEnum::EUROPE_LONDON)
                    ->beforeNormalization()
                        ->always(function ($value) {
                            if (is_string($value)) {
                                $value = TimezoneEnum::from($value);
                            }
                            return $value;
                        })
                        ->end()
                    ->end()
                ->integerNode('notification_timeout')
                    ->defaultValue(5)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
                ->booleanNode('show_fork')->defaultValue(true)->end()
                ->booleanNode('skip_welcome')->defaultValue(false)->end()
                ->booleanNode('admin_shortcut')->defaultValue(true)->end()
                ->enumNode('admin_shortcut_position')
                    ->values(['top-left', 'top-right', 'bottom-left', 'bottom-right'])
                    ->defaultValue('bottom-right')
                    ->end()
                ->booleanNode('selfie_mode')->defaultValue(false)->end()
                ->enumNode('style')
                    ->values(['classic', 'classic_rounded', 'modern', 'modern_squared'])
                    ->defaultValue('modern_squared')
                    ->end()
                ->enumNode('button')
                    ->values(['classic', 'classic_rounded', 'modern', 'modern_squared'])
                    ->defaultValue('modern_squared')
                    ->end()
                ->integerNode('scale')
                    ->defaultValue(100)
                    ->min(100)
                    ->max(200)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
                ->integerNode('scale_resultImage')
                    ->defaultValue(60)
                    ->min(10)
                    ->max(100)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $value): int { return intval($value); })
                        ->end()
                    ->end()
                ->booleanNode('shutter_animation')->defaultValue(true)->end()
                ->scalarNode('shutter_cheese_img')->defaultValue('')->end()
                ->booleanNode('result_buttons')->defaultValue(true)->end()
                ->booleanNode('decore_lines')->defaultValue(true)->end()
                ->booleanNode('rounded_corners')->defaultValue(false)->end()
            ->end();
    }
}

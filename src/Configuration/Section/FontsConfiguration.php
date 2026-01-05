<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class FontsConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('fonts'))
            ->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
            ->scalarNode('default')->defaultValue('')->end()
            ->booleanNode('default_bold')->defaultFalse()->end()
            ->booleanNode('default_italic')->defaultFalse()->end()
            ->scalarNode('default_color')->defaultValue('#c9c9c9')->end()
            ->scalarNode('start_screen_title')->defaultValue('')->end()
            ->booleanNode('start_screen_title_bold')->defaultTrue()->end()
            ->booleanNode('start_screen_title_italic')->defaultFalse()->end()
            ->scalarNode('start_screen_title_color')->defaultValue('#333333')->end()
            ->scalarNode('event_text')->defaultValue('')->end()
            ->booleanNode('event_text_bold')->defaultTrue()->end()
            ->booleanNode('event_text_italic')->defaultFalse()->end()
            ->scalarNode('event_text_color')->defaultValue('#c9c9c9')->end()
            ->scalarNode('countdown_text')->defaultValue('')->end()
            ->booleanNode('countdown_text_bold')->defaultTrue()->end()
            ->booleanNode('countdown_text_italic')->defaultFalse()->end()
            ->scalarNode('countdown_text_color')->defaultValue('#1b3faa')->end()
            ->scalarNode('gallery_title')->defaultValue('')->end()
            ->booleanNode('gallery_title_bold')->defaultFalse()->end()
            ->booleanNode('gallery_title_italic')->defaultFalse()->end()
            ->scalarNode('gallery_title_color')->defaultValue('')->end()
            ->scalarNode('screensaver_text')->defaultValue('')->end()
            ->booleanNode('screensaver_text_bold')->defaultFalse()->end()
            ->booleanNode('screensaver_text_italic')->defaultFalse()->end()
            ->scalarNode('screensaver_text_color')->defaultValue('#ffffff')->end()
            ->scalarNode('button_font')->defaultValue('')->end()
            ->booleanNode('button_font_bold')->defaultFalse()->end()
            ->booleanNode('button_font_italic')->defaultFalse()->end()
            ->scalarNode('button_font_color')->defaultValue('#ffffff')->end()
            ->scalarNode('button_buzzer_message_font')->defaultValue('')->end()
            ->booleanNode('button_buzzer_message_font_bold')->defaultFalse()->end()
            ->booleanNode('button_buzzer_message_font_italic')->defaultFalse()->end()
            ->scalarNode('button_buzzer_message_font_color')->defaultValue('#c9c9c9')->end()
            ->end();
    }
}

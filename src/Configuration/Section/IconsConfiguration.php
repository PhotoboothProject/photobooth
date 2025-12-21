<?php

namespace Photobooth\Configuration\Section;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class IconsConfiguration
{
    public static function getNode(): NodeDefinition
    {
        return (new TreeBuilder('icons'))->getRootNode()->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->scalarNode('admin_back')->defaultValue('fa fa-long-arrow-left')->end()
                ->scalarNode('admin_back_short')->defaultValue('fa fa-arrow-left')->end()
                ->scalarNode('admin_menutoggle')->defaultValue('fa fa-bars')->end()
                ->scalarNode('admin_save')->defaultValue('fa fa-circle-notch fa-spin fa-fw')->end()
                ->scalarNode('admin_save_success')->defaultValue('fa fa-check')->end()
                ->scalarNode('admin_save_error')->defaultValue('fa fa-times')->end()
                ->scalarNode('admin_signout')->defaultValue('fa fa-sign-out')->end()
                ->scalarNode('admin')->defaultValue('fa fa-cog')->end()
                ->scalarNode('home')->defaultValue('fa fa-house')->end()
                ->scalarNode('gallery')->defaultValue('fa fa-image')->end()
                ->scalarNode('dependencies')->defaultValue('fa fa-list-ul')->end()
                ->scalarNode('update')->defaultValue('fa fa-tasks')->end()
                ->scalarNode('slideshow')->defaultValue('fa fa-play')->end()
                ->scalarNode('chromaCapture')->defaultValue('fa fa-paint-brush')->end()
                ->scalarNode('faq')->defaultValue('fa fa-question-circle')->end()
                ->scalarNode('manual')->defaultValue('fa fa-info-circle')->end()
                ->scalarNode('telegram')->defaultValue('fa-brands fa-telegram')->end()
                ->scalarNode('cups')->defaultValue('fa fa-print')->end()
                ->scalarNode('take_picture')->defaultValue('fa fa-camera')->end()
                ->scalarNode('take_collage')->defaultValue('fa fa-th-large')->end()
                ->scalarNode('take_video')->defaultValue('fa fa-video')->end()
                ->scalarNode('close')->defaultValue('fa fa-times')->end()
                ->scalarNode('refresh')->defaultValue('fa fa-arrows-rotate')->end()
                ->scalarNode('delete')->defaultValue('fa fa-trash-can')->end()
                ->scalarNode('print')->defaultValue('fa fa-print')->end()
                ->scalarNode('save')->defaultValue('fa fa-floppy-disk')->end()
                ->scalarNode('download')->defaultValue('fa fa-download')->end()
                ->scalarNode('qr')->defaultValue('fa fa-qrcode')->end()
                ->scalarNode('mail')->defaultValue('fa fa-envelope')->end()
                ->scalarNode('mail_close')->defaultValue('fa fa-times')->end()
                ->scalarNode('mail_submit')->defaultValue('fa fa-spinner fa-spin')->end()
                ->scalarNode('filter')->defaultValue('fa fa-wand-magic-sparkles')->end()
                ->scalarNode('chroma')->defaultValue('fa fa-paint-brush')->end()
                ->scalarNode('fullscreen')->defaultValue('fa fa-maximize')->end()
                ->scalarNode('share')->defaultValue('fa fa-share-alt')->end()
                ->scalarNode('zoom')->defaultValue('fa fa-search-plus')->end()
                ->scalarNode('logout')->defaultValue('fa fa-right-from-bracket')->end()
                ->scalarNode('date')->defaultValue('fa fa-clock')->end()
                ->scalarNode('spinner')->defaultValue('fa fa-cog fa-spin')->end()
                ->scalarNode('update_git')->defaultValue('fa fa-play-circle')->end()
                ->scalarNode('password_visibility')->defaultValue('fa fa-eye')->end()
                ->scalarNode('password_toggle')->defaultValue('fa-eye fa-eye-slash')->end()
                ->scalarNode('slideshow_play')->defaultValue('fa fa-play')->end()
                ->scalarNode('slideshow_toggle')->defaultValue('fa-play fa-pause')->end()
                ->scalarNode('take_custom')->defaultValue('fa fa-paint-brush')->end()
            ->end();
    }
}

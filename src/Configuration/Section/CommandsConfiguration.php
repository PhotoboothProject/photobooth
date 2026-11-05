<?php

namespace Photobooth\Configuration\Section;

use Photobooth\Environment;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class CommandsConfiguration
{
    public static function getNode(): NodeDefinition
    {
        $commands = [
            'windows' => [
                'take_picture'  => 'digicamcontrol\CameraControlCmd.exe /capture /filename %s',
                'take_collage'  => '',
                'take_video'    => '',
                'take_custom'   => '',
                'print'         => 'rundll32 C:\WINDOWS\system32\shimgvw.dll,ImageView_PrintTo %s Printer_Name',
                'exiftool'      => '',
                'nodebin'       => '',
                'reboot'        => '',
                'shutdown'      => '',
                'preview'       => '',
                'preview_kill'  => '',
                'pre_photo'     => '',
                'post_photo'    => '',
            ],
            'linux' => [
                'take_picture'  => 'gphoto2 --capture-image-and-download --filename=%s',
                'take_collage'  => '',
                'take_video'    => 'python3 cameracontrol.py -v %s --vlen 3 --vframes 4',
                'take_custom'   => 'python3 cameracontrol.py --chromaImage=/var/www/html/resources/img/bg.jpg --chromaColor 00ff00 --chromaSensitivity 0.4 --chromaBlend 0.1 --capture-image-and-download %s',
                'print'         => 'lp -o landscape -o fit-to-page %s',
                'exiftool'      => 'exiftool -overwrite_original -TagsFromFile %s %s',
                'nodebin'       => '/usr/bin/node',
                'reboot'        => '/sbin/shutdown -r now',
                'shutdown'      => '/sbin/shutdown -h now',
                'preview'       => '',
                'preview_kill'  => '',
                'pre_photo'     => '',
                'post_photo'    => '',
            ],
        ];

        $os = Environment::getOperatingSystem();

        if (!isset($commands[$os])) {
            throw new \RuntimeException(sprintf('Unsupported OS "%s"', $os));
        }

        $defaults = $commands[$os];

        return (new TreeBuilder('commands'))
            ->getRootNode()
            ->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->scalarNode('take_picture')->defaultValue($defaults['take_picture'])->end()
                ->scalarNode('take_collage')->defaultValue($defaults['take_collage'])->end()
                ->scalarNode('take_custom')->defaultValue($defaults['take_custom'])->end()
                ->scalarNode('take_video')->defaultValue($defaults['take_video'])->end()
                ->scalarNode('print')->defaultValue($defaults['print'])->end()
                ->scalarNode('exiftool')->defaultValue($defaults['exiftool'])->end()
                ->scalarNode('preview')->defaultValue($defaults['preview'])->end()
                ->scalarNode('preview_kill')->defaultValue($defaults['preview_kill'])->end()
                ->scalarNode('nodebin')->defaultValue($defaults['nodebin'])->end()
                ->scalarNode('pre_photo')->defaultValue($defaults['pre_photo'])->end()
                ->scalarNode('post_photo')->defaultValue($defaults['post_photo'])->end()
                ->scalarNode('reboot')->defaultValue($defaults['reboot'])->end()
                ->scalarNode('shutdown')->defaultValue($defaults['shutdown'])->end()
            ->end();
    }
}

<?php

namespace Photobooth\Configuration;

use Photobooth\Configuration\Section\AdminPanelConfiguration;
use Photobooth\Configuration\Section\BackgroundConfiguration;
use Photobooth\Configuration\Section\ButtonConfiguration;
use Photobooth\Configuration\Section\ChromaCaptureConfiguration;
use Photobooth\Configuration\Section\CollageConfiguration;
use Photobooth\Configuration\Section\ColorsConfiguration;
use Photobooth\Configuration\Section\CommandsConfiguration;
use Photobooth\Configuration\Section\CustomConfiguration;
use Photobooth\Configuration\Section\DatabaseConfiguration;
use Photobooth\Configuration\Section\DeleteConfiguration;
use Photobooth\Configuration\Section\DevConfiguration;
use Photobooth\Configuration\Section\DownloadConfiguration;
use Photobooth\Configuration\Section\EventConfiguration;
use Photobooth\Configuration\Section\FilterConfiguration;
use Photobooth\Configuration\Section\FtpConfiguration;
use Photobooth\Configuration\Section\GalleryConfiguration;
use Photobooth\Configuration\Section\GetRequestConfiguration;
use Photobooth\Configuration\Section\IconsConfiguration;
use Photobooth\Configuration\Section\ScreensaverConfiguration;
use Photobooth\Configuration\Section\FontsConfiguration;
use Photobooth\Configuration\Section\KeyingConfiguration;
use Photobooth\Configuration\Section\LoginConfiguration;
use Photobooth\Configuration\Section\LogoConfiguration;
use Photobooth\Configuration\Section\MailConfiguration;
use Photobooth\Configuration\Section\PhotoSwipeConfiguration;
use Photobooth\Configuration\Section\PictureConfiguration;
use Photobooth\Configuration\Section\PreviewConfiguration;
use Photobooth\Configuration\Section\PrintConfiguration;
use Photobooth\Configuration\Section\ProtectConfiguration;
use Photobooth\Configuration\Section\QrConfiguration;
use Photobooth\Configuration\Section\QualityConfiguration;
use Photobooth\Configuration\Section\ReloadConfiguration;
use Photobooth\Configuration\Section\RembgConfiguration;
use Photobooth\Configuration\Section\RemoteBuzzerConfiguration;
use Photobooth\Configuration\Section\SlideshowConfiguration;
use Photobooth\Configuration\Section\SoundConfiguration;
use Photobooth\Configuration\Section\StartScreenConfiguration;
use Photobooth\Configuration\Section\SyncToDriveConfiguration;
use Photobooth\Configuration\Section\TextOnCollageConfiguration;
use Photobooth\Configuration\Section\TextOnPictureConfiguration;
use Photobooth\Configuration\Section\TextOnPrintConfiguration;
use Photobooth\Configuration\Section\ThemeConfiguration;
use Photobooth\Configuration\Section\UiConfiguration;
use Photobooth\Configuration\Section\VideoConfiguration;
use Photobooth\Configuration\Section\WebserverConfiguration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class PhotoboothConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('photobooth');

        $rootNode = $treeBuilder->getRootNode()->addDefaultsIfNotSet();
        $rootNode
            // we are ignoring extra keys to avoid having old configuration mixed up
            // only the current configuration will be processed
            ->ignoreExtraKeys()
            ->children()
                ->append(UiConfiguration::getNode())
                ->append(AdminPanelConfiguration::getNode())
                ->append(DevConfiguration::getNode())
                ->append(WebserverConfiguration::getNode())
                ->append(StartScreenConfiguration::getNode())
                ->append(ScreensaverConfiguration::getNode())
                ->append(LogoConfiguration::getNode())
                ->append(DownloadConfiguration::getNode())
                ->append(ReloadConfiguration::getNode())
                ->append(PictureConfiguration::getNode())
                ->append(TextOnPictureConfiguration::getNode())
                ->append(DatabaseConfiguration::getNode())
                ->append(DeleteConfiguration::getNode())
                ->append(EventConfiguration::getNode())
                ->append(ButtonConfiguration::getNode())
                ->append(FilterConfiguration::getNode())
                ->append(CustomConfiguration::getNode())
                ->append(CollageConfiguration::getNode())
                ->append(TextOnCollageConfiguration::getNode())
                ->append(QualityConfiguration::getNode())
                ->append(LoginConfiguration::getNode())
                ->append(FtpConfiguration::getNode())
                ->append(PhotoSwipeConfiguration::getNode())
                ->append(VideoConfiguration::getNode())
                ->append(GalleryConfiguration::getNode())
                ->append(GetRequestConfiguration::getNode())
                ->append(ProtectConfiguration::getNode())
                ->append(ColorsConfiguration::getNode())
                ->append(FontsConfiguration::getNode())
                ->append(BackgroundConfiguration::getNode())
                ->append(PreviewConfiguration::getNode())
                ->append(IconsConfiguration::getNode())
                ->append(KeyingConfiguration::getNode())
                ->append(SyncToDriveConfiguration::getNode())
                ->append(RemoteBuzzerConfiguration::getNode())
                ->append(SlideshowConfiguration::getNode())
                ->append(TextOnPrintConfiguration::getNode())
                ->append(QrConfiguration::getNode())
                ->append(ChromaCaptureConfiguration::getNode())
                ->append(PrintConfiguration::getNode())
                ->append(CommandsConfiguration::getNode())
                ->append(MailConfiguration::getNode())
                ->append(SoundConfiguration::getNode())
                ->append(RembgConfiguration::getNode())
                ->append(ThemeConfiguration::getNode())
            ->end();

        return $treeBuilder;
    }
}

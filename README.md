# photobooth by Andre Rinas
A Photobooth webinterface for Raspberry Pi and Windows.

## :heart_eyes: Features
- Works on Windows and Linux.
  - Under Windows [digiCamControl](http://digicamcontrol.com/) by Duka Istvan can be used to control the camera and to take pictures.
  - Under Linux [gPhoto2](http://gphoto.org/) is used to control the camera and to take pictures.
- Images are processed with GD/ImageMagick.
- Photobooth caches all generated QR-Codes, Thumbnails and Prints.
- Pictures can be printed directly after they were taken or later from the gallery. Photobooth uses the command line to print the picture. The command can be modified in ```config/my.config.inc.php```.
- Pictures can be send via E-Mail.
- You can choose an image filter before taking a picture.
- Settings can be changed in ```config/my.config.inc.php``` or via Admin Page (under /admin):
  - You can hide the gallery.
  - The gallery can be ordered ascending oder descending by picture age (see ```$config['gallery']['newest_first']``` in ```config/my.config.inc.php```).
  - Choose between md5format and dateformat images.
  - You can switch between german, english, spanish and french language lables.
  - QR-Code to allow downloading pictures from your Photobooth can be enabled/disabled.
  - Print feature can be enabled/disabled.
    - Optional: Print a frame on your picture (replace resources/img/frames/frame.png with a proper frame).
    - Optional: Print text on your picture.
  - LivePreview can be enabled/disabled (uses device cam).
  - Wedding specifig config to show a symbol (e.g. heart) betweeen two names on the startpage.
  - Green screen keying can be enabled/disabled (chroma keying).
  - Photo collage function: take 4 pictures in a row and let it generate a collage out of it.
  - Blue-gray theme can be enabled.
  - Save pictures with a polaroid effect.
  
## :camera: Screenshots
![](https://raw.githubusercontent.com/wiki/andreknieriem/photobooth/images/start.png)

## :gear: Prerequisites
- gphoto2 installed, if used on a Raspberry for DSLR control
- digiCamControl, if used unter Windows for DSLR control
- Apache

## :wrench: Installation & Troubleshooting
Please follow the installation instructions in our [Photobooth-Wiki](https://github.com/andreknieriem/photobooth/wiki) to setup Photobooth.

If you're having trouble or questions please take a look at our [FAQ](https://github.com/andreknieriem/photobooth/wiki#faq---frequently-asked-questions) before opening a new issue.


### :mag: Changelog
- [2.0.0 alpha](https://github.com/andreknieriem/photobooth/wiki/Changelog#200-alpha)
- [1.9.0](https://github.com/andreknieriem/photobooth/wiki/Changelog#190)
- [1.8.3](https://github.com/andreknieriem/photobooth/wiki/Changelog#183)
- [1.8.2](https://github.com/andreknieriem/photobooth/wiki/Changelog#182)
- [1.8.1](https://github.com/andreknieriem/photobooth/wiki/Changelog#181)
- [1.8.0](https://github.com/andreknieriem/photobooth/wiki/Changelog#180)
- [1.7.0](https://github.com/andreknieriem/photobooth/wiki/Changelog#170)
- [1.6.3](https://github.com/andreknieriem/photobooth/wiki/Changelog#163)
- [1.6.2](https://github.com/andreknieriem/photobooth/wiki/Changelog#162)
- [1.6.1](https://github.com/andreknieriem/photobooth/wiki/Changelog#161)
- [1.6.0](https://github.com/andreknieriem/photobooth/wiki/Changelog#160)
- [1.5.3](https://github.com/andreknieriem/photobooth/wiki/Changelog#153)
- [1.5.2](https://github.com/andreknieriem/photobooth/wiki/Changelog#152)
- [1.5.1](https://github.com/andreknieriem/photobooth/wiki/Changelog#151)
- [1.5.0](https://github.com/andreknieriem/photobooth/wiki/Changelog#150)
- [1.4.0](https://github.com/andreknieriem/photobooth/wiki/Changelog#140)
- [1.3.2](https://github.com/andreknieriem/photobooth/wiki/Changelog#132)
- [1.3.1](https://github.com/andreknieriem/photobooth/wiki/Changelog#131)
- [1.3.0](https://github.com/andreknieriem/photobooth/wiki/Changelog#130)
- [1.2.0](https://github.com/andreknieriem/photobooth/wiki/Changelog#120)
- [1.1.1](https://github.com/andreknieriem/photobooth/wiki/Changelog#111)
- [1.1.0](https://github.com/andreknieriem/photobooth/wiki/Changelog#110)
- [1.0.0](https://github.com/andreknieriem/photobooth/wiki/Changelog#100)

### :mortar_board: Tutorial
[Raspberry Pi Weddingphotobooth (german)](https://www.andrerinas.de/tutorials/raspberry-pi-einen-dslr-weddingphotobooth-erstellen.html)

### :clap: Contributors and thanks to
- [dimsemenov](https://github.com/dimsemenov/photoswipe) for photoswipe
- [t0k4rt](https://github.com/t0k4rt/phpqrcode) for phpqrcode
- [nihilor](https://github.com/nihilor/photobooth) for Printing feature, code rework and bugfixes
- [vrs01](https://github.com/vrs01)
- [F4bsi](https://github.com/F4bsi)
- [got-x](https://github.com/got-x)
- [RaphaelKunis](https://github.com/RaphaelKunis)
- [andi34](https://github.com/andi34)
- [Norman-Sch](https://github.com/Norman-Sch)
- [marcogracklauer](https://github.com/marcogracklauer)
- [dnks23](https://github.com/dnks23)
- [tobiashaas](https://github.com/tobiashaas)
- Martin Kaiser-Kaplaner
- [MoJou90](https://github.com/MoJou90)
- [Reinhard Reberning](https://www.reinhard-rebernig.at/website/websites/fotokasterl)
- [Steffen Musch](https://github.com/Nie-Oh)
- [flighter18](https://github.com/flighter18)
- [thymon13](https://github.com/thymon13)
- [vdubuk](https://github.com/vdubuk)
- [msmedien](https://github.com/msmedien)
- [sualko](https://github.com/sualko)

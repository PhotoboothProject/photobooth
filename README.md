# Photobooth v3

A Photobooth web interface for Raspberry Pi and Windows.

Photobooth was initially developped by Andre Rinas, you can find his source [here](https://github.com/andreknieriem/photobooth).

**This is my personal Photobooth fork with a lot of extras and improvements.**  
_(The full changelog can be found inside [the Photobooth Wiki](https://github.com/andi34/photobooth/wiki/changelog).)_

[![Chat on Telegram](https://img.shields.io/badge/Chat%20on-Telegram-blue.svg)](https://t.me/PhotoboothGroup)  

[![Translate on Crowdin](https://img.shields.io/badge/Traslate%20on-Crowdin-green.svg)](https://crowdin.com/project/photobooth)  

[![Lint](https://github.com/andi34/photobooth/workflows/Lint/badge.svg?branch=stable2)](https://github.com/andi34/photobooth/actions?query=branch%3Astable2+workflow%3ALint)
[![gulp-sass](https://github.com/andi34/photobooth/workflows/gulp-sass/badge.svg?branch=stable2)](https://github.com/andi34/photobooth/actions?query=branch%3Astable2+workflow%3Agulp-sass)

## :heart_eyes: Features

- Works on Windows and Linux.
  - Under Windows [digiCamControl](http://digicamcontrol.com/) by Duka Istvan
    can be used to control the camera and to take pictures.
  - Under Linux [gPhoto2](http://gphoto.org/) is used to control the camera and
    to take pictures.
- Images are processed with GD.
- Photobooth caches all generated QR-Codes, Thumbnails and Prints.
- Updated [PhotoSwipe Gallery](https://github.com/andi34/PhotoSwipe)
- Standalone Gallery ([localhost/gallery.php](http://localhost/gallery.php)).
- Slideshow via Gallery or standalone Gallery at [localhost/slideshow](http://localhost/slideshow).
- Access login via [localhost/login](http://localhost/login) instead [localhost/login.php](http://localhost/login.php).
- Offline manual with settings explained at [localhost/manual](http://localhost/manual).
- Offline FAQ at [localhost/manual/faq.php](http://localhost/manual/faq.php).
- Disk usage page, access via admin panel or directly at [localhost/admin/diskusage.php](http://localhost/admin/diskusage.php).
- Settings can be changed via Admin Panel at ([localhost/admin](http://localhost/admin)):
  - Multi-language support:
    - [Translate on Crowdin](https://crowdin.com/project/photobooth)
  - Login to protect Admin Panel, Start page and/or Manual & FAQ can be enabled.
  - Hardware Button functionality, supports two separate modes of operation (select via admin panel):
    - **Button Mode**: Distinct hardware buttons can be connected to distinct GPIOs. Each button will trigger a separate functionality (i.e. take photo).
    - **Rotary Mode**: A rotary encoder connected to GPIOs will drive the input on the screen. This enables to use the rotary to scroll through the Photobooth UI buttons, and click to select actions.
  - Gallery:
    - Order pictures in gallery ascending oder descending by picture age.
    - Hide the gallery.
    - allow to adjust PhotoSwipe Gallery config via Adminpanel, also allow to use some PhotoSwipe functions and make more PhotoSwipe settings available (settings explained inside the manual):
      - Mouse click on image should close the gallery (enable/disable)
      - Close gallery if clicked outside of the image (enable/disable)
      - Close picture on page scroll (enable/disable)
      - Close gallery when dragging vertically and when image is not zoomed (enable/disable)
      - Show image counter (enable/disable)
      - Show PhotoSwipe fullscreen button (enable/disable)
      - Show PhotoSwipe zoom button (enable/disable)
      - PhotoSwipe history module (enable/disable)
      - Pinch to close gallery (enable/disable)
      - Toggle visibility of controls/buttons by tap (enable/disable)
      - allow to adjust PhotoSwipe background opacity (0-1)
      - Loop images (enable/disable)
      - Slide transition effect (enable/disable)
      - Swiping to change slides (enable/disable)
    - gallery: button to delete an image, enable by default
    - Choose thumbnail size:
      - XS = max 360px
      - S = max 540px
      - M = max 900px
      - L = max 1080px
      - XL = max 1260px"
  - Choose between date-formatted, numbered or random image names.
  - Choose an image filter after taking a picture.
  - QR-Code to allow downloading pictures from your Photobooth.
  - Pictures can be directly downloaded from the gallery.
  - Print feature:
    - Optional: Print a frame on your picture
    - Optional: Print text on your picture.
    - Optional: Print QR-Code on the right side of your picture.
    - Optional: Auto print function
    - Optional: allow to delay auto print
    - Optional: allow to adjust time "Started printing! Please wait..." is visible
    - Optional: allow to trigger print via defined key
    - Optional: options to show the print button independent (e.g. can be only visible on gallery)
  - Pictures can be sent via e-mail.
  - Different Live Preview options
  - Event specific (e.g. wedding, birthday) config to show a symbol (e.g. heart)
    between some text on the start page.
  - Chroma keying
  - Photo collage function: take pictures in a row with or without
    interruption and let it generate a collage out of it. Choose between different collage layouts!
  - Save pictures with a Polaroid effect.
  - Adjust take picture and print commands.
  - Adjust the style of Photobooth via admin panel.
- ... _And many more options to adjust and style Photobooth for your personal needs_

## :camera: Screenshots

![](https://raw.githubusercontent.com/wiki/andi34/photobooth/resources/img/start.png)

## :gear: Prerequisites

- gphoto2 installed, if used on a Raspberry for DSLR control
- digiCamControl, if used unter Windows for DSLR control
- Apache, NGINX or Lighttpd

## :wrench: Installation & Troubleshooting

Please follow the installation instructions in our
[Photobooth-Wiki](https://github.com/andi34/photobooth/wiki) to setup
Photobooth.

If you're having trouble or questions please take a look at our
[FAQ](https://github.com/andi34/photobooth/wiki#faq---frequently-asked-questions)
before opening a new issue.

## :globe_with_meridians: Browser support

[Click here](https://github.com/andi34/photobooth/wiki#browser-support) to find out if your Browser is supported.

### :mag: Changelog

Please take a look at the changelog in our [Photobooth Wiki](https://github.com/andi34/photobooth/wiki/changelog).

### :tada: Donation

If you like my work and like to keep me motivated you can buy me a coconut water:

[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.me/andreasblaesius)

### :mortar_board: Tutorial

[Raspberry Pi Weddingphotobooth (german)](https://www.andrerinas.de/tutorials/raspberry-pi-einen-dslr-weddingphotobooth-erstellen.html)

### :clap: Contributors and thanks to

- [dimsemenov](https://github.com/dimsemenov/photoswipe)
- [t0k4rt](https://github.com/t0k4rt/phpqrcode)
- [nihilor](https://github.com/nihilor/photobooth)
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
- [rawbertp](https://github.com/rawbertp)
- [jacques42](https://github.com/jacques42)
- [poldixd](https://github.com/poldixd)
- [TheVaan](https://github.com/TheVaan)
- [Andreas Remdt](https://andreasremdt.com)
- [philippselle](https://github.com/philippselle)
- [Natalie Stroud](https://github.com/stroudn1)
- [jarettrude](https://github.com/jarettrude)
- [alzo425](https://github.com/alzo425)
- [KH404](https://github.com/KH404)
- [joiyco](https://github.com/joiyco)
- [EccoB](https://github.com/EccoB)
- [couz74](https://github.com/couz74)
- [thatonedude3470](https://github.com/thatonedude3470)
- [Christian Tarne](https://github.com/Metropo)
- [DeNeD1](https://github.com/DeNeD1)
- [DIY89](https://github.com/DIY89)


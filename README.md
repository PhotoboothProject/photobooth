# Photobooth

A Photobooth web interface for Raspberry Pi and Windows.

Photobooth was initially developped by Andre Rinas, you can find his source [here](https://github.com/andreknieriem/photobooth).

This is my personal Photobooth fork with some extras on top (more information can be found [below](https://github.com/andi34/photobooth#extras-on-my-personal-fork)).

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
- Standalone Gallery ([localhost/gallery.php](http://localhost/gallery.php)).
- Slideshow via Gallery or standalone Gallery (under [localhost/slideshow](http://localhost/slideshow)).
- Settings can be changed via Admin Panel (under [localhost/admin](http://localhost/admin)):
  - Multi-language support
    - [Translate on Crowdin](https://crowdin.com/project/photobooth)
  - Login to protect Admin Panel and/or Start page can be enabled.
  - Gallery:
    - Order pictures in gallery ascending oder descending by picture age.
    - Hide the gallery.
  - Choose between date-formatted, numbered or random image names.
  - Choose an image filter after taking a picture.
  - QR-Code to allow downloading pictures from your Photobooth.
  - Pictures can be directly downloaded from the gallery.
  - Print feature.
    - Optional: Print a frame on your picture
    - Optional: Print text on your picture.
    - Optional: Print QR-Code on the right side of your picture.
  - Pictures can be sent via e-mail.
  - LivePreview (via device cam or from stream URL).
  - Event specific (e.g. wedding, birthday) config to show a symbol (e.g. heart)
    between some text on the start page.
  - Green screen keying (chroma keying).
  - Photo collage function: take 4 pictures in a row with or without
    interruption and let it generate a collage out of it.
  - Save pictures with a Polaroid effect.
  - Adjust take picture and print commands.
  - Adjust the style of Photobooth via admin panel.
  - And many more options to adjust and style Photobooth for your personal needs.

## Extras on my personal fork
### General changes:

  - javascript transpiled to es5 to support older browsers (e.g. Safari 9)
  - install-raspbian: use Apache2 webserver by default for a no-hassle setup
  - added Slideshow option to Gallery
  - standalone slideshow via [localhost/slideshow](http://localhost/slideshow)
  - access login via [localhost/login](http://localhost/login) instead [localhost/login.php](http://localhost/login.php)
  - offline manual with settings explained under [localhost/manual](http://localhost/manual) ([andi34/photobooth#59](https://github.com/andi34/photobooth/pull/59))
  - offline FAQ under [localhost/manual/faq.html](http://localhost/manual/faq.html)
  - disk usage page, access via admin panel or at [localhost/admin/diskusage.php](http://localhost/admin/diskusage.php)
  - fix windows compatibility
  - fix check for image filter
  - performance improvement ([andreknieriem/photobooth#226](https://github.com/andreknieriem/photobooth/pull/226))
  - Improved width of admin- and login-panel (partially [andreknieriem/photobooth#221](https://github.com/andreknieriem/photobooth/pull/221))
  - general bug-fixes if device cam is used to take pictures ([andreknieriem/photobooth#220](https://github.com/andreknieriem/photobooth/pull/220))
  - Remove unused resources/fonts/style.css
  - language: use correkt ISO 639-1 Language Code for Greek
  - Optimize picture size on result screen
  - blue-gray color theme by default
  - Admin panel: range slider and toggles
  - Switch to simple-translator for translations, use english as fallback language if a translation is missing. This also gives the possibility to easily translate Photobooth. [How to update or add translations?](https://github.com/andi34/photobooth/wiki/FAQ#how-to-update-or-add-translations)
  - Add database name to picture name if database changed from default name
  - Close opened picture if photo/collage is triggered
  - Only take Photos via defined key if we aren't already

### New Options:

  - Option to disable the delete button ([andreknieriem/photobooth#228](https://github.com/andreknieriem/photobooth/pull/228))
  - Show/Hide button to toggle fullscreen mode
  - Option to keep original images in tmp folder
  - Configurable image preview while post-processing
  - Adjustable time a image is shown after capture
  - Allow to rotate photo after taking
  - Optional EXIF data preservation (disabled by default)
  - define collage frame seperately ([andi34/photobooth#63](https://github.com/andi34/photobooth/pull/63))
  - event specific database: You can now rename the picture and email database via Admin panel. Only pictures inside the defined database are visible via gallery. ([andi34/photobooth#61](https://github.com/andi34/photobooth/pull/61))
  - Preview/Stream from device cam as background on start page ([andi34/photobooth#58](https://github.com/andi34/photobooth/pull/58))
  - Allow using a stream from URL at countdown for preview
  - Allow to rotate preview from URL
  - Auto reload Photobooth on error while taking a photo
  - Allow to change permissons on picture
  - qrHelp: define WiFi SSID used on QR via admin panel
  - Updated [PhotoSwipe Gallery](https://github.com/andi34/PhotoSwipe)
  - Show button bar inside gallery on bottom or on top of the image
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
  - Remote Buzzer Server based on io sockets
    - Enables a GPIO pin connected hardware button / buzzer for a setup where the display / screen is connected via WLAN / network to the photobooth webserver (e.g. iPad)
  - Choose thumbnail size:
    - XS = max 360px
    - S = max 540px
    - M = max 900px
    - L = max 1080px
    - XL = max 1260px"
  - Advanced printing functions [#109](https://github.com/andi34/photobooth/pull/109):
    - Auto print function
    - allow to delay auto print
    - allow to adjust time "Started printing! Please wait..." is visible
    - allow to trigger print via defined key
    - options to show the print button independent (e.g. can be only visible on gallery)
  - Advanced collage options [#108](https://github.com/andi34/photobooth/pull/108):
    - Choose collage layout:
      - 2x2
      - 2x4
      - 2x4 + background image
    - Collage: apply frame once after taking or to every picture of the collage

## :camera: Screenshots

![](https://raw.githubusercontent.com/wiki/andi34/photobooth/resources/img/start.png)

## :gear: Prerequisites

- gphoto2 installed, if used on a Raspberry for DSLR control
- digiCamControl, if used unter Windows for DSLR control
- NGINX, Lighttpd or Apache

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
- [Andreas Remdt](https://github.com/andreasremdt)
- [alzo425](https://github.com/alzo425)

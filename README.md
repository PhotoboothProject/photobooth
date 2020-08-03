# Photobooth

A Photobooth web interface for Raspberry Pi and Windows.

Photobooth was initially developped by Andre Rinas, you can find his source [here](https://github.com/andreknieriem/photobooth).

This is my personal Photobooth fork with some extras on top (more information can be found [below](https://github.com/andi34/photobooth#extras-on-my-personal-fork)).


![Lint](https://github.com/andi34/photobooth/workflows/Lint/badge.svg?branch=stable2)
![gulp-sass](https://github.com/andi34/photobooth/workflows/gulp-sass/badge.svg?branch=stable2)

**Please note:**

Safari Browser on iOS 9 in not compatible with es6, which means Photobooth won't work. Supported browser can be found inside the [Wiki](https://github.com/andi34/photobooth/wiki#browser-support).
If you like to use an old iPad anyway, please take a look [here (andi34/photobooth#47)](https://github.com/andi34/photobooth/issues/47).
If I find enough time I'll post some updates from time to time

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
  - Multi-language support:
    - German
    - English
    - Spanish
    - French
    - Greek
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

  - install-raspbian: use Apache2 webserver by default again
  - added Slideshow option to Gallery
  - standalone slideshow via [localhost/slideshow](http://localhost/slideshow)
  - access login via [localhost/login](http://localhost/login) instead [localhost/login.php](http://localhost/login.php)
  - offline manual with settings explained under [localhost/manual](http://localhost/manual) ([andi34/photobooth#59](https://github.com/andi34/photobooth/pull/59))
  - offline FAQ under [localhost/manual/faq.html](http://localhost/manual/faq.html)
  - fix windows compatibility
  - fix check for image filter
  - performance improvement ([andreknieriem/photobooth#226](https://github.com/andreknieriem/photobooth/pull/226))
  - Improved width of admin- and login-panel (partially [andreknieriem/photobooth#221](https://github.com/andreknieriem/photobooth/pull/221))
  - general bug-fixes if device cam is used to take pictures ([andreknieriem/photobooth#220](https://github.com/andreknieriem/photobooth/pull/220))
  - Remove unused resources/fonts/style.css
  - language: use correkt ISO 639-1 Language Code for Greek
  - Optimize picture size on result screen
  - Switch to blue-gray color theme by default
  - Admin panel: switch to range config and use toggles instead checkboxes
  - Switch to simple-translator for translations, use english as fallback langauage if a translation is missing. This also gives the possibility to easily translate Photobooth. [How to update or add translations?](https://github.com/andi34/photobooth/wiki/FAQ#how-to-update-or-add-translations)
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
  - Show button bar inside gallery on bottom or on top of the image
  - Auto reload Photobooth on error while taking a photo
  - Allow to change permissons on picture
  - qrHelp: define WiFi SSID used on QR via admin panel

## :camera: Screenshots

![](https://raw.githubusercontent.com/wiki/andi34/photobooth/images/start.png)

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

### :information_source: Donators Early Access

Donators who donated 5€ or more get early access to new features/options i am adding (please leave a message at donation telling your email address).

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

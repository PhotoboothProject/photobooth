# Photobooth by Andre Rinas

A Photobooth web interface for Raspberry Pi and Windows.

## :heart_eyes: Features

- Works on Windows and Linux.
  - Under Windows [digiCamControl](http://digicamcontrol.com/) by Duka Istvan
    can be used to control the camera and to take pictures.
  - Under Linux [gPhoto2](http://gphoto.org/) is used to control the camera and
    to take pictures.
- Images are processed with GD.
- Photobooth caches all generated QR-Codes, Thumbnails and Prints.
- Standalone Gallery (`localhost/gallery.php`).
- Settings can be changed via Admin Panel (under `localhost/admin`):
  - Multi-language support:
    - German
    - English
    - Spanish
    - French
    - Greek
  - Gallery:
    - Order pictures in gallery ascending oder descending by picture age.
    - Hide the gallery.
  - Choose between md5- or date-formatted image names.
  - Choose an image filter after taking a picture.
  - QR-Code to allow downloading pictures from your Photobooth.
  - Pictures can be directly downloaded from the gallery.
  - Print feature.
    - Optional: Print a frame on your picture
      (replace `resources/img/frames/frame.png` with a frame of your choice).
    - Optional: Print text on your picture.
    - Optional: Print QR-Code on the right side of your picture.
  - Pictures can be sent via e-mail.
  - LivePreview (uses device cam).
  - Event specific (e.g. wedding, birthday) config to show a symbol (e.g. heart)
    between some text on the start page.
  - Green screen keying (chroma keying).
  - Photo collage function: take 4 pictures in a row with or without
    interruption and let it generate a collage out of it.
  - Save pictures with a Polaroid effect.
  - Adjust take picture and print commands.
  - And many more options to adjust and style Photobooth for your personal needs.
  
## :camera: Screenshots

![](https://raw.githubusercontent.com/wiki/andreknieriem/photobooth/images/start.png)

## :gear: Prerequisites

- gphoto2 installed, if used on a Raspberry for DSLR control
- digiCamControl, if used unter Windows for DSLR control
- NGINX, Lighttpd or Apache

## :wrench: Installation & Troubleshooting

Please follow the installation instructions in our
[Photobooth-Wiki](https://github.com/andreknieriem/photobooth/wiki) to setup
Photobooth.

If you're having trouble or questions please take a look at our
[FAQ](https://github.com/andreknieriem/photobooth/wiki#faq---frequently-asked-questions)
before opening a new issue.

### :mag: Changelog

Please take a look at the changelog in our [Photobooth Wiki](https://github.com/andreknieriem/photobooth/wiki/changelog).

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

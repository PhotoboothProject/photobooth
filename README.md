
# Photobooth v4

![](resources/img/logo/banner.png)

A Photobooth web interface for Linux and Windows.

Photobooth was initially developped by Andre Rinas to use on a Raspberry Pi, you can find his source [here](https://github.com/andreknieriem/photobooth).  
In 2019 Andreas Blaesius picked up the work and continued to work on the source.  
With the help of the community Photobooth grew to a powerfull Photobooth software with a lot of features and possibilities.

_(The full changelog can be found on [https://photoboothproject.github.io](https://photoboothproject.github.io).)_

[![Chat on Telegram](https://img.shields.io/badge/Chat%20on-Telegram-blue.svg)](https://t.me/PhotoboothGroup)  

[![Translate on Crowdin](https://img.shields.io/badge/Traslate%20on-Crowdin-green.svg)](https://crowdin.com/project/photobooth)  

_Latest stable release:_  
[![Lint](https://github.com/PhotoboothProject/photobooth/workflows/Lint/badge.svg?branch=stable4)](https://github.com/PhotoboothProject/photobooth/actions?query=branch%3Astable4+workflow%3ALint)
[![gulp-sass](https://github.com/PhotoboothProject/photobooth/workflows/gulp-sass/badge.svg?branch=stable4)](https://github.com/PhotoboothProject/photobooth/actions?query=branch%3Astable4+workflow%3Agulp-sass)
[![Build](https://github.com/PhotoboothProject/photobooth/workflows/Build/badge.svg?branch=stable4)](https://github.com/PhotoboothProject/photobooth/actions?query=branch%3Astable4+workflow%3ABuild)

_Latest development version:_  
[![Lint](https://github.com/PhotoboothProject/photobooth/workflows/Lint/badge.svg?branch=dev)](https://github.com/PhotoboothProject/photobooth/actions?query=branch%3Adev+workflow%3ALint)
[![gulp-sass](https://github.com/PhotoboothProject/photobooth/workflows/gulp-sass/badge.svg?branch=dev)](https://github.com/PhotoboothProject/photobooth/actions?query=branch%3Adev+workflow%3Agulp-sass)
[![Build](https://github.com/PhotoboothProject/photobooth/workflows/Build/badge.svg?branch=dev)](https://github.com/PhotoboothProject/photobooth/actions?query=branch%3Adev+workflow%3ABuild)


## :camera: Screenshots

![](https://raw.githubusercontent.com/PhotoboothProject/PhotoboothProject.github.io/master/resources/img/start.png)


## :gear: Supported Platforms and Cameras

| Hardware-Platform  | Software-Platform                  | Supported Cameras                                                                                                                                                                     |
|--------------------|------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Raspberry Pi 3 / 4 | Raspberry Pi OS 64bit Bullseye     | [Camera Modules](https://www.raspberrypi.com/documentation/accessories/camera.html), [gphoto2 DSLR](http://www.gphoto.org/proj/libgphoto2/support.php), webcam _*2_ |
| Raspberry Pi 3 / 4 | Raspberry Pi OS 32bit Bullseye _*1_ | [Camera Modules](https://www.raspberrypi.com/documentation/accessories/camera.html), [gphoto2 DSLR](http://www.gphoto.org/proj/libgphoto2/support.php), webcam _*2_ |
| Generic PC         | Debian/Ubuntu                      | [gphoto2 DSLR](http://www.gphoto.org/proj/libgphoto2/support.php), webcam _*2_                                                                                      |
| Generic PC         | Windows                            | [digiCamControl](http://digicamcontrol.com/), webcam _*2_                                                                                                           |

_*1 On Raspberry Pi OS 32bit Bullseye you **must** add `arm_64bit=0` to your `/boot/config.txt` and reboot once before installing Photobooth._  
_The Raspberry Pi foundation uses a 64bit kernel while the system is 32bit. The Installation fails because the v4l2loopback module can't be compiled for a 32bit OS while using a 64bit kernel._

_*2 Capture from webcam is possible e.g. using [fswebcam](https://www.sanslogic.co.uk/fswebcam/), else it only works on access via [http://localhost](http://localhost)_


## :gear: Prerequisites

| Software          | Tested version         | Note
|-------------------|------------------------|-------------------------------------------------------------------------------------------------------------------------------------|
| Node.js           | v16                    | Currently only v16 is tested. Our installer will check your Node.js version and suggest an update/downgrade if needed. Versions below v16 aren't supported anymore. |
| php               | v7.4, v8.0, v8.1       |                                                                                                                                     |
| Webserver         |                        | Apache, Nginx or lighttpd is needed. By default Apache webserver is installed by our installer.                                     |


## :heart_eyes: Features

- Works on Windows and Linux.
- Images are processed with GD.
- Photobooth caches all generated QR-Codes, Thumbnails and Prints.
- Updated [PhotoSwipe Gallery](https://github.com/PhotoboothProject/PhotoSwipe)
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
  - Choose between date-formatted or random image names.
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

## :wrench: Installation & Troubleshooting

Please follow the installation instructions
[here](https://photoboothproject.github.io/INSTALL) to setup
Photobooth.

If you're having trouble or questions please take a look at our
[FAQ](https://photoboothproject.github.io/FAQ_MENU)
before opening a new issue.

For local testing and development, the docker setup can be used with `docker-compose up --build`.

### :mag: Changelog

Please take a look at the changelog available on [https://photoboothproject.github.io](https://photoboothproject.github.io).

### :warning: Security advice

Photobooth is not hardened against any kind of *targeted* attacks.
It uses user defined commands for tasks like taking photos and is allowed to replace its own files for easy updating.
Because of this it's not advised to operate Photobooth in an untrusted network and 
**you should absolutely not make Photobooth accessible through the internet without heavy modifications!**

### :copyright: License

Photobooth source is licensed under the MIT license.  
  
Once build, Photobooth incorporates several parts and optimizations that are covered by a different license which could apply to Photobooth as well.  
All dependencies include their respective LICENSE files.

### :tada: Donation

If you like our work and consider a donation, we have to tell you that we don't accept any money. We're happy about every contribution to this project and strive to make it better every day. Just get in touch with us on [Telegram](https://t.me/PhotoboothGroup) to say thank you or help us find ways to improve.

If you still want to donate money to make us happy: consider a donation to an organization that cares about (kids having) cancer. Our lead developer Andreas had blood cancer in 2001 (when he was 12 years old) and he's happy to be still alive.

One good choice of an organisation to donate to would be the [DKMS](https://www.dkms.de) (they also accept donations via [paypal](https://webforms.dkms.de/en/DE/moneydonation/paypal)), but any similar organisation would be fine, too.

Thanks for reading!

### :mortar_board: Tutorial

[Raspberry Pi Weddingphotobooth (german)](https://www.andrerinas.de/tutorials/raspberry-pi-einen-dslr-weddingphotobooth-erstellen.html)  
[Raspberry Pi Fotobox für Hochzeiten und Geburtstage (German)](https://www.dennis-henss.de/2020/01/25/raspberry-pi-fotobox-fuer-hochzeiten-und-geburtstage)  
[Raspberry Pi Photobooth in a classic vintage plate camera](https://florianmuller.com/raspberry-pi-photobooth-in-a-classic-vintage-plate-camera)  

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
- [mhellmeier](https://github.com/mhellmeier)
- [Uwe Pieper](https://github.com/up-87)
- [s-dinda](https://github.com/s-dinda)
- [Moarqi](https://github.com/Moarqi)
- [kreativmonkey](https://github.com/kreativmonkey)
- [Khaos66](https://github.com/Khaos66)
- [DJ DT-Sommer](https://dt-sommer.jimdofree.com)
- [ledsi](https://github.com/ledsi)
- [Spike-78](https://github.com/Spike-78)
- [Mathias Fiege](https://www.webpension.de/)


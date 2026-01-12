# Photobooth v4

![](resources/img/logo/banner.png)

A Photobooth web interface for Linux and Windows (often also called a "photo box" in some languages/regions).

Photobooth was initially developped by Andre Rinas to use on a Raspberry Pi, you can find his source [here](https://github.com/andreknieriem/photobooth).
In 2019 Andreas Blaesius picked up the work and continued to work on the source.
With the help of the community Photobooth grew to a powerfull Photobooth software with a lot of features and possibilities.

_(The full changelog can be found on [https://photoboothproject.github.io](https://photoboothproject.github.io).)_

[![Chat on Telegram](https://img.shields.io/badge/Chat%20on-Telegram-blue.svg)](https://t.me/PhotoboothGroup)

[![Translate on Crowdin](https://img.shields.io/badge/Traslate%20on-Crowdin-green.svg)](https://crowdin.com/project/photobooth)

_Latest development version:_
[![Lint](https://github.com/PhotoboothProject/photobooth/actions/workflows/linters.yaml/badge.svg)](https://github.com/PhotoboothProject/photobooth/actions/workflows/lint.yaml)
[![gulp-sass](https://github.com/PhotoboothProject/photobooth/actions/workflows/gulp_sass.yml/badge.svg)](https://github.com/PhotoboothProject/photobooth/actions/workflows/gulp_sass.yml)
[![Build](https://github.com/PhotoboothProject/photobooth/actions/workflows/build.yml/badge.svg)](https://github.com/PhotoboothProject/photobooth/actions/workflows/build.yml)

## :camera: Screenshots

![](https://raw.githubusercontent.com/PhotoboothProject/PhotoboothProject.github.io/master/resources/img/start.png)

## :gear: Supported Platforms and Cameras

| Hardware-Platform  | Software-Platform                  | Supported Cameras                                                                                                                                                                     |
|--------------------|------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Raspberry Pi 3 / 4 / 5 | Raspberry Pi OS 64bit Bookworm / Trixie    | [Camera Modules](https://www.raspberrypi.com/documentation/accessories/camera.html), [gphoto2 DSLR](http://www.gphoto.org/proj/libgphoto2/support.php), webcam _*1_ |
| Generic PC         | Debian/Ubuntu                      | [gphoto2 DSLR](http://www.gphoto.org/proj/libgphoto2/support.php), webcam _*1_                                                                                      |
| Generic PC         | Windows                            | [digiCamControl](http://digicamcontrol.com/), webcam _*1_                                                                                                           |

_*1 Capture from webcam is possible using third party software e.g. [fswebcam](https://www.sanslogic.co.uk/fswebcam/) on Linux, else it only works on access via [http://localhost](http://localhost)_

## :gear: Prerequisites

| Software  | Required version | Note                                                                                                                                                                |
| --------- | ---------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Node.js   | >=v20.15.0       | Currently only v20 is tested. Our installer will check your Node.js version and suggest an update/downgrade if needed. Versions below v20 aren't supported anymore. |
| npm       | >=v10.7.0        |                                                                                                                                                                     |
| php       | >=v8.4           |                                                                                                                                                                     |
| Webserver |                  | Apache or Nginx is needed. By default Apache webserver is installed by our installer.                                                                                 |

## :heart_eyes: Features

-   Works on Windows and Linux.
-   Images are processed with GD.
-   Gallery based on [PhotoSwipe v5](https://github.com/PhotoboothProject/PhotoSwipe)
-   Standalone Gallery based on PhotoSwipe v5 ([localhost/gallery](http://localhost/gallery)).
-   Standalone Slideshow based on PhotoSwipe v5 ([localhost/slideshow](http://localhost/slideshow)).
-   Access login via [localhost/login](http://localhost/login).
-   Offline manual with settings explained at [localhost/manual](http://localhost/manual).
-   Offline FAQ at [localhost/manual/faq](http://localhost/manual/faq).
- Disk usage page, access via admin panel or directly at [localhost/admin/diskusage](http://localhost/admin/diskusage) (
  or [localhost/photobooth/admin/diskusage](http://localhost/photobooth/admin/diskusage)).
- Settings can be changed via Admin Panel at ([localhost/admin](http://localhost/admin)) (or [localhost/photobooth/admin](http://localhost/photobooth/admin)):
    -   Multi-language support:
        -   [Translate on Crowdin](https://crowdin.com/project/photobooth)
    -   Login to protect Admin Panel, Start page and/or Manual & FAQ can be enabled.
    -   Hardware Button functionality, supports two separate modes of operation (select via admin panel):
        -   **Button Mode**: Distinct hardware buttons can be connected to distinct GPIOs. Each button will trigger a separate functionality (i.e. take photo).
        -   **Rotary Mode**: A rotary encoder connected to GPIOs will drive the input on the screen. This enables to use the rotary to scroll through the Photobooth UI buttons, and click to select actions.
    -   Gallery: allow to adjust the look and feel (settings explained inside the manual).
    -   Choose between date-formatted or random image names.
    -   Choose an image filter after taking a picture.
    -   QR-Code to allow downloading pictures from your Photobooth.
    -   Pictures can be directly downloaded from the gallery.
    -   Print feature:
        -   Optional: Print a frame on your picture
        -   Optional: Print text on your picture.
        -   Optional: Print QR-Code on the right side of your picture.
        -   Optional: Auto print function
        -   Optional: allow to delay auto print
        -   Optional: allow to adjust time "Started printing! Please wait..." is visible
        -   Optional: allow to trigger print via defined key
        -   Optional: options to show the print button independent (e.g. can be only visible on gallery)
    -   Pictures can be sent via e-mail.
    -   Different Live Preview options
    -   Event specific (e.g. wedding, birthday) config to show a symbol (e.g. heart)
        between some text on the start page.
    -   Chroma keying
    -   Photo collage function: take pictures in a row with or without
        interruption and let it generate a collage out of it. Choose between different collage layouts!
    -   Save pictures with a Polaroid effect.
    -   Adjust take picture and print commands.
    -   Adjust the style of Photobooth via admin panel.
-   ... _And many more options to adjust and style Photobooth for your personal needs_

## :wrench: Installation & Troubleshooting

Please follow the installation instructions
[here](https://photoboothproject.github.io/) to setup
Photobooth.

If you're having trouble or questions please take a look at our
[FAQ](https://photoboothproject.github.io/faq/)
before opening a new issue.

For local testing and development, the docker setup can be used with `docker compose up --build`.

### Local dev with DDEV (alternative to docker compose)

Use DDEV if you want an all-in-one local stack without touching your host PHP/Node toolchain (Docker Desktop/WSL2/macOS/Linux supported).

1. Install [DDEV](https://ddev.readthedocs.io/en/stable/) and Docker, then `ddev start` in the repo.
2. First run will install npm deps and run `npm run build` automatically; rerun manually with `ddev build` if needed.
3. Access the app at http://photobooth.ddev.site:9080 (HTTPS https://photobooth.ddev.site:9443).
4. Commands inside the web container: `ddev composer <cmd>` for PHP tools, `ddev npm <cmd>` for JS, `ddev qa` (composer cgl+lint+phpstan+phpunit), `ddev pre-commit` (eslint + full build + QA).
5. Helpers: GET request helper auto-starts on port 9100 (`ddev getserver` to restart); asset watcher auto-starts (`ddev npm run watch:gulp` / `watch:lint` to run manually).

Notes:

- Matches the docker compose image (PHP 8.4, Apache, Node 20).
- If DDEV isn’t installed, continue using `docker compose up --build` as documented above.
- Quick validation: after `ddev start`, open the site URL, take a test photo, check gallery/slideshow, and run `ddev qa`; if assets look stale, run `ddev build`.

### :mag: Changelog

Please take a look at the changelog available on [https://photoboothproject.github.io](https://photoboothproject.github.io).

### :warning: Security advice

Photobooth is not hardened against any kind of _targeted_ attacks.
It uses user defined commands for tasks like taking photos and is allowed to replace its own files for easy updating.
Because of this it's not advised to operate Photobooth in an untrusted network and
**you should absolutely not make Photobooth accessible through the internet without heavy modifications!**

### :copyright: License

Photobooth source is licensed under the MIT license.

Once build, Photobooth incorporates several parts and optimizations that are covered by a different license which could apply to Photobooth as well.
All dependencies include their respective LICENSE files.

### :tada: Donation

If you like our work and consider a donation, we have to tell you that we don't accept any money. We're happy about every contribution to this project and strive to make it better every day. Just get in touch with us on [Telegram](https://t.me/PhotoboothGroup) to say thank you or help us find ways to improve.

If you still want to donate money to make us happy: consider a donation to the [seal station Norddeich](https://seehundstation-norddeich.de) ([donate via Paypal](https://paypal.me/seehundstation)) or to the [DKMS](https://www.dkms.de/) ([donate via Paypal](https://www.paypal.com/DE/fundraiser/charity/3847251)) to provide blood cancer patients with a second chance at life.

Thanks for reading!

### :mortar_board: Tutorial

[Raspberry Pi Weddingphotobooth (german)](https://www.andrerinas.de/tutorials/raspberry-pi-einen-dslr-weddingphotobooth-erstellen.html)
[Raspberry Pi Fotobox für Hochzeiten und Geburtstage (German)](https://www.dennis-henss.de/2020/01/25/raspberry-pi-fotobox-fuer-hochzeiten-und-geburtstage)
[Raspberry Pi Photobooth in a classic vintage plate camera](https://florianmuller.com/raspberry-pi-photobooth-in-a-classic-vintage-plate-camera)

### :clap: Contributors and thanks to

-   [dimsemenov](https://github.com/dimsemenov/photoswipe)
-   [t0k4rt](https://github.com/t0k4rt/phpqrcode)
-   [nihilor](https://github.com/nihilor/photobooth)
-   [vrs01](https://github.com/vrs01)
-   [F4bsi](https://github.com/F4bsi)
-   [got-x](https://github.com/got-x)
-   [RaphaelKunis](https://github.com/RaphaelKunis)
-   [andi34](https://github.com/andi34)
-   [Norman-Sch](https://github.com/Norman-Sch)
-   [marcogracklauer](https://github.com/marcogracklauer)
-   [dnks23](https://github.com/dnks23)
-   [tobiashaas](https://github.com/tobiashaas)
-   Martin Kaiser-Kaplaner
-   [MoJou90](https://github.com/MoJou90)
-   [Reinhard Reberning](https://www.reinhard-rebernig.at/website/websites/fotokasterl)
-   [Steffen Musch](https://github.com/Nie-Oh)
-   [flighter18](https://github.com/flighter18)
-   [thymon13](https://github.com/thymon13)
-   [vdubuk](https://github.com/vdubuk)
-   [msmedien](https://github.com/msmedien)
-   [sualko](https://github.com/sualko)
-   [rawbertp](https://github.com/rawbertp)
-   [jacques42](https://github.com/jacques42)
-   [poldixd](https://github.com/poldixd)
-   [TheVaan](https://github.com/TheVaan)
-   [Andreas Remdt](https://andreasremdt.com)
-   [philippselle](https://github.com/philippselle)
-   [Natalie Stroud](https://github.com/stroudn1)
-   [jarettrude](https://github.com/jarettrude)
-   [alzo425](https://github.com/alzo425)
-   [KH404](https://github.com/KH404)
-   [joiyco](https://github.com/joiyco)
-   [EccoB](https://github.com/EccoB)
-   [couz74](https://github.com/couz74)
-   [thatonedude3470](https://github.com/thatonedude3470)
-   [Christian Tarne](https://github.com/Metropo)
-   [DeNeD1](https://github.com/DeNeD1)
-   [DIY89](https://github.com/DIY89)
-   [mhellmeier](https://github.com/mhellmeier)
-   [Uwe Pieper](https://github.com/up-87)
-   [s-dinda](https://github.com/s-dinda)
-   [Moarqi](https://github.com/Moarqi)
-   [kreativmonkey](https://github.com/kreativmonkey)
-   [Khaos66](https://github.com/Khaos66)
-   [DJ DT-Sommer](https://dt-sommer.jimdofree.com)
-   [ledsi](https://github.com/ledsi)
-   [vucubcaquix](https://github.com/vucubcaquix)
-   [Spike-78](https://github.com/Spike-78)
-   [Mathias Fiege](https://www.webpension.de/)
-   [Guillaume Roure](https://github.com/Ippephyx)
-   [Francesco Miccolis](https://github.com/fmiccolis)

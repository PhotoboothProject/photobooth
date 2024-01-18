# Changelog

## Upcoming Photobooth release

To use a preview of the upcoming Version you need to install the `Install last development version` using the `install-photobooth.sh` installer (now also works on all devices running debian / a debian based OS).  
Last development version is installed by default! You can check the commit history for changes made since your installation [here](https://github.com/PhotoboothProject/photobooth/commits/dev).  
An updated FAQ can always be found at [localhost/faq](http://localhost/faq).  

Please read the license notice [here](https://github.com/PhotoboothProject/photobooth/blob/dev/LICENSE_NOTICE).
<hr>

## 4.4.0 (09.01.2024)

**Breaking changes**

* requires Node.js v18.17.0 or newer
* requires npm 9.6.7 or newer

**General**

  * api: get rid of deleteTmpPhoto
  * cameracontrol.py: don't fail capturing images using Sony Cameras
  * result screen: reload after all files have been deleted
  * core: simplify print message handling
  * core: define tempImageUrl if and where needed
  * core: improve logging
  * api (takePic): only log return value issues on loglevel > 1
  * core/gallery: central QR modal handling
  * config: move preview bsm to commands section
  * preview: remove arrow functions
  * config(cleanup): remove unused success messages
  * api(previewCamera): use play or stop string instead true and false
  * preview: combine api.startWebcam and api.stopPreviewVideo functions
  * preview: adjust logging
  * preview: fix preview TEST mode
  * preview: fix checking for frame config
  * test(preview): fix preview with cmd
  * core(print): remove unneeded delay
  * optimized keyboard trigger
  * Remove fake buttons to trigger actions, use global functions instead
  * core: simplify check to.clear timeout
  * core: blur picture / collage buttons as needed
  * core: we already blur printBtn after print
  * livechroma: add notification via modal message on trigger via keyCode
  * livechroma: adjust keyCode trigger handling
  * core: adjust keyCode trigger handling
  * logging: adjust some console log messages
  * core: remove unneded focusSet on QR btn click
  * config: remove collage only config, allow to enable/disable picture instead
  * configsetup: define remotebuzzer server port first
  * lib(applyText): remove unneeded if-else statements
  * lib(applyText): add error handling, return unmodified resource on error
  * lib(applyText): throw Exception if text can't be applied
  * lib(applyFrame): add error handling, return unmodified resource on error
   * lib(applyFrame): throw Exceptions if needed
  * lib(polaroid): return unmodified resource on error
  * lib(polaroid): throw exceptions if needed
  * lib(resize): add try-catch-block to rotateResizeImage function
  * lib(resize): throw exceptions on rotateResizeImage if needed
  * lib(resize): add try-catch-block to resizeImage function
  * lib(resize): throw exceptions where needed on resizeImage
  * lib(resize): validate dimensions
  * lib(resize): add try-catch-block to resizePngImage function
  * lib(resize): validate dimensions on resizePngImage function
  * lib(resize): throw exceptions where needed on resizePngImage function
  * lib(resize): add try-catch-block to resizeCropImage function
  * lib(resize): validate dimensions on resizeCropImage function
  * lib(resize): try to clear cache on error
  * lib(resize): adjust check if resize was possible
  * lib (collage): only test file if needed
  * js(tools): add print image function to reuse
  * gulp: updated with ECMAScript Modules (ESM) syntax
  * task: bind docker do port 80 and 443
  * task: bump node version to v18 LTS and drop yarn
  * task: only limit node and npm version by minimum versions
  * package: switch to marked to formatt faq
  * remotebuzzer server: log earlier
  * remotebuzzer-server: update sanity check, only check if needed
  * remotebuzzer-server: allow GPIO to be any number [](https://github.com/PhotoboothProject/photobooth/pull/529)

**Bugfix**

  * fix: start shutter animation independent of cheese image
  * cameracontrol.py: don't fail capturing images using Sony Cameras
  * core: fix Test value for CameraDisplayMode
  * test preview: fix Test value for CameraDisplayMode
  * UI
    * UI: use highlight color for hover
    * gallery: respect button and font color
    * Prettier display delete status
    * ui: adjust modal transparency and font-weight
    * core: use own div for general different modal messages
    * delete: adjust delete notification messages
    * style: use secondary color for backgrounds where needed
    * task: fix colors while processing images [](https://github.com/PhotoboothProject/photobooth/pull/375)
    * ui(gallery): only show 3 images in a row on mobile phones
  * core: fix delete on collage with interruption
  * Check if images array is empty
  * Standalone Gallerie: add missing js file
  * core(fix): init PhotoSwipe after remoteBuzzer
  * api (takePic): only abort if file was not created
  * preview: respect offset to hide preview
  * preview: respect offset to hide preview from URL
  * slideshow: clear timeout to prevent from running timeout multiple times
  * remotebuzzer server: Determine whether or not GPIO access is possible
    * remotebuzzer server: fix checking for gpio config
  * welcome: use index.php instead of detected URL
  * api (admin): don't reset log on preview config error
  * core(preview): remove preview mode check if image is captured from preview
  * core: check for preview stream instead use of demo images
  * core(preview): stop preview on countdown slightly earlier
  * remotebuzzer server: fix for Node.js v16
  * submodules: ignore dirty state
  * core: hide result screen on reset
  * remotebuzzer client: fix log
  * Revert "stabilized rotary encoder handling"
  * remotebuzzer client: handle QR in gallery
  * remotebuzzer client: close qr modals directly if needed
  * added explicit int conversion
  * gallery: Collage only config was removed
  * sync-to-drive: fix for Node.js v16
    * Sync more Filetypes
  * lib(config): adjust print command for Windows
  * api (previewCamera): $config is not available, remove logging for now
  * remotebuzzer: respect config also on get request trigger
  * remotebuzzer: don't trigger a different action if disabled
  * remotebuzzer client: check if taking pictures is enabled
  * trigger.php: add missing options, respect hardware button config
  * lib (db): fix for PHP 8
  * api(admin): check if fonts, frames and collage placeholder are valid
  * core: clear/reset timeout on keyup [](https://github.com/PhotoboothProject/photobooth/pull/226)
  * lib (image): fix applying text to images, fail if text CAN NOT be applied
  * lib(image): don't keep aspect ratio while resizing PNG
  * bugfix: supprress error messages on getimagesize function
  * api(admin): image filter depends on images in tmp folder
  * fix hiding/showing home button independent of button bar on result screen
  * index (frame): respect preview frame config
  * core: don't add images to gallery if gallery is disabled
  * core: print & qr code only if enabled
  * bugfix: update dockerfile
    * add missing python3
  * lib(resize): fix rotating images on PHP8

**Feature**

  * config: make time adjustable a notification is visible
  * preview: allow to delay the visibility of the preview from URL
  * preview: allow execution of start/stop cmd without validation
    * config: run preview CMD's without validation by default
  * preview: run preview cmd & stop cmd independent of preview mode
  * remotebuzzer: make print and rotary functions available via get request
  * remotebuzzer: Reboot Button
  * remotebuzzer: make shutdown and reboot available via get request
  * task: enable lazy loading for gallery images [](https://github.com/PhotoboothProject/photobooth/pull/383)

**FAQ**

  * FAQ: add note to fix broken v4l2loopback module [](https://github.com/PhotoboothProject/photobooth/pull/109)
  * FAQ: add notes about issues while taking a picture
  * faq: a username is needed while running enable-usb-sync.sh
  * Updated FAQ - how to upload pictures to remote server [](https://github.com/PhotoboothProject/photobooth/pull/127)
  * FAQ: preview bsm was moved
  * FAQ: no need for success message
  * FAQ: add instructions to update gphoto2 & libgphoto
  * FAQ: adjust PiCamera information [](https://github.com/PhotoboothProject/photobooth/pull/417)
  * FAQ: Add possible preview error because of secure boot [](https://github.com/PhotoboothProject/photobooth/pull/437)
  * FAQ: adjust GPIO setup for PiOS Bookworm
  * FAQ: adjust GPIO information for Raspberry Pi 5; adjust for Pi OS versions

**Install**

  * install: adjust PolKit rule naming
  * install-photobooth: allow to add PolKit rule on all linux distros
  * install-photobooth: PolKit rule doesn't depend on update
  * install-photobooth: make system update optional on photobooth update
  * install-photobooth: fix permissions on yarn folder
  * install-photobooth: easy access to private folder
  * install-photobooth: always ignore filemode changes on git
  * install-photobooth: allow Node.js minor version to match or be newer
  * install-photobooth: be flexible on Node.js version
  * install: yarn: use of apt-key is deprecated
  * install-photobooth: use PHP 8.2 by default
  * install-photobooth: PHP8 sources needed earlier
  * install-photobooth: add https://packages.sury.org/php/ only if distro is supported
  * install: also run apt autoremove while removing nodejs
  * install: switch to latest development version of Gphoto2
  * install: fix PHP8 install in Ubuntu 20
  * install-photobooth: improve/fix PHP8 install on Ubuntu
  * install-photobooth: fix missing sources.lst on Ubuntu 22.04
  * install-photobooth: install npm on Raspberry Pi
    * bugfix: correct installer
  * install: add ppa for PHP on all Debian based distributions
  * install: run commands as www-data user
  * install: install needed packages for gphoto2 webcam service only if needed
  * install: use wget to check internet connection
  * install: update nodejs install
  * install: suppress most progress infos, only display essential output
  * install: fix permissions for www-data user on install
  * install: check for npm on all devices
  * install: remove npm from packages, uninstall libnode72 if installed
  * install: update npm to latest
  * install: fix Download of node-install (Raspberry Pi)
  * install: also check NODEJS_MINOR version
  * install: always install nodejs from official node source
  * install: only fail on Node.js below v18, recheck version if update is skipped
  * install: proof npm version, abort if needed
  * install: fix checking npm version
  * install: check for Firefox browser first
  * install: also check for firefox-esr
  * install-photobooth: remove autostart and hide mouse
    * installer: remove leftover from removed questions/features
  * install-photobooth: don't compile gphoto2 and libgphoto2 [](https://github.com/PhotoboothProject/photobooth/pull/439)
  * install: check if /boot/config.txt is a symlink [](https://github.com/PhotoboothProject/photobooth/pull/465)
  * install-photobooth: adjust Update for www-data user [](https://github.com/PhotoboothProject/photobooth/pull/498)
  * install-photobooth: install npm 9.6.7 if needed [](https://github.com/PhotoboothProject/photobooth/pull/531)

**Full Changelog**:

 [https://github.com/PhotoboothProject/photobooth/compare/v4.3.1...v4.4.0](https://github.com/PhotoboothProject/photobooth/compare/v4.3.1...v4.4.0)

<hr>

## 4.3.1 (06.12.2022)

**Bugfixes**

* core: improve timings while taking an image by @andi34 in [#95](https://github.com/PhotoboothProject/photobooth/pull/95)
  * Adjust the code to start taking an image before the Cheeeeeese message disappears 
  (the Cheese-Message can be used to "hide" the delay we have until the camera triggers).
  * Also adjust the code to respect the defined offset while taking an image. The take picture action now runs independent of the visible running countdown.
  * Stop the shutter animation after 500ms which should look more fluid and also make sure we can't forget to stop it. If cheese is enabled cheese time will be used instead.
  * Don't run shutter animation twice if an cheese image is used and cheese enabled.

**General**

* build(deps): bump sass from 1.55.0 to 1.56.1 by @dependabot in [#93](https://github.com/PhotoboothProject/photobooth/pull/93)
* build(deps-dev): bump @babel/preset-env from 7.19.4 to 7.20.2 by @dependabot in [#94](https://github.com/PhotoboothProject/photobooth/pull/94)
* build(deps-dev): bump eslint from 8.26.0 to 8.28.0 by @dependabot in [#92](https://github.com/PhotoboothProject/photobooth/pull/92)
* build(deps): bump socket.io from 4.5.3 to 4.5.4 by @dependabot in [#90](https://github.com/PhotoboothProject/photobooth/pull/90)
* build(deps-dev): bump @prettier/plugin-php from 0.19.1 to 0.19.2 by @dependabot in [#91](https://github.com/PhotoboothProject/photobooth/pull/91)

**Full Changelog**

 [https://github.com/PhotoboothProject/photobooth/compare/v4.3.0...v4.3.1](https://github.com/PhotoboothProject/photobooth/compare/v4.3.0...v4.3.1)

<hr>

## 4.3.0 (29.11.2022)

**Bugfixes**

* FAQ: fix small typo by @modularTaco in  [#68](https://github.com/PhotoboothProject/photobooth/pull/68)
* fix(documentation): remove reference to dev mode by @modularTaco in  [#71](https://github.com/PhotoboothProject/photobooth/pull/71)
* fix(gallery): respect clickToCloseNonZoomable PhotoSwipe config by @andi34 in  [#72](https://github.com/PhotoboothProject/photobooth/pull/72)
* fix(gallery): fix click on close button while an image is opened by @andi34 in  [#73](https://github.com/PhotoboothProject/photobooth/pull/73)
* install-photobooth: also check for "chromium" package by @andi34 in  [#86](https://github.com/PhotoboothProject/photobooth/pull/86)

**New Options**

* New options for the QR Code on print by @fmiccolis in  [#65](https://github.com/PhotoboothProject/photobooth/pull/65)
* Feature: make icons adjustable by @andi34 in  [#74](https://github.com/PhotoboothProject/photobooth/pull/74)
* Show frame over the preview by @fmiccolis in  [#45](https://github.com/PhotoboothProject/photobooth/pull/45)
* feature: add simple trigger ( [http://localhost/trigger.php](http://localhost/trigger.php) ) for remotebuzzer server by @andi34 in  [#66](https://github.com/PhotoboothProject/photobooth/pull/66)

**General**

* allow collage background images to be in any filetype gd understands by @up-87 in [#63](https://github.com/PhotoboothProject/photobooth/pull/63)
* core(navbar): restructure navbar function by @andi34 in [#64](https://github.com/PhotoboothProject/photobooth/pull/64)
* Restructure preview handling, add testpage for preview settings  ( [http://localhost/test/preview.php](http://localhost/test/preview.php) ) by @andi34 in  [#61](https://github.com/PhotoboothProject/photobooth/pull/61)
* make collage handling more configurable, fix collages and the retry mechanism by @up-87 in  [#69](https://github.com/PhotoboothProject/photobooth/pull/69)
* cleanup: remove experimental updater & dependencies checker by @andi34 in  [#84](https://github.com/PhotoboothProject/photobooth/pull/84)
* cleanup: api, also improve random image naming by @andi34 in  [#87](https://github.com/PhotoboothProject/photobooth/pull/87)
* crowdin: translation import by @andi34 in  [#88](https://github.com/PhotoboothProject/photobooth/pull/88)

**New Contributors**

* @fmiccolis made their first contribution in  [#65](https://github.com/PhotoboothProject/photobooth/pull/65)
* @modularTaco made their first contribution in  [#68](https://github.com/PhotoboothProject/photobooth/pull/68)

**Full Changelog**:

[https://github.com/PhotoboothProject/photobooth/compare/v4.2.0...v4.3.0](https://github.com/PhotoboothProject/photobooth/compare/v4.2.0...v4.3.0)

<hr>

## 4.2.0 (16.10.2022)

**Bugfixes**

* fix(admin): show 3rd line text of text on * in advanced view by @andi34 in [#52](https://github.com/PhotoboothProject/photobooth/pull/52)
* (fix) Stop shutter on fail, respect retry settings on fail by @andi34 in [#54](https://github.com/PhotoboothProject/photobooth/pull/54)
* api(takePic): fix flipping image taken from device cam preview, fix [Issue 55](https://github.com/PhotoboothProject/photobooth/issues/55) by @andi34 in [#57](https://github.com/PhotoboothProject/photobooth/pull/57)
* core(api.initializeMedia): retry every second by @andi34 in [#56](https://github.com/PhotoboothProject/photobooth/pull/56)
* tools(api.getRequest): don't use JSON.parse while getting a response
* install-photobooth: iputils-ping is needed to check Internet connection
* install-photobooth: fix placing Install log inside Photobooth folder

**General**

* build(deps): bump sass from 1.54.8 to 1.55.0 by @dependabot in [#47](https://github.com/PhotoboothProject/photobooth/pull/47)
* build(deps-dev): bump eslint from 8.23.0 to 8.24.0 by @dependabot in [#48](https://github.com/PhotoboothProject/photobooth/pull/48)
* build(deps-dev): bump @babel/preset-env from 7.18.10 to 7.19.3 by @dependabot in [#49](https://github.com/PhotoboothProject/photobooth/pull/49)
* build(deps-dev): bump @prettier/plugin-php from 0.18.9 to 0.19.1 by @dependabot in [#50](https://github.com/PhotoboothProject/photobooth/pull/50)
* build(deps-dev): bump @babel/core from 7.18.13 to 7.19.3 by @dependabot in [#51](https://github.com/PhotoboothProject/photobooth/pull/51)
* crowdin: translation import by @andi34 in [#58](https://github.com/PhotoboothProject/photobooth/pull/58)
* vendor(PHPMailer): update to v6.6.5 by @andi34 in [#59](https://github.com/PhotoboothProject/photobooth/pull/59)
* core(preview): remove unneeded checks on preview handling


**Full Changelog:**

[https://github.com/PhotoboothProject/photobooth/compare/v4.1.0...v4.2.0](https://github.com/PhotoboothProject/photobooth/compare/v4.1.0...v4.2.0)


<hr>

## 4.1.0 (30.09.2022)

**Bugfixes**

* fix(UI): don't run shutter animation twice by @andi34 in [#32](https://github.com/PhotoboothProject/photobooth/pull/32)
* fix(ui): add version tag to own stylesheets by @andi34 in [#33](https://github.com/PhotoboothProject/photobooth/pull/33)
* fix(ui): add version tag to own javascripts by @andi34 in [#36](https://github.com/PhotoboothProject/photobooth/pull/36)
* fix(admin): don't remove images from archives and private folder by @andi34 in [#42](https://github.com/PhotoboothProject/photobooth/pull/42)
* fix(admin): respect changed config before executing an reset by @andi34 in [#43](https://github.com/PhotoboothProject/photobooth/pull/43)

**New Options**

* feature(collage): add background to collage for easier template creation. closes [Issue #28](https://github.com/PhotoboothProject/photobooth/issues/28) by @up-87 in [#31](https://github.com/PhotoboothProject/photobooth/pull/31)
* feature(debugpanel): add access to installation log by @andi34 in [#37](https://github.com/PhotoboothProject/photobooth/pull/37)
* feature(ui): allow to define language resource path, also fix path for javascript folders on subfolder installation by @andi34 in [#38](https://github.com/PhotoboothProject/photobooth/pull/38)
* feature(preview): allow to rotate all preview options by @andi34 in [#44](https://github.com/PhotoboothProject/photobooth/pull/44)
* rework(preview): remove gphoto preview option by @andi34 in [#46](https://github.com/PhotoboothProject/photobooth/pull/46)

**General**

* lib(configsetup): make more settings available on basic view by @andi34 in [#35](https://github.com/PhotoboothProject/photobooth/pull/35)
* api(debug): log information from admin.php on config changes and reset by @andi34 in [#41](https://github.com/PhotoboothProject/photobooth/pull/41)
* crowdin: translation import by @andi34 in [#39](https://github.com/PhotoboothProject/photobooth/pull/39)


**Full Changelog:**

[https://github.com/PhotoboothProject/photobooth/compare/v4.0.0...v4.1.0](https://github.com/PhotoboothProject/photobooth/compare/v4.0.0...v4.1.0)


<hr>

## 4.0.0 (10.09.2022)

Source code moved to [https://github.com/PhotoboothProject/photobooth](https://github.com/PhotoboothProject/photobooth),  
old Releases etc. will still be available at [https://github.com/andi34/photobooth](https://github.com/andi34/photobooth)


**Security**
  - Security advice added to the README and welcome page [#376](https://github.com/andi34/photobooth/pull/376)
  - PHPMailer: update to v6.6.4

**Breaking changes**

  - QR code is now printed onto the image instead on the right side to not break the image ratio,  
    new options have been added for best user experience (see **New Options** for details)
  - Remove Greek, Polish and Spanish from Language options
    because they aren't maintained ([further information](https://github.com/andi34/photobooth/issues/64#issuecomment-1025126230))
  - Collage: layout's changed slightly (positions and image size now get calculated depending on defined dpi). Existing frames might not fit anymore and need to be updated.
  - vendor: remove rpihotspot from repo (it's still available on github, but we don't need it as a direct dependencie for Photobooth)

**Bugfixes**
  - chromium: fix white screen of death on first start in kiosk mode (Raspberry Pi only)
  - livechroma: fix text formatting on error/retry
  - api(takePic):
    - fix error message, take picture command can be anything
    - check if picture exists: If a picture exists already, rename it before using the same filename for a new picture. This might fix an issue on collage where a retaken image isn't saved / used and should also prevent overriding an existing image by accident.
  - fix print with QR Code
  - Video preview [#448](https://github.com/andi34/photobooth/pull/448), [#476](https://github.com/andi34/photobooth/pull/476):
    - Fix gphoto preview on retry / next collage image.
    - fix taking pictures from gphoto preview [#24](https://github.com/PhotoboothProject/photobooth/pull/24) (**Note:** Gphoto won't be used, it's more like taking a screenshot of the Preview. Since Gphoto won't be used there's no flash light of the camera!)
    - hide video preview in background of interrupted collage
    - fixed possible DSLR preview stop time bug
    - fixed bug: preview['flip'] was saved but not reloaded. So if the correct setting should be retained it needed to be set every time.
    - fixed bug: in the background of the continuos collage image preview the gphoto2 preview (without bsm) continued to run visibly
    - Improve preview handling [#6](https://github.com/PhotoboothProject/photobooth/pull/6)
  - core:
    - respect retry timeout:  
      The timeout should not be a new countdown, it should be a timeout as it's name says. Retry notification will now be visible for defined time. Countdown for picture/next collage image is not touched and will be used as defined.
    - fix get request at countdown
    - handle errors on get requests[#15](https://github.com/PhotoboothProject/photobooth/pull/15)
  - welcome.php: use detected URL to start Photobooth
  - FAQ: fix command to execute setup-network.sh for hotspot
  - frame/font: again allow to be located outside of photobooth source
  - config(print): add fallback to defaults if print font/frame is not defined
  - collage:
    - Continue collage with keypress [#408](https://github.com/andi34/photobooth/pull/408)
    - prevent caching of collage images with unique image name [#425](https://github.com/andi34/photobooth/pull/425)
    - use collage countdown timer on first collage image [#22](https://github.com/PhotoboothProject/photobooth/pull/22)
  - installation script:
    - fix permissions on www-data users cachefolder and .yarnrc if exists
    - fix and improve use of lighttpd and nginx [#477](https://github.com/andi34/photobooth/pull/477)
    - install `curl` (and `raspberrypi-kernel-headers` for PiOS) early enough to be used
  - stabilized rotary encoder handling [#449](https://github.com/andi34/photobooth/pull/449)
  - email:
    - fix wrong button text if "Store email addresses in file" is enabled [#462](https://github.com/andi34/photobooth/pull/462) (fixes [Issue #461](https://github.com/andi34/photobooth/issues/461))
    - stop spinning loader icon once the email has been sent (fixes [Issue #453](https://github.com/andi34/photobooth/issues/453))
  - language:
    - fix mixed up german translation for pre- and post-photo command (fixes [Issue #451](https://github.com/andi34/photobooth/issues/451))
  - PHP8:
    - fix Deprecated Passing null to parameter error
    - fix Implicit conversion from float (number) to int loses precision

**New Options**
  - Add traslate button to Adminpanel, opens Photobooth project on Crowdin
  - UI:
    - Shutter animation, enabled by default [#368](https://github.com/andi34/photobooth/pull/368)
    - add config to define highlight color used on round buttons (modern style)
    - make UI and button style independent [#442](https://github.com/andi34/photobooth/pull/442)
    - add modern squared design by @Moarqi [#440](https://github.com/andi34/photobooth/pull/440)
  - QR [#371](https://github.com/andi34/photobooth/pull/371):
    - Error correction level adjustable
  - Gallery:
    - Add config option to enable/disable the figure caption in the gallery view [#398](https://github.com/andi34/photobooth/pull/398)
    - Add config option to enable/disable action button footer (photo, collage)
  - Collage:
    - added simple 2+1 style that is to be used with a frame that fills the open space [#424](https://github.com/andi34/photobooth/pull/424)
    - Added an option to set a placeholder (eg custom image / graphic) for one image of the collage [#424](https://github.com/andi34/photobooth/pull/436)
    - simple customizable collage config with a json file [#26](https://github.com/PhotoboothProject/photobooth/pull/26)
  - Preview:
    - config: allow to adjust the time to stop the gphoto preview
    - choose to flip the preview along the X or Y axis [#465](https://github.com/andi34/photobooth/pull/465), fixup [#476](https://github.com/andi34/photobooth/pull/476)
    - specify how the preview should be resized to fit:  
      This setting uses the css _object-fit_ property which is used to specify how the preview should be resized to fit.  
      The following options are available:
      - _fill_ - The image is resized to fill the given dimension. If necessary, the image will be stretched or squished to fit.
      - _contain_ - The image keeps its aspect ratio, but is resized to fit within the given dimension.
      - _cover_ - The image keeps its aspect ratio and fills the given dimension. The image will be clipped to fit.
      - _none_ - The image is not resized.
      - _scale-down_- the image is scaled down to the smallest version of none or contain.
    - allow to set _background-size_ to _cover_ on URL preview
  - Print:
    - Always print the QR onto the image instead in the right side (see **Breaking Changes**)
    - QR size adjustable
    - QR offset adjustable
    - QR position adjustable
    - allow to disable rotation before print
  - Countdown:
    - allow using a customizable image instead of cheese message if shutter animation is used [#4](https://github.com/PhotoboothProject/photobooth/pull/4)
  - Post picture command:
    - Passes the Filename (doesn't include the full path!) to the _post-cmd_. This can be used e.g. to manipulate the Image (e.g. with Imagemagick) after the Picture was taken.

**General**
  - restore windows compatibility [#23](https://github.com/PhotoboothProject/photobooth/pull/23)
  - Import latest Crowdin translations
  - Cleanup core.js [#369](https://github.com/andi34/photobooth/pull/369):
    - remove unneeded if checks
    - improve readability
    - Let's start the picture process from beginning on retry.
      Also start all previews like defined at countdown.
    - Centralized preview start/stop functions, this helps getting a better overview of the code.
    - log error messages to console from api.errorPic
    - optimize error messages
    - time configurations moved to constants
    - move more ID selector to constants
  - Collage:
    - Collage code cleanup (can most likely still be improved by using php classes but this should be okay)
    - rename some collage styles (2+2 instead of 2x2 as x now always indicates pictures are reused - old name still works) [#424](https://github.com/andi34/photobooth/pull/424)
    - Collage Images will be dynamically scaled based on a given resolution (eg 300dpi etc)[#424](https://github.com/andi34/photobooth/pull/436)
  - configsetup:
    - add `<input type="number">` and use where possible to avoid issues on input
  - install script:
    - install-raspbian: rename to install-photobooth.sh, photobooth works fine on debian and other debian based distributions
    - pass options to the script (run `sudo bash install-photobooth.sh -h` to see all options)
    - ask to install Gutenprint drivers
    - make sure a new question is recognized
    - always mention the setup choice
    - adjust warning messages
    - fix git installation message
    - install latest development version via git by default (latest stable3 version can be passed to the installer via option)
    - prevent double entry in autostart while re-running the installer
    - allow to install gphoto2 Webcam Service 
    - create installation log inside `/tmp/photobooth`
    - always install latest stable gphoto2
    - adjust package array handling
    - Add new parameter to setup the php version [#477](https://github.com/andi34/photobooth/pull/477)
    - Webserver [#477](https://github.com/andi34/photobooth/pull/477):
      - Apply PHP Version for nginx and lighttpd
      - Fix (improve) nginx / lighttpd service startup
      - Fix client_max_body_size problems on nginx
    - detect installed webbrowser and create autostart file depending on it
      - The following browser can be detected: _chromium-browser_ , _google-chrome_ , _google-chrome-stable_ , _google-chrome-beta_ , _firefox_
    - skip Desktop related config if _lxde_ is not installed
    - avoid Chrome/Chromium asking for password to unlock keyring
    - run apt upgrade instead apt dist-upgrade:  
      Users might have come to rely on packages that were installed implicitly because of other (explicitly installed) packages' dependency on them. Thus the packaging system cannot be aware of a user/administrator desire to retain certain functionality that might otherwise be removed via dist-upgrade.
    - fix permission on hidden files and folder
    - seperate general and pi specific permissions
    - ignore filemode changes on git install
    - Check for internet connection (we can't install without an internet connection)
    - *allow to run an update on git installation:*
      - to update to latest development version please run `sudo bash install-photobooth.sh --update`  
      If local changes are detected, the installer can try to apply those changes after the update was completed. If the changes can not be applied after update, your local changes will be kept inside a backup branch and can be applied manually again (you might need to resolve conflicts because of source changes).  
      If local changes are detected, the installer will abort on silent install. You can re-run the update-process without the `-s` flag and confirm backing up your local changes for update.  
    - mention to clear Browser Cache on Update
  - enable-usb-sync.sh
    - Disable reboot for silent mode (fixes [Issue #450](https://github.com/andi34/photobooth/issues/450))
    - support other users (fixes [Issue #423](https://github.com/andi34/photobooth/issues/423))
  - gphoto:
    - Added another way to control dslr cameras with cameracontrol.py [#386](https://github.com/andi34/photobooth/pull/386), [#387](https://github.com/andi34/photobooth/pull/387), [#393](https://github.com/andi34/photobooth/pull/393)
  - Add a docker setup for local testing and development [#397](https://github.com/andi34/photobooth/pull/397)
  - Remote buzzer:
    - Add simple GET endpoints to trigger photos / collages (to support WiFi hardware buttons) [#400](https://github.com/andi34/photobooth/pull/400)
    - Rework option to use remotebuzzer server without GPIO and remove hidden faulty HID and softbutton implementation
  - UI:
    - sass(button): convert button size from px to em
    - sass(modern button): adjust font awesome icon size
    - Dark UI Style by @Moarqi
    - replace default background image
    - add clouds background images
  - Email:
    - hide checkbox to add email to database, adjust information text
  - debloat:
    - remove outdated update-booth.sh
  - Video preview:
    - minor change: don't display "please wait" text for interrupted collage when the selection is shown on screen [#476](https://github.com/andi34/photobooth/pull/476)
    - Improve preview handling [#6](https://github.com/PhotoboothProject/photobooth/pull/6)
  - sass/css:
    - video preview:
      - simplify rules
      - adjust position and video handling
  - scripts(pack-build):
    - remove unavailable files
    - add missing node_modules dependencies
  - admin:
    - hide experimental updater, instead use the _install-photobooth.sh_ to update
  - pass filename to post cmd [#7](https://github.com/PhotoboothProject/photobooth/pull/7)
  - Overhaul Photobooth logging [#14](https://github.com/PhotoboothProject/photobooth/pull/14)
  - more code-cleanup in various places


<hr>

## 3.3.0 (16.01.2022)

**Breaking changes**
  - If you are using an older version of Rasperry Pi OS or Debian / Debian based distribution make sure Node.js v12.22.x is installed!  
    Check your Node.js version while running `node -v` from your terminal.  
  - (config) Switch from milliseconds to seconds the image is visible on result screen  
    _Please adjust your configuration if you've changed the default setting on previous version. If you've not changed the default setting there's nothing to do._
  - vendor: phpqrcode as submodule
  - config: Webserver IP should not contain subfolder/subpages, IP should be detected if not defined. QR now needs it's own URL defined (see new Options).

**Bugfixes**
  - standalone slideshow: fix auto refresh
  - hide inner navigation panel if thrill is triggered from result page
  - remotebuzzer:
    - fix hang of remotebuzzer server on error
    - bugfix for hardware button to trigger collage mode [#351](https://github.com/andi34/photobooth/pull/351) (fixes [Issue #300](https://github.com/andi34/photobooth/issues/300))
    - fix socket.io Server on Photobooth subfolder installation [#364](https://github.com/andi34/photobooth/pull/364) (fixes [Issue #360](https://github.com/andi34/photobooth/issues/360))
  - picture and mail database always need a name, add fallback to default if empty
  - configsetup: add event option to basic view (fixes [Issue #320](https://github.com/andi34/photobooth/issues/320))
  - build: fix build failing on macOS (fixes [Issue #318](https://github.com/andi34/photobooth/issues/318))
  - Fix Typo in admin.php while using a custom style [#322](https://github.com/andi34/photobooth/pull/322)
  - Fix preview from gphoto as background if BSM is disabled (thanks to Uwe Pieper), **note:** This is not recommended for a Raspberry Pi as it requires faster hardware!

**New Options**
  - remotebuzzer:
    - Allow to configure GPIO debouce delay through admin panel [#294](https://github.com/andi34/photobooth/pull/294)
  - ui: add option to show / hide button bar on result screen
  - general: add config to use sample pictures instead taking a picture, dev-mode now only enables advanced logging for debugging purpose
  - add button for reboot and shutdown on linux
  - collage:
    - continuous collage: allow to disable single images being visible
    - allow to define collage background color [#324](https://github.com/andi34/photobooth/pull/324)
    - add option to add all images from collage to gallery [#307](https://github.com/andi34/photobooth/pull/307) (fixes [Issue #269](https://github.com/andi34/photobooth/issues/269))
    - add cutting lines on 2x4 collage layouts
  - feature: allow sending a GET request at countdown and/or after processing [#308](https://github.com/andi34/photobooth/pull/308)
  - text on {picture,collage,print}: use color picker - This gives the possibility to use any color instead choosing one out of three defined colors! [#312](https://github.com/andi34/photobooth/pull/312)
  - QR:
    - Add close button to QR [#316](https://github.com/andi34/photobooth/pull/316) (fixes [Issue #315](https://github.com/andi34/photobooth/issues/315))
    - Own QR menu entry [#325](https://github.com/andi34/photobooth/pull/325):
      - Enable/Disable QR-Code
      - Allow to define a own URL used for the QR-Code
        - Add fallback to default setting if not defined
      - Decide whether to append the filename to defined URL
      - Allow to define a own help text visible below the QR-Code
  - optional retry to take a picture on error [#366](https://github.com/andi34/photobooth/pull/366)

**General**
  - Add welcome screen on first access [#296](https://github.com/andi34/photobooth/pull/296), add config to skip by default
  - Add experimental Photobooth Updater and dependencies checker [#285](https://github.com/andi34/photobooth/pull/285)
  - install-raspbian.sh:
    - ask all questions before installing anything
    - allow silent installation (`sudo bash install-raspbian.sh WEBSERVER silent`)
    - don't delete INSTALLFOLDERPATH if exists, make a backup instead
    - inform about URL to access Photobooth
    - ask if remote access to CUPS should be enabled
    - install Node.js v12.22 if needed (for Debian buster compatibility)
  - update-booth.sh:
    - also copy hidden files and folder
  - adjust default chromium flags
  - build: add "clean" task
  - style: allow adjustments via `private/overrides.css` (automatically used if the file exist)
  - debugpanel: show latest git changes of installation
  - Add script to disable automount and add polkit rules for USB Sync (Only needed if you have declined the question to enable the USB sync file backup while running the `install-raspbian.sh` and like to use the USB Sync feature.):
```
wget https://raw.githubusercontent.com/andi34/photobooth/dev/enable-usb-sync.sh
sudo bash enable-usb-sync.sh
```
  - disabled version checker on dev branch
  - add tools.js with central access to common functions
  - Adjust and optimize different API endpoints
  - Updated build dependencies
  - general jquery improvements (thanks to Uwe Pieper)
  - retry getting preview via gphoto if failed (thanks to Uwe Pieper)
  - retry taking a picture if failed (thanks to Uwe Pieper)
  - crowdin: translation import
  - config: try to dectect Webserver IP if not defined

**FAQ**
  - adjust chromium flags
  - Raspberry Touchpanel DSI simultaneously with HDMI
  - How to administer CUPS remotely using the web interface?

<hr>

## 3.2.1

**Bugfixes**
- fix collage without filter/effect applied to single images

<hr>


[Compare changes with v3.2.0](https://github.com/andi34/photobooth/compare/v3.2.0...v3.2.1).

## 3.2.0

**Security**
- api: don't show mail password and sensible login data [#274](https://github.com/andi34/photobooth/pull/274)
- api: Bugfix for server-side node scripts to correctly parse the config file
- Temporary removed numbered image naming option to prevent overriding existing images. For details see [Issue 291](https://github.com/andi34/photobooth/issues/291).

**Bugfixes**
- sync-to-drive: bugfix for depreciated handling of type error - cannot read property of undefined
- collage: Apply defined effect(s) and/or filter to the single images instead of the final collage (partially [#290](https://github.com/andi34/photobooth/pull/290))
- core: new timeout only if no activity in progress [#273](https://github.com/andi34/photobooth/pull/273), fixes Issue [#272](https://github.com/andi34/photobooth/pull/272)

**New Options**
- countdown offset to compensate shutter-delay and cheese time [#286](https://github.com/andi34/photobooth/pull/286)
- Remote-buzzer:
  - allow to enable/disable rotary control for standalone gallery [#261](https://github.com/andi34/photobooth/pull/261)
  - allow parallel use of buttons- and rotary control [#262](https://github.com/andi34/photobooth/pull/262)

**General**
- Updated build dependencies
- Collage: Always show image after taken [#271](https://github.com/andi34/photobooth/pull/271), partially [#290](https://github.com/andi34/photobooth/pull/290)
- Debug Panel [#275](https://github.com/andi34/photobooth/pull/27) and better logging on issues while taking a picture [#277](https://github.com/andi34/photobooth/pull/277) and post-processing (partially [#290](https://github.com/andi34/photobooth/pull/290)):  
  Implements a panel for to help debugging in case of issues. Focus is to be able to access through the browser key configuration and log files on the server side.  
  This feature is  
  1) for fast and efficient debugging iterations  
  and  
  2) also well positioned to help people with less experience on the server administration and Unix / Raspberry Pi OS side of things.  

  Access to the debug panel is available through the admin panel (switch to expert view) or via direct URL [http://localhost/admin/debugpanel.php](http://localhost/admin/debugpanel.php).  
- Removed unneeded file-type checks all around the Photobooth api (we check for jpeg images already inside the api/applyEffects.php)
- result screen: smaller QR code & smaller font-size


[Compare changes with v3.1.0](https://github.com/andi34/photobooth/compare/v3.1.0...v3.2.0).

<hr>

## 3.1.0

**Bugfixes**
- fix horizontal flip of preview on some browser
- fix decore line config [#257](https://github.com/andi34/photobooth/pull/257)
- fix rotary button support for standalone gallery [#253](https://github.com/andi34/photobooth/pull/253)
- core: fix background from device cam
- login: protect Live keying if index is protected via login
- lang (en): fix delete request

**New Options**
- Pre- / Post- Command [#232](https://github.com/andi34/photobooth/pull/232):
  - execute a shell command before a picture is taken (pre-command)
  - execute a shell command after processing is is completed (post-command)
- Add HTML capability to E-Mail text [#231](https://github.com/andi34/photobooth/pull/231)
- Hide home button on results screen [#256](https://github.com/andi34/photobooth/pull/256)

**General**
- updated build dependencies
- hidden adminshortcut: direct to login panel
- login panel:
  - allow to access Live keying
  - add link to Telegram Community


[Compare changes with v3.0.0](https://github.com/andi34/photobooth/compare/v3.0.0...v3.1.0).

<hr>

## 3.0.0
A lot of changes have been applied to Photobooth! We're proud to tell that some bugs have been fixed and a lot of user wishes could be realized!  
We have added a lot of new options to make Photobooth adjustable for much more use cases.  
A big thanks goes to [jacques42@GitHub](https://github.com/jacques42) (who was involved a lot for this Release) and everyone who helped on making Photobooth this powerfull!  
Photobooth UI has changed to a modern look on most pages and our Admin panel and configuration setup has changed completely (please read the following Changelog).  

**Breaking changes**
- The configuration setup has changed completely on Photobooth v3 and some config options have been removed!
  **Please note:** Your old config (Photobooth v2.x and older) won't work, **you must** setup your configuration via [adminpanel](http://localhost/admin) again!

**Bugfixes**
- Chromakeying:
  - respect thumnail config
- Delete:
  - fix removing deleted images from database
- Translations:
  - fix translation fallback on all *.js files
- E-Mail:
  - fix small bug for mail subject and text templates to be applied
- Compatibility:
  - adjust default background URL setup to fix backgrounds on iOS (don't use relative path)
  - qr: also display correct url on subfolder installation [Fix #204](https://github.com/andi34/photobooth/issues/204)

**New Options**
- Standalone gallery:
  - continous check for new pictures [#121](https://github.com/andi34/photobooth/pull/121)
- Collage:
  - allow to deactivate standalone picture [Fix #155](https://github.com/andi34/photobooth/issues/155)
  - new collage layouts: 1+3, 1+3 (2), 1+2 & 2x2 (2)
  - remove use of background images, user should apply frames instead
  - test your collage settings accessing [localhost/test/collage.php](http://localhost/test/collage.php)
- Chroma keying:
  - Allow to switch between MarvinJ and Seriously.js algorithm for chroma keying [#123](https://github.com/andi34/photobooth/pull/123)
  - Seriously.js: use color picker to define keyed color, use Seriously.js by default [#213](https://github.com/andi34/photobooth/pull/213)
  - allow to define background path used for chroma keying, place your own backgrounds inside a subfolder of your Photobooth, e.g. inside `private/backgrounds` and define it via admin panel
  - added "live chroma keying" (choose a background -> take a picture -> get the keyed image with choosen background), access via [http://localhost/livechroma.php](http://localhost/livechroma.php) or use the config option to use it as default start page [#157](https://github.com/andi34/photobooth/pull/157)
  - Make imagesize for chromakeying adjustable
    - S = max 1000px
    - M = max 1500px (default like before)
    - L = max 2000px
    - XL = max 2500px
- Userinterface:
  - feature: Allow custom index, add new index layout by Mathias Fiege [#159](https://github.com/andi34/photobooth/pull/159)
  - allow to hide decore lines on start screen [Partially #165](https://github.com/andi34/photobooth/pull/165)
  - allow to hide title and subtitle on start screen [Partially #165](https://github.com/andi34/photobooth/pull/165)
- Backup:
  - Allow syncing of new pictures to USB device using rsync [#158](https://github.com/andi34/photobooth/pull/158)
- Preview:
  - Options "See preview by device cam" and "Preview from URL" have been replaced by a select menu
  - live preview from gphoto2 [#131](https://github.com/andi34/photobooth/pull/131)
- Athentication:
  - allow to protect FAQ and manual [#212](https://github.com/andi34/photobooth/pull/212)
  - allow to access login-protected pages without login on localhost access
- Database:
  - make database optional, add button to (re)generate database to admin panel [#203](https://github.com/andi34/photobooth/pull/203)
- Remotebuzzer:
  - Add rotary switch support [#202](https://github.com/andi34/photobooth/pull/202), [#221](https://github.com/andi34/photobooth/pull/221)
- What else:
  - allow delete of images without request [#215](https://github.com/andi34/photobooth/pull/215)
  - allow to take pictures right after the countdown, "Cheese" will be skipped [#129](https://github.com/andi34/photobooth/pull/129)
  - allow to flip image after taken [#209](https://github.com/andi34/photobooth/pull/209)
  - allow to add text to picture and/or collage [#210](https://github.com/andi34/photobooth/pull/210)

**General**
- User Interface:
  - Switch to modern styling by default
- Adminpanel
  - new adminpanel design [#162](https://github.com/andi34/photobooth/pull/162)
  - choose between `Basic View`, `Advanced View` and `Expert View`:
    - Basic View: Show config elements relevant for most simple and most common use-case. Default settings are largely sufficient. Maybe 20-30 % of all config options. The focus are entry-level user, who start to get their feet wet.
    - Advanced View: Features and elements used more often - i.e. Printing, Frames for Pictures, Chroma-Keying, etc. - maybe around 50% of all options on top. This should be sufficient for most of the users.
    - Expert View: Dev-Setting, Data folders, Commands, etc. - the remaining 20-30% of options are mapped to this view. Geeks right here.
  - Admin panel option to hide / show panel headings by Operating System
  - Allow to download data folder as zip from [http://localhost/admin/diskusage.php](http://localhost/admin/diskusage.php)
- Installation:
  - Installation [Instructions for Windows](https://github.com/andi34/photobooth/wiki/Installation-on-Windows) added to Wiki
  - install-raspbian.sh script:
    - Ask if a Raspberry Pi (HQ) camera is used, if yes setup personal config with needed changes
    - allow to install from all devices running debian/debian based OS [#181](https://github.com/andi34/photobooth/pull/181)
- Error handling:
  - api (applyEffects): check if GD library is loaded
  - check if frames and font are valid
- Lanugage support:
  - Add Italian to supported languages
- Collage:
  - Rotate collage images and final collage if needed (Fix [#156](https://github.com/andi34/photobooth/issues/156))  [#63](https://github.com/andi34/photobooth/pull/63)
  - Allow to retake a single picture on collage with interruption (Fix [#166](https://github.com/andi34/photobooth/issues/166))
- Remotebuzzer [#201](https://github.com/andi34/photobooth/pull/201), [#202](https://github.com/andi34/photobooth/pull/202):
  - replace gpio by onoff library
  - Add additional button support for collage, print and shutdown
- Code style:
  - Add prettier-php plugin (and slightly adjust prettier config for php files) to force one codestyle [#124](https://github.com/andi34/photobooth/pull/124)
- Robustness and improvements:
  - don't use relative paths for font, frames and background images
  - folders are always part of data folder (besides data folder itself and archives folder)
    - e.g. images folder config before: `data/images`  
      now: `images` (this will also point to `data/images`)
  - use 100% picture quality while processing images to not lower given configured jpeg quality for the final image
- Authentication:
  - handle login check earlier to protect other api endpoints [#205](https://github.com/andi34/photobooth/pull/205)

<hr>

## 2.10.0
**Bugfixes**
- check if we're already printing to avoid double printing
- deletePhoto: also delete keying and tmp pictures
- add back fallback to english if translation is missing

**New Options**
- allow to use thumnails for download
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

**General**
- reordered folder setup
- Crowdin translation import
- add Polish to supported languages

<hr>

## 2.9.0
**Bugfixes**
- fix saving images on chroma keying

**New Options**
- allow to adjust PhotoSwipe (Gallery) config via Adminpanel, also allow to use some PhotoSwipe functions and make more PhotoSwipe settings available (settings explained inside the manual):
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
- gallery: add button to delete an image, enable by default
- Remote Buzzer Server based on io sockets
  - Enables a GPIO pin connected hardware button / buzzer for a setup where the display / screen is connected via WLAN / network to the photobooth webserver (e.g. iPad).

**General**
- check for supported mime types on API files (print, chromakeying, applyEffects, deletePhoto)
- core/chromakeying: Handle print.php API errors
- Standalone slideshow & Gallery:
  - only use pictures if they exist and if they are readable
  - only use thumbnails if thumbnail exist and is readable, fallback to full-sized images if not
- gallery: update picture counter font-size
- Crop on print: set image quality to 100% by default
- added disk usage page, access via admin panel or at [localhost/admin/diskusage.php](http://localhost/admin/diskusage.php).
- Updated [PhotoSwipe](https://github.com/andi34/PhotoSwipe)
- added `private/` to .gitignore, can be used e.g. to store own background images
- install-raspbian.sh:
  - check if gvfs-gphoto2-volume-monitor exists
  - remove unneeded "sudo" on yarn installation
  - make sure webserver is defined
  - Add missing common "nodejs" package
  - allow to choose between stable and development version
- update build dependencies to it's latest versions
- Photobooth joined Crowdin as localization manager, [join here](https://crowdin.com/project/photobooth) to translate Photobooth

**Workflow**
- github: add pull request template
- github: don't allow empty issues, emojis to issue template names

<hr>

## 2.8.0
**Bugfixes**
- fix install-raspbian.sh
- add missing units for crop on print values (fixes [Issue #91](https://github.com/andi34/photobooth/issues/91))
- exit slideshow on close if running
- takeCollage: fix button size on small screens

**General**
- simple-translator updated to v2.0.2
- updated PHPMailer to latest version

**Behind the scenes**
- Add GitHub contribution doc
- run `yarn eslint` once changes to our JavaScripts get pushed to GitHub or if a Pullrequest contains changes on them
- run `gulp sass` once changes to our SCSS files get pushed to GitHub or if a Pullrequest contains changes on them

**New build pipeline and improved JavaScript**  
(special thanks to [Andreas Remdt](https://github.com/andreasremdt)) [#90](https://github.com/andi34/photobooth/pull/90)
  

- added Prettier to have consistent formatting for both JavaScript & SCSS
- Support older browser (should fix [Issue #47](https://github.com/andi34/photobooth/issues/47))
  - javascript transpiled to es5 to support older browsers (e.g. Safari 9)
  - use "whatwg-fetch" polyfill which should enable Safari 9 to use simple translator

<hr>

## 2.7.2
**Bugfixes**
- use htmlentities on input type configuration (allows to load config containing quotes)

**General changes**
- Handle -1 & 100% picture quality the same way

**Changed default config**
- 100% picture quality by default
- Don't print QR Code by default
- Allow collage by default
  - Use collage without interruption by default
- Show date below pictures inside Gallery by default
- Disable Chromakeying by default

<hr>

## 2.7.1
**Bugfixes**
- Fix taking photo collage

**General changes**
- simple-translator updated to v1.2.0

<hr>

## 2.7.0
**New options**
- Add option to use numbered image names
- Allow to change picture permissons while taking a photo
  - usefully if you e.g. like to delete pictures as different user

<hr>

**General changes**
- Add database name to picture name if database changed from default name
- Show "Photobooth Gallery" if using date formatted images but no date available
- Add rpihotspot repo as submodule:
  - FAQ contains instructions to turn Photobooth into a WIFI Hotspot

**Bugfixes**
- Fix "Cheeeeese" on Apple devices
- Fix loading language resources
- Only take Photos if we aren't already

<hr>

## 2.6.1
**Bugfixes**
- Fix video--sensor canvas
- Update Style for 5inch Display (800x480px)
- Attempt to fix taking Pictures and Collage via defined keys

<hr>

**General changes**
- Add offline FAQ, access directly via [http://localhost/manual/faq.html](http://localhost/manual/faq.html)
- Update jQuery to v3.5.1

<hr>

## 2.6.0
**New options**
- Automatically reload Photobooth if an error occurs while taking a photo/collage (enabled by default)

**Bugfixes**
- Fix FC on Standalone Gallery if a keycode is defined to take a photo/collage
- Close gallery if a keycode is defined to take a photo/collage

**General changes**
- update PHPMailer to latest version

<hr>

## 2.5.0
**New options**
- buttons inside gallery on bottom (can be put back on top via admin panel) [#66](https://github.com/andi34/photobooth/pull/66)
- define SSID used for QR on result page via admin panel [#70](https://github.com/andi34/photobooth/pull/70)

**Bugfixes**
- Fix Start Screen on devices with max-width @ 1024px [#68](https://github.com/andi34/photobooth/pull/68)

**General changes**
- install-raspbian: install recommended via git (easier update of Photobooth)
- mention personal fork additions inside README

<hr>

## 2.4.0
**New Options**
- offline manual with settings explained under [localhost/manual](http://localhost/manual) (https://github.com/andi34/photobooth/pull/59)
- define collage frame seperately (https://github.com/andi34/photobooth/pull/63)
- event specific database: You can now rename the picture and email database via Adminpanel. Only pictures inside the defined database are visible via gallery. (https://github.com/andi34/photobooth/pull/61)
- Preview/Stream from device cam as background on start page (https://github.com/andi34/photobooth/pull/58)

<hr>

## 2.3.3
**Bugfixes**
- qr code: no need to define width for the text

**General changes**
- index: remove unused "blurred" class
- Remove focus on "New Picture" and "New Collage" buttons
- update-booth.sh: delete old files if exist
- result screen: don't reload page after print

<hr>

## 2.3.2
**Bugfixes**
- chromakeying: add favicon, add apple meta tags

**New options**
- Allow to rotate preview from URL

**General changes**
- Bump jquery from 3.4.1 to 3.5.0 (fixes a security vulnerability)
- .gitignore changes:
  - config folder: ignore everything but not "config.inc.php"
  - ignore the whole css folder instead defining every .css seperately
- Down-sized QR code
- adjust countdown and cheese colors for default blue-gray theme

<hr>

## 2.3.1
**Bugfixes**
- Fix loading language files if Photobooth is installed in a subfolder

**General changes**
- add license files for node modules on packed builds
- Installer: Allow using latest prebuild package again

<hr>

## 2.3.0
**General changes**
- Switch to blue-gray color theme by default
- Admin panel: switch to range config and use toggles instead checkboxes
- Switch to `simple-translator` for translations, use english as fallback language if a translation is missing. This also gives the possibility to easily translate Photobooth. ( [How to update or add translations?](https://github.com/andi34/photobooth/wiki/FAQ#how-to-update-or-add-translations) )

**New Options**
- Show/Hide button to toggle fullscreen mode

**Bugfixes**
- Fix placeholder for preview from stream URL

<hr>

## 2.2.1
**New Options**
- Allow to rotate photo after taking
- Allow using a stream from URL at countdown for preview

**General changes**
- Remove unused resources/fonts/style.css
- language: use correkt ISO 639-1 Language Code for Greek
- Optimize picture size on result screen

<hr>

## 2.2.0
**General changes**
- install-raspbian: use Apache2 webserver by default again
- added Slideshow option to Gallery
- standalone slideshow [localhost/slideshow](http://localhost/slideshow)
- access login via [localhost/login](http://localhost/login) instead [localhost/login.php](http://localhost/login.php)
- fix windows compatibility
- fix check for image filter
- performance improvement (https://github.com/andreknieriem/photobooth/pull/226)
- Improved width of admin- and login-panel (partially https://github.com/andreknieriem/photobooth/pull/221)
- general bug-fixes if device cam is used to take pictures (https://github.com/andreknieriem/photobooth/pull/220)

**New options**
- Option to disable the delete button (https://github.com/andreknieriem/photobooth/pull/228)
- Option to keep original images in tmp folder
- Configurable image preview while post-processing
- Adjustable time a image is shown after capture
- Optional EXIF data preservation (disabled by default)

<hr>

## 2.1.0
**Optimize performance:**
- separate trigger and post-process task
- if possible use faster method to resize a picture

**Many new features and options added:**
- new options:
  - Make collage countdown timer adjustable
  - enable/disable real error messages
  - Allow setting a default filter
  - allow to disable filter
  - JPEG quality configurable
  - enable/disable download button in gallery
  - Allow defining a background via admin panel:
    This also gives the possibility to define a livestream URL (e.g. http://192.168.239.77:8081
    if motion is used ) to use a livestream as background.
  - Allow admins to choose what gets deleted at reset (inspired by https://github.com/andreknieriem/photobooth/issues/178)
    - always:
      - delete db.txt
    - optional (but enabled by default):
      - delete images
      - delete "mail-addresses.txt
      - delete personal config (my.config.inc.php)
  - Allow defining Photobooth web server IP to fix image download via QR-Code if Photobooth is accessed via localhost/127.0.0.1
  - Allow choosing a frame at take pic
  - Frames and font adjustable
  - allow protection of admin panel and index with password
  - allow using device cam to take pictures (save origin (localhost/127.0.0.1 if accessed on server, else HTTPS) needed!
  - define Photobooth colors using colorpicker
  - allow more elements to change color
  - allow defining default font size
  - optional rounded edges style
- admin panel style:
  - change weeding config to event config and add several new symbols to choose
  - own printer submenu
- Added raspi reset script
- allow to abort collage creation
- improve installation script
  - make kiosk mode optional
  - don't delete /var/www/html without request
  - use NGINX by default, optional allow to install Apache or Lighttpd
  - fix printer permissions and install CUPS by default

**General changes:**
- README: update formatting and cleanup
- Fix undefined placeholder warnings
- take picture: red error messages
- choose a filter after picture was taken instead before
- Display collage count before taking photo
- Handle take photo error cases

<hr>

## 2.0.2:
- fix saving of chroma keying results, style for chroma keying, style of gallery caption, datetime string on images without date info

<hr>

## 2.0.1:
- fix deletion of db file, fix config changes via admin settings

<hr>

## 2.0.0:
- Overhaul: reorganized all source files, completely overhaul coding
- New features: gallery standalone (localhost/gallery.php), add button to delete the picture after it was taken and displayed on the screen, change style via admin panel, add trigger keys via config, add option to force the use of a buzzer, add option to enable CUPS button, add option to resize and crop image by center at print, use same printing settings/options for chromakeying as for normal prints, take pictures for collage one after the other with or without interruption, add version checker to admin page, add greek, add option to specify data folder
- Some more bugfixes and improvements as usually

<hr>

## 1.9.0:
- Responsive Layout. Use relative paths to allow running Photobooth in a subfolder. Fix config.json being ignored on chromakeying. Adjustments on blue-gray theme. Some more small adjustments and bugfixes.

<hr>

## 1.8.3:
- Adjust scrollbar config and add blue-gray scrollbar theme, allow using Pi Cam for preview and to take pictures, add hidden shortcut for admin settings, add polaroid effect, add print confirmation dialogue

<hr>

## 1.8.2:
- Added spanish as supported language, print text on picture feature, optional blue-gray theme, adjust admin panel. Small bugfixes and improvements as always.

<hr>

## 1.8.1:
- Small bugfixes and improvements. New Features: enable/disable printing QR-Code, enable/disable photo collage function, enable/disable printing a frame on your picture

<hr>

## 1.8.0:
- Update jQuery, GSAP and PhotoSwipe to latest versions, add chroma keying feature (green screen keying)

<hr>

## 1.7.0:
- Add possibillity to choose an image filter before taking a picture

<hr>

## 1.6.3:
- Add config and instructions to use a GPIO Button to take a picture (address https://github.com/andreknieriem/photobooth/issues/10), translate sucess and error messages while sending images via mail

<hr>

## 1.6.2:
- Add wedding specific config, fix gallery settings not being saved from admin panel

<hr>

## 1.6.1:
- Add possibillity to disable mobile view, update Kiosk Mode instruction

<hr>

## 1.6.0:
- Button to send image via mail (uses [PHPMailer](https://github.com/PHPMailer/PHPMailer) ), add use of "my.config.inc.php" for personal use to prevent sharing personal data (e.g. E-Mail password and username) on Github by accident

<hr>

## 1.5.3:
- Several new options (disable gallery via config, set countdown timer via config, set cheeeese! Timer via config, ability to show the date/time in the caption of the images in the gallery), all config changes now available in admin page, complete french translation, add empty Gallery message, Fullscreen Mode on old iOS-Devices when starting photobooth from homescreen, StartScreen message is an option in config/admin page now, add instructions for Kiosk Mode, should fix #11, and #2, improve instructions in README, some more small Bugfixes and improvements. Merged pull-request #53 which includes updated pull-requests #52 & #54

<hr>

## 1.5.2:
- Bugfixing QR-Code from gallery and live-preview position. Merged pull #45

<hr>

## 1.5.1:
- Bugfixing

<hr>

## 1.5.0:
- Added Options page under /admin. Bugfix for homebtn. Added option for device webcam preview on countdown

<hr>

## 1.4.0:
- Merged several pull requests

<hr>

## 1.3.2:
- Bugfix for QR Code on result page

<hr>

## 1.3.1:
- Merged pull-request #6,#15 and #16

<hr>

## 1.3.0:
- Option for QR and Print Butons, code rework, gulp-sass feature enabled

<hr>

## 1.2.0:
- Printing feature, code rework, bugfixes

<hr>

## 1.1.1:
- Bugix - QR not working on touch devices

<hr>

## 1.1.0:
- Added QR Code to Gallery

<hr>

## 1.0.0:
- Initial Release

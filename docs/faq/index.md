# FAQ - Frequently asked questions

FAQ on [https://photoboothproject.github.io/](https://photoboothproject.github.io/) always matches latest development version of Photobooth. Some options might not be available on older version or might be handled different.

An FAQ matching your installed version can be found at [http://localhost/faq](http://localhost/faq).

## Is my Camera supported?

Some DSLR and Compact Cameras are not supported by this project. Please check for your specific model [here](http://gphoto.org/proj/libgphoto2/support.php).

---

## Is Pi Camera supported?

Yes it is.

If you're able to capture a picture via `raspistill` / `libcamera-still` / `rpicam-still` from terminal you're good to go!

You need to allow the webserver to use `raspistill` / `libcamera-still` / `rpicam-still`. You need add the webserver user to video group and reboot once:

```sh
sudo gpasswd -a www-data video
reboot
```

Once done you need to adjust the configuration. Open the admin panel in your browser [localhost/admin](http://localhost/admin) and make the following changes:

**"Take picture command on Pi OS based on bookworm":**

`rpicam-still -n -o %s -q 100 -t 1 | echo Done`

**"Take picture command on Pi OS based on bullseye":**

`libcamera-still -n -o %s -q 100 -t 1 | echo Done`

**"Take picture command on Pi OS based on buster":**

`raspistill -n -o %s -q 100 -t 1 | echo Done`

`raspistill` / `libcamera-still` / `rpicam-still` does not give any feedback after the picture was taken, workaround for that with "echo".

(Thanks to Andreas Maier for that information)

You've the possibility to add more parameters if needed (define ISO, exposure, white balance etc.). Type `raspistill -?` / `libcamera-still -?` / `rpicam-still -?` in your terminal to get information about possible parameters / settings.

---

## I've found a bug, how can I report?

Please take a look at the issue page [here](https://github.com/PhotoboothProject/photobooth/issues), if your bug isn't mentioned already you can create a new issue. Please give informations detailed as possible to reproduce and analyse the problem.

---

## I've a white page after updating to latest Source, how can I solve this?

On v1.9.0 and older:

It could be your local `config.json` file doesn't match latest source. This file is generated if you've used the admin panel to change your config.
Remove the file and try again!

```sh
sudo rm /var/www/html/admin/config.json
```

---

## How do I change the configuration?

Open `http://localhost/admin` in your Webbrowser and change the configuration for your personal needs.
Changed options are stored inside `config/my.config.inc.php` to prevent sharing personal data on Github by accident and to make an update of Photobooth easier.

---

## How to change the language?

Open `http://localhost/admin` in your Webbrowser and change the configuration for your personal needs.

---

## How to update or add translations?

Photobooth joined Crowdin as localization manager, [join here](https://crowdin.com/project/photobooth) to translate Photobooth.

Crowdin gives a nice webinterface to make translating easy as possible. If there's different translations for a string, translator can use the vote function on suggested translations.

With Crowdin and your help translating we're able to get high-quality translations for all supported languages. Also it's easy to support a wider range of languages!

Your language is missing? Don't worry, create a [localization request here](https://github.com/PhotoboothProject/photobooth/issues/new/choose) and we'll add it to the project.

---

## How can I test my current photo settings?

Open [http://localhost/test/photo.php](http://localhost/test/photo.php) in your Webbrowser and a you can find a photo that is created with your current settings.

---

## How can I test my current collage settings?

Open [http://localhost/test/collage.php](http://localhost/test/collage.php) in your Webbrowser and a you can find a collage that is created with your current settings.

---

## How can setup a custom collage design?

In the collage settings you can select the layout `private/collage.json`. This references a file with the given name in the photobooth's `private` folder. This file could be created manually or by using the collage generator at [http://localhost/admin/generator/index.php](http://localhost/admin/generator/index.php) via the button next to it.

Photobooth config **must** be saved again via Admin panel after `private/collage.json` was modified!

Content of the file is an array of arrays. The outer array defines the number of images, the inner array defines the horizontal position, vertical position, width, height, rotation and add frame (in that order) of one image.
For calculation of the values the variables x and y get converted to the width and height of the collage respectively, additionally math operations +, -, \*, / and () can be used to calculate values.
The following example should look exactly like the 1+2 layout with only the first and the third image framed (this layout looks more complicated than it is due to the decimal places).

```
[
[ "0",                     "y * 0.055",           "1.5 * y * 0.55546",   "y * 0.55546",   "10",         true       ],
[ "x * 0.555",             "y * 0.055",           "1.5 * y * 0.40812",   "y * 0.40812",   "0",          false      ],
[ "x * 0.555",             "y * 0.5368",          "1.5 * y * 0.40812",   "y * 0.40812",   "0",          true       ]
]
```

```
[ "horizontal position",   "vertical position",   "width",               "height",        "rotation",   "apply frame" ]
```

Please note that if the number of images in a collage design was changed the admin page has to be saved again to calculate the correct number of photos to be used for a collage.
Other value changes can be checked on the collage test page immediately with a simple reload - so it's quite easy to configure a layout with the help of [http://localhost/test/collage.php](http://localhost/test/collage.php).
The file `collage.json` needs to be a well-formed json array and something like a missing quotation or a trailing comma can be enough to make a design fail.

If you want to configure additional properties of the collage layout you have to wrap this array in a json object like this (**requires Photobooth v4.99 or newer**):

```
{
  "width": "1800",
  "height": "1200",
  "text_custom_style": true,
  "text_font_size": "50",
  "text_rotation": "10",
  "text_locationx": "200",
  "text_locationy": "220",
  "text_font_color": "#420C09",
  "text_font": "/resources/fonts/GreatVibes-Regular.ttf",
  "text_line1": "This is",
  "text_line2": "a",
  "text_line3": "Custom Collage",
  "text_linespace": "100",
  "apply_frame": "once",
  "frame": "/resources/img/frames/frame_stone.png",
  "background": "/resources/img/background.png",
  "background_color": "#FFFFFF",
  "placeholder": true,
  "placeholderpath": "/resources/img/background/01.jpg",
  "placeholderposition": "1",
  "layout": [ # the array from above ]
}
```

`width` and `height` (optional) can be defined in pixel to change the resolution of the collage. **Note:** both dimensions must be defined, else the default collage configuration will be used.
`text_custom_style` set to `true` if you want to apply text, `false` to disable. The following properties can override the config from Adminpanel, else the text on collage configuration will be used from Photobooth:

-   `text_font_size`
-   `text_rotation`
-   `text_locationx`
-   `text_locationy`
-   `text_font_color`
-   `text_font` (real path to font)
-   `text_line1` (text, can also be empty)
-   `text_line2`(text, can also be empty)
-   `text_line`(text, can also be empty)
-   `text_linespace`

`frame` (optional) the real path to the frame to be used, else the default collage frame configuration will be used.
`apply_frame` needed if `frame` is defined. Needs `once` or `always` to be defined, else the default collage configuration will be used.
`background` (optional) the real path to the background image to be used.

The single framed images work only if the property `apply_frame` is set to `always` otherwise it will not apply any frame (when it's set to `off`) or apply only once (when it's set to `once`).

The `placeholder` works just like the property in the admin: if enabled the image set into `placeholderpath` will be applied at the position set into `placeholderposition`.

---

## How to keep pictures on my Camera using gphoto2?

Add `--keep` (or `--keep-raw` to keep only the raw version on camera) option for gphoto2 via admin panel:

```sh
gphoto2 --capture-image-and-download --keep --filename=%s
```

On some cameras you also need to define the capturetarget because Internal RAM is used to store captured picture. To do this use `--set-config capturetarget=X` option for gphoto2 (replace "X" with the target of your choice):

```sh
gphoto2 --set-config capturetarget=1 --capture-image-and-download --keep --filename=%s
```

To know which capturetarget needs to be defined you need to run:

```sh
gphoto2 --get-config capturetarget
```

Example:

```
pi@raspberrypi:~ $ gphoto2 --get-config capturetarget
Label: Capture Target
Readonly: 0
Type: RADIO
Current: Internal RAM
Choice: 0 Internal RAM
Choice: 1 Memory card
```

---

## My external flash is not working after using the live preview

The reason for this might be that the camera is still in PC mode.

Try setting the output setting to `Off` in your capture command,
for example like this:

```bash
gphoto2 --set-config output=Off --capture-image-and-download
```

---

## Chromakeying is saving without finishing saving

Checking the browser console you'll see a `413 Request Entity Too Large` error. To fix that you'll have to update your nginx.conf

Follow the steps mentioned here: [How to Fix NGINX 413 Request Entity Too Large Error](https://datanextsolutions.com/blog/how-to-fix-nginx-413-request-entity-too-large-error/)

---

## Can I use Hardware Button to take a Picture?

Yes, there's different ways!

### Key code using connected HID devices

An HID device connected to your hardware can trigger different actions on your device. The HID device must be connected to the device you're accessing Photobooth from!

For example use <a href="https://keycode.info" target="_blank">https://keycode.info</a> to find out the key id of the button you like to use.

-   Related configuration:

    **PICTURE section**:

    -   Key code which triggers a picture: **define**

    **COLLAGE section**:

    -   Key code which triggers a collage: **define**

    **PRINT section**:

    -   Key code which triggers printing: **define**

### Remotebuzzer Hardware Button & LED feature using GPIO connected hardware

GPIO support was removed in September 2025. With PiOS Bookworm the used sysfs became deprecated which makes the use impossible with its current implementation.

You can still use the trigger via Socket.io or simple web requests (see below).

### Remote trigger using Socket.io

The trigger server controls and coordinates sending commands via socket.io to the photobooth client. Next to a hardware button, any socket.io client can connect to the trigger server over the network, and send a trigger command. This gives full flexibility to integrate other backend systems for trigger signals.

-   Channel: `photobooth-socket`
-   Commands: `start-picture`, `start-collage`, `collage-next`, `start-custom`, `start-video`, `print`, `rotary-cw`, `rotary-ccw`, `rotary-btn-press`, `move2usb`
-   Response: `completed` will be emitted to the client, once photobooth finished the task

### Remote trigger using simple web requests

_Note: This feature depends on the Socket.io implementation and needs option `Hardware Button` - `Enable Hardware Buttons` to be active and `Hardware Button` - `Remote buzzer Server IP`  must be defined ._

Photobooth can start the Remotebuzzer server and does not depend on a different device running the Socket.io Server.

To start the Remotebuzzer server with Photobooth, you must enable `Hardware Button` - `Start remote buzzer Server` from Adminpanel.

If Photobooth is running the implemented Remotebuzzer server, simple `GET` requests can be used to trigger different actions. Those endpoints can be found under `http://[Hardware Button Server IP]:[Hardware Button Server Port]` where:

-   `[Hardware Button Server IP]` needs to match the value from `Hardware Button` - `Remote buzzer Server IP` (same IP as your Photobooth) and
-   `[Hardware Button Server Port]` the value from `Hardware Button` - `Enable Hardware Buttons`

The available endpoints of Photobooths Remotebuzzer server, depending on enabled features and hardware button options, are:

-   `[Base Url]/` - Simple help page with all available endpoints
-   `[Base Url]/commands/start-picture` - Triggers a single picture
-   `[Base Url]/commands/start-collage` - Triggers a collage
-   `[Base Url]/commands/start-custom` - Triggers custom button action
-   `[Base Url]/commands/start-print` - Triggers print
-   `[Base Url]/commands/start-video` - Triggers a video capture
-   `[Base Url]/commands/reboot-now` - Triggers reboot command
-   `[Base Url]/commands/shutdown-now` - Triggers shutdown command
-   `[Base Url]/commands/rotary-cw` - Focus next element
-   `[Base Url]/commands/rotary-ccw` - Focus previous element
-   `[Base Url]/commands/rotary-btn-press` - Triggers a click action
-   `[Base Url]/commands/start-move2usb` - Trigger picture move to USB

These trigger URLs can be used for example with [myStrom WiFi Buttons](https://mystrom.com/wifi-button/) or [Shelly Buttons](https://shelly.cloud/products/shelly-button-1-smart-home-automation-device/), but also using a ESP32/ESP8266 or other micro controllers like the Raspberry Pi Pico / Pico W is possible.

### Setup a ESP32 / ESP8266 for simple web request
Example Projects:

- [https://github.com/PhotoboothProject/photobooth-ino](https://github.com/PhotoboothProject/photobooth-ino) (requires Photobooths Remotebuzzer Server to be running, contributions welcome)

### Setup an Raspberry Pi Pico / Pico W for simple web request
Example Projects:

- [https://github.com/frogro/PhotoboothProject_Pico_as_HID_Button_and_rotary_encoder](https://github.com/frogro/PhotoboothProject_Pico_as_HID_Button_and_rotary_encoder)
- [https://github.com/frogro/PhotoboothProject_Pico_W_as_remote_button_and_rotary_encoder](https://github.com/frogro/PhotoboothProject_Pico_W_as_remote_button_and_rotary_encoder)

### Installation steps for myStrom WiFi Button

-   Be sure to connect the button to the same network as the photobooth
-   The button can be configured using the following commands
    ```sh
    curl --location -g --request POST http://[Button IP]/api/v1/action/single --data-raw get://[Photobooth IP]:[Hardware Button Server Port]/commands/start-picture
    curl --location -g --request POST http://[Button IP]/api/v1/action/long --data-raw get://[Photobooth IP]:[Hardware Button Server Port]/commands/start-collage
    ```

### Remotebuzzer trouble shooting

#### Important Notes
Using the Remotebuzzer feature takes effect at the same time on all devices accessing Photobooth! If you trigger a picture, every connected client gets the signal to capture.

Works if you access Photobooth via [http://localhost](http://localhost) or [http://your-ip-adress](#), but accessing via the loopback IP (127.0.0.1) does not work!

#### Debugging
- Set Photobooth loglevel to 1 (or above). (admin panel -> general section) and save your configuration
- Reload the Photobooth homepage
- Check the browser developer console for error logs
- Check the server logs for errors at the Debug panel: [http://localhost/admin/debugpanel](http://localhost/admin/debugpanel)

---

## How do I enable Kiosk Mode to automatically start Photobooth in full screen?

### Autostart on Pi OS Bookworm

Since late 2024 Wayland/labwc is the default on all new installations of RPiOS with desktop. Add to the user specific autostart file location: `~/.config/labwc/autostart`, though it doesn't exist by default. If there is a section [autostart] already, just add the line chromium --... otherwise insert the complete section.

```
[autostart]
chromium --kiosk --disable-features=Translate --noerrdialogs --disable-infobars --no-first-run --ozone-platform=wayland --touch-events=enabled --start-maximized http://localhost
```

If you use Wayland/Wayfire modify `~/.config/wayfire.ini` as stated.

```
[autostart]
chromium = chromium-browser --kiosk --disable-features=Translate --noerrdialogs --disable-infobars --no-first-run --ozone-platform=wayland --touch-events=enabled --start-maximized http://localhost
```

### Autostart on Pi OS Bullseye and prior

Add the autostart file:

```sh
sudo nano /etc/xdg/autostart/photobooth.desktop
```

now add the following lines:

```
[Desktop Entry]
Version=1.3
Terminal=false
Type=Application
Name=Photobooth
Exec=chromium-browser --noerrdialogs --disable-infobars --disable-features=Translate --no-first-run --check-for-update-interval=31536000 --kiosk http://localhost --touch-events=enabled --use-gl=egl
Icon=/var/www/html/resources/img/favicon-96x96.png
StartupNotify=false
Terminal=false
```

save the file.

**NOTE:**

If you have installed Photobooth inside a subdirectory (e.g. to `/var/www/html/photobooth`), make sure you adjust the kiosk url (e.g. to `http://localhost/photobooth`) and the Icon path (e.g. to `/var/www/html/photobooth/resources/img/favicon-96x96.png`).

The flag `--use-gl=egl` might only be needed on a Raspberry Pi to avoid a white browser window on the first start of kiosk mode! If you're facing issues while using Photobooth on a different device, please remove that flag.

---

## How to hide the mouse cursor, disable screen blanking and screen saver?

**Note:** Applications like _unclutter_ don't work on Wayland!

### Pi OS trixie

To hide the mouse cursor we can rename the icon to hide it:
```
sudo mv /usr/share/icons/PiXtrix/cursors/left_ptr /usr/share/icons/PiXtrix/cursors/left_ptr.bak
```

To make the mouse cursor visible again we need to rename it back to it's original name:
```
sudo mv /usr/share/icons/PiXtrix/cursors/left_ptr.bak /usr/share/icons/PiXtrix/cursors/left_ptr
```

### Pi OS bookworm

To hide the mouse cursor we can rename the icon to hide it:
```
sudo mv /usr/share/icons/PiXflat/cursors/left_ptr /usr/share/icons/PiXflat/cursors/left_ptr.bak
```

To make the mouse cursor visible again we need to rename it back to it's original name:
```
sudo mv /usr/share/icons/PiXflat/cursors/left_ptr.bak /usr/share/icons/PiXflat/cursors/left_ptr
```

### Pi OS Bullseye and X11 environment

There are two options to hide the cursor on Pi OS Bullseye and prior. The first approach allows you to show the cursor for a short period of time (helpful if you use a mouse and just want to hide the cursor of some time of inactivity), or to hide it permanently.

#### Solution A

To hide the Mouse Cursor we'll use "unclutter":

```sh
sudo apt-get install unclutter
```

Edit the LXDE Autostart Script:

```sh
sudo nano /etc/xdg/lxsession/LXDE-pi/autostart
```

and add the following lines:

```
# Photobooth
# turn off display power management system
@xset -dpms
# turn off screen blanking
@xset s noblank
# turn off screen saver
@xset s off

# Hide mousecursor (3 describes the time after which the cursor should be hidden)
@unclutter -idle 3
# Photobooth End
```

#### Solution B

If you are using LightDM as display manager, you can edit `/etc/lightdm/lightdm.conf` to hide the cursor permanently. Just add `xserver-command=X -nocursor` to the end of the file.

---

## How to use a live stream as background at countdown?

**Note:** Before setting up a preview please make sure your Photobooth works without as expected. If you're having problems with the preview this makes debugging a lot easier.

There's different ways depending on your needs and personal setup:

### Preview _"from URL"_ (remote preview, **preferred**)

If you like to have the same preview independent of the device you access Photobooth from:

Make sure to have a stream available you can use (e.g. from your Webcam, Smartphone Camera or Raspberry Pi Camera)

-   Admin panel config _"Preview mode"_: `from URL`
-   Admin panel config _"Preview-URL"_ example (add needed IP address instead): `http://192.168.0.2:8081`

**Note**

-   Do NOT enable _"Capture screenshot (preview "from device cam" only)"_ in admin panel config!
-   Capture from Pi Camera won't work if motion is installed!
-   Requires Photobooth v2.2.1 or later!

### Setting up a preview stream from your DSLR or PiCamera

If you want to use a stream from your DSLR or Pi Camera, install go2rtc and setup needed service to use.

go2rtc can be accessed at `http://localhost:1984`. Use `http://localhost:1984/api/stream.mjpeg?src=photobooth` as _"Preview-URL"_ (replace `localhost` with Photobooths IP for remote access).
To be able to also capture images you need to adjust the capture command.
_"Commands"_: _"Take picture command"_: `capture %s`

For preview via DSLR first make sure `gphoto2 --capture-movie` works via terminal, for PiCamera make sure `rpicam-vid` or `libcamera-vid` works via terminal.

Install go2rtc preview service:
```sh
wget -O install-go2rtc-preview.sh https://raw.githubusercontent.com/PhotoboothProject/photobooth/dev/scripts/install-go2rtc-preview.sh
sudo bash install-go2rtc-preview.sh
```

Follow the instructions and choose your variant to install.

Once installed successfully reboot your device and adjust your Photobooth config as mentioned above or open [http://localhost/admin/captureconfig.php](http://localhost/admin/captureconfig.php) to apply the default suggested configuration automatically.

**Note:**
- go2rtc does not work in v1.9.3 and v1.9.4 for Pi Camera!
- For PiCamera default width is set to _2304px_ and height to _1296px_ for the preview stream generation. If needed, adjust the width and height inside `/etc/go2rtc.yaml` (needs root access!) to your personal needs.

### Preview _"from device cam"_ (no remote preview)

If you access Photobooth on your Raspberry Pi you could use a Raspberry Pi Camera. Raspberry Pi Camera will be detected as "device cam" on PiOS bookworm inside Firefox which is shipped with the OS.

-   Admin panel config "Preview mode": `from device cam`

**Note:**

-   Preview `"from device cam"` will always use the camera of the device where Photobooth get opened in a Browser (e.g. on a tablet it will always show the tablet camera while on a smartphone it will always show the smartphone camera instead)!
-   Pi Camera: capturing via `raspistill` / `libcamera-still` / `rpicam-still` does not work!
-   Secure origin or exception required!
    -   [Prefer Secure Origins For Powerful New Features](https://medium.com/@Carmichaelize/enabling-the-microphone-camera-in-chrome-for-local-unsecure-origins-9c90c3149339)
    -   [Enabling the Microphone/Camera in Chrome for (Local) Unsecure Origins](https://www.chromium.org/Home/chromium-security/prefer-secure-origins-for-powerful-new-features)
-   Admin panel config _"Capture screenshot (preview "from device cam" only)"_ can be used to take a picture from this preview instead using gphoto / digicamcontrol / raspistill / libcamera-still / rpicam-still.

### Preview from DSLR via _"from device cam"_ preview option (no remote preview)

By now the DSLR handling of Photobooth on Linux was done exclusively using `gphoto2 CLI` (command line interface). When taking pictures while using preview video from the same camera one command has to be stopped and another one is run after that.

The computer terminates the connection to the camera just to reconnect immediately. Because of that there was an ugly video gap and the noises of the camera could be irritating as stopping the video sounded very similar to taking a picture. But most cameras can shoot quickly from live-view.

The underlying libery of `gphoto2 CLI` is `libgphoto` and it can be accessed using several programming languages. Because of this we can have a python script that handles both preview and taking pictures without terminating the connection to the camera in between.

**From Photobooth v4.1.0 a preview from DSLR depends on the _"Preview from device cam"_ config**

To use `gphoto-python`, first execute the `install-gphoto-python.sh` if you have not already installed "a service to set up a virtual webcam that gphoto2 can stream video to" while using the Photobooth installer on initial installation:

```sh
wget -O install-gphoto-python.sh https://raw.githubusercontent.com/PhotoboothProject/photobooth/dev/scripts/install-gphoto-python.sh
sudo bash install-gphoto-python.sh
```

Change your Photobooth configuration:

-   _"Live Preview_": _"Preview Mode"_: _"from device cam"_
-   _"Commands_": _"Execute start command for preview on take picture/collage"_:
    -   if **enabled**:
        _"Commands"_: _"Command to generate a live preview"_: `python3 cameracontrol.py --bsm`
    -   if **disabled**:
        _"Commands"_: _"Command to generate a live preview"_: `python3 cameracontrol.py`
-   _"Commands"_: _"Take picture command"_: `python3 cameracontrol.py --capture-image-and-download %s`

**Further information**:

The _"Command to generate a live preview"_ is only executed if the _"Preview Mode"_ is set to _"from device cam"_.

There's no need to define the _"Command to kill live preview"_ while using the _cameracontrol.py_, so just empty that field. The _"Command to kill live preview"_ is only executed if defined.

If you want to use the DSLR view as background video, enable _"Use stream for live preview as background"_ and disable the _"Execute start command for preview on take picture/collage"_ setting of Photobooth, which is enabled by default.

If you're worried about the sensor of your DSLR but still want to use background video you can use `--bsmtime`.

```sh
python3 cameracontrol.py --bsmtime 1
```

With the parameter `--bsmtime` you can define a number of minutes after which the camera preview ends. Please note the last image of the preview stays for a few seconds before the background turns to black. Additionally you should add `python3 cameracontrol.py` to the _pre-photo command_ to restart the preview if it got disabled by the timeout. Restarting the preview takes a few seconds.

If you don't want to use the DSLR view as background video enable the _Execute start command for preview on take picture/collage_ setting of Photobooth and make sure `--bsm` was added to the preview command.

```sh
python3 cameracontrol.py --bsm
```

If _Execute start command for preview on take picture/collage_ is enabled, the preview video is activated when the countdown for a photo starts and after taking a picture the video is deactivated while waiting for the next photo.

As you possibly noticed the params of the script are designed to be similar to the ones of `gphoto2 CLI` but with some shortcuts like `-c` for `--capture-image-and-download`. If you want to know more check out the help of the script by running:

```sh
python3 /var/www/html/api/cameracontrol.py --help
```

or on subfolder installation of Photobooth

```sh
python3 /var/www/html/photobooth/api/cameracontrol.py --help
```

If you want to keep your images on the camera you need to use the same `capturetarget` config as when you were using `gphoto CLI` (see "How to keep pictures on my Camera using gphoto2?"). Set the config on the capture command like this:

```sh
python3 cameracontrol.py --set-config capturetarget=1 --capture-image-and-download %s
```

If you get errors from Photobooth and want to get more information try to run the preview command manually. The script is in Photobooth's `api` folder. To do so end all running services that potentially try to access the camera with `killall gphoto2` and `killall python3` (if you added any other python scripts manually you might have to be a bit more selective than this command).

Finally if you just run `venv/bin/python3 cameracontrol.py --capture-image-and-download %s` as take picture command without having a preview started it only takes a picture without starting any kind of preview and ends the script immediately after the picture.

In theory `cameracontrol.py` might be able to completely replace `gphoto2 CLI` for all DSLR connection handling in the future.

**Note**

-   Liveview **must** be supported for your camera model, [check here](http://gphoto.org/proj/libgphoto2/support.php)
-   Give permissions to /dev/video\*: `sudo gpasswd -a www-data video` (this was done automatically if you used the installation script) and reboot once.
-   Requires Photobooth v4.1.0 or later! (Instructions for older versions have been removed from the FAQ, but an FAQ with instructions matching your installed Photobooth version can always be found at [http://localhost/faq](http://localhost/faq)).
-   You need to access Photobooth directly via [http://localhost](http://localhost), you won't be able to see the preview on a different device (e.g. Tablet).
-   There's a delay of about 3 seconds until the preview starts, to avoid that disable the `Execute start command for preview on take picture/collage` option to generate a preview in background. **This results in a high battery usage and also a general slowdown.**
-   Chromium sometimes has trouble, if there is another webcam like `bcm2835-isp`, it will take it by default instead. Disable other webcams, e.g. `sudo rmmod bcm2835-isp`.
-   Make sure the countdown is long enough to start the preview, for best user experience the countdown should be set at least to 8 seconds.

**Troubleshooting**

In some cases, the v4l2loopback doesn't seem to be working after an update and breaking the preview from DSLR.

Run `v4l2-ctl --list-devices` from your terminal to see if everything is fine.

If it works you get the following output:

```
GPhoto2 Webcam (platform:v4l2loopback-000):
        /dev/video0
```

If it doesn't work:

```
Cannot open device /dev/video0, exiting
```

If it doesn't work, you might need to compile the v4l2loopback Module yourself by running the following commands:

```sh
curl -LO https://github.com/umlaeute/v4l2loopback/archive/refs/tags/v0.12.7.tar.gz
tar xzf v0.12.7.tar.gz && cd v4l2loopback-0.12.7
make && sudo make install
sudo depmod -a
sudo modprobe v4l2loopback exclusive_caps=1 card_label="GPhoto2 Webcam"
```

Now again check if everything is fine (`v4l2-ctl --list-devices`).

If you having problems with this version (`v0.12.7`), especially if you see these errors:

```bash
[video4linux2,v4l2 @ 0x641d7f294f00] ioctl(VIDIOC_G_FMT): Invalid argument
[out#0/video4linux2,v4l2 @ 0x641d7f287e00] Could not write header (incorrect codec parameters ?): Invalid argument
Error while filtering: Invalid argument
[out#0/video4linux2,v4l2 @ 0x641d7f287e00] Nothing was written into output file, because at least one of its streams received no packets.
```

Then please try using version `v0.12.5` or even the latest (untagged) version of the github repo.

Another problem could be, that your system has `secure boot` enabled.
Disable `secure boot` in the BIOS and try again.

If you're still having trouble feel free to join us at Telegram to get further support.

---

## Can I use a video as background?

Yes you can. Using the file uploader you can add your video into the `/private/videos/background` folder.

Once done go to [User interface](http://localhost/admin/#userinterface), switch from `image` to `video` as background and choose your video in "Background video path".

You can also use a youtube video/livestream!\
In the background video path put the link pulled from youtube. Note that the link should be in the following format: `https://www.youtube.com/embed/<video_id>`.\
To get a link like that you have to choose your youtube video/livestream and click on the "share" button. Then by choosing "incorporate" it will be shown an HTML code `<iframe />` you have to copy the content of the "src" property from the start till the first question mark `?` in order to resemble the format.

---

## Can I use a live stream as background?

Yes you can. There's different ways depending on your needs and personal setup:

1. On Photobooth v2.4.0 and newer you can use the option "Use stream from device cam as background" inside admin panel.

    - If enabled, a stream from your device cam is used as background on start screen. It's still possible to use preview from your device cam as background on countdown. It is **not possible** capturing via `raspistill` / `libcamera-still` / `rpicam-still` for Pi Camera.

2. You need to change the background URL path via config or admin panel. Replace `url(../img/bg.jpg)` with your IP-Adress and port (if needed) as URL.
   Example:

    ```sh
    -   url(../img/bg.jpg)
    +   url(http://192.168.0.2:8081)
    ```

    To use an DSLR or an Raspberry Pi Camera module see _Setting up a preview stream from your DSLR or PiCamera_ above.

---

## I've trouble setting up E-Mail config. How do I solve my problem?

If connection fails some help can be found [here](https://github.com/PHPMailer/PHPMailer/wiki/Troubleshooting), especially gmail needs some special config.

-   Should be obvious but the photobooth must be connected to WIFI/internet to send photos live.

    Otherwise, tell them to check the box to send them the photo later and it will add everyone's email to a list for you.

-   For gmail you need to generate an app password if you have 2-factor authentication on.

Tested working setup:

-   gmail.com

    -   Email host adress: `smtp.gmail.com`
    -   Username: `*****@gmail.com`
    -   Port: `587`
    -   Security: `TLS`

-   gmx.de

    -   Email host adress: `mail.gmx.net`
    -   Username: `*****@gmx.de`
    -   Port: `587`
    -   Security: `TLS`

-   web.de
    -   Email host adress: `smtp.web.de`
    -   Username: `*****` (@web.de is not needed in your username)
    -   Port: `587`
    -   Security: `TLS`

---

## How to only open the gallery to avoid people taking pictures?

Open [http://localhost/gallery](http://localhost/gallery) in your browser (you can replace `localhost` with your IP adress).

---

## Chromakeying isn't working if I access the Photobooth page on my Raspberry Pi, but it works if I access Photobooth from an external device (e.g. mobile phone or tablet). How can I solve the problem?

Open `chrome://flags` in your browser.

Look for _"Accelerated 2D canvas"_ and change it to `"disabled"`.

Now restart your Chromium browser.

---

## How to adjust the `php.ini` file?

Open [http://localhost/phpinfo.php](http://localhost/phpinfo.php) in your browser.

Take a look for "Loaded Configuration File" to get the path of your php.ini, you need _sudo_ rights to edit the file.

---

## Automatic picture syncing to USB stick

This feature will automatically and in regular intervals copy (sync) new pictures to a plugged-in USB stick. Currently works on Raspberry PI OS only.

Use the `install-photobooth.sh` script to get the operating system setup in place.

**Note:** If you have declined the question to enable the USB sync file backup while running the `install-photobooth.sh` you need to run the following commands to get the operating system setup done:

```sh
wget -O enable-usb-sync.sh https://raw.githubusercontent.com/PhotoboothProject/photobooth/dev/scripts/enable-usb-sync.sh
sudo bash enable-usb-sync.sh -username='<YourUsername>'
```

The target USB device is selected through the admin panel.

A USB drive / stick can be identified either by the USB stick label (e.g. `photobooth`), the operating system specific USB device name (e.g. `/dev/sda1`) or the USB device system subsystem name (e.g. `sda`). The preferred method would be the USB stick label (for use of a single USB stick) or the very specific USB device name, for different USB stick use. The default config will look for a drive with the label photobooth. The script only supports one single USB stick connected at a time

Pictures will be synced to the USB stick matched by the pattern, as long as it is mounted (aka USB stick is plugged in)

Debugging: Check the server logs for errors at the Debug panel: [http://localhost/admin/debugpanel](http://localhost/admin/debugpanel)

---

## Raspberry Touchpanel DSI simultaneously with HDMI

When using a touchscreen on DSI and an HDMI screen simultaneously, the touch input is offset. This is because both monitors are recognized as one screen.

The remedy is the following:

```
xinput list
```

remember the device id=[X] of the touchscreen.

```
xinput list-props "Device Name"
```

Get the ID in brackets (Y) of Coordinate Transformation Matrix

```
xinput set-prop [X] --type=float [Y] c0 0 c1 0 c2 c3 0 0 1
```

adjust the coding c0 0 c1 0 c2 c3 0 0 1 with your own data.

You can get the values of your screens with the following command:

```
xrandr | grep \* # xrandr uses "*"
```

to identify the screen being used

```
c0 = touch_area_width / total_width
(width of touch screen divided by width of both screens)
c2 = touch_area_height / total_height
(height touch screen divided by height of both screens)
c1 = touch_area_x_offset / total_width
c3 = touch_area_y_offset / total_height
```

and execute the above command again with your own coding!

Example:

```
xinput set-prop 6 --type=float 136 0.3478260869565217 0 0 0.55555555555556 0 0 0 1
```

Now unfortunately the settings are only valid for the current session. So create the following desktop startup file with your own values:

```sh
nano ~/.config/autostart/touch.desktop
```

Example:

```
[Desktop Entry]
Name=TouchSettingsAutostart
Comment=Set up touch screen setting when starting desktop
Type=Application
## Adapt command to own values
Exec=xinput set-prop 6 --type=float 136 0.3478260869565217 0 0 0 0.55555555555556 0 0 0 1
Terminal=false
```

If you want to use the touchscreen as photobooth and the second monitor for the standalone slideshow for example, open the autostart file:

```sh
sudo nano /etc/xdg/lxsession/LXDE-pi/autostart
```

and enter/adjust the @chromium-browser entries as followed (adjust the value _1920_ to your own resolution and URL if necessary):

```
@chromium-browser --new-window --start-fullscreen --kiosk http://localhost --window-position=1920,0 --user-data-dir=Default
@chromium-browser --new-window --start-fullscreen --kiosk http://localhost/slideshow/ --window-position=0,0 --user-data-dir='Profile 1'
```

---

## How does the connection to the FTP server work?

The connection to the FTP server needs 4 distinct properties.

-   `baseURL` which is the url where all requests will be made
-   `port` for ssl connection (the default value is 21)
-   `username` the username of the user authorized to interact to the FTP server
-   `password` the password of the user

With these four variables you can test the connection to the FTP server to check if everything is alright.

The next variables are for the place where you want the pictures to be stored:

-   `baseFolder` is the folder of your website (if you have multiple websites living on the server with this property you can choose on which of these the file should be stored)
-   `folder` the folder dedicated to the upload of the files
-   `title` if you are doing an event you can set the title of the event to create another folder (the system will slugify the string)

In the end the processed picture, and the thumbnails, will be uploaded in the folder according to these variables.

If you have a website, you can use the following variables to generate the qr codes that will point to the photos uploaded to the ftp server

-   `useForQr` to enable this functionality
-   `website` accessible from the internet, it will be the base of the qr code link
-   `urlTemplate` starting from the previous set of variables, you have to define the template which will be used to generate the qrcode link (each variable should be written whit '%' before e.g. %website/%folder/%date)

Last but not least you can upload a php file on the `title` folder on the FTP server to create an online gallery which is updated with every new picture (and collage) taken.
The variable to manage this feature are the following:

-   `create_webpage` to enable this functionality
-   `template_location` which is the location of the index.php file, which is formatted with the title of the current event and uploaded to the FTP server

In the end you can enable the `delete` functionality that will delete photos (and collages) from the ftp server when they are deleted from the photobooth gallery (no admin reset)

---

## I get the error message "Something went wrong." while taking a picure, what can i do?

There's different reasons if you get the error "Something went wrong. Please try it again. Photobooth reloads automatically." while taking an image.

First of all, please set the **Loglevel** to **2** via admin panel (GENERAL section, [http://localhost/admin](http://localhost/admin)) and try again. You'll still see the error message, but we make sure to log enough information to see what's wrong.

Now open the Debug panel ([http://localhost/admin/debugpanel](http://localhost/admin/debugpanel)) and check the Photobooth log for error messages. You should see something like this:

```
2023-01-03T08:34:37+01:00:
Array
(
    [error] => Take picture command returned an error code
    [cmd] => gphoto2 --capture-image-and-download --filename=/var/www/html/data/tmp/20230103_083437.jpg 2>&1
    [returnValue] => 1
    [output] => Array
        (
            [0] =>
            [1] => *** Error ***
            [2] => Could not detect any camera
            [3] => *** Error (-105: 'Unknown model') ***
            [4] =>
            [5] => For debugging messages, please use the --debug option.
            [6] => Debugging messages may help finding a solution to your problem.
            [7] => If you intend to send any error or debug messages to the gphoto
            [8] => developer mailing list , please run
            [9] => gphoto2 as follows:
            [10] =>
            [11] =>     env LANG=C gphoto2 --debug --debug-logfile=my-logfile.txt --capture-image-and-download --filename=/var/www/html/testa/data/tmp/20230103_083437.jpg
            [12] =>
            [13] => Please make sure there is sufficient quoting around the arguments.
            [14] =>
        )

    [php] => takePic.php
)
```

Most of the time the error messages are self explained (in our case no camera was detected, the cable wasn't plugged in), if you're still having trouble you can check the troubleshooting section.

---

## How to upload pictures to a remote server after picture has been taken?

### Goal:

After a picture is taken with the photobox upload it automatically to a remote server.

### Usecase:

You have a remote server (e.g. with your website on it) or another Raspberry Pi to which you’d like instantly synchronizing your taken pictures. Also you could upload the pictures to a remote server and make them available through the QR code over the internet. By this you would not require people to access a local Wifi to download the picture from your local device which is running your Photobox.

### How to:

-   You should have a remote server with an SSH login. Know your username and password: (e.g.: [username.strato-hosting.eu]@ssh.strato.de)
-   We will be using the Post-photo script / command of the Photobox which you can find in the admin panel in the section Commands.
-   The command is being executed after the picture has been taken and gets the picture’s name as an attribute.
-   Command:

```sh
scp /var/www/html/photobooth/data/images/%s [username@remotehost]:/[path_to_where_you_want_to_store_the_pictures_on_the_remote_host]
```

-   If we keep it like that the remote server would require the source server to type in a password each time a picture is being copied to the remote server. An SSH connection using a private/public SSH key needs to be established:

1. Create a public/private key-pair for the www-data user on the source machine (why for that user? The www-data user is executing the Post-photo script/command in the background) – Do not enter a passphrase when prompted.

```sh
sudo -u www-data ssh-keygen -t rsa
```

2. Copy the public key to the remote (destination) server

```sh
sudo -u www-data ssh-copy-id [username@remotehost]
```

3. You can now manually test whether the connection works. Try to copy anything to the remote server and change the file in the below example to a file that you actually have on your source machine. You shouldn’t be prompted with a password, but the copy and transfer should complete successfully just with the following command. If that is going to be successful, copying your pictures automatically should work now.

```sh
sudo -u www-data scp /var/www/html/photobooth/data/images/20230129_125148.jpg [username@remotehost]:/[path_to_where_you_want_to_store_the_pictures]
```

You can now use the URL with which you can access your remote server from the internet and paste it into the QR code field in the Photobox admin panel. Now using the QR code your pictures can be downloaded from your remote server.

## How to use the image randomizer

To use the image randomizer images must be placed inside private/images/{folderName}.
For hassle-free (ssh/sftp-free) upload, you may want to use the integrated images uploader: [http://localhost/admin/upload](http://localhost/admin/upload).

### Use for PICTURE FRAMES:

1. Upload / Copy all the (transparent) frames you want to private/images/{FrameFolder}
2. Enable picture_take_frame
3. specify picture_frame url : http://localhost/api/randomImg.php?dir={FrameFolder}

### Use for COLLAGE FRAMES:

1. Upload / Copy all the (transparent) frames you want to private/images/{FrameFolder}
2. Enable collage_take_frame (always or once)
3. specify collage_frame url : http://localhost/api/randomImg.php?dir={FrameFolder}

### Use for BACKGROUNDS:

1. Upload / Copy all the backgrounds you want to private/images/{BgFolder}
2. specify collage_background url : http://localhost/api/randomImg.php?dir={BgFolder}

**NOTES:**

-   Replace _"localhost"_ with your IP-Adress.
-   Same thing can be applied for collage_placeholderpath so a random holder image takes place.
-   You can specify a diffrent {FrameFolder} for collage frames if needed.

---

## How to use Magic Greenscreen (AI Background Removal)

Magic Greenscreen is a feature that uses AI to automatically remove backgrounds from photos, creating professional-looking images with transparent or custom backgrounds. This feature is powered by the rembg library and requires Python 3.

### Prerequisites

- Python: >=3.10, <3.14
- Internet connection for initial setup
- Sufficient disk space (approximately 200MB for the AI model)

### Installation

1. **Download and run the installation script:**
   ```sh
   cd /var/www/html/scripts
   sudo bash install_rembg.sh
   ```

   This script will:
   - Check for Python 3 and required packages
   - Create a virtual environment in `scripts/rembg_venv`
   - Install rembg and its dependencies (PIL, onnxruntime)
   - Verify the installation

2. **Alternative manual installation:**
   If you prefer to install manually:
   ```sh
   # Create virtual environment
   python3 -m venv /var/www/html/scripts/rembg_venv

   # Activate virtual environment
   source /var/www/html/scripts/rembg_venv/bin/activate

   # Install dependencies
   pip install rembg pillow onnxruntime
   ```

### Configuration

1. **Open the Admin Panel:**
   Navigate to [http://localhost/admin](http://localhost/admin)

2. **Enable Magic Greenscreen:**
   - Go to the "Magic Greenscreen" section (positioned between "Custom" and "Gallery")
   - Check "Remove background" to enable the feature
   - Optionally configure:
     - **Background image:** Path to a custom background image (leave empty for transparent background)
     - **AI model:** Choose the AI model (default: u2net)
     - **Alpha matting:** Enable for better edge quality
     - **Alpha matting thresholds:** Fine-tune edge detection (advanced users)
     - **Post-processing:** Enable for improved results
     - **Max image size:** Limit processing size for performance

3. **Save Configuration:**
   Click the "Save" button in the admin panel

### Usage

Once enabled, Magic Greenscreen will automatically process photos after they are taken:

1. Take a photo as usual using the photobooth
2. The AI will automatically remove the background
3. The processed image will be saved with a transparent or custom background
4. Both original and processed images are available in the gallery

### Supported Formats

- Input: JPEG, PNG, and other common image formats
- Output: PNG (for transparency) or JPEG (with custom background)

### Performance Notes

- Processing is performed on the CPU, it requires faster hardware to achieve optimal performance (not recommended for Raspberry Pi 4 or older)
- First processing may take longer as the AI model loads
- Processing time depends on image size and complexity
- Recommended max image size: 1024x1024 pixels for optimal performance
- Processing happens after photo capture and doesn't delay the user experience

### Troubleshooting

#### "rembg virtual environment not found" error
- Ensure the installation script completed successfully
- Check that the virtual environment exists: `ls /var/www/html/scripts/rembg_venv`
- Verify permissions: `sudo chown -R www-data:www-data /var/www/html/scripts/rembg_venv`

#### Processing fails or takes too long
- Check available disk space
- Reduce max image size in configuration
- Verify internet connection (some models may require online access)
- Check server logs in the debug panel: [http://localhost/admin/debug](http://localhost/admin/debugpanel)

#### Background not removed properly
- Try a different AI model (u2net, u2netp, u2net_cloth_seg)
- Enable alpha matting for better edge detection
- Adjust alpha matting thresholds
- Ensure good lighting and contrast in the original photo

#### Permission errors
- Ensure www-data user has access to the scripts directory
- Run: `sudo chown -R www-data:www-data /var/www/html/scripts`

#### Memory issues
- Reduce max image size
- Disable post-processing if memory is limited
- Consider using a more powerful device

### Advanced Configuration

For advanced users, you can modify the rembg processing parameters:

- **AI Models:**
  - `u2net`: General purpose (default)
  - `u2netp`: Portrait optimized
  - `u2net_cloth_seg`: Clothing segmentation
  - `silueta`: Simple backgrounds
  - `isnet`: High quality

- **Alpha Matting:** Improves edge quality around hair and fine details
- **Post-processing:** Applies additional smoothing and refinement

### File Locations

- Virtual environment: `/var/www/html/scripts/rembg_venv`
- Installation script: `/var/www/html/scripts/install_rembg.sh`
- Processed images: `/var/www/html/data/images/` (with background removed)

### Updating

To update rembg to the latest version:

```sh
source /var/www/html/scripts/rembg_venv/bin/activate
pip install --upgrade rembg
```

For additional support, check the Photobooth logs.

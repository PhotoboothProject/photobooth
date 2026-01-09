# FAQ - Frequently asked questions

FAQ on [https://photoboothproject.github.io/](https://photoboothproject.github.io/) always matches latest development version of Photobooth. Some options might not be available on older version or might be handled different.

An FAQ matching your installed version can be found at [http://localhost/faq](http://localhost/faq) (or [http://localhost/photobooth/faq](http://localhost/photobooth/faq) ).

## Is my Camera supported?

Some DSLR and Compact Cameras are not supported by this project. Please check for your specific model [here](http://gphoto.org/proj/libgphoto2/support.php).

---

## Is Pi Camera supported?

Yes. See the dedicated steps in [Pi Camera setup](pi-camera.md).

---

## I've found a bug, how can I report?

Please take a look at the issue page [here](https://github.com/PhotoboothProject/photobooth/issues), if your bug isn't mentioned already you can create a new issue. Please give informations detailed as possible to reproduce and analyse the problem.

---

## I've a white page after updating to latest Source, how can I solve this?

If you updated from v1.9.0 or older and get a white page, your old `admin/config.json` may be incompatible. Remove the legacy file and retry; current versions store changes in `config/my.config.inc.php`:

```
sudo rm /var/www/html/admin/config.json
```

---

## How do I change the configuration?

Open [http://localhost/admin](http://localhost/admin) (
or [http://localhost/photobooth/admin](http://localhost/photobooth/admin)) in your Webbrowser and change the configuration for your personal needs.
Changed options are stored inside `config/my.config.inc.php` to prevent sharing personal data on Github by accident and to make an update of Photobooth easier.

---

## How do I set up the screensaver?

Use the Admin Panel → General → Screensaver and follow [Screensaver function](screensaver.md) for modes, file locations, and timing tips.

---

## How to change the language?

Open [http://localhost/admin](http://localhost/admin) (
or [http://localhost/photobooth/admin](http://localhost/photobooth/admin)) in your Webbrowser and change the configuration for your personal needs.

---

## How to update or add translations?

Photobooth joined Crowdin as localization manager, [join here](https://crowdin.com/project/photobooth) to translate Photobooth.

Crowdin gives a nice webinterface to make translating easy as possible. If there's different translations for a string, translator can use the vote function on suggested translations.

With Crowdin and your help translating we're able to get high-quality translations for all supported languages. Also it's easy to support a wider range of languages!

Your language is missing? Don't worry, create a [localization request here](https://github.com/PhotoboothProject/photobooth/issues/new/choose) and we'll add it to the project.

---

## How can I test my current photo settings?

Open [http://localhost/test/photo.php](http://localhost/test/photo.php) (or [http://localhost/photobooth/test/photo.php](http://localhost/photobooth/test/photo.php)) in your Webbrowser and you can find a photo that is created with your current settings.

---

## How can I test my current collage settings?

Open [http://localhost/test/collage.php](http://localhost/test/collage.php) (or [http://localhost/photobooth/test/collage.php](http://localhost/photobooth/test/collage.php)) in your Webbrowser and you can find a collage that is created with your current settings.

---

## How can setup a custom collage design?

See the detailed walkthrough in [Custom collage design](custom-collage.md).

---

## How to keep pictures on my Camera using gphoto2?

Add `--keep` (or `--keep-raw` to keep only the raw version on camera) option for gphoto2 via admin panel:

```
gphoto2 --capture-image-and-download --keep --filename=%s
```

On some cameras you also need to define the capturetarget because Internal RAM is used to store captured picture. To do this use `--set-config capturetarget=X` option for gphoto2 (replace "X" with the target of your choice):

```
gphoto2 --set-config capturetarget=1 --capture-image-and-download --keep --filename=%s
```

To know which capturetarget needs to be defined you need to run:

```
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

Yes. See the dedicated page [Remote Buttons & Triggers](remote-button.md) for:

- Browser hotkeys (HID to focused browser)
- Direct USB HID on the Photobooth host (no browser focus)
- Socket.io triggers
- Simple HTTP triggers and example devices (ESP, Pico, myStrom, Shelly)
- Troubleshooting tips

---

## How do I enable Kiosk Mode to automatically start Photobooth in full screen?

Use the [kiosk mode guide](kiosk-mode.md).

---

## How to hide the mouse cursor, disable screen blanking and screen saver?

See [Hide cursor, screen blanking and screen saver](hide-cursor.md) for Pi OS-specific steps.

---

## How to use a live stream as background at countdown?

See the dedicated [Preview and live background](preview.md) guide.

---

## Can I use a video as background?

Yes you can. Using the file uploader you can add your video into the `/private/videos/background` folder.

Once done go to [User interface](http://localhost/admin/#userinterface) (or [User interface](http://localhost/photobooth/admin/#userinterface)), switch from `image` to `video` as background and choose
your video in "Background video path".

You can also use a youtube video/livestream!

In the background video path put the link pulled from youtube. Note that the link should be in the following format: `https://www.youtube.com/embed/<video_id>`.

To get a link like that you have to choose your youtube video/livestream and click on the "share" button. Then by choosing "incorporate" it will be shown an HTML code `<iframe />` you have to copy the content of the "src" property from the start till the first question mark `?` in order to resemble the format.

---

## Can I use a live stream as background?

Yes you can. There's different ways depending on your needs and personal setup:

1. On Photobooth v2.4.0 and newer you can use the option "Use stream from device cam as background" inside admin panel.

    - If enabled, a stream from your device cam is used as background on start screen. It's still possible to use preview from your device cam as background on countdown. It is **not possible** capturing via `raspistill` / `libcamera-still` / `rpicam-still` for Pi Camera.

2. You need to change the background URL path via config or admin panel. Replace `url(../img/bg.jpg)` with your IP-Adress and port (if needed) as URL.
   Example:

   Use `http://192.168.0.2:8081` (or your own stream URL) in place of `/img/bg.jpg`.

    To use an DSLR or an Raspberry Pi Camera module see _Setting up a preview stream from your DSLR or PiCamera_ above.

---

## I've trouble setting up E-Mail config. How do I solve my problem?

If connection fails some help can be found inside the official [PHPMailer Wiki](https://github.com/PHPMailer/PHPMailer/wiki/Troubleshooting), especially gmail needs some special config.


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

Open [http://localhost/gallery](http://localhost/gallery) (or [http://localhost/photobooth/gallery](http://localhost/photobooth/gallery)) in your browser (you can replace `localhost` with your IP adress).

---

## Chromakeying isn't working if I access the Photobooth page on my Raspberry Pi, but it works if I access Photobooth from an external device (e.g. mobile phone or tablet). How can I solve the problem?

Open `chrome://flags` in your browser.

Look for _"Accelerated 2D canvas"_ and change it to `"disabled"`.

Now restart your Chromium browser.

---

## How to adjust the `php.ini` file?

Open [http://localhost/phpinfo.php](http://localhost/phpinfo.php) (or [http://localhost/photobooth/phpinfo.php](http://localhost/photobooth/phpinfo.php)) in your browser.

Take a look for "Loaded Configuration File" to get the path of your php.ini, you need _sudo_ rights to edit the file.

---

## Automatic picture syncing to USB stick

This feature will automatically and in regular intervals copy (sync) new pictures to a plugged-in USB stick

Use the [Photobooth Setup Wizard](https://photoboothproject.github.io/install/setup_wizard) to get the operating system setup in place.

- 6. Permissions -> USB Sync policy

The target USB device is selected through the admin panel.

A USB drive / stick can be identified either by the USB stick label (e.g. `photobooth`), the operating system specific USB device name (e.g. `/dev/sda1`) or the USB device system subsystem name (e.g. `sda`). The preferred method would be the USB stick label (for use of a single USB stick) or the very specific USB device name, for different USB stick use. The default config will look for a drive with the label photobooth. The script only supports one single USB stick connected at a time

Pictures will be synced to the USB stick matched by the pattern, as long as it is mounted (aka USB stick is plugged in)

Debugging: Check the server logs for errors at the Debug panel: [http://localhost/admin/debugpanel](http://localhost/admin/debugpanel) (
or [http://localhost/photobooth/admin/debugpanel](http://localhost/photobooth/admin/debugpanel)).

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

```
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

```
sudo nano /etc/xdg/lxsession/LXDE-pi/autostart
```

and enter/adjust the @chromium-browser entries as followed (adjust the value _1920_ to your own resolution and URL if necessary):

```
@chromium-browser --new-window --start-fullscreen --kiosk http://localhost --window-position=1920,0 --user-data-dir=Default
@chromium-browser --new-window --start-fullscreen --kiosk http://localhost/photobooth --window-position=1920,0 --user-data-dir=Default
@chromium-browser --new-window --start-fullscreen --kiosk http://localhost/slideshow/ --window-position=0,0 --user-data-dir='Profile 1'
@chromium-browser --new-window --start-fullscreen --kiosk http://localhost/photobooth/slideshow/ --window-position=0,0 --user-data-dir='Profile 1'
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

First of all, please set the **Loglevel** to **2** via admin panel (GENERAL section, [http://localhost/admin](http://localhost/admin)
or [http://localhost/photobooth/admin](http://localhost/photobooth/admin)) and try again. You'll still see the error message, but we make sure to log enough information to see what's wrong.

Now open the Debug panel ([http://localhost/admin/debugpanel](http://localhost/admin/debugpanel) or [http://localhost/photobooth/admin/debugpanel](http://localhost/photobooth/admin/debugpanel)) and
check the Photobooth log for error messages. You should see something like this:

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

-   You should have a remote server with an SSH login. Know your username and password: (e.g.: `[username.strato-hosting.eu]@ssh.strato.de`)
-   We will be using the Post-photo script / command of the Photobox which you can find in the admin panel in the section Commands.
-   The command is being executed after the picture has been taken and gets the picture’s name as an attribute.
-   Command (adjust path if needed):

```
scp /var/www/html/data/images/%s [username@remotehost]:/[path_to_where_you_want_to_store_the_pictures_on_the_remote_host]
```

-   If we keep it like that the remote server would require the source server to type in a password each time a picture is being copied to the remote server. An SSH connection using a private/public SSH key needs to be established:

1. Create a public/private key-pair for the **www-data** user on the source machine (why for that user? The www-data user is executing the Post-photo script/command in the background) – Do not enter a passphrase when prompted.

```
sudo -u www-data ssh-keygen -t rsa
```

2. Copy the public key to the remote (destination) server

```
sudo -u www-data ssh-copy-id [username@remotehost]
```

3. You can now manually test whether the connection works. Try to copy anything to the remote server and change the file in the below example to a file that you actually have on your source machine. You shouldn’t be prompted with a password, but the copy and transfer should complete successfully just with the following command. If that is going to be successful, copying your pictures automatically should work now.

```
sudo -u www-data scp /var/www/html/data/images/20230129_125148.jpg [username@remotehost]:/[path_to_where_you_want_to_store_the_pictures]
```

You can now use the URL with which you can access your remote server from the internet and paste it into the QR code field in the Photobox admin panel. Now using the QR code your pictures can be downloaded from your remote server.

## How do I use QR codes for downloads?

- Touch-friendly viewer page: `view.php?image=<filename>` shows the photo/video with a large download button.
- Direct file download (no UI): `api/download.php?image=<filename>`.
- Set the QR target in the admin config under `qr[url]`; a good default is `view.php?image=` so guests open the viewer after scanning.
- Network reminder: guests must reach the URL in the QR. Either put them on the same Wi-Fi/LAN as the Photobooth (no internet needed) or point the QR to a public endpoint that serves the image.

## How to use the image randomizer

To use the image randomizer images must be placed inside `private/images/{folderName}`.
For hassle-free (ssh/sftp-free) upload, you may want to use the integrated images uploader: [http://localhost/admin/upload](http://localhost/admin/upload) (
or [http://localhost/photobooth/admin/upload](http://localhost/photobooth/admin/upload)).

### Use for PICTURE FRAMES:

1. Upload / Copy all the (transparent) frames you want to `private/images/{FrameFolder}`
2. Enable picture_take_frame
3. specify picture_frame url : `http://localhost/api/randomImg.php?dir={FrameFolder}` (or `http://localhost/photobooth/api/randomImg.php?dir={FrameFolder}`)

### Use for COLLAGE FRAMES:

1. Upload / Copy all the (transparent) frames you want to `private/images/{FrameFolder}`
2. Enable collage_take_frame (always or once)
3. specify collage_frame url : `http://localhost/api/randomImg.php?dir={FrameFolder}` (or `http://localhost/photobooth/api/randomImg.php?dir={FrameFolder}`)

### Use for BACKGROUNDS:

1. Upload / Copy all the backgrounds you want to `private/images/{BgFolder}`
2. specify collage_background url : `http://localhost/api/randomImg.php?dir={BgFolder}` (or `http://localhost/photobooth/api/randomImg.php?dir={BgFolder}`)

**NOTES:**

-   Replace _"localhost"_ with your IP-Adress.
-   Same thing can be applied for collage_placeholderpath so a random holder image takes place.
-   You can specify a diffrent {FrameFolder} for collage frames if needed.

---

## How do I reset keypad login attempts?

If the keypad is blocked after too many wrong PINs, clear the throttle file to reset attempts:

1. Open a shell on the photobooth host.
2. Delete the throttle JSON that stores attempt counts:

Path may vary based on your installation:

   ```
   rm /var/www/html/photobooth/var/run/login_throttle.json
   ```

or

   ```
   rm /var/www/html/var/run/login_throttle.json
   ```

---

## How to use Magic Greenscreen (AI Background Removal)

Magic Greenscreen is a feature that uses AI to automatically remove backgrounds from photos, creating professional-looking images with transparent or custom backgrounds. This feature is powered by the rembg library and requires Python 3.

### Prerequisites

- Python: >=3.10, <3.14
- Internet connection for initial setup
- Sufficient disk space (approximately 200MB for the AI model)

### Installation

1. **Download and run the the [Photobooth Setup Wizard](https://photoboothproject.github.io/install/setup_wizard)**

   Choose __8 Rembg Setup__

   This script will:

   - Check for Python 3 and required packages
   - Create a virtual environment in `/var/www/rembg/rembg_venv`
   - Install rembg and its dependencies (PIL, onnxruntime)
   - Verify the installation
   - start rembg server via a system service on port 7000 (API endpoints are used by Photobooth), access rembg server at [localhost:7000](http://localhost:7000)).

### Configuration

1. **Open the Admin Panel:**
   Navigate to [http://localhost/admin](http://localhost/admin) (or [http://localhost/photobooth/admin](http://localhost/photobooth/admin))

2. **Enable Magic Greenscreen:**
   - Go to the "Magic Greenscreen" section (positioned between "Custom" and "Gallery")
   - Check "Remove background" to enable the feature
   - Optionally configure:
     - **Background image:** Path to a custom background image (leave empty for transparent background)
     - **Background scaling mode:** How the background image is scaled/cropped (default: scale-fill)
       - `scale-fill` (recommended): Covers entire canvas, preserves aspect ratio, may crop edges
       - `scale-fit`: Fits inside canvas, preserves aspect ratio, may have black bars
       - `crop-center`: Crops from center to exact canvas size
       - `stretch`: Stretches to fit (may distort)
       - `none`: Direct copy without scaling (original behavior)
     - **AI model:** Choose the AI model (default: u2net)
     - **Alpha matting:** Enable for better edge quality
     - **Alpha matting thresholds:** Fine-tune edge detection (advanced users)
     - **Post-processing:** Enable for improved results

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
- Processing happens after photo capture and doesn't delay the user experience

### Troubleshooting

First, make sure the rembg server is avalable ([localhost:7000](http://localhost:7000)).

In case you're unable to access the rembg server, check the rembg server status:
```
sudo systemctl status rembg.service
```

#### "rembg virtual environment not found" error
- Ensure the installation completed successfully

#### Processing fails or takes too long
- Check available disk space
- Verify internet connection (some models may require online access)
- Check server logs in the debug panel: [http://localhost/admin/debug](http://localhost/admin/debugpanel) (
  or [http://localhost/photobooth/admin/debugpanel](http://localhost/photobooth/admin/debugpanel)).

#### Background not removed properly
- Try a different AI model (u2net, u2netp, u2net_cloth_seg)
- Enable alpha matting for better edge detection
- Adjust alpha matting thresholds
- Ensure good lighting and contrast in the original photo

#### Memory issues
- Disable post-processing if memory is limited
- Consider using a more powerful device

### Advanced Configuration

For advanced users, you can modify the rembg processing parameters:

- **AI Models:**
  -   `u2net`: General purpose (default)
  -   `u2netp`: Portrait optimized
  -   `u2net_cloth_seg`: Clothing segmentation
  -   `silueta`: Simple backgrounds
  -   `isnet`: High quality

- **Alpha Matting:** Improves edge quality around hair and fine details
- **Post-processing:** Applies additional smoothing and refinement

### File Locations

- Virtual environment: `/var/www/rembg/rembg_venv`
- Processed images: `/var/www/html/data/images/` (with background removed)
- Installation and uninstall: Run the [Photobooth Setup Wizard](https://photoboothproject.github.io/install/setup_wizard)

### Updating

To update rembg to the latest version:

```
source /var/www/rembg/rembg_venv/bin/activate
pip install --upgrade rembg
```

For additional support, check the Photobooth logs.

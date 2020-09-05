# FAQ - Frequently asked questions

### Is my Camera supported?
Some DSLR and Compact Cameras are not supported by this project. Please check for your specific model [here](http://gphoto.org/proj/libgphoto2/support.php).

<hr>

### Is Pi Camera supported?
Yes it is.

Enable camera support using the `raspi-config` program you will have used when you first set up your Raspberry Pi.

`sudo raspi-config`

Use the cursor keys to select and open Interfacing Options, and then select Camera and follow the prompt to enable the camera.

Now you need to allow the webserver to use `raspistill`. You need add the webserver user to video group and reboot once:  
```
sudo gpasswd -a www-data video
reboot
```
Once done you need to adjust the configuration. Open the admin panel in your browser [localhost/admin](http://localhost/admin) and make the following changes:

**"Take picture command":**   
`raspistill -n -o %s -q 100 -t 1 | echo Done`

**"Success message for take picture":**  
`Done`

Pi Camera works with these config changes (also works together with preview at countdown if enabled).
Raspistill does not give any feedback after the picture was taken, workaround for that with "echo".
(Thanks to Andreas Maier for that information)

You've the possibility to add more parameters if needed (define ISO, exposure, white balance etc.). Type `raspistill -?` in your terminal to get information about possible parameters / settings.

<hr>

### I've found a bug, how can I report?
Please take a look at the issue page [here](https://github.com/andi34/photobooth/issues) , if your bug isn't mentioned already you can create a new issue. Please give informations detailed as possible to reproduce and analyse the problem.

<hr>

### I've a white page after updating to latest Source, how can I solve this?
On v1.9.0 and older:
It could be your local `config.json` file doesn't match latest source. This file is generated if you've used the admin panel to change your config.
Remove the file and try again!
`sudo rm /var/www/html/admin/config.json`

<hr>

### How do I change the configuration?
Open `http://localhost/admin` in your Webbrowser and change the configuration for your personal needs.
Changed options are stored inside `config/my.config.inc.php` to prevent sharing personal data on Github by accident and to make an update of Photobooth easier.

<hr>

### How to change the language?
Open `http://localhost/admin` in your Webbrowser and change the configuration for your personal needs.

<hr>

### How to keep pictures on my Camera using gphoto2?
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

<hr>

### Cromakeying is saving without finishing saving
Checking the browser console you'll see a `413 Request Entity Too Large` error. To fix that you'll have to update your nginx.conf

Follow the steps mentioned here: [How to Fix NGINX 413 Request Entity Too Large Error](https://datanextsolutions.com/blog/how-to-fix-nginx-413-request-entity-too-large-error/)

<hr>

### Can I use Hardware Button to take a Picture on my Raspberry Pi?
When the photobooth display / screen is directly connected to the Raspberry Pi, this is a simple way to use a hardware button connected on GPIO24 to trigger a photo. Set the "Take Pictures key" to `13` (enter key) via Admin panel to specify the key. Next you have to install some dependencies:

```
sudo apt install libudev-dev
sudo pip install python-uinput
echo "uinput" | sudo tee -a /etc/modules
```

After a reboot (`sudo shutdown -r now`), you should check if the uinput kernel module is loaded by executing `lsmod | grep uinput`. If you get some output, everything is fine.

You also need to run a python script in background to read the state of GPIO24 and send the key if hardware button is pressed to trigger the website to take a photo.
```
sudo crontab -e
@reboot python /var/www/html/button.py &
```

<hr>

### Hardware Button for WLAN connected screen (i.e. iPad) - Remote Buzzer Server
This feature enables a GPIO pin connected hardware button / buzzer for a setup where the display / screen is connected via WLAN / network to the photobooth webserver (e.g. iPad). Configuration takes place in the admin settings - Remote Buzzer Server area.

**Important: You must make sure to set the IP address of the Photobooth web server in the admin settings - section "General"**. The loopback IP (127.0.0.1) does not work, it has to be the exact IP address of the Photobooth web server, to which the remote display connects to. 

Debugging: switch on dev settings for server logs to be written to the "tmp" directory of the photobooth installation (i.e. `data/tmp/io_server.log`). Clients will log server communication information to the browser console.

If you experience crashes or access permission problems to GPIO pins, check [https://www.npmjs.com/package/rpio](https://www.npmjs.com/package/rpio) for additional settings required on the Pi

***************
Hardware Buzzer / Button
***************
The hardware buzzer connects to a GPIO pin, the server will watch for a PIN_DOWN event (pull to ground). This will initiate a message to the photobooth screen over network / WLAN, to trigger the action (thrill).

- Short button press (default <= 2 sec) will trigger a single picture
- Long button press (default > 2 sec) will trigger a collage
 - If collage is configured with interruption, next button presses will trigger the next collage pictures. 
 - If collage is disabled in the admin settings, long button press also triggers a single picture

After triggered, the hardware button remains disabled until an action (picture / collage) has fully completed. Then the hardware button re-arms / is active again.

**************
Other Remote Trigger (experimental)
**************
The trigger server controls and coordinates sending commands via socket.io to the photobooth client. Next to a hardware button, any socket.io client can connect to the trigger server over the network, and send a trigger command. This gives full flexibility to integrate other backend systems for trigger signals.

- Channel: `photobooth-socket`
- Commands: `start-picture`, `start-collage`
- Response: `completed`  will be emitted to the client, once photobooth finished the task

This functionality is experimental and largely untested. 

<hr>

### How do I enable Kiosk Mode to automatically start Photobooth in full screen?
Edit the LXDE Autostart Script:
```
sudo nano /etc/xdg/lxsession/LXDE-pi/autostart
```
and add the following lines:
```
@xset s off
@xset -dpms
@xset s noblank
@chromium-browser --incognito --kiosk http://localhost/
```
**NOTE:** If you're using QR-Code replace `http://localhost/` with your local IP-Adress (e.g. `http://192.168.4.1`), else QR-Code does not work.

<hr>

#### Enable touch events
If touch is not working on your Raspberry Pi edit the LXDE Autostart Script again
```
sudo nano /etc/xdg/lxsession/LXDE-pi/autostart
```
and add `--touch-events=enabled` for Chromium:
```
@chromium-browser --incognito --kiosk http://localhost/ --touch-events=enabled
```

<hr>

#### How to hide the Mouse Cursor?
There are two options to hide the cursor. The first approach allows you to show the cursor for a short period of time (helpful if you use a mouse and just want to hide the cursor of some time of inactivity), or to hide it permanently.

**Solution A**
To hide the Mouse Cursor we'll use "unclutter":
```
sudo apt-get install unclutter
```
Edit the LXDE Autostart Script again:
```
sudo nano /etc/xdg/lxsession/LXDE-pi/autostart
```
and add the following line (0 describes the time after which the cursor should be hidden):
```
@unclutter -idle 0
```

**Solution B**
If you are using LightDM as display manager, you can edit `/etc/lightdm/lightdm.conf` to hide the cursor permanently. Just add `xserver-command=X -nocursor` to the end of the file.

<hr>

### How to disable the blank screen on Raspberry Pi (Raspbian)?
You can follow the instructions [here](https://www.geeks3d.com/hacklab/20160108/how-to-disable-the-blank-screen-on-raspberry-pi-raspbian/) to disable the blank screen.

<hr>

### How to use a live stream as background at countdown?
There's different ways depending on your needs and personal setup:

1. If you access Photobooth on your Raspberry Pi you could use a Raspberry Pi Camera. Raspberry Pi Camera will be detected as "device cam".
    - Admin panel config "See preview by device cam": `true`

    **Note:**
    - Preview by "device cam" will always use the camera of the device where Photobooth get opened in a Browser (e.g. on a tablet it will always show the tablet camera while on a smartphone it will always show the smartphone camera instead)!
    - Secure origin or exception required!
      - [Prefer Secure Origins For Powerful New Features](https://medium.com/@Carmichaelize/enabling-the-microphone-camera-in-chrome-for-local-unsecure-origins-9c90c3149339)
      - [Enabling the Microphone/Camera in Chrome for (Local) Unsecure Origins](https://www.chromium.org/Home/chromium-security/prefer-secure-origins-for-powerful-new-features)
    - Admin panel config *"Device cam takes picture"* can be used to take a picture from this preview instead using gphoto / digicamcontrol / raspistill.

2. If you like to have the same preview independent of the device you access Photobooth from:
    - Make sure to have a stream available you can use (e.g. from your Webcam, Smartphone Camera or Raspberry Pi Camera)
    - Admin panel config *"Preview from URL"*: `true`
    - Admin panel config *"Preview-URL"* example (add needed IP address instead): `url(http://127.0.0.1:8081)`

    **Note**
    - Do NOT enable *"Device cam takes picture"* in admin panel config!
    - Capture pictures via `raspistill` won't work if motion is installed!
    - Requires Photobooth v2.2.1 or later!

<hr>

### Can I use a live stream as background?
Yes you can. There's different ways depending on your needs and personal setup:

1. On Photobooth v2.4.0 and newer you can use the option "Use stream from device cam as background" inside admin panel.
    - If enabled, a stream from your device cam is used as background on start screen. It's still possible to use preview from your device cam as background on countdown and also still possible to take pictures via device cam or using `raspistill` for Pi Camera.

2. You need to change the background URL path via config or admin panel. Replace `url(../img/bg.jpg)` with your IP-Adress and port (if needed) as URL.
    Example:
    ```
    -   url(../img/bg.jpg)
    +   url(http://127.0.0.1:8081)
    ```

    To use a Raspberry Pi Camera module Motion is required, but you won't be able to use the Raspberry Pi Camera 
    for preview at countdown!
    ```
    sudo apt-get install -y motion
    ```
    /etc/motion/motion.conf needs to be changed to your needs (e.g. starting on boot, using videoX, resolution 
    etc.).
    If you're accessing Photobooth from an external device (e.g. Tablet or Mobile Phone) replace `127.0.0.1` 
    with your IP-Adress.

    For reference:
    https://github.com/andreknieriem/photobooth/pull/20

<hr>

### I've trouble setting up E-Mail config. How do I solve my problem?
If connection fails some help can be found [here](https://github.com/PHPMailer/PHPMailer/wiki/Troubleshooting), especially gmail needs some special config.

- Should be obvious but the photobooth must be connected to WIFI/internet to send photos live.

  Otherwise, tell them to check the box to send them the photo later and it will add everyone's email to a list for you.

- For gmail you need to generate an app password if you have 2-factor authentication on.

Tested working setup:

- gmail.com
  - Email host adress: `smtp.gmail.com`
  - Username: `*****@gmail.com`
  - Port: `587`
  - Security: `TLS`

- gmx.de
  - Email host adress: `mail.gmx.net`
  - Username: `*****@gmx.de`
  - Port: `587`
  - Security: `TLS`

- web.de
  - Email host adress: `smtp.web.de`
  - Username: `*****` (@web.de is not needed in your username)
  - Port: `587`
  - Security: `TLS`

<hr>

### How to only open the gallery to avoid people taking pictures?
Open [http://localhost/gallery.php](http://localhost/gallery.php) in your browser (you can replace `localhost` with your IP adress).

<hr>

### Chromakeying isn't working if I access the Photobooth page on my Raspberry Pi, but it works if I access Photobooth from an external device (e.g. mobile phone or tablet). How can I solve the problem?
Open `chrome://flags` in your browser.
Look for *"Accelerated 2D canvas"* and change it to `"disabled"`.
Now restart your Chromium browser.

<hr>

### How to update or add translations?
**On v2.3.0 and newer:**  
Photobooth joined Crowdin as localization manager, [join here](https://crowdin.com/project/photobooth) to translate Photobooth.  
Crowdin gives a nice webinterface to make translating easy as possible. If there's different translations for a string, translator can use the vote function on suggested translations.  
With Crowdin and your help translating we're able to get high-quality translations for all supported languages. Also it's easy to support a wider range of languages!  
Your language is missing? Don't worry, create a [localization request here](https://github.com/andi34/photobooth/issues/new/choose) and we'll add it to the project.  

**On v2.2.0 and older:**  
Edit the language file inside `resources/lang/` with your favorite text editor.  
Once you're done upload your changes and create a [pull request](https://github.com/andi34/photobooth/pulls).

<hr>

### How to ajust the ```php.ini``` file?
Open [http://localhost/phpinfo.php](http://localhost/phpinfo.php) in your browser.
Take a look for "Loaded Configuration File", you need  sudo rights to edit the file.
Page will look like this:
<details><summary>CLICK ME</summary>
<img src="../resources/img/faq/php-ini.png">
</details>

<hr>

### Turn Photobooth into a WIFI hotspot
If you would like to allow your guests to download their images without connecting to your private WIFI or when there is no other WIFI around, you can turn your Raspberry Pi into setup an access point and WiFi client/station network on the single WiFi chip of the Raspberry Pi.

The default setting is to call your wifi hotspot *Photobooth* as this is built into the Photobooth prompt for guests to download images via QR code.

First head over to the hotspot directory to run the installer:
```
cd /var/www/html/vendor/rpihotspot
```
There are a couple of flags you need to change from the example command below:
 - change `password` to your desired password, make it easy enough for guests to remember.
 - change `country code` from `CA` to your own localization.
 - keep or change the ip address `10.10.10.10`. Remember what you change it to.

```
sudo ./setup-network.sh --install-upgrade --ap-ssid="Photobooth" --ap-password="password" --ap-password-encrypt
--ap-country-code="CA" --ap-ip-address="10.10.10.10" --wifi-interface="wlan0"
```
If you run into any errors setting up your hotspot we can remove all the settings and try it again. The first time I ran this I ran into an error, I reset it using the command below, then reinstalled it. It went smoothly the second time:

```
sudo ./setup-network.sh --clean
```


<?php
session_start();

require_once('../lib/config.php');

// Login / Authentication check
if (
    !$config['login']['enabled'] ||
    (!$config['protect']['localhost_manual'] && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) ||
    ((isset($_SESSION['auth']) && $_SESSION['auth'] === true) || !$config['protect']['manual'])
) {
    require_once('../lib/configsetup.inc.php');
} else {
    header('location: ../login');
    exit();
}

?>
<!DOCTYPE html>
<html>
  <head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0 user-scalable=no">

	<title><?=$config['ui']['branding']?> FAQ</title>

	<!-- Favicon + Android/iPhone Icons -->
	<link rel="apple-touch-icon" sizes="180x180" href="../resources/img/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="../resources/img/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="../resources/img/favicon-16x16.png">
	<link rel="manifest" href="../resources/img/site.webmanifest">
	<link rel="mask-icon" href="../resources/img/safari-pinned-tab.svg" color="#5bbad5">

	<!-- Fullscreen Mode on old iOS-Devices when starting photobooth from homescreen -->
	<meta name="apple-mobile-web-app-capable" content="yes" />
	<meta name="apple-mobile-web-app-status-bar-style" content="black" />
    <link rel="stylesheet" type="text/css" href="../node_modules/github-markdown-css/github-markdown.css">
  </head>
  <body class="markdown-body" style="padding: 5rem;">

<h1>FAQ - Frequently asked questions</h1>

<h3>Is my Camera supported?</h3>

<p>Some DSLR and Compact Cameras are not supported by this project. Please check for your specific model <a href="http://gphoto.org/proj/libgphoto2/support.php">here</a>.</p>

<hr />

<h3>Is Pi Camera supported?</h3>

<p>Yes it is.</p>

<p>Enable camera support using the <code>raspi-config</code> program you will have used when you first set up your Raspberry Pi.</p>

<pre><code>
sudo raspi-config
</code></pre>

<p>Use the cursor keys to select and open Interfacing Options, and then select Camera and follow the prompt to enable the camera.</p>

<p>Now you need to allow the webserver to use <code>raspistill</code> / <code>libcamera-still</code>. You need add the webserver user to video group and reboot once:</p>

<pre><code>
sudo gpasswd -a www-data video
reboot
</code></pre>

<p>Once done you need to adjust the configuration. Open the admin panel in your browser <a href="http://localhost/admin">localhost/admin</a> and make the following changes:</p>

<p><strong>"Take picture command on Pi OS based on bullseye":</strong></p>

<p><code>libcamera-still -n -o %s -q 100 -t 1 | echo Done</code></p>

<p><strong>"Take picture command on Pi OS based on buster":</strong></p>

<p><code>raspistill -n -o %s -q 100 -t 1 | echo Done</code></p>

<p>Pi Camera works with these config change (also works together with preview at countdown if enabled).</p>

<p>Raspistill / libcamera-still does not give any feedback after the picture was taken, workaround for that with "echo".</p>

<p>(Thanks to Andreas Maier for that information)</p>

<p>You've the possibility to add more parameters if needed (define ISO, exposure, white balance etc.). Type <code>raspistill -?</code> / <code>libcamera-still -?</code>in your terminal to get information about possible parameters / settings.</p>

<hr />

<h3>I've found a bug, how can I report?</h3>

<p>Please take a look at the issue page <a href="https://github.com/PhotoboothProject/photobooth/issues">here</a>, if your bug isn't mentioned already you can create a new issue. Please give informations detailed as possible to reproduce and analyse the problem.</p>

<hr />

<h3>I've a white page after updating to latest Source, how can I solve this?</h3>

<p>On v1.9.0 and older:</p>

<p>It could be your local <code>config.json</code> file doesn't match latest source. This file is generated if you've used the admin panel to change your config.<br />Remove the file and try again!</p>

<pre><code>
sudo rm /var/www/html/admin/config.json
</code></pre>

<hr />

<h3>How do I change the configuration?</h3>

<p>Open <code><a href='http://localhost/admin'>http://localhost/admin</a></code> in your Webbrowser and change the configuration for your personal needs.<br />Changed options are stored inside <code>config/my.config.inc.php</code> to prevent sharing personal data on Github by accident and to make an update of Photobooth easier.</p>

<hr />

<h3>How to change the language?</h3>

<p>Open <code><a href='http://localhost/admin'>http://localhost/admin</a></code> in your Webbrowser and change the configuration for your personal needs.</p>

<hr />

<h3>How to update or add translations?</h3>

<h4>On v2.3.0 and newer:</h4>

<p>Photobooth joined Crowdin as localization manager, <a href="https://crowdin.com/project/photobooth">join here</a> to translate Photobooth.</p>

<p>Crowdin gives a nice webinterface to make translating easy as possible. If there's different translations for a string, translator can use the vote function on suggested translations.</p>

<p>With Crowdin and your help translating we're able to get high-quality translations for all supported languages. Also it's easy to support a wider range of languages!</p>

<p>Your language is missing? Don't worry, create a <a href="https://github.com/PhotoboothProject/photobooth/issues/new/choose">localization request here</a> and we'll add it to the project.</p>

<h4>On v2.2.0 and older:</h4>

<p>Edit the language file inside <code>resources/lang/</code> with your favorite text editor.</p>

<p>Once you're done upload your changes and create a <a href="https://github.com/PhotoboothProject/photobooth/pulls">pull request</a>.</p>

<hr />

<h3>How can I test my current photo settings?</h3>

<p>Open <a href="http://localhost/test/photo.php"><a href='http://localhost/test/photo.php'>http://localhost/test/photo.php</a></a> in your Webbrowser and a you can find a photo that is created with your current settings.</p>

<hr />

<h3>How can I test my current collage settings?</h3>

<p>Open <a href="http://localhost/test/collage.php"><a href='http://localhost/test/collage.php'>http://localhost/test/collage.php</a></a> in your Webbrowser and a you can find a collage that is created with your current settings.</p>

<hr />

<h3>How can setup a custom collage design?</h3>

<p>In the collage settings you can select the layout <code>private/collage.json</code>. This references a file with the given name in the photobooth's <code>private</code> folder. This file has to be created manually.</p>

<p>Content of the file is an array of arrays. The outer array defines the number of images, the inner array defines the horizontal position, vertical position, width, height and rotation (in that order) of one image.<br />For calculation of the values the variables x and y get converted to the width and height of the collage respectively, additionally math operations +, -, *, / and () can be used to calculate values.<br />The following example should look exactly like the 1+2 layout (this layout looks more complicated than it is due to the decimal places).</p>

<div class="highlight"><pre lang="">[
[ "0",                     "y * 0.055",           "1.5 * y * 0.55546",   "y * 0.55546",   "10"       ],
[ "x * 0.555",             "y * 0.055",           "1.5 * y * 0.40812",   "y * 0.40812",   "0"        ],
[ "x * 0.555",             "y * 0.5368",          "1.5 * y * 0.40812",   "y * 0.40812",   "0"        ]
]
</pre></div>

<div class="highlight"><pre lang="">[ "horizontal position",   "vertical position",   "width",               "height",        "rotation" ]
</pre></div>

<p>Please note that if the number of images in a collage design was changed the admin page has to be saved again to calculate the correct number of photos to be used for a collage.<br />Other value changes can be checked on the collage test page immediatly with a simple reload - so it's quite easy to configure a layout with the help of <a href="http://localhost/test/collage.php"><a href='http://localhost/test/collage.php'>http://localhost/test/collage.php</a></a>.<br />The file <code>collage.json</code> needs to be a well-formed json array and something like a missing quotation or a trailing comma can be enough to make a design fail.</p>

<hr />

<h3>How to change the look of my Photobooth?</h3>

<p>Photobooth can be easylie styled for your personal needs via admin panel, open <a href="http://localhost/admin">localhost/admin</a> in your browser and take a look at the <code>User Interface</code> options.</p>

<p>To use a private custom index you need to create the following files:</p>

<ul>
<li><code>resources/css/custom_style.css</code>
<ul><li>Optional: <code>src/sass/custom_style.scss</code> (<code>yarn build</code> will create the <code>resources/css/custom_style.css</code> out of it)</li></ul></li>
<li><code>resources/css/custom_admin.css</code>
<ul><li>Optional: <code>src/sass/custom_admin.scss</code> (<code>yarn build</code> will create the <code>resources/css/custom_admin.css</code> out of it)</li></ul></li>
<li><code>resources/css/custom_chromakeying.css</code>
<ul><li>Optional: <code>src/sass/custom_chromakeying.scss</code> (<code>yarn build</code> will create the <code>resources/css/custom_chromakeying.css</code> out of it)</li></ul></li>
<li><code>resources/css/custom_live_chromakeying.css</code>
<ul><li>Optional: <code>src/sass/custom_live_chromakeying.scss</code> (<code>yarn build</code> will create the <code>resources/css/custom_live_chromakeying.css</code> out of it)</li></ul></li>
<li><code>template/custom.template.php</code></li>
</ul>

<p>At least one of these custom style files need to exist! If other custom style files are missing a copy of the modern style file will be used.</p>

<p>Once you've created needed files you will be able to use the selection <code>custom</code> from the <code>"Styling"</code> option.</p>

<p><strong>Please note</strong>: the custom style and template will not be tracked by git to avoid sharing by accident!</p>

<p>If you have e.g. private backgrounds (maybe files without a usable license) you can create a folder called <code>private</code> inside the root of your Photbooth source. This folder (and subfolders) will not be tracked by git to avoid sharing by accident!</p>

<hr />

<h3>How to keep pictures on my Camera using gphoto2?</h3>

<p>Add <code>--keep</code> (or <code>--keep-raw</code> to keep only the raw version on camera) option for gphoto2 via admin panel:</p>

<pre><code>
gphoto2 --capture-image-and-download --keep --filename=%s
</code></pre>

<p>On some cameras you also need to define the capturetarget because Internal RAM is used to store captured picture. To do this use <code>--set-config capturetarget=X</code> option for gphoto2 (replace "X" with the target of your choice):</p>

<pre><code>
gphoto2 --set-config capturetarget=1 --capture-image-and-download --keep --filename=%s
</code></pre>

<p>To know which capturetarget needs to be defined you need to run:</p>

<pre><code>
gphoto2 --get-config capturetarget
</code></pre>

<p>Example:</p>

<div class="highlight"><pre lang="">pi@raspberrypi:~ $ gphoto2 --get-config capturetarget
Label: Capture Target
Readonly: 0
Type: RADIO
Current: Internal RAM
Choice: 0 Internal RAM
Choice: 1 Memory card
</pre></div>

<hr />

<h3>Cromakeying is saving without finishing saving</h3>

<p>Checking the browser console you'll see a <code>413 Request Entity Too Large</code> error. To fix that you'll have to update your nginx.conf</p>

<p>Follow the steps mentioned here: <a href="https://datanextsolutions.com/blog/how-to-fix-nginx-413-request-entity-too-large-error/">How to Fix NGINX 413 Request Entity Too Large Error</a></p>

<hr />

<h3>Can I use Hardware Button to take a Picture?</h3>

<p>Yes, there's different ways!</p>

<h4>Key code using connected HID devices</h4>

<p>An HID device connected to your hardware can trigger different actions on your device. The HID device must be connected to the device you're accessing Photobooth from!</p>

<p>For example use <a href="https://keycode.info" target="_blank"><a href='https://keycode.info'>https://keycode.info</a></a> to find out the key id of the button you like to use.</p>

<ul>
<li><p>Related configuration:</p>

<p><strong>PICTURE section</strong>:</p>

<ul><li>Key code which triggers a picture: <strong>define</strong></li></ul>

<p><strong>COLLAGE section</strong>:</p>

<ul><li>Key code which triggers a collage: <strong>define</strong></li></ul>

<p><strong>PRINT section</strong>:</p>

<ul><li>Key code which triggers printing: <strong>define</strong></li></ul></li>
</ul>

<h4>Remotebuzzer Hardware Button feature using GPIO connected hardware (Raspberry Pi only)</h4>

<p><strong>Important:</strong> Works if you access Photobooth via <a href="http://localhost"><a href='http://localhost'>http://localhost</a></a> or <a href="#"><a href='http://your-ip-adress'>http://your-ip-adress</a></a>, but accessing via the loopback IP (127.0.0.1) does not work!</p>

<p>The <strong>Hardware Button</strong> feature enables to control Photobooth through hardware buttons connected to Raspberry GPIO pins. This works for directly connected screens and as well for WLAN connected screen (i.e. iPad). Configuration takes place in the admin settings - Hardware Button section.</p>

<p>Using the Remotebuzzer feature makes the button action taking effect at the same time on all devices accessing Photobooth!</p>

<p>The Hardware Button functionality supports two separate modes of operation (select via admin panel):</p>

<ul>
<li><strong>Buttons</strong>: Distinct hardware buttons can be connected to distinct GPIOs. Each button will trigger a separate functionality (i.e. take photo).</li>
<li><strong>Rotary Encoder</strong>: A rotary encoder connected to GPIOs will drive the input on the screen. This enables to use the rotary to scroll through the Photobooth UI buttons, and click to select actions. </li>
</ul>

<p>Both buttons and rotary encoder controls can be combined.</p>

<p>Photobooth will watch GPIOs for a PIN_DOWN event - so the hardware button needs to pull the GPIO to ground, for to trigger. This requires the GPIOs to be configured in PULLUP mode - always. </p>

<h5>Troubleshooting / Debugging</h5>

<p><strong>Important: For WLAN connected screens you must make sure to set the IP address of the Photobooth web server in the admin settings - section "General"</strong>. The loopback IP (127.0.0.1) does not work, it has to be the exact IP address of the Photobooth web server, to which the remote display connects to.</p>

<p>Having trouble?<br />- Set Photobooth loglevel to 1 (or above). (admin screen -> general section)<br />- Reload the Photobooth homepage<br />- Check the browser developer console for error logs<br />- Check the server logs for errors at the Debug panel: <a href="http://localhost/admin/debugpanel.php"><a href='http://localhost/admin/debugpanel.php'>http://localhost/admin/debugpanel.php</a></a><br />- If there is no errors logged but hardware buttons still do not trigger:<br />  - GPIO interrupts might be disabled. Check file <code>/boot/config.txt</code> and remove / disable the following overlay <code>dtoverlay=gpio-no-irq</code> to enable interrupts for GPIOs.<br />  - GPIOs may not be configured as PULLUP. The configuration for this is done in fie <code>/boot/config.txt</code> by adding the GPIO numbers in use as follows - you <strong>must reboot</strong> the Raspberry Pi in order to activate changes in this setting.<br />    <div class="highlight"><pre lang="">    gpio=16,17,20,21,22,26,27=pu<br />    </pre></div><br />- For the Shutdown button to work, <code>www-data</code> needs to have the necessary sudo permissions. This is done by the <code>install-photobooth.sh</code> script or can be manually added as<br />    <div class="highlight"><pre lang="sh">    cat >> /etc/sudoers.d/020_www-data-shutdown &lt;&lt; EOF<br />    www-data ALL=(ALL) NOPASSWD: /sbin/shutdown<br />    EOF<br />    </pre></div></p>

<p>As of Photobooth v3, hardware button support is fully integrated into Photobooth. Therefore the <code>button.py</code> script has been removed from the distribution. In case you are using this script and for continued backward compatibility please do not activate the Remote Buzzer Hardware Button feature in the admin GUI. Please note that continued backward compatibility is not guaranteed and in case of issues please switch to the integrated functionality.</p>

<h5>Button Support</h5>

<p>The server supports up to four connected hardware buttons for the following functionalities:</p>

<p>1) <strong>Picture Button</strong></p>

<ul>
<li>Defaults to GPIO21</li>
<li>Short button press (default &lt;= 2 sec) will trigger a single picture in Photobooth</li>
<li>Long button press (default > 2 sec) will trigger a collage in Photobooth</li>
</ul>

<p><strong>Note:</strong></p>

<ul>
<li>If collage is configured with interruption, next button presses will trigger the next collage pictures. </li>
<li>If collage is disabled in the admin settings, long button press also triggers a single picture</li>
<li>If the collage button is activated (see next), the picture button will never trigger a collage, regardless</li>
</ul>

<p>2)  <strong>Collage Button</strong></p>

<ul>
<li>Defaults to GPIO20</li>
<li>Button press will trigger a collage in Photobooth.</li>
</ul>

<p><strong>Note:</strong></p>

<ul>
<li>If collage is configured with interruption, next button presses will trigger the next collage pictures. </li>
<li>If collage is disabled in the admin settings (Collage section), this button will do nothing.</li>
</ul>

<p>3) <strong>Shutdown Button</strong></p>

<ul>
<li>Defaults to GPIO16</li>
<li>This button will initate a safe system shutdown and halt (<code>shutdown -h now</code>).</li>
</ul>

<p><strong>Note:</strong></p>

<ul>
<li>Hold the button for a defined time to initiate the shut down (defaults to 5 seconds). This can be adjusted in the admin settings.</li>
<li>The shutdown button will only trigger if there is currently no action in progress in Photobooth (picture, collage). </li>
</ul>

<p>4) <strong>Print Button</strong></p>

<ul>
<li>Defaults to GPIO26</li>
<li>This button will initiate a print of the current picture either from the results screen or the gallery.</li>
</ul>

<p>After any button is triggered, all hardware button remain disabled until the action (picture / collage) completed. Once completed, the hardware buttons re-arms / are active again.</p>

<p>The wiring layout is</p>

<div class="highlight"><pre lang="">Button            Raspberry

Picture     ---   GPIO 21
Collage     ---   GPIO 20
Shutdown    ---   GPIO 16
Print       ---   GPIO 26
All         ---   GND
</pre></div>

<h5>Rotary Encoder</h5>

<p>A rotary encoder (i.e. <a href="https://sensorkit.en.joy-it.net/index.php?title=KY-040_Rotary_encoder">KY-040</a>) is connected to the GPIOs. Turning the rotary left / right will navigate through the currently visible set of buttons on the screen. Button press on the rotary will activate the currently highlighted button in Photobooth.</p>

<p>The wiring layout is</p>

<pre><code>
Encoder    Raspberry

CLK  ---   GPIO 27
DT   ---   GPIO 17
BTN  ---   GPIO 22
+    ---   3V3
GND  ---   GND
</code></pre>

<h5>Known limitations:</h5>

<ul>
<li>Delete Picture: in order to be able to access the Delete button through rotary control, please activate admin setting General -> "Delete images without confirm request"</li>
</ul>

<p>The following elements are currently not supported and not accessible through rotary control navigation</p>

<ul>
<li>Full Screen Mode button: Looks like modern browser only allow to change to full screen mode upon user gesture. It seems not possible to change to full-screen using Javascript.</li>
<li>Photoswipe download button: Not needed for Rotary Control. (well, if you can come up with a decent use-case, let us know).</li>
</ul>

<h4>Remote trigger using Socket.io (experimental)</h4>

<p>The trigger server controls and coordinates sending commands via socket.io to the photobooth client. Next to a hardware button, any socket.io client can connect to the trigger server over the network, and send a trigger command. This gives full flexibility to integrate other backend systems for trigger signals.</p>

<ul>
<li>Channel:  <code>photobooth-socket</code></li>
<li>Commands: <code>start-picture</code>, <code>start-collage</code></li>
<li>Response: <code>completed</code> will be emitted to the client, once photobooth finished the task</li>
</ul>

<p>This functionality is experimental and largely untested. Not sure if there is a use-case but if you have one, happy to learn about it. Currently this does not support rotary encoder use but could be if needed.</p>

<h4>Remote trigger using simple web requests</h4>

<p><em>Note: This feature depends on the experimental Socket.io implementation and needs option <code>Hardware Button</code> - <code>Enable Hardware Buttons</code> to be active.</em></p>

<p>Simple <code>GET</code> requests can be used to trigger single pictures or collages. Those endpoints can be found under <code><a href='http://[Photobooth'>http://[Photobooth</a> IP]:[Hardware Button Server Port]</code> where:<br />- <code>[Photobooth IP]</code> needs to match the configured value under <code>General</code> - <code>IP address of the Photobooth web server</code> and<br />- <code>[Hardware Button Server Port]</code> the value from <code>Hardware Button</code> - <code>Enable Hardware Buttons</code></p>

<p>The available endpoints are:<br />- <code>[Base Url]/</code> - Simple help page with all available endpoints<br />- <code>[Base Url]/commands/start-picture</code> - Triggers a single picture<br />- <code>[Base Url]/commands/start-collage</code> - Triggers a collage</p>

<p>These trigger URLs can be used for example with <a href="https://mystrom.com/wifi-button/">myStrom WiFi Buttons</a> or <a href="https://shelly.cloud/products/shelly-button-1-smart-home-automation-device/">Shelly Buttons</a> (untested).</p>

<h5>Installation steps for myStrom WiFi Button</h5>

<ul>
<li>Be sure to connect the button to the same network as the photobooth</li>
<li><p>The button can be configured using the following commands</p>

<div class="highlight"><pre lang="sh">curl --location -g --request POST <a href='http://[Button'>http://[Button</a> IP]/api/v1/action/single --data-raw get://[Photobooth IP]:[Hardware Button Server Port]/commands/start-picture


curl --location -g --request POST <a href='http://[Button'>http://[Button</a> IP]/api/v1/action/long --data-raw get://[Photobooth IP]:[Hardware Button Server Port]/commands/start-collage
</pre></div></li>
</ul>

<hr />

<h3>How do I enable Kiosk Mode to automatically start Photobooth in full screen?</h3>

<p>Add the autostart file:</p>

<pre><code>
sudo nano /etc/xdg/autostart/photobooth.desktop
</code></pre>

<p>now add the following lines:</p>

<div class="highlight"><pre lang="">[Desktop Entry]
Version=1.3
Terminal=false
Type=Application
Name=Photobooth
Exec=chromium-browser --noerrdialogs --disable-infobars --disable-features=Translate --no-first-run --check-for-update-interval=31536000 --kiosk <a href='http://localhost'>http://localhost</a> --touch-events=enabled --use-gl=egl
Icon=/var/www/html/resources/img/favicon-96x96.png
StartupNotify=false
Terminal=false
</pre></div>

<p>save the file.</p>

<p><strong>NOTE:</strong></p>

<p>If you have installed Photobooth inside a subdirectory (e.g. to <code>/var/www/html/photobooth</code>), make sure you adjust the kiosk url (e.g. to <code><a href='http://localhost/photobooth'>http://localhost/photobooth</a></code>) and the Icon path (e.g. to <code>/var/www/html/photobooth/resources/img/favicon-96x96.png</code>).</p>

<p>The flag <code>--use-gl=egl</code> might only be needed on a Raspberry Pi to avoid a white browser window on the first start of kiosk mode! If you're facing issues while using Photobooth on a different device, please remove that flag.</p>

<hr />

<h4>How to hide the mouse cursor, disable screen blanking and screen saver?</h4>

<p>There are two options to hide the cursor. The first approach allows you to show the cursor for a short period of time (helpful if you use a mouse and just want to hide the cursor of some time of inactivity), or to hide it permanently.</p>

<h5>Solution A</h5>

<p>To hide the Mouse Cursor we'll use "unclutter":</p>

<pre><code>
sudo apt-get install unclutter
</code></pre>

<p>Edit the LXDE Autostart Script:</p>

<pre><code>
sudo nano /etc/xdg/lxsession/LXDE-pi/autostart
</code></pre>

<p>and add the following lines:</p>

<div class="highlight"><pre lang="">

<h1>Photobooth</h1>



<h1>turn off display power management system</h1>

@xset -dpms


<h1>turn off screen blanking</h1>

@xset s noblank


<h1>turn off screen saver</h1>

@xset s off



<h1>Hide mousecursor (3 describes the time after which the cursor should be hidden)</h1>

@unclutter -idle 3


<h1>Photobooth End</h1>

</pre></div>

<h5>Solution B</h5>

<p>If you are using LightDM as display manager, you can edit <code>/etc/lightdm/lightdm.conf</code> to hide the cursor permanently. Just add <code>xserver-command=X -nocursor</code> to the end of the file.</p>

<hr />

<h3>How to use a live stream as background at countdown?</h3>

<p>There's different ways depending on your needs and personal setup:</p>

<h4>Preview <em>"from device cam"</em></h4>

<p>If you access Photobooth on your Raspberry Pi you could use a Raspberry Pi Camera. Raspberry Pi Camera will be detected as "device cam".</p>

<ul>
<li>Admin panel config "Preview mode": <code>from device cam</code></li>
</ul>

<p><strong>Note:</strong></p>

<ul>
<li>Preview <code>"from device cam"</code> will always use the camera of the device where Photobooth get opened in a Browser (e.g. on a tablet it will always show the tablet camera while on a smartphone it will always show the smartphone camera instead)!</li>
<li>Secure origin or exception required!
<ul><li><a href="https://medium.com/@Carmichaelize/enabling-the-microphone-camera-in-chrome-for-local-unsecure-origins-9c90c3149339">Prefer Secure Origins For Powerful New Features</a></li>
<li><a href="https://www.chromium.org/Home/chromium-security/prefer-secure-origins-for-powerful-new-features">Enabling the Microphone/Camera in Chrome for (Local) Unsecure Origins</a></li></ul></li>
<li>Admin panel config <em>"Device cam takes picture"</em> can be used to take a picture from this preview instead using gphoto / digicamcontrol / raspistill / libcamera-still.</li>
</ul>

<h4>Preview from DSLR</h4>

<p>By now the DSLR handling of Photobooth on Linux was done exclusively using <code>gphoto2 CLI</code> (command line interface). When taking pictures while using preview video from the same camera one command has to be stopped and another one is run after that.</p>

<p>The computer terminates the connection to the camera just to reconnect immediately. Because of that there was an ugly video gap and the noises of the camera could be irritating as stopping the video sounded very similar to taking a picture. But most cameras can shoot quickly from live-view.</p>

<p>The underlying libery of <code>gphoto2 CLI</code> is <code>libgphoto</code> and it can be accessed using several programming languages. Because of this we can have a python script that handles both preview and taking pictures without terminating the connection to the camera in between.</p>

<p><strong>From Photobooth v4.1.0 a preview from DSLR depends on the <em>"Preview from device cam"</em> config</strong></p>

<p>To use <code>gphoto-python</code>, first execute the <code>install-gphoto-python.sh</code> if you have not already installed "a service to set up a virtual webcam that gphoto2 can stream video to" while using the Photobooth installer on initial installation:</p>

<pre><code>
wget <a href='https://raw.githubusercontent.com/PhotoboothProject/photobooth/dev/gphoto/install-gphoto-python.sh'>https://raw.githubusercontent.com/PhotoboothProject/photobooth/dev/gphoto/install-gphoto-python.sh</a>
sudo bash install-gphoto-python.sh
</code></pre>

<p>Change your Photobooth configuration:</p>

<ul>
<li><em>"Live Preview</em>": <em>"Preview Mode"</em>: <em>"from device cam"</em></li>
<li><em>"Commands</em>": <em>"Execute start command for preview on take picture/collage"</em>:
<ul><li>if <strong>enabled</strong>:
<em>"Commands"</em>: <em>"Command to generate a live preview"</em>: <code>python3 cameracontrol.py --bsm</code></li>
<li>if <strong>disabled</strong>:
<em>"Commands"</em>: <em>"Command to generate a live preview"</em>: <code>python3 cameracontrol.py</code></li></ul></li>
<li><em>"Commands"</em>: <em>"Take picture command"</em>: <code>python3 cameracontrol.py --capture-image-and-download %s</code></li>
</ul>

<p><strong>Further information</strong>:</p>

<p>The <em>"Command to generate a live preview"</em> is only executed if the <em>"Preview Mode"</em> is set to <em>"from device cam"</em>.</p>

<p>There's no need to define the <em>"Command to kill live preview"</em> while using the <em>cameracontrol.py</em>, so just empty that field. The <em>"Command to kill live preview"</em> is only executed if defined.</p>

<p>If you want to use the DSLR view as background video, enable <em>"Use stream for live preview as background"</em> and disable the <em>"Execute start command for preview on take picture/collage"</em> setting of Photobooth, which is enabled by default.</p>

<p>If you don't want to use the DSLR view as background video enable the <em>Execute start command for preview on take picture/collage</em> setting of Photobooth and make sure <code>--bsm</code> was added to the preview command.</p>

<pre><code>
python3 cameracontrol.py --bsm
</code></pre>

<p>If <em>Execute start command for preview on take picture/collage</em> is enabled, the preview video is activated when the countdown for a photo starts and after taking a picture the video is deactivated while waiting for the next photo.</p>

<p>As you possibly noticed the params of the script are designed to be similar to the ones of <code>gphoto2 CLI</code> but with some shortcuts like <code>-c</code> for <code>--capture-image-and-download</code>. If you want to know more check out the help of the script by running:</p>

<pre><code>
python3 /var/www/html/api/cameracontrol.py --help
</code></pre>

<p>or on subfolder installation of Photobooth</p>

<pre><code>
python3 /var/www/html/photobooth/api/cameracontrol.py --help
</code></pre>

<p>If you want to keep your images on the camera you need to use the same <code>capturetarget</code> config as when you were using <code>gphoto CLI</code> (see "How to keep pictures on my Camera using gphoto2?"). Set the config on the preview command like this:</p>

<pre><code>
python3 cameracontrol.py --set-config capturetarget=1
</code></pre>

<p>If you get errors from Photobooth and want to get more information try to run the preview command manually. The script is in Photobooth's <code>api</code> folder. To do so end all running services that potentially try to access the camera with <code>killall gphoto2</code> and <code>killall python3</code> (if you added any other python scripts manually you might have to be a bit more selective than this command).</p>

<p>Finally if you just run <code>venv/bin/python3 cameracontrol.py --capture-image-and-download %s</code> as take picture command without having a preview started it only takes a picture without starting any kind of preview and ends the script immediately after the picture.</p>

<p>In theory <code>cameracontrol.py</code> might be able to completely replace <code>gphoto2 CLI</code> for all DSLR connection handling in the future.</p>

<p><strong>Note</strong></p>

<ul>
<li>Liveview <strong>must</strong> be supported for your camera model, <a href="http://gphoto.org/proj/libgphoto2/support.php">check here</a></li>
<li>Give permissions to /dev/video*: <code>sudo gpasswd -a www-data video</code> (this was done automatically if you used the installation script) and reboot once.</li>
<li>Requires Photobooth v4.1.0 or later! (Instructions for older versions have been removed from the FAQ, but an FAQ with instructions matching your installed Photobooth version can always be found at <a href="http://localhost/faq"><a href='http://localhost/faq'>http://localhost/faq</a></a>).</li>
<li>You need to access Photobooth directly via <a href="http://localhost"><a href='http://localhost'>http://localhost</a></a>, you won't be able to see the preview on a different device (e.g. Tablet).</li>
<li>There's a delay of about 3 seconds until the preview starts, to avoid that disable the <code>Execute start command for preview on take picture/collage</code> option to generate a preview in background. <strong>This results in a high battery usage and also a general slowdown.</strong></li>
<li>Chromium sometimes has trouble, if there is another webcam like <code>bcm2835-isp</code>, it will take it by default instead. Disable other webcams, e.g. <code>sudo rmmod bcm2835-isp</code>.</li>
<li>Make sure the countdown is long enough to start the preview, for best user experience the countdown should be set at least to 8 seconds.</li>
</ul>

<p><strong>Troubleshooting</strong></p>

<p>In some cases, the v4l2loopback doesn't seem to be working after an update and breaking the preview from DSLR.</p>

<p>Run <code>v4l2-ctl --list-devices</code> from your terminal to see if everything is fine.</p>

<p>If it works you get the following output:</p>

<div class="highlight"><pre lang="">GPhoto2 Webcam (platform:v4l2loopback-000):
        /dev/video0
</pre></div>

<p>If it doesn't work:</p>

<div class="highlight"><pre lang="">Cannot open device /dev/video0, exiting
</pre></div>

<p>If it doesn't work, you might need to compile the v4l2loopback Module yourself by running the following commands:</p>

<pre><code>
curl -LO <a href='https://github.com/umlaeute/v4l2loopback/archive/refs/tags/v0.12.7.tar.gz'>https://github.com/umlaeute/v4l2loopback/archive/refs/tags/v0.12.7.tar.gz</a>
tar xzf v0.12.7.tar.gz && cd v4l2loopback-0.12.7
make && sudo make install
sudo depmod -a
sudo modprobe v4l2loopback exclusive_caps=1 card_label="GPhoto2 Webcam"
</code></pre>

<p>Now again check if everything is fine (<code>v4l2-ctl --list-devices</code>).</p>

<p>If you're still having trouble feel free to join us at Telegram to get further support.</p>

<h4>Preview <em>"from URL"</em></h4>

<p>If you like to have the same preview independent of the device you access Photobooth from:</p>

<p>Make sure to have a stream available you can use (e.g. from your Webcam, Smartphone Camera or Raspberry Pi Camera)</p>

<ul>
<li>Admin panel config <em>"Preview mode"</em>: <code>from URL</code></li>
<li>Admin panel config <em>"Preview-URL"</em> example (add needed IP address instead): <code>url(<a href='http://192.168.0.2:8081'>http://192.168.0.2:8081</a>)</code></li>
</ul>

<p><strong>Note</strong></p>

<ul>
<li>Do NOT enable <em>"Device cam takes picture"</em> in admin panel config!</li>
<li>Capture pictures via <code>raspistill</code> or <code>libcamera-still</code> won't work if motion is installed!</li>
<li>Requires Photobooth v2.2.1 or later!</li>
</ul>

<hr />

<h3>Can I use a live stream as background?</h3>

<p>Yes you can. There's different ways depending on your needs and personal setup:</p>

<ol>
<li><p>On Photobooth v2.4.0 and newer you can use the option "Use stream from device cam as background" inside admin panel.</p>

<ul><li>If enabled, a stream from your device cam is used as background on start screen. It's still possible to use preview from your device cam as background on countdown and also still possible to take pictures via device cam or using <code>raspistill</code> / <code>libcamera-still</code> for Pi Camera.</li></ul></li>
<li><p>You need to change the background URL path via config or admin panel. Replace <code>url(../img/bg.jpg)</code> with your IP-Adress and port (if needed) as URL.<br />Example:</p>

<div class="highlight"><pre lang="sh"><ul><li>url(../img/bg.jpg)</li>
<li>url(<a href='http://192.168.0.2:8081'>http://192.168.0.2:8081</a>)
</pre></div></li></ul>
To use a Raspberry Pi Camera module Motion is required, but you won't be able to use the Raspberry Pi Camera 
for preview at countdown!


<div class="highlight"><pre lang="sh">sudo apt-get install -y motion
</pre></div>

<p><em>/etc/motion/motion.conf</em> needs to be changed to your needs (e.g. starting on boot, using videoX, resolution, etc.).</p>

<p>If you're accessing Photobooth from an external device (e.g. Tablet or Mobile Phone) replace <code>127.0.0.1</code> with your IP-Adress.</p>

<p>For reference:<br /><a href="https://github.com/andreknieriem/photobooth/pull/20"><a href='https://github.com/andreknieriem/photobooth/pull/20'>https://github.com/andreknieriem/photobooth/pull/20</a></a></p></li>
</ol>

<hr />

<h3>I've trouble setting up E-Mail config. How do I solve my problem?</h3>

<p>If connection fails some help can be found <a href="https://github.com/PHPMailer/PHPMailer/wiki/Troubleshooting">here</a>, especially gmail needs some special config.</p>

<ul>
<li><p>Should be obvious but the photobooth must be connected to WIFI/internet to send photos live.</p>

<p>Otherwise, tell them to check the box to send them the photo later and it will add everyone's email to a list for you.</p></li>
<li><p>For gmail you need to generate an app password if you have 2-factor authentication on.</p></li>
</ul>

<p>Tested working setup:</p>

<ul>
<li><p>gmail.com</p>

<ul><li>Email host adress: <code>smtp.gmail.com</code></li>
<li>Username: <code>*****@gmail.com</code></li>
<li>Port: <code>587</code></li>
<li>Security: <code>TLS</code></li></ul></li>
<li><p>gmx.de</p>

<ul><li>Email host adress: <code>mail.gmx.net</code></li>
<li>Username: <code>*****@gmx.de</code></li>
<li>Port: <code>587</code></li>
<li>Security: <code>TLS</code></li></ul></li>
<li><p>web.de</p>

<ul><li>Email host adress: <code>smtp.web.de</code></li>
<li>Username: <code>*****</code> (@web.de is not needed in your username)</li>
<li>Port: <code>587</code></li>
<li>Security: <code>TLS</code></li></ul></li>
</ul>

<hr />

<h3>How to only open the gallery to avoid people taking pictures?</h3>

<p>Open <a href="http://localhost/gallery.php"><a href='http://localhost/gallery.php'>http://localhost/gallery.php</a></a> in your browser (you can replace <code>localhost</code> with your IP adress).</p>

<hr />

<h3>Chromakeying isn't working if I access the Photobooth page on my Raspberry Pi, but it works if I access Photobooth from an external device (e.g. mobile phone or tablet). How can I solve the problem?</h3>

<p>Open <code>chrome://flags</code> in your browser.</p>

<p>Look for <em>"Accelerated 2D canvas"</em> and change it to <code>"disabled"</code>.</p>

<p>Now restart your Chromium browser.</p>

<hr />

<h3>How to adjust the <code>php.ini</code> file?</h3>

<p>Open <a href="http://localhost/phpinfo.php"><a href='http://localhost/phpinfo.php'>http://localhost/phpinfo.php</a></a> in your browser.</p>

<p>Take a look for "Loaded Configuration File", you need <em>sudo</em> rights to edit the file.</p>

<p>Page will look like this:</p>

<p><details><summary>CLICK ME</summary><br /><img src="../resources/img/faq/php-ini.png" alt="php.ini Screenshot"><br /></details></p>

<hr />

<h3>Turn Photobooth into a WIFI hotspot</h3>

<p>If you would like to allow your guests to download their images without connecting to your private WIFI or when there is no other WIFI around, you can turn your Raspberry Pi into setup an access point and WiFi client/station network on the single WiFi chip of the Raspberry Pi.</p>

<p>The default setting is to call your wifi hotspot <em>Photobooth</em> as this is built into the Photobooth prompt for guests to download images via QR code.</p>

<p>First, make sure <code>iptables</code> package is installed:</p>

<pre><code>
sudo apt-get install iptables
</code></pre>

<p>Now download and run the rpihotspot installer:</p>

<pre><code>
wget <a href='https://raw.githubusercontent.com/idev1/rpihotspot/master/setup-network.sh'>https://raw.githubusercontent.com/idev1/rpihotspot/master/setup-network.sh</a>
chmod +x setup-network.sh
sudo ./setup-network.sh --install-upgrade --ap-ssid="Photobooth" --ap-password="password" --ap-password-encrypt
--ap-country-code="CA" --ap-ip-address="10.10.10.10" --wifi-interface="wlan0"
</code></pre>

<p>There are a couple of flags you need to change from the example command below:</p>

<ul>
<li>change <code>password</code> to your desired password, make it easy enough for guests to remember.</li>
<li>change <code>country code</code> from <code>CA</code> to your own localization.</li>
<li>keep or change the ip address <code>10.10.10.10</code>. Remember what you change it to.</li>
</ul>

<p>If you run into any errors setting up your hotspot we can remove all the settings and try it again. The first time I ran this I ran into an error, I reset it using the command below, then reinstalled it. It went smoothly the second time:</p>

<pre><code>
sudo bash setup-network.sh --clean
</code></pre>

<hr />

<h3>Automatic picture syncing to USB stick</h3>

<p>This feature will automatically and in regular intervals copy (sync) new pictures to a plugged-in USB stick. Currently works on Raspberry PI OS only.</p>

<p>Use the <code>install-photobooth.sh</code> script to get the operating system setup in place.</p>

<p><strong>Note:</strong> If you have declined the question to enable the USB sync file backup while running the <code>install-photobooth.sh</code> you need to run the following commands to get the operating system setup done:</p>

<pre><code>
wget <a href='https://raw.githubusercontent.com/PhotoboothProject/photobooth/dev/enable-usb-sync.sh'>https://raw.githubusercontent.com/PhotoboothProject/photobooth/dev/enable-usb-sync.sh</a>
sudo bash enable-usb-sync.sh -username='<YourUsername>'
</code></pre>

<p>The target USB device is selected through the admin panel.</p>

<p>A USB drive / stick can be identified either by the USB stick label (e.g. <code>photobooth</code>), the operating system specific USB device name (e.g. <code>/dev/sda1</code>) or the USB device system subsystem name (e.g. <code>sda</code>). The preferred method would be the USB stick label (for use of a single USB stick) or the very specific USB device name, for different USB stick use. The default config will look for a drive with the label photobooth. The script only supports one single USB stick connected at a time</p>

<p>Pictures will be synced to the USB stick matched by the pattern, as long as it is mounted (aka USB stick is plugged in)</p>

<p>Debugging: Check the server logs for errors at the Debug panel: <a href="http://localhost/admin/debugpanel.php"><a href='http://localhost/admin/debugpanel.php'>http://localhost/admin/debugpanel.php</a></a></p>

<hr />

<h3>Raspberry Touchpanel DSI simultaneously with HDMI</h3>

<p>When using a touchscreen on DSI and an HDMI screen simultaneously, the touch input is offset. This is because both monitors are recognized as one screen.</p>

<p>The remedy is the following:</p>

<div class="highlight"><pre lang="">xinput list
</pre></div>

<p>remember the device id=[X] of the touchscreen.</p>

<div class="highlight"><pre lang="">xinput list-props "Device Name" 
</pre></div>

<p>Get the ID in brackets (Y) of Coordinate Transformation Matrix</p>

<div class="highlight"><pre lang="">xinput set-prop [X] --type=float [Y] c0 0 c1 0 c2 c3 0 0 1
</pre></div>

<p>adjust the coding c0 0 c1 0 c2 c3 0 0 1 with your own data.</p>

<p>You can get the values of your screens with the following command:</p>

<div class="highlight"><pre lang="">xrandr | grep \* # xrandr uses "*" 
</pre></div>

<p>to identify the screen being used</p>

<div class="highlight"><pre lang="">c0 = touch_area_width / total_width
(width of touch screen divided by width of both screens)
c2 = touch_area_height / total_height
(height touch screen divided by height of both screens)
c1 = touch_area_x_offset / total_width
c3 = touch_area_y_offset / total_height
</pre></div>

<p>and execute the above command again with your own coding!</p>

<p>Example:</p>

<div class="highlight"><pre lang="">xinput set-prop 6 --type=float 136 0.3478260869565217 0 0 0.55555555555556 0 0 0 1
</pre></div>

<p>Now unfortunately the settings are only valid for the current session. So create the following desktop startup file with your own values:</p>

<pre><code>
nano ~/.config/autostart/touch.desktop
</code></pre>

<p>Example:</p>

<div class="highlight"><pre lang="">[Desktop Entry]
Name=TouchSettingsAutostart
Comment=Set up touch screen setting when starting desktop
Type=Application


<h2>Adapt command to own values</h2>

Exec=xinput set-prop 6 --type=float 136 0.3478260869565217 0 0 0 0.55555555555556 0 0 0 1
Terminal=false
</pre></div>

<p>If you want to use the touchscreen as photobooth and the second monitor for the standalone slideshow for example, open the autostart file:</p>

<pre><code>
sudo nano /etc/xdg/lxsession/LXDE-pi/autostart
</code></pre>

<p>and enter/adjust the @chromium-browser entries as followed (adjust the value <em>1920</em> to your own resolution and URL if necessary):</p>

<div class="highlight"><pre lang="">@chromium-browser --new-window --start-fullscreen --kiosk <a href='http://localhost'>http://localhost</a> --window-position=1920,0 --user-data-dir=Default
@chromium-browser --new-window --start-fullscreen --kiosk <a href='http://localhost/slideshow/'>http://localhost/slideshow/</a> --window-position=0,0 --user-data-dir='Profile 1'
</pre></div>

<hr />

<h3>How to administer CUPS remotely using the web interface?</h3>

<p>By default the CUPS webinterface can only be accessed via <a href="http://localhost:631"><a href='http://localhost:631'>http://localhost:631</a></a> from your local machine.</p>

<p>To remote access CUPS from other clients you need to run the following commands:</p>

<pre><code>
sudo cupsctl --remote-any
sudo /etc/init.d/cups restart
</code></pre>

<h3>I get the error message "Something went wrong." while taking a picure, what can i do?</h3>

<p>There's different reasons if you get the error "Something went wrong. Please try it again. Photobooth reloads automatically." while taking an image.</p>

<p>First of all, please set the <strong>Loglevel</strong> to <strong>2</strong> via admin panel (GENERAL section, <a href="http://localhost/admin"><a href='http://localhost/admin'>http://localhost/admin</a></a>) and try again. You'll still see the error message, but we make sure to log enough information to see what's wrong.</p>

<p>Now open the Debug panel (<a href="http://localhost/admin/debugpanel.php"><a href='http://localhost/admin/debugpanel.php'>http://localhost/admin/debugpanel.php</a></a>) and check the Photobooth log for error messages. You should see something like this:</p>

<div class="highlight"><pre lang="">2023-01-03T08:34:37+01:00:
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

<pre><code>[php] =&gt; takePic.php
</code></pre>

)
</pre></div>

<p>Most of the time the error messages are self explained (in our case no camera was detected, the cable wasn't plugged in), if you're still having trouble you can check the following information about possible known problems.</p>

<h4>GPhoto2 troubleshooting</h4>

<p>Please note, that GPhoto2 is an own software we can use to take images via Photobooth. The full documentation can be found at <a href="http://www.gphoto.org/doc/"><a href='http://www.gphoto.org/doc/'>http://www.gphoto.org/doc/</a></a>.</p>

<p><strong>Here are some general known problems you should know about:</strong></p>

<p>Make sure "Image capture" is supported by GPhoto2 for your camera (<a href="http://gphoto.org/proj/libgphoto2/support.php"><a href='http://gphoto.org/proj/libgphoto2/support.php'>http://gphoto.org/proj/libgphoto2/support.php</a></a>)</p>

<p>Try another camera mode.<br />- Sometimes not every camera mode is supported by GPhoto2.</p>

<p>Make sure your camera is set to "JPEG/JPG only", Photobooth isn't able to use RAW images.<br />- Reducing the image quality on your camera can have a positive effect to the performance - especially on low end hardware like a Raspberry Pi.</p>

<p>Disable the auto-focus.<br />- GPhoto2 won't be able to take a picture if your camera can't find a focus.</p>

<p>Turn off the WiFi of your camera (if available).<br />- There might be issues on the connection if WiFi is turned on on your camera.</p>

<p>Make sure a SD-Card is inserted into the camera.<br />- GPhoto2 sometimes has trouble to trigger an image if no SD-Card is inserted.</p>

<p>Make sure pictures aren't taken into the RAM of the camera.<br />- Sometimes we need to define the Capturetarget to the memory card manually. To find out the right capturetarget type the following into your terminal and press enter:</p>

<div class="highlight"><pre lang="">gphoto2 --get-config capturetarget
</pre></div>

<p>Your output will look like this:</p>

<div class="highlight"><pre lang="">pi@raspberrypi:~ $ gphoto2 --get-config capturetarget
Label: Capture Target
Readonly: 0
Type: RADIO
Current: Internal RAM
Choice: 0 Internal RAM
Choice: 1 Memory card   <--- !!!
</pre></div>

<p>Adjust your take picture command via adminpanel accordingly:</p>

<div class="highlight"><pre lang="">gphoto2 --set-config capturetarget=1 --capture-image-and-download --filename=%s
</pre></div>

<h4>Hardware issues</h4>

<p>Enough power on the USB port?</p>

<p>Defect USB cable?</p>

<h4>Permission problem</h4>

<p>It's easy to check if there's an issue with the permissions. The Photobooth installer takes care about needed permissions and shouldn't be a thing.</p>

<p>Sometimes permission can be wrong after an update.</p>

<p>Open your terminal and try to take an image:</p>

<div class="highlight"><pre lang="">gphoto2 --capture-image-and-download --filename=test.jpg
</pre></div>

<p>Everything works?</p>

<ul>
<li><strong>No</strong>: Please again read previous information.</li>
<li><strong>Yes</strong>: let's test further!</li>
</ul>

<p>Now try to take an image as "www-data" User:</p>

<div class="highlight"><pre lang="">cd /var/www/html
sudo -u www-data -s
gphoto2 --capture-image-and-download --filename=test.jpg
</pre></div>

<p>Everything working?</p>

<ul>
<li><strong>Yes</strong>: Check your Photobooth configuration for issues. Maybe reset the config to restore the default settings.</li>
<li><strong>No</strong>: It looks like there's an issue with the permissions! But we can fix that!</li>
</ul>

<p>Set the ownership for Photobooth to the "www-data" User:</p>

<div class="highlight"><pre lang="">sudo chown -R www-data:www-data /var/www/
</pre></div>

<p>Also make sure the "www-data" User is able to access the USB device (reboot required!):</p>

<div class="highlight"><pre lang="">sudo gpasswd -a www-data plugdev
reboot
</pre></div>

<p>Everything working?</p>

<ul>
<li><strong>No</strong>: The camera might be claimed by a different process. The gvfs-gphoto2-volume-monitor is known to cause trouble and shouldn't be executed. <br />
The Photobooth installer should take care about it, but maybe something went wrong and we should change it manually (reboot required!):</li>
</ul>

<div class="highlight"><pre lang="">sudo chmod -x /usr/lib/gvfs/gvfs-gphoto2-volume-monitor
reboot
</pre></div>

<p>Does it work?</p>

<ul>
<li><strong>No</strong>: Sorry, we're am almost out of ideas! Please check the special notes below and feel free to contact us at <a href="https://t.me/PhotoboothGroup">Telegram</a> if you still have issues.</li>
</ul>

<h4>Special notes</h4>

<h5>Canon EOS 1300D</h5>

<p>To capture an image gphoto2 might need some time. Add <code>–wait-event=300ms</code> to the take picture CMD. Your take picture CMD should look like this:</p>

<div class="highlight"><pre lang="">gphoto2 –wait-event=300ms –capture-image-and-download –filename=%s
</pre></div>

<p>Source: <a href="https://www.dennis-henss.de/2020/01/25/raspberry-pi-fotobox-fuer-hochzeiten-und-geburtstage/#comment-1211"><a href='https://www.dennis-henss.de/2020/01/25/raspberry-pi-fotobox-fuer-hochzeiten-und-geburtstage/#comment-1211'>https://www.dennis-henss.de/2020/01/25/raspberry-pi-fotobox-fuer-hochzeiten-und-geburtstage/#comment-1211</a></a></p>

<h3>How to upload pictures to a remote server after picture has been taken?</h3>

<h4>Goal:</h4>

<p>After a picture is taken with the photobox upload it automatically to a remote server.</p>

<h4>Usecase:</h4>

<p>You have a remote server (e.g. with your website on it) or another Raspberry Pi to which you’d like instantly synchronizing your taken pictures. Also you could upload the pictures to a remote server and make them available through the QR code over the internet. By this you would not require people to access a local Wifi to download the picture from your local device which is running your Photobox. </p>

<h4>How to:</h4>

<ul>
<li>You should have a remote server with an SSH login. Know your username and password: (e.g.: [username.strato-hosting.eu]@ssh.strato.de)</li>
<li>We will be using the Post-photo script / command of the Photobox which you can find in the admin panel in the section Commands. </li>
<li>The command is being executed after the picture has been taken and gets the picture’s name as an attribute.</li>
<li>Command:    </li>
</ul>

<pre><code>
scp /var/www/html/photobooth/data/images/%s [username@remotehost]:/[path_to_where_you_want_to_store_the_pictures_on_the_remote_host]
</code></pre>

<ul>
<li><p>If we keep it like that the remote server would require the source server to type in a password each time a picture is being copied to the remote server. An SSH connection using a private/public SSH key needs to be established:</p></li>
<li><p>Create a public/private key-pair for the www-data user on the source machine (why for that user? The www-data user is executing the Post-photo script/command in the background) – Do not enter a passphrase when prompted.</p></li>
</ul>

<pre><code>
sudo -u www-data ssh-keygen -t rsa
</code></pre>

<ol>
<li>Copy the public key to the remote (destination) server </li>
</ol>

<pre><code>
sudo -u www-data ssh-copy-id [username@remotehost]
</code></pre>

<ol>
<li>You can now manually test whether the connection works. Try to copy anything to the remote server and change the file in the below example to a file that you actually have on your source machine. You shouldn’t be prompted with a password, but the copy and transfer should complete successfully just with the following command. If that is going to be successful, copying your pictures automatically should work now.</li>
</ol>

<pre><code>
sudo -u www-data scp /var/www/html/photobooth/data/images/20230129_125148.jpg [username@remotehost]:/[path_to_where_you_want_to_store_the_pictures]
</code></pre>

<p>You can now use the URL with which you can access your remote server from the internet and paste it into the QR code field in the Photobox admin panel. Now using the QR code your pictures can be downloaded from your remote server. </p>
  </body>
</html>

# go2rtc Troubleshooting

## Initial notes

- make sure your camera is supported by one of the following libraries:
  - `gphoto2`
  - `rpicam-apps`
  - `fswebcam`

- make sure your camera is working on Photobooth **without** setting up go2rtc for preview
  - **if not tested:** uninstall go2rtc while running the [Photobooth Setup Wizard](https://photoboothproject.github.io/install/setup_wizard) again (4  go2rtc --> 6 Uninstall go2rtc and the related services).

- make sure you can access [http://localhost:1984/api/stream.mjpeg?src=photobooth](http://localhost:1984/api/stream.mjpeg?src=photobooth) and having the stream available.

If your camera is supported in general but not having a preview available please check go2rtc for errors.

Access go2rtc via [http://localhost:1984](http://localhost:1984) and, maybe, adjust the config if needed.

---

## About the `capture` wrapper used to capture images

`capture` is simply a wrapper and accepts exactly the same parameters as `gphoto2` / `rpicam-apps` / `fswebcam`. The only difference is that the wrapper does the following:

**1 Stops Go2rtc**

**2 executes `gphoto2` / `rpicam-still` / `fswebcam` with the corresponding parameters.**

If only `%s` is passed, it uses by default the following arguments:

- on `gphoto2`: `--set-config output=Off --capture-image-and-download --filename=%s`
- on `rpicam-still`: `-n -q 100 -o %s`
- on `fswebcam`: `--no-banner -d /dev/video0 -r 1280x720 %s`

**Note:**: `%s` gets replaced by Photobooth with the corresponding path and filename.

Run `capture --help` inside your terminal to get information about the usage of the `capture` wrapper.

**3 Restarts go2rtc**

That means: anything that works with the command `gphoto2` / `rpicam-still` / `fswebcam` will also work with capture, and the word can simply be replaced.

What’s not covered there is a timing issue related to previewing via go2rtc.

---

## Problem Solving: go2rtc Preview via gphoto2

### Error: could not claim the USB Device

Another process is using the camera. That's what the log says. Therefore, it can't record.

If you're using `gphoto2` this issue is generally addressed inside the troubleshooting for gphoto2, but could also happen because of timing problems.

The preview is created using gphoto2 and streamed by go2rtc. In some rare cases, the preview may not be fully stopped when the capture is triggered. Usually, it's just milliseconds that cause problems. Continue reading on [Delay capture](#delay-capture) to know how to fix possible timing issues.

### Error: Device is busy

If the preview is running, the camera may be busy when trying to capture an image. This can lead to errors like "Device is busy" or "Could not claim the USB device". There is a bug in go2rtc startingF
by version 1.9.10 which keeps the camera in use even after stopping the preview.
Try to downgrade to version 1.9.9 as a workaround:

```
sudo bash install-photobooth.sh
choose 4 go2rtc
choose 5 updat or downgrade go2rtc only
chose 1.9.9
```

#### Delay capture

We can use a tiny pause between stopping the preview and starting the capture to work around this:
```ell
sudo wget -O /usr/local/bin/capture https://raw.githubusercontent.com/PhotoboothProject/photobooth/refs/heads/dev/scripts/capture-gphoto2
sudo chmod +x /usr/local/bin/capture
```
The updated wrapper includes a half-second pause after stopping the preview and before triggering the capture.

Now try again!

Didn’t work? Then continue...

#### Force the camera to exit Live View

It may be necessary to manually take the camera out of Live View mode. Adjust the capture command (this doesn't work with all cameras!):
```ell
capture --set-config movie=0 --trigger-capture --wait-event-and-download=FILEADDED --filename=%s
```
Now try again!

Didn’t work? Then continue...

#### More alternative capture commands

```ell
capture --trigger-capture --wait-event-and-download=FILEADDED --filename=%s
```
Or:
```ell
capture --wait-event=300ms --capture-image-and-download --filename=%s
```
For some cameras, this can also noticeably speed up the capture process.

Now try again!

Didn’t work? Then continue...

#### Set the default go2rtc config correctly

Open [http://localhost/admin/captureconfig.php](http://localhost/admin/captureconfig.php) (or [http://localhost/photobooth/admin/captureconfig.php](http://localhost/photobooth/admin/captureconfig.php)) to apply the default suggested configuration automatically.

Then try again.

#### Capture frame directly from preview

Another alternative, without starting or stopping the preview, is to pull a frame directly from go2rtc.

Open [http://localhost/admin/wgetcaptureconfig.php](http://localhost/admin/wgetcaptureconfig.php) (or [http://localhost/photobooth/admin/wgetcaptureconfig.php](http://localhost/photobooth/admin/wgetcaptureconfig.php)) to apply the adjusted configuration.

Then try again.

---

## Adjusting the go2rtc configuration

It might be needed to update the go2rtc configuration for personal needs or because of camera specific needed changes.

To allow adjustments of the config via web interface adjust the permissions:

```ell
sudo chmod 755 /etc/go2rtc.yaml
```

Access go2rtc via [http://localhost:1984](http://localhost:1984).


**Alternative:**

Open the corresponding configuration file with root privileges:

```ell
sudo nano /etc/go2rtc.yaml
```

Make your changes and save them, after that restart go2rtc to apply:

```ell
sudo systemctl stop go2rtc
sudo systemctl start go2rtc
```

The full configuration documentation can be found [here](https://github.com/AlexxIT/go2rtc?tab=readme-ov-file#configuration) inside the go2rtc GitHub repository.

---

## Camera specific notes

While setting up go2rtc using the Photobooth Setup Wizard we're creating a default configuration which covers a wide range of devices. It might be, that your camera does not work out of the box with the default configuration and requires some extra work to be used on Photobooth. You're always welcome to contribute camera specific notes not listed below.

Dont forget to restart go2rtc after changing the configuration!

```ell
sudo systemctl stop go2rtc
sudo systemctl start go2rtc
```

### Raspberry Pi Camera Modules

- Default preview width and height match the Pi Camera v3. You might need to adjust the width and height inside the go2rtc configuration to matching values for your camera module.

### Canon EOS 500D

- Camera Mode must be set to **P**

If still having trouble accessing the preview please adjust the go2rtc configuration:

```
streams:
  photobooth:
    - "exec:gphoto2 --capture-movie --stdout#input=mjpeg#video=h264#encoder=h264_v4l2m2m#fps=25#bitrate=2M"

log:
  exec: trace
```

### Canon EOS EOS40D

```
streams:
photobooth:
- "exec:gphoto2 --capture-movie --stdout#input=mjpeg#fps=8#scale=640:480#pause-on-snapshot=true"

log:
exec: trace
```

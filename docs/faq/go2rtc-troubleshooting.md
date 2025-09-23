# go2rtc troubleshooting

## Initial notes

- make sure your camera is supported by gphoto2 or rpicam-apps
- make sure your canera is working on Photobooth without setting up go2rtc for preview (**if not tested:** uninstall go2rtc while running the `install-go2rtc-preview.sh` script again)
- make sure you can access [http://localhost:1984/api/stream.mjpeg?src=photobooth](http://localhost:1984/api/stream.mjpeg?src=photobooth) and having the stream available.

If your camera is supported in general but not having a preview available please check go2rtc for errors.
Access go2rtc via [http://localhost:1984](http://localhost:1984) and, maybe, adjust the config if needed.

To allow adjustments of the config via web interface adjust the permissions:
```shell
sudo chmod 755 /etc/go2rtc.yaml
```

You might want to check the following information

## Problem Solving: go2rtc Preview via gphoto2

### Error: could not claim the USB Device

Another process is using the camera. That's what the log says. Therefore, it can't record. This issue is generally addressed in the FAQ under troubleshooting for gphoto2.

`capture` is simply a wrapper and accepts exactly the same parameters as |gphoto2`. The only difference is that the wrapper does the following:

1. Stops Go2rtc

2. Executes `gphoto2` with the corresponding parameters (if only `%s` is passed, it uses by default `--set-config output=Off --capture-image-and-download --filename=%s`)

3. Restarts go2rtc

That means: anything that works with the command gphoto2 will also work with capture, and the word can simply be replaced.

What’s not covered there is a timing issue related to previewing via go2rtc.

The preview is created using gphoto2 and streamed by go2rtc. In some rare cases, the preview may not be fully stopped when the capture is triggered. Usually, it's just milliseconds that cause problems.

#### Delay capture

We can use a tiny pause between stopping the preview and starting the capture to work around this:
```shell
sudo wget -O /usr/local/bin/capture https://raw.githubusercontent.com/PhotoboothProject/photobooth/refs/heads/dev/scripts/capture-gphoto2
sudo chmod +x /usr/local/bin/capture
```
The updated wrapper includes a half-second pause after stopping the preview and before triggering the capture.

Now try again!

Didn’t work? Then continue...

#### Force the camera to exit Live View

It may be necessary to manually take the camera out of Live View mode. Adjust the capture command (this doesn't work with all cameras!):
```shell
capture --set-config movie=0 --trigger-capture --wait-event-and-download=FILEADDED --filename=%s
```
Now try again!

Didn’t work? Then continue...

#### More alternative capture commands

```shell
capture --trigger-capture --wait-event-and-download=FILEADDED --filename=%s
```
Or:
```shell
capture --wait-event=300ms --capture-image-and-download --filename=%s
```
For some cameras, this can also noticeably speed up the capture process.

Now try again!

Didn’t work? Then continue...

#### Set the default go2rtc config correctly

Open [http://localhost/admin/captureconfig.php](http://localhost/admin/captureconfig.php) to apply the default suggested configuration automatically.

Then try again.

#### Capture frame directly from preview

Another alternative, without starting or stopping the preview, is to pull a frame directly from go2rtc.

Open [http://localhost/admin/wgetcaptureconfig.php](http://localhost/admin/wgetcaptureconfig.php) to apply the adjusted configuration.

Then try again.

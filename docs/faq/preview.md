# Preview and live background

Set up a live preview or countdown background that fits your hardware. Make sure Photobooth works without preview first—debugging is easier that way.

## Preview “from URL” (remote preview, preferred)

Use when you want the same preview on every device (tablet, phone, kiosk):

- Admin panel: `Preview mode` → `from URL`
- Example `Preview-URL`: `http://192.168.0.2:8081` (replace with your stream IP)
- Do **not** enable “Capture screenshot (preview ‘from device cam’ only)”
- Pi Camera preview via this method fails if motion is installed
- Requires Photobooth v2.2.1+

## go2rtc stream from DSLR or Pi Camera

Provides a stream you can consume via `Preview-URL`.

- Install via Photobooth Setup Wizard: `4 go2rtc` → choose variant, then reboot.
- Use `http://localhost:1984/api/stream.mjpeg?src=photobooth` as `Preview-URL` (replace `localhost` for remote access).
- Adjust capture command: `capture %s`
- Verify camera commands: DSLR `gphoto2 --capture-movie`; Pi Camera `rpicam-vid` or `libcamera-vid`
- Pi Camera defaults: width 2304px, height 1296px (edit `/etc/go2rtc.yaml` if needed)

## Preview “from device cam” (no remote preview)

Uses the camera of the device you open Photobooth on (e.g. tablet/phone/desktop).

- Admin panel: `Preview mode` → `from device cam`
- Notes:
  - Uses the local device camera only; not shared across other devices
  - Pi Camera capture via `raspistill` / `libcamera-still` / `rpicam-still` does **not** work here
  - Secure origin or exception required for camera access
  - “Capture screenshot (preview ‘from device cam’ only)” captures from this preview instead of gphoto/digicamcontrol/raspistill/libcamera-still/rpicam-still

## DSLR preview via device cam mode (cameracontrol.py)

Keeps the DSLR connection alive for preview + capture on Linux.

- Run Photobooth Setup Wizard: `5 gphoto2 webcam` → install service (recommended) or cronjob.
- Admin panel:
  - `Live Preview` → `Preview Mode`: `from device cam`
  - If **Execute start command for preview on take picture/collage** is enabled: `Command to generate a live preview`: `python3 cameracontrol.py --bsm`
  - If disabled: `python3 cameracontrol.py`
  - `Take picture command`: `python3 cameracontrol.py --capture-image-and-download %s`
- Optional:
  - Background video timeout: `python3 cameracontrol.py --bsmtime 1`
  - Use background video continuously: disable “Execute start command for preview on take picture/collage” and add `--bsm`
  - Capture target (keep images on camera): `python3 cameracontrol.py --set-config capturetarget=1 --capture-image-and-download %s`
- Permissions: ensure `www-data` is in `video` group (`sudo gpasswd -a www-data video`; reboot). Liveview must be supported by your camera ([check list](http://gphoto.org/proj/libgphoto2/support.php)).

## Troubleshooting


- Check v4l2loopback after updates:
  ```
  v4l2-ctl --list-devices
  ```
  Expected: `GPhoto2 Webcam (platform:v4l2loopback-000): /dev/video0`

- If missing, rebuild v4l2loopback:
  ```
  curl -LO https://github.com/umlaeute/v4l2loopback/archive/refs/tags/v0.12.7.tar.gz
  tar xzf v0.12.7.tar.gz && cd v4l2loopback-0.12.7
  make && sudo make install
  sudo depmod -a
  sudo modprobe v4l2loopback exclusive_caps=1 card_label="GPhoto2 Webcam"
  ```

- Chromium may pick the wrong webcam (e.g. `bcm2835-isp`); disable other webcams if needed.
- Long preview startup? Increase countdown (e.g. 8s) or run preview continuously (higher battery/CPU).
- For command help: `python3 /var/www/html/api/cameracontrol.py --help` (adjust path if installed in a subfolder).

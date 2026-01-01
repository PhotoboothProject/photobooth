# GPhoto2 Troubleshooting

GPhoto2 is a software that allows taking images via Photobooth. Full documentation is available at [gphoto.org](http://www.gphoto.org/doc/).

---

## General Known Issues

1. **Check Camera Support**
   Ensure "Image capture" is supported for your camera: [Camera Support List](http://gphoto.org/proj/libgphoto2/support.php)

2. **Try Different Camera Modes**
   Not every mode is supported by GPhoto2.

3. **Set Camera to JPEG/JPG Only**
   Photobooth does not support RAW images. Reducing image quality may improve performance, especially on low-end hardware like Raspberry Pi.

4. **Disable Auto-Focus**
   GPhoto2 cannot take pictures if the camera cannot find focus.

5. **Turn Off WiFi**
   Some cameras have connection issues when WiFi is enabled.

6. **Insert SD Card**
   GPhoto2 may fail to trigger images if no SD card is inserted.

7. **Set Capture Target to Memory Card**
   Sometimes, you need to manually set the capture target:

```
gphoto2 --get-config capturetarget
```

Example output:

```
pi@raspberrypi:~ $ gphoto2 --get-config capturetarget
Label: Capture Target
Readonly: 0
Type: RADIO
Current: Internal RAM
Choice: 0 Internal RAM
Choice: 1 Memory card   <--- !!!
```

Adjust your capture command:

```
gphoto2 --set-config capturetarget=1 --capture-image-and-download --filename=%s
```

---

## Hardware Issues

* Ensure the USB port provides enough power.
* Check for defective USB cables.

---

## Permission Issues

**1. Test as Current User**

```
gphoto2 --capture-image-and-download --filename=test.jpg
```

* No: Recheck previous steps
* Yes: Continue testing

**2. Test as `www-data` User**

```
cd /var/www/html
sudo -u www-data -s
gphoto2 --capture-image-and-download --filename=test.jpg
```

* Yes: Check Photobooth configuration or reset it
* No: Fix permissions:

```
sudo chown -R www-data:www-data /var/www/
sudo gpasswd -a www-data plugdev
reboot
```

**3. Check for Conflicting Processes**
Disable `gvfs-gphoto2-volume-monitor` if necessary:

```
sudo chmod -x /usr/lib/gvfs/gvfs-gphoto2-volume-monitor
reboot
```

**4. Update GPhoto2 and libgphoto2**

```
wget -O gphoto2-updater.sh https://raw.githubusercontent.com/gonzalo/gphoto2-updater/master/gphoto2-updater.sh
wget -O .env https://raw.githubusercontent.com/gonzalo/gphoto2-updater/master/.env
chmod +x gphoto2-updater.sh
sudo ./gphoto2-updater.sh --development
sudo chmod -x /usr/lib/gvfs/gvfs-gphoto2-volume-monitor
reboot
```

---

## Special Notes

### Alternative Capture Commands

```
gphoto2 --trigger-capture --wait-event-and-download=FILEADDED --filename=%s
gphoto2 --wait-event=300ms --capture-image-and-download --filename=%s
gphoto2 --set-config output=Off --trigger-capture --wait-event-and-download=FILEADDED --filename=%s
gphoto2 --set-config output=Off --wait-event=300ms --capture-image-and-download --filename=%s
```

### Canon EOS Models

* **EOS 1300D**

```
gphoto2 --wait-event=300ms --capture-image-and-download --filename=%s
```

* **EOS 2000D**

```
gphoto2 --set-config output=Off --trigger-capture --wait-event-and-download=FILEADDED --filename=%s
```

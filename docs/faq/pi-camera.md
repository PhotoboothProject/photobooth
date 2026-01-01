# Pi Camera setup

Yes, Pi Cameras are supported. Make sure your Pi can capture an image from the terminal before using Photobooth.

## Verify your camera works
- Try a capture command that matches your Pi OS version:
  - Bookworm: `rpicam-still -n -o test.jpg -q 100 -t 1`
  - Bullseye: `libcamera-still -n -o test.jpg -q 100 -t 1`
  - Buster and earlier: `raspistill -n -o test.jpg -q 100 -t 1`
- Add the web server user to the `video` group and reboot:
  ```
  sudo gpasswd -a www-data video
  reboot
  ```

## Configure Photobooth commands
Open the adminpanel ([http://localhost/admin](http://localhost/admin) or [http://localhost/photobooth/admin](http://localhost/photobooth/admin)) and set the capture command that matches your OS:

- Pi OS (Bookworm and newer): `rpicam-still -n -o %s -q 100 -t 1 | echo Done`
- Pi OS (Bullseye): `libcamera-still -n -o %s -q 100 -t 1 | echo Done`
- Pi OS (Buster): `raspistill -n -o %s -q 100 -t 1 | echo Done`

The `echo` ensures Photobooth sees a response after the capture finishes. You can append extra parameters (ISO, exposure, white balance, etc.); run the command with `-?` to list options.

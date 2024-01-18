# Supported Platforms and Cameras

| Hardware-Platform  | Software-Platform                  | Supported Cameras                                                                                                                                                                     |
|--------------------|------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Raspberry Pi 3 / 4 / 5 | Raspberry Pi OS 64bit Bullseye /Bookworm    | [Camera Modules](https://www.raspberrypi.com/documentation/accessories/camera.html), [gphoto2 DSLR](http://www.gphoto.org/proj/libgphoto2/support.php), webcam _*2_ |
| Raspberry Pi 3 / 4 / 5 | Raspberry Pi OS 32bit Bullseye / Bookworm _*1_ | [Camera Modules](https://www.raspberrypi.com/documentation/accessories/camera.html), [gphoto2 DSLR](http://www.gphoto.org/proj/libgphoto2/support.php), webcam _*2_ |
| Generic PC         | Debian/Ubuntu                      | [gphoto2 DSLR](http://www.gphoto.org/proj/libgphoto2/support.php), webcam _*2_                                                                                      |
| Generic PC         | Windows                            | [digiCamControl](http://digicamcontrol.com/), webcam _*2_                                                                                                           |

_*1 On Raspberry Pi OS 32bit you **must** add `arm_64bit=0` to your `/boot/config.txt` and reboot once before installing Photobooth._
_The Raspberry Pi foundation uses a 64bit kernel while the system is 32bit. The Installation fails because the v4l2loopback module can't be compiled for a 32bit OS while using a 64bit kernel._

_*2 Capture from webcam is possible e.g. using [fswebcam](https://www.sanslogic.co.uk/fswebcam/), else it only works on access via [http://localhost](http://localhost)_

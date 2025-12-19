# Automated installation on Raspberry Pi OS (previously called Raspbian) and on Debian / Debian based distributions:

To start the installation, simply run the [Photobooth Setup Wizard](https://photoboothproject.github.io/install/setup_wizard).

If you encounter any issues or want more freedom to configure your Pi, Computer or Laptop, we recommend you look at the detailed installation instruction below.

# Manually install Photobooth on Raspberry Pi OS (previously called Raspbian) and on Debian / Debian based distributions:

The steps below were tested on "Raspberry Pi OS with desktop" based on Debian Buster, but should work for Debian and all Debian based distributions. Photobooth can also be used on any other PC/Laptop running a supported OS.

## Update your system

```
sudo apt update
sudo apt dist-upgrade
```

## Install a Webserver

Currently NGINX and Apache Webserver are supported.
For an easy and no-hassle setup you should install Apache Webserver.
NGINX has a smaller memory footprint and typically better performance, which is especially important on the Raspberry Pis, but it needs some additional steps until you're good to go.

### Install Apache & PHP

```
sudo apt install -y libapache2-mod-php
```

### or Install NGINX & PHP

```
sudo apt install -y nginx php-fpm
```

[Additional needed steps to enable PHP in NGINX](install-nginx.md)

## Install dependencies

```
sudo apt install -y curl gcc g++ make git ffmpeg gphoto2 libimage-exiftool-perl nodejs php-xml php-gd php-zip php-mbstring python3 python3-gphoto2 python3-psutil python3-zmq rsync udisks2 v4l2loopback-dkms v4l-utils
```

**Optional:** If you have a new camera, you can also install the latest version of libgphoto2 directly from the maintainer. Choose "Install last stable release":

```
wget -O gphoto2-updater.sh https://raw.githubusercontent.com/gonzalo/gphoto2-updater/master/gphoto2-updater.sh
wget -O .env https://raw.githubusercontent.com/gonzalo/gphoto2-updater/master/.env
chmod +x gphoto2-updater.sh
sudo ./gphoto2-updater.sh
```

## Install photobooth

Give our webserver user access to `/var/www/`:

```
sudo chown -R www-data:www-data /var/www/
```

Get the Photobooth source:

```
cd /var/www/
sudo -u www-data -s
rm -r html/*
git clone https://github.com/PhotoboothProject/photobooth html
cd /var/www/html
git submodule update --init
npm install
npm run build
exit
```

**Please note:** depending on your hardware `npm install` and `npm run build` takes up to 15min! Node.js is needed in v20 or later!

Next we have to give our webserver user access to the USB device:

```
sudo gpasswd -a www-data plugdev
```

If you like to use a printer you need to have `CUPS` installed. On Raspbian `CUPS` is not installed by default:

```
sudo apt install -y cups
```

Next you also have to add your webserver user to the `lp` and `lpadmin` group:

```
sudo gpasswd -a www-data lp
sudo gpasswd -a www-data lpadmin
```

By default the CUPS webinterface can only be accessed via [http://localhost:631](http://localhost:631) from your local machine.

To remote access CUPS from other clients you need to run the following commands:

```
sudo cupsctl --remote-any
sudo /etc/init.d/cups restart
```

## Install Remote Buzzer support

Please follow the steps mentioned in the FAQ:

**Q:** [Can I use Hardware Button to take a Picture?](../faq/index.md#can-i-use-hardware-button-to-take-a-picture)

## Try it out

Now you should restart your Raspberry Pi to apply those settings:

```
reboot
```

Please use the following to test if your Webserver is able to take pictures (gphoto must be executed in a dir with write permission):

```
cd /var/www/html
sudo -u www-data gphoto2 --capture-image
```

If it is not working, your operation system probably automatically mounted your camera. You can unmount it, or remove execution permission for gphoto2 Volume Monitor to ensure that the camera is not mounted anymore:

```
sudo chmod -x /usr/lib/gvfs/gvfs-gphoto2-volume-monitor
```

Now reboot or unmount your camera with the following commands (you get a list of mounted cameras with `gio mount -l`):

```
gio mount -u gphoto2://YOUR-CAMERA
```

Now try again.

If everything is working, open the IP address (you get it via `ip addr`) of your Raspberry Pi, or if you open it on your machine, type [http://localhost](http://localhost) in your browser.

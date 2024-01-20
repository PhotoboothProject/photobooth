# Installation on Windows

There are two supported installation methods for windows.

1. Installation in the windows subsystem for linux (wsl) **Recommended**
2. Direct installation on the windows host

## Which installation method to use

Both variants work, but only with the first you will be able to use **gphoto2**
to capture images with your camera.
The corresponding windows tool **digicamcontrol**, which will be
used in the second method, does it job, but is not maintained anymore
and has [many open issues](https://github.com/dukus/digiCamControl/issues).

## Installation in the windows subsystem (method 1)

The first methods covers the following steps:

- Activating the windows subsystem for linux
- Installing the required additional tools
- Configuring windows to automatically start
- Installing photobooth in the subsystem

### Step 1: Activating the windows subsystem for linux

Open a powershell and run:

```powershell
wsl --install
```

This will enable the required windows features, install ubuntu and sets the default
version of wsl to version 2.

If something goes wrong here, check out this [page](https://learn.microsoft.com/en-us/windows/wsl/install).

Do not restart the computer yet.

### Step 2: Installing the required additional tools

We'll need [usbipd-win](https://github.com/dorssel/usbipd-win) to make the dslr
camera inside of wsl available. Here are the steps you'll need to do:

Open a powershell and run the installation command:

```powershell
winget install --exact dorssel.usbipd-win
```

Now connect your camera via usb.

Next open a powershell with admin privileges and run:

```powershell
# get the busid of your camera
usbipd list

# now bind the camera
usbipd bind --busid=<BUSID>
```

Now we can use `usbipd attach -a --wsl --busid=<BUSID>` to attach the camera to
the wsl machine.

### Step 3: Configuring windows to automatically start

Press `WIN+R` and run `shell:startup`. This will open the startup directory in your
explorer.

Create a file named `wsl.bat` with this content (adjust the `BUSID`):

```bat
wsl --exec dbus-launch true
:loop
usbidp attach -a --wsl --busid=<BUSID>
timeout 1
goto loop
```

The wsl command will startup the ubuntu machine and keeps it running.
The usbipd command will attach the camera to the ubuntu machine as soon as
you plug it in.

Now restart your computer.

### Step 4: Installing photobooth in the subsystem

Open a powershell and run `wsl`. This will open a shell to the ubuntu machine.
It will ask you for a username and password. You can choose anything you want.
After creating the account, run these commands:

```bash
wget https://raw.githubusercontent.com/PhotoboothProject/photobooth/dev/install-photobooth.sh
sudo bash install-photobooth.sh --username='<YourUsername>' --mjpeg --silent
```

Now open the [admin section](http://localhost/admin) and
set this as the live preview url:

> [http://localhost:1984/stream.html?src=dslr&mode=mjpeg](http://localhost:1984/stream.html?src=dslr&mode=mjpeg)

and this as capture command: `capture %s`.

## Direct installation on windows (method 2)

### Download needed files

- Download Apache2 and Visual C++ Redistributable for Visual Studio: [https://www.apachelounge.com/download](https://www.apachelounge.com/download)
- Download PHP 8.2 (Thread Safe): [https://windows.php.net/download](https://windows.php.net/download)
- Download Digicamcontrol: [http://digicamcontrol.com/download](http://digicamcontrol.com/download)
- Download Notepad++: [https://notepad-plus-plus.org/downloads](https://notepad-plus-plus.org/downloads)
- Download latest Photobooth release (_photobooth-4.x.x.zip_ or _photobooth-4.x.x.tar.gz_
  **Note:** the _Source code_ files won't work!): [https://github.com/PhotoboothProject/photobooth/releases](https://github.com/PhotoboothProject/photobooth/releases)

### Install & extract needed Software

- Install Notepad++
- Install Visual C++ Redistributable for Visual Studio
- Extract the Apache2 ZIP (_httpd-2.4.X-winXX-XXXX.zip_) to C:/
- Extract the PHP ZIP to C:/php

### Prepare Apache HTTP Server

Edit `C:\Apache24\conf\httpd.conf` using **Notepad++**
Find the following text (~ on line 284):

```apacheconf
<IfModule dir_module>
    DirectoryIndex index.html
</IfModule>
```

And change it to

```apacheconf
<IfModule dir_module>
    DirectoryIndex index.html index.php
</IfModule>
```

To the end of the file add the following:

```apacheconf
LoadModule php_module "C:/php/php8apache2_4.dll"
<FilesMatch \.php$>
    SetHandler application/x-httpd-php
</FilesMatch>

PHPIniDir "C:/php"
```

For reference see [f260b49](https://github.com/PhotoboothProject/photobooth/commit/f260b49d2029825d33eb9d35ceda3f19423418db)

Inside `C:\Apache24\htdocs` add a new file called `info.php`
and add the following content:

```php
<?php
phpinfo();
?>
```

Inside `C:\Apache24` create a new file called `cmd.bat` and add the following content:

```bat
cd "C:\Apache24\bin"
cmd
```

### Prepare PHP

Go to `C:\php` and rename the `php.ini-production` to `php.ini`.

Edit the `php.ini` using **Notepad++** to enable the GD library:
Find `;extension=fileinfo` and remove the `;` in front of the line.
Find `;extension=gd2` and remove the `;` in front of the line.
Find `;extension=mbstring` and remove the `;` in front of the line.
Find `;extension_dir = "ext"` and remove the `;` in front of the
line and change it to `extension_dir = "C:/php/ext"`.

For reference see [ff4259a](https://github.com/PhotoboothProject/photobooth/commit/ff4259aece2094922c1d9b8fc2825fb44a710560)

### Start Apache Server

Go to `C:\Apache24` and right click on the cmd.bat, choose "Run as administrator":
To start the Webserver on boot automatically, type `httpd.exe -k install`.

Once that's done, lets start our webserver:
`httpd.exe -k start`

If you need to stop the webserver (e.g. if you like to change the `php.ini`):
`httpd.exe -k stop`

### Test your Webserver & PHP

Open [http://localhost/info.php](http://localhost/info.php) in your Browser,
you should see the PHP Information page.

### Install Digicamcontrol

Install Digicamcontrol to `C:\Apache24\htdocs\digicamcontrol\`

### Setup Photobooth

Remove all files inside `C:\Apache24\htdocs\`.
Next you need to extract the Photobooth Release-ZIP to `C:\Apache24\htdocs\`.

Open [http://localhost/admin](http://localhost/admin) in your Browser and adjust
your "_take picture command_" (inside the "_Commands_" section):

> > > > > > > `C:\Apache24\htdocs\digicamcontrol\CameraControlCmd.exe /capture /filename %s`

## Enjoy

You should now be able to [access photobooth](http://localhost/) on your Windows machine!

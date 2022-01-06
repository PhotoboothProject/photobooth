#!/bin/bash

# Stop on the first sign of trouble
set -e

# Show all commands
# set -x

RUNNING_ON_PI=true
SILENT_INSTALL=false
DATE=$(date +"%Y%m%d-%H-%M")
IPADDRESS=$(hostname -I | cut -d " " -f 1)

SUBFOLDER=true
PI_CAMERA=false
KIOSK_MODE=false
USB_SYNC=false
SETUP_CUPS=false
CUPS_REMOTE_ANY=false

if [ ! -z $1 ]; then
    WEBSERVER=$1
else
    WEBSERVER=apache
fi

if [ "silent" = "$2" ]; then
    SILENT_INSTALL=true
    info "Performing silent install"
fi

function info {
    echo -e "\033[0;36m${1}\033[0m"
}

function error {
    echo -e "\033[0;31m${1}\033[0m"
}

#Param 1: Question / Param 2: Default / silent answer
function ask_yes_no {
    if [ "$SILENT_INSTALL" = false ]; then
        read -p "${1}: " -n 1 -r
    else
        REPLY=${2}
    fi
}

function no_raspberry {
    info "WARNING: This script is intended to run on a Raspberry Pi."
    info "Running the script on other devices running Debian / a Debian based distribution is possible, but PI specific features will be missing!"
    ask_yes_no "Do you want to continue? (y/n)" "Y"
    if [[ $REPLY =~ ^[Yy]$ ]]
    then
        RUNNING_ON_PI=false
        return
    fi
    exit ${1}
}

if [ $UID != 0 ]; then
    error "ERROR: Only root is allowed to execute the installer. Forgot sudo?"
    exit 1
fi

if [ ! -f /proc/device-tree/model ]; then
    no_raspberry 2
else
    PI_MODEL=$(tr -d '\0' </proc/device-tree/model)

    if [[ $PI_MODEL != Raspberry* ]]; then
        no_raspberry 3
    fi
fi

COMMON_PACKAGES=(
    'curl'
    'git'
    'gphoto2'
    'jq'
    'libimage-exiftool-perl'
    'nodejs'
    'php-gd'
    'php-zip'
    'yarn'
    'rsync'
    'udisks2'
)

print_logo() {
echo "


                    @@@@@@@@@@@@@@@@@@@
                   @@.               .@@
     %@@@@@@.     @@     @@@@@@@@@     @@
    @@@    @@*   @@.                   .@@
  &@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@&
@@@%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%@@@
@@                                                       @@
@@                     @@@@@@@@@@@@@.        *@@  @@@@@  @@
@@                  @@@@           @@@@                  @@
@@@@@@@@@@@@@@@@@@@@@    #@@@@@@@#    @@@@@@@@@@@@@@@@@@@@@
@@              @@@   @@@@(     (@@@@   @@@              @@
@@             &@@  .@@%           %@@.  @@&             @@
@@             @@   @@               @@   @@             @@
@@            %@@  @@*               /@@  @@%            @@
@@            @@%  @@                 @@  %@@            @@
@@            *@@  @@&               &@@  @@*            @@
@@             @@   @@*             *@@   @@             @@
@@              @@   @@@           @@@   @@              @@
@@%%%%%%%%%%%%%%%@@%   @@@@@&%&@@@@@   %@@%%%%%%%%%%%%%%%@@
@@@@@@@@@@@@@@@@@@@@@@     *&@&*     @@@@@@@@@@@@@@@@@@@@@@
@@                  ,@@@@&       &@@@@,                  @@
@@                      (@@@@@@@@@(                      @@
@@                                                       @@
@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
"
}

common_software() {
    info "### First we update your system. That's not worth mentioning."
    apt update
    apt dist-upgrade -y

    info "### Photobooth needs some software to run."
    if [ "$WEBSERVER" == "nginx" ]; then
        nginx_webserver
    elif [ "$WEBSERVER" == "lighttpd" ]; then
        lighttpd_webserver
    else
        apache_webserver
    fi

    info "### Installing common software..."
    for package in "${COMMON_PACKAGES[@]}"; do
        if [ $(dpkg-query -W -f='${Status}' ${package} 2>/dev/null | grep -c "ok installed") -eq 1 ]; then
            info "[Package]   ${package} installed already"
        else
            info "[Package]   Installing missing common package: ${package}"
            if [[ ${package} == "yarn" ]]; then
                curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
                echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list
                apt update
            fi
            apt install -y ${package}
        fi
    done
}

apache_webserver() {
    info "### Installing Apache Webserver..."
    apt install -y libapache2-mod-php
}

nginx_webserver() {
    info "### Installing NGINX Webserver..."
    apt install -y nginx php-fpm

    nginx_conf="/etc/nginx/sites-enabled/default"

    if [ -f "${nginx_conf}" ]; then
        info "### Enable PHP in NGINX"
        cp "${nginx_conf}" ~/nginx-default.bak
        sed -i 's/^\(\s*\)index index\.html\(.*\)/\1index index\.php index\.html\2/g' "${nginx_conf}"
        sed -i '/location ~ \\.php$ {/s/^\(\s*\)#/\1/g' "${nginx_conf}"
        sed -i '/include snippets\/fastcgi-php.conf/s/^\(\s*\)#/\1/g' "${nginx_conf}"
        sed -i '/fastcgi_pass unix:\/run\/php\//s/^\(\s*\)#/\1/g' "${nginx_conf}"
        sed -i '/.*fastcgi_pass unix:\/run\/php\//,// { /}/s/^\(\s*\)#/\1/g; }' "${nginx_conf}"

        info "### Testing NGINX config"
        /usr/sbin/nginx -t -c /etc/nginx/nginx.conf

        info "### Restarting NGINX"
        systemctl reload nginx
    else
        error "Can not find ${nginx_conf} !"
        info "Using Apache Webserver !"
        apt remove -y nginx php-fpm
        apache_webserver

    fi
}

lighttpd_webserver() {
    info "### Installing Lighttpd Webserver..."
    apt install -y lighttpd php-fpm
    lighttpd-enable-mod fastcgi
    lighttpd-enable-mod fastcgi-php

    php_conf="/etc/lighttpd/conf-available/15-fastcgi-php.conf"

    if [ -f "${php_conf}" ]; then
        info "### Enable PHP for Lighttpd"
        cp ${php_conf} ${php_conf}.bak

        cat > ${php_conf} <<EOF
# -*- depends: fastcgi -*-
# /usr/share/doc/lighttpd/fastcgi.txt.gz
# http://redmine.lighttpd.net/projects/lighttpd/wiki/Docs:ConfigurationOptions#mod_fastcgi-fastcgi

## Start an FastCGI server for php (needs the php5-cgi package)
fastcgi.server += ( ".php" => 
	((
		"socket" => "/var/run/php/php7.3-fpm.sock",
		"broken-scriptfilename" => "enable"
	))
)
EOF

        service lighttpd force-reload
    else
        error "Can not find ${php_conf} !"
        info "Using Apache Webserver !"
        apt remove -y lighttpd php-fpm
        apache_webserver
    fi
}

general_setup() {
    if [ "$SUBFOLDER" = true ]; then
        cd /var/www/html/
        INSTALLFOLDER="photobooth"
        INSTALLFOLDERPATH="/var/www/html/$INSTALLFOLDER"
    else
        cd /var/www/
        INSTALLFOLDER="html"
        INSTALLFOLDERPATH="/var/www/html"
    fi

    if [ -d "$INSTALLFOLDERPATH" ]; then
        BACKUPFOLDER="html-$DATE"
        info "${INSTALLFOLDERPATH} found. Creating backup as ${BACKUPFOLDER}."
        mv "$INSTALLFOLDER" "$BACKUPFOLDER"
    else
        info "$INSTALLFOLDERPATH not found."
    fi

    if [ "$INSTALLFOLDER" == "photobooth" ]; then
        URL="http://$IPADDRESS/photobooth"
    else
        URL="http://$IPADDRESS"
    fi

}

start_install() {
    info "### Now we are going to install Photobooth."
    git clone https://github.com/andi34/photobooth $INSTALLFOLDER
    cd $INSTALLFOLDERPATH

    info "### We are installing last development version for bullseye"
    VERSION="development"
    git fetch origin dev
    git checkout origin/dev

    git submodule update --init

    info "### Get yourself a hot beverage. The following step can take up to 15 minutes."
    yarn install
    yarn build
}

pi_camera() {
    cat > $INSTALLFOLDERPATH/config/my.config.inc.php << EOF
<?php
\$config = array (
  'take_picture' => 
  array (
    'cmd' => 'libcamera-still -n -o %s -q 100 -t 1 | echo "Done"',
    'msg' => 'Done',
  ),
);
EOF
}

general_permissions() {
    info "### Setting permissions."
    chown -R www-data:www-data $INSTALLFOLDERPATH/
    gpasswd -a www-data plugdev
    gpasswd -a www-data video

    if [ -f "/usr/lib/gvfs/gvfs-gphoto2-volume-monitor" ]; then
        info "### Disabling camera automount."
        chmod -x /usr/lib/gvfs/gvfs-gphoto2-volume-monitor
    fi

    # Add configuration required for www-data to be able to initiate system shutdown / reboot
    info "### Note: In order for the shutdown and reboot button to work we install /etc/sudoers.d/020_www-data-shutdown"
    cat > /etc/sudoers.d/020_www-data-shutdown << EOF
# Photobooth buttons for www-data to shutdown or reboot the system from admin panel or via remotebuzzer
www-data ALL=(ALL) NOPASSWD: /sbin/shutdown
EOF

    if [ "$RUNNING_ON_PI" = true ]; then
        info "### Remote Buzzer Feature"
        info "### Configure Raspberry PI GPIOs for Photobooth - please reboot in order use the Remote Buzzer Feature"
        usermod -a -G gpio www-data
        # remove old artifacts from node-rpio library, if there was
        if [ -f '/etc/udev/rules.d/20-photobooth-gpiomem.rules' ]; then
            info "### Remotebuzzer switched from node-rpio to onoff library. We detected an old remotebuzzer installation and will remove artifacts"
            rm -f /etc/udev/rules.d/20-photobooth-gpiomem.rules
            sed -i '/dtoverlay=gpio-no-irq/d' /boot/config.txt
        fi
        # add configuration required for onoff library
        sed -i '/Photobooth/,/Photobooth End/d' /boot/config.txt
cat >> /boot/config.txt  << EOF
# Photobooth
gpio=16,17,20,21,22,26,27=pu
# Photobooth End
EOF

        # update artifacts in user configuration from old remotebuzzer implementation
        if [ -f "$INSTALLFOLDERPATH/config/my.config.inc.php" ]; then
            sed -i '/remotebuzzer/{n;n;s/enabled/usebuttons/}' $INSTALLFOLDERPATH/config/my.config.inc.php
        fi

        if [ "$USB_SYNC" = true ]; then
            info "### Disabling automount for pi user"

            mkdir -p /home/pi/.config/pcmanfm/LXDE-pi/
            cat >> /home/pi/.config/pcmanfm/LXDE-pi/pcmanfm.conf <<EOF
[volume]
mount_on_startup=0
mount_removable=0
autorun=0
EOF

            chown -R pi:pi /home/pi/.config/pcmanfm

            info "### Adding polkit rule so www-data can (un)mount drives"

            cat >> /etc/polkit-1/localauthority/50-local.d/udisks2.pkla <<EOF
[Allow www-data to mount drives with udisks2]
Identity=unix-user:www-data
Action=org.freedesktop.udisks2.filesystem-mount*;org.freedesktop.udisks2.filesystem-unmount*
ResultAny=yes
ResultInactive=yes
ResultActive=yes
EOF
        fi
    fi
}

kioskbooth_desktop() {
    info "### We are installing Photobooth in Kiosk Mode for"
    info "### Raspberry Pi OS with desktop / Raspberry Pi OS with desktop and recommended software"
cat >> /etc/xdg/lxsession/LXDE-pi/autostart <<EOF
# turn off display power management system
@xset -dpms
# turn off screen blanking
@xset s noblank
# turn off screen saver
@xset s off

# Run Chromium in kiosk mode
@chromium-browser --noerrdialogs --disable-infobars --disable-features=Translate --no-first-run --check-for-update-interval=31536000 --kiosk http://127.0.0.1 --touch-events=enabled

# Hide mousecursor
@unclutter -idle 3

EOF
}

kioskbooth_light() {
    info "### Installing needed dependencies..."
    apt install -y --no-install-recommends xserver-xorg x11-xserver-utils xinit openbox chromium-browser

cat >> /etc/xdg/openbox/autostart <<EOF
# turn off display power management system
xset -dpms
# turn off screen blanking
xset s noblank
# turn off screen saver
xset s off

# Allow quitting the X server with CTRL-ATL-Backspace
setxkbmap -option terminate:ctrl_alt_bksp

# Remove exit errors from the config files that could trigger a warning
sed -i 's/"exited_cleanly":false/"exited_cleanly":true/' ~/.config/chromium/'Local State'
sed -i 's/"exited_cleanly":false/"exited_cleanly":true/; s/"exit_type":"[^"]\+"/"exit_type":"Normal"/' ~/.config/chromium/Default/Preferences

# Run Chromium in kiosk mode
chromium-browser --noerrdialogs --disable-infobars --disable-features=Translate --no-first-run --check-for-update-interval=31536000 --kiosk http://127.0.0.1 --touch-events=enabled
EOF

cat >> /etc/X11/Xwrapper.config <<EOF
allowed_users=anybody
EOF

cat >> /home/pi/.bash_profile <<EOF
[[ -z \$DISPLAY && \$XDG_VTNR -eq 1 ]] && startx -- -nocursor
EOF

chmod u+s /usr/bin/Xorg
usermod -a -G tty pi
}

cups_setup() {
    info "### Setting printer permissions."
    gpasswd -a www-data lp
    gpasswd -a www-data lpadmin
    if [ "$CUPS_REMOTE_ANY" = true ]; then
        info "### Access to CUPS will be allowed from all devices in your network."
        cupsctl --remote-any
        /etc/init.d/cups restart
    fi
}

############################################################
#                                                          #
# Ask all questions before installing Photobooth           #
#                                                          #
############################################################

print_logo

info "### The Photobooth installer for your Raspberry Pi."

echo -e "\033[0;33m### Is Photobooth the only website on this system?"
echo -e "### NOTE: If typing y, the whole /var/www/html folder will be renamed"
ask_yes_no "          to /var/www/html-$DATE if exists! [y/N] " "Y"
echo -e "\033[0m"
if [ "$REPLY" != "${REPLY#[Yy]}" ]; then
    info "### We will install Photobooth into /var/www/html."     
    SUBFOLDER=false
else
    info "### We will install Photobooth into /var/www/html/photobooth."
fi

echo -e "\033[0;33m### You probably like to use a printer."
ask_yes_no "### You like to install CUPS and set needing printer permissions? [y/N] " "Y"
echo -e "\033[0m"
if [[ $REPLY =~ ^[Yy]$ ]]
then
    SETUP_CUPS=true
    COMMON_PACKAGES+=('cups')
    echo -e "\033[0;33m### By default CUPS can only be accessed via localhost."
    ask_yes_no "### You like to allow remote access to CUPS over IP from all devices inside your network? [y/N] " "Y"
    echo -e "\033[0m"
    if [[ $REPLY =~ ^[Yy]$ ]]
    then
        CUPS_REMOTE_ANY=true
    fi
fi

# Pi specific setup start
if [ "$RUNNING_ON_PI" = true ]; then
    echo -e "\033[0;33m### Do you like to use a Raspberry Pi (HQ) Camera to take pictures?"
    ask_yes_no "### If yes, this will generate a personal configuration with all needed changes. [y/N] " "N"
    echo -e "\033[0m"
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        PI_CAMERA=true
    fi

    echo -e "\033[0;33m### You probably like to start the browser on every start."
    ask_yes_no "### Open Chromium in Kiosk Mode at every boot and hide the mouse cursor? [y/N] " "N"
    echo -e "\033[0m"
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        KIOSK_MODE=true
        COMMON_PACKAGES+=('unclutter')
    fi

    echo -e "\033[0;33m### Sync to USB - this feature will automatically copy (sync) new pictures to a USB stick."
    echo -e "### The actual configuration will be done in the admin panel but we need to setup Raspberry Pi OS first"
    ask_yes_no "### Would you like to enable the USB sync file backup? [y/N] " "Y"
    echo -e "\033[0m"
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        USB_SYNC=true
    fi
fi
# Pi specific setup end


############################################################
#                                                          #
# Go through the installation steps of Photobooth          #
#                                                          #
############################################################

common_software
general_setup
start_install
general_permissions
if [ "$PI_CAMERA" = true ]; then
    pi_camera
fi
if [ "$KIOSK_MODE" = true ]; then
    kioskbooth_desktop
fi
if [ "$SETUP_CUPS" = true ]; then
    cups_setup
fi

print_logo
info ""
info "### Congratulations you finished the install process."
info "    Photobooth was installed inside:"
info "        $INSTALLFOLDERPATH"
info ""
info "    Used webserver: $WEBSERVER"
info ""
info "    Photobooth can be accessed at:"
info "        $URL"
info ""
info "###"
info "### Have fun with your Photobooth, but first restart your device!"

echo -e "\033[0;33m"
ask_yes_no "### Do you like to reboot now? [y/N] " "N"
echo -e "\033[0m"
if [[ $REPLY =~ ^[Yy]$ ]]
then
    info "### Your device will reboot now."
    shutdown -r now
fi


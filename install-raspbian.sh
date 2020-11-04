#!/bin/bash

# Stop on the first sign of trouble
set -e

# Show all commands
# set -x

if [ ! -z $1 ]; then
    webserver=$1
else
    webserver=apache
fi

function info {
    echo -e "\033[0;36m${1}\033[0m"
}

function error {
    echo -e "\033[0;31m${1}\033[0m"
}

if [ $UID != 0 ]; then
    error "ERROR: Only root is allowed to execute the installer. Forgot sudo?"
    exit 1
fi

if [ ! -f /proc/device-tree/model ]; then
    error "ERROR: This installer is only intended to run on a Raspberry Pi."
    exit 2
fi

PI_MODEL=$(tr -d '\0' </proc/device-tree/model)

if [[ $PI_MODEL != Raspberry* ]]; then
    error "ERROR: This installer is only intended to run on a Raspberry Pi."
    exit 3
fi

if [[ ! -z $1 && ("$1" = "nginx" || "$1" = "lighttpd") ]]; then
    info "### Used webserver: $webserver"
else
    info "### Used webserver: Apache Webserver"
fi

COMMON_PACKAGES=(
    'git'
    'gphoto2'
    'jq'
    'libimage-exiftool-perl'
    'nodejs'
    'npm'
    'php-gd'
    'yarn'
)

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

info "### The Photobooth installer for your Raspberry Pi."

info "### First we update your system. That's not worth mentioning."
apt update
apt dist-upgrade -y

info "### Photobooth needs some software to run."
if [ "$webserver" == "nginx" ]; then
    nginx_webserver
elif [ "$webserver" == "lighttpd" ]; then
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

echo -e "\033[0;33m### Is Photobooth the only website on this system?"
read -p "### Warning: If typing y, the whole /var/www/html folder will be removed! [y/N] " -n 1 -r deleteHtmlFolder
echo -e "\033[0m"
if [ "$deleteHtmlFolder" != "${deleteHtmlFolder#[Yy]}" ] ;then
    info "### Ok, we will replace the html folder with the Photobooth."
    cd /var/www/
    rm -rf html
    INSTALLFOLDER="html"
    INSTALLFOLDERPATH="/var/www/html/"
else
    info "### Ok, we will install Photobooth into /var/www/html/photobooth."
    cd /var/www/html/
    INSTALLFOLDER="photobooth"
    INSTALLFOLDERPATH="/var/www/html/$INSTALLFOLDER/"
fi

echo -e "\033[0;33m### Do you like to install from git? This will take more"
read -p "### time but it is recommended and makes updating Photobooth easier. [y/N] " -n 1 -r
echo -e "\033[0m"
if [[ $REPLY =~ ^[Yy]$ ]]
then
    info "### Your wish is my command!"

    info "### Now we are going to install Photobooth."
    git clone https://github.com/andi34/photobooth $INSTALLFOLDER
    cd $INSTALLFOLDERPATH

    echo -e "\033[0;33m### Please select a version to install:"
    echo -e "    1 Install last development version"
    echo -e "    2 Install last stable Release"
    read -p "Please enter your choice: " -n 1 -r
    echo -e "\033[0m"
    if [[ $REPLY =~ ^[1]$ ]]
    then
      info "### We are installing last development version"
      git fetch origin dev
      git checkout origin/dev
    else
      if [[ ! $REPLY =~ ^[2]$ ]]
        then
        info "### Invalid choice!"
      fi
      LATEST_VERSION=$( git describe --tags `git rev-list --tags --max-count=1` )
      info "### We are installing last stable Release: Version $LATEST_VERSION"
      git checkout $LATEST_VERSION
    fi

    git submodule update --init

    info "### Get yourself a hot beverage. The following step can take up to 15 minutes."
    yarn install
    yarn build
else
    info "### Your wish is my command!"

    info "### Downloading the latest release and extracting it."
    curl -s https://api.github.com/repos/andi34/photobooth/releases/latest |
        jq '.assets[].browser_download_url | select(endswith(".tar.gz"))' |
        xargs curl -L --output /tmp/photobooth-latest.tar.gz

    mkdir -p $INSTALLFOLDERPATH
    tar -xzvf /tmp/photobooth-latest.tar.gz -C $INSTALLFOLDERPATH
    cd $INSTALLFOLDERPATH
fi

echo -e "\033[0;33m### Do you like to use a Raspberry Pi (HQ) Camera to take pictures?"
read -p "### If yes, this will generate a personal configuration with all needed changes. [y/N] " -n 1 -r
echo -e "\033[0m"
if [[ $REPLY =~ ^[Yy]$ ]]
then
    (cat << EOF) > $INSTALLFOLDERPATH/config/my.config.inc.php
<?php
\$config = array (
  'take_picture' => 
  array (
    'cmd' => 'raspistill -n -o %s -q 100 -t 1 | echo "Done"',
    'msg' => 'Done',
  ),
);
EOF
fi

info "### Setting permissions."
chown -R www-data:www-data $INSTALLFOLDERPATH
gpasswd -a www-data plugdev

if [ -f "/usr/lib/gvfs/gvfs-gphoto2-volume-monitor" ]; then
    info "### Disabling camera automount."
    chmod -x /usr/lib/gvfs/gvfs-gphoto2-volume-monitor
fi

echo -e "\033[0;33m### You probably like to use a printer."
read -p "### You like to install CUPS and set needing printer permissions? [y/N] " -n 1 -r
echo -e "\033[0m"
if [[ $REPLY =~ ^[Yy]$ ]]
then
    info "### Installing CUPS and setting printer permissions."

    apt install -y cups
    gpasswd -a www-data lp
    gpasswd -a www-data lpadmin
fi

echo -e "\033[0;33m### You probably like to start the browser on every start."
read -p "### Open Chromium in Kiosk Mode at every boot and hide the mouse cursor? [y/N] " -n 1 -r
echo -e "\033[0m"
if [[ $REPLY =~ ^[Yy]$ ]]
then
    apt install -y unclutter

    cat >> /etc/xdg/lxsession/LXDE-pi/autostart <<EOF

@xset s off
@xset -dpms
@xset s noblank
@chromium-browser --incognito --kiosk http://localhost/

@unclutter -idle 3

EOF

fi

info "### Enable Nodejs GPIO access - please reboot in order to use the Remote Buzzer Feature"
usermod -a -G gpio www-data
cat > /etc/udev/rules.d/20-photobooth-gpiomem.rules <<EOF
SUBSYSTEM=="bcm2835-gpiomem", KERNEL=="gpiomem", GROUP="gpio", MODE="0660"
EOF
sed -i '/dtoverlay=gpio-no-irq/d' /boot/config.txt
cat >> /boot/config.txt  << EOF
dtoverlay=gpio-no-irq
EOF

info "### Congratulations you finished the install process."
info "### Have fun with your Photobooth, but first restart your Pi."

echo -e "\033[0;33m"
read -p "### Do you like to reboot now? [y/N] " -n 1 -r
echo -e "\033[0m"
if [[ $REPLY =~ ^[Yy]$ ]]
then
    info "### Your Raspberry Pi will reboot now."
    shutdown -r now
fi

#!/bin/bash

# Stop on the first sign of trouble
set -e

# Show all commands
# set -x

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
if [ "$1" == "apache" ]; then
    apache_webserver
elif [ "$1" == "lighttpd" ]; then
    lighttpd_webserver
else
    nginx_webserver
fi

info "### Installing common software..."
apt install -y php-gd gphoto2

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
read -p "### time and is recommended only for brave users. [y/N] " -n 1 -r
echo -e "\033[0m"
if [[ $REPLY =~ ^[Yy]$ ]]
then
    info "### Your wish is my command!"

    info "### We have to make sure that git is installed."
    apt install -y git

    info "### Also a packet manager is needed."
    curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
    echo "deb https://dl.yarnpkg.com/debian/ stable main" | sudo tee /etc/apt/sources.list.d/yarn.list
    apt update
    apt install -y yarn

    info "### Now we are going to install Photobooth."
    git clone https://github.com/andreknieriem/photobooth $INSTALLFOLDER
    cd $INSTALLFOLDERPATH
    LATEST_VERSION=$( git describe --tags `git rev-list --tags --max-count=1` )
    info "### We ar installing version $LATEST_VERSION".
    git checkout $LATEST_VERSION
    git submodule update --init

    info "### Get yourself a hot beverage. The following step can take up to 15 minutes."
    yarn install
    yarn build
else
    info "### Downloading the latest build."

    info "### Installing a little helper tool to determine the correct url."
    apt install -y jq

    info "### Downloading the latest release and extracting it."
    curl -s https://api.github.com/repos/andreknieriem/photobooth/releases/latest |
        jq '.assets[].browser_download_url | select(endswith(".tar.gz"))' |
        xargs curl -L --output /tmp/photobooth-latest.tar.gz

    mkdir -p $INSTALLFOLDERPATH
    tar -xzvf /tmp/photobooth-latest.tar.gz -C $INSTALLFOLDERPATH
    cd $INSTALLFOLDERPATH
fi

info "### Setting permissions."
chown -R www-data:www-data $INSTALLFOLDERPATH
gpasswd -a www-data plugdev

info "### Installing CUPS and setting printer permissions."
apt install -y cups
gpasswd -a www-data lp
gpasswd -a www-data lpadmin

info "### Disable camera automount"
chmod -x /usr/lib/gvfs/gvfs-gphoto2-volume-monitor

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

info "### Congratulations you finished the install process."
info "### Have fun with your booth, but first restart your Pi."

echo -e "\033[0;33m"
read -p "### Do you like to reboot now? [y/N] " -n 1 -r
echo -e "\033[0m"
if [[ $REPLY =~ ^[Yy]$ ]]
then
    info "### Your Raspberry Pi will reboot now."
    shutdown -r now
fi
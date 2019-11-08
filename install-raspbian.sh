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
apt install -y libapache2-mod-php php-gd gphoto2 unclutter

cd /var/www/
rm -rf html
mkdir html

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
    git clone https://github.com/andreknieriem/photobooth html
    cd /var/www/html
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

    tar -xzvf /tmp/photobooth-latest.tar.gz -C /var/www/html/
fi

info "### Setting permissions."
chown -R www-data:www-data /var/www/

gpasswd -a www-data plugdev
gpasswd -a www-data lp
gpasswd -a www-data lpadmin

info "### Disable camera automount"
chmod -x /usr/lib/gvfs/gvfs-gphoto2-volume-monitor

info "### You probably like to start the browser on every start."
cat >> /etc/xdg/lxsession/LXDE-pi/autostart <<EOF

@xset s off
@xset -dpms
@xset s noblank
@chromium-browser --incognito --kiosk http://localhost/

@unclutter -idle 3

EOF

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
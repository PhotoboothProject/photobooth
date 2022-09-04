#!/bin/bash

# Stop on the first sign of trouble
set -e

USERNAME=''
WEBSERVER="apache"
SILENT_INSTALL=false
RUNNING_ON_PI=true
FORCE_RASPBERRY_PI=false
DATE=$(date +"%Y%m%d-%H-%M")
IPADDRESS=$(hostname -I | cut -d " " -f 1)
if [ ! -d "/tmp/photobooth" ]; then
    mkdir -p "/tmp/photobooth"
fi
PHOTOBOOTH_LOG="/tmp/photobooth/$DATE-photobooth.log"

BRANCH="dev"
GIT_INSTALL=false
SUBFOLDER=true
PI_CAMERA=false
KIOSK_MODE=false
HIDE_MOUSE=false
USB_SYNC=false
SETUP_CUPS=false
GPHOTO_PREVIEW=true
CUPS_REMOTE_ANY=false
WEBBROWSER="unknown"
KIOSK_FLAG="--kiosk http://127.0.0.1"
CHROME_FLAGS=false
CHROME_DEFAULT_FLAGS="--noerrdialogs --disable-infobars --disable-features=Translate --no-first-run --check-for-update-interval=31536000 --touch-events=enabled --password-store=basic"
AUTOSTART_FILE=""
DESKTOP_OS=true
PHP_VERSION="7.4"

# Update
RUN_UPDATE=false
BACKUPBRANCH=""
PHOTOBOOTH_FOUND=false
PHOTOBOOTH_PATH=(
        '/var/www/html'
        '/var/www/html/photobooth'
)
PHOTOBOOTH_SUBMODULES=(
        'vendor/PHPMailer'
        'vendor/phpqrcode'
        'vendor/PhotoSwipe'
        'vendor/rpihotspot'
        'vendor/Seriously'
)

# Node.js v12.22.(4 or newer) is needed on installation via git
NEEDS_NODEJS_CHECK=true
NEEDED_NODE_VERSION="v12.22.(4 or newer)"
NODEJS_NEEDS_UPDATE=false
NODEJS_CHECKED=false

COMMON_PACKAGES=(
        'ffmpeg'
        'gphoto2'
        'libimage-exiftool-perl'
        'nodejs'
        'php-gd'
        'php-zip'
        'python3'
        'python3-gphoto2'
        'python3-psutil'
        'python3-zmq'
        'rsync'
        'udisks2'
        'v4l2loopback-dkms'
        'v4l-utils'
)

EXTRA_PACKAGES=('curl')

INSTALL_PACKAGES=()

function info {
    echo -e "\033[0;36m${1}\033[0m"
    echo "${1}" >> "$PHOTOBOOTH_LOG"
}

function warn {
    echo -e "\033[0;33m${1}\033[0m"
    echo "WARN: ${1}" >> "$PHOTOBOOTH_LOG"
}

function error {
    echo -e "\033[0;31m${1}\033[0m"
    echo "ERROR: ${1}" >> "$PHOTOBOOTH_LOG"
}

print_spaces() {
    echo ""
    info "###########################################################"
    echo ""
}

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

#Param 1: Question / Param 2: Default / silent answer
function ask_yes_no {
    if [ "$SILENT_INSTALL" = false ]; then
        read -p "${1}: " -n 1 -r
    else
        REPLY=${2}
    fi
}

function no_raspberry {
    warn "WARNING: This script is intended to run on a Raspberry Pi."
    warn "Running the script on other devices running Debian / a Debian based distribution is possible, but Raspberry Pi specific features will be missing!"
    RUNNING_ON_PI=false
    print_spaces
}

view_help() {
    cat << EOF
Usage: sudo bash install-photobooth.sh -u=<YourUsername> [-b=<stable3:dev:package> -hprsV -w=<apache:nginx:lighttpd]

    -h,  -help,       --help        Display help.

    -b,  -branch,     --branch      Enter the Photobooth branch (version) you like to install.
                                    Available branches: stable3, dev, package
                                    By default, latest development verison (dev) will be installed.
                                    package will install latest Release from zip.

    -p,  -php,        --php         Choos the PHP version to install (Default = 7.4)

    -r,  -raspberry,  --raspberry   Skip Pi detection and add Pi specific adjustments.
                                    Note: only to use on Raspberry Pi OS!

    -s,  -silent,     --silent      Run silent installation:
                                    - Uses Apache Webserver
                                    - installs Photobooth into /var/www/html
                                    - installs latest Photobooth development version via git
                                    - installs CUPS
                                    - deny remote access to CUPS over IP from all devices inside
                                      your network (automatic image building failes to enable
                                      because cups can't be configured while in chroot env)
                                    - installs a collection of free-software printer drivers (Gutenprint)
                                    - disables screen saver and hide the mouse cursor (Raspberry Pi only)
                                    - adds config for USB sync file backup (Raspberry Pi only)

         -update,     --update      Try updating Photobooth via git.

    -u,  -username,   --username    Enter your OS username you like to use Photobooth
                                    on (Raspberry Pi only)

    -V,  -verbose,    --verbose     Run script in verbose mode.

    -w,  -webserver,  --webserver   Enter the webserver to use [apache, nginx, lighttpd].
                                    Apache is used by default.

Example to install Photobooth on a Raspberry Pi getting asked for enabled options:
sudo bash install-photobooth.sh -u="photobooth"

Options can be combined. Example for a silent installation on a Raspberry Pi:
sudo bash install-photobooth.sh -u="photobooth" -s
EOF
}

print_logo
print_spaces
info "### The Photobooth installer for your Raspberry Pi."
print_spaces
info "################## Passed options #########################"
echo ""
options=$(getopt -l "help,branch::,php::,update,username::,raspberry,silent,verbose,webserver::" -o "hb::p::u::rsVw::" -a -- "$@")
eval set -- "$options"

while true
do
    case $1 in
        -h|--help)
            view_help
            exit 0
            ;;
        -b|--branch)
            shift
            if [ "$1" == "dev" ] || [ "$1" == "stable3" ]; then
                BRANCH=$1
                GIT_INSTALL=true
            elif [ "$1" == "package" ]; then
                BRANCH="stable3"
                GIT_INSTALL=false
                NEEDS_NODEJS_CHECK=false
            else
                BRANCH="dev"
                warn "[WARN]      Invalid branch / version!"
                warn "[WARN]      Falling back to defaults. Installing latest development branch from git."
            fi
            info "### Photobooth version / branch:  $1"
            ;;
        -p|--php)
            shift
            PHP_VERSION=$1
            info "### PHP Version: $1"
            ;;
        --update)
            RUN_UPDATE=true
            info "### Trying to update Photobooth..."
            ;;
        -u|--username)
            shift
            USERNAME=$1
            info "### Username: $1"
            ;;
        -s|--silent)
            SILENT_INSTALL=true
            info "### Silent installtion starting..."
            ;;
        -r|--raspberry)
            FORCE_RASPBERRY_PI=true
            info "### Skipping Pi detection and add specific adjustments..."
            ;;
        -V|--verbose)
            set -xv
            info "### Set xtrace and verbose mode."
            ;;
        -w|--webserver)
            shift
            WEBSERVER=$1
            info "### Webserver: $1"
            ;;
        --)
        shift
        break;;
    esac
    shift
done
print_spaces

if [ $(dpkg-query -W -f='${Status}' "lxde" 2>/dev/null | grep -c "ok installed") -eq 1 ]; then
    DESKTOP_OS=true
else
    DESKTOP_OS=false
fi

check_username() {
    info "[Info]      Checking if user $USERNAME exists..."
    if id "$USERNAME" &>/dev/null; then
        info "[Info]      User $USERNAME found. Installation process continues."
    else
        error "ERROR: An valid OS username is needed! Please re-run the installer."
        view_help
        exit
    fi
}

check_nodejs() {
    NODE_VERSION=$(node -v || echo "0")
    IFS=. VER=(${NODE_VERSION##*v})
    major=${VER[0]}
    minor=${VER[1]}
    micro=${VER[2]}

    if [[ -n "$major" && "$major" -eq "12" ]]; then
        if [[ -n "$minor" && "$minor" -eq "22" ]]; then
            if [[ -n "$micro" && "$micro" -ge "4" ]]; then
                info "[Info]      Node.js matches our requirements!"
            elif [[ -n "$micro" ]]; then
                warn "[WARN]      Node.js needs to be updated, micro version not matching our requirements!"
                warn "[WARN]      Node.js $NODE_VERSION, but $NEEDED_NODE_VERSION is needed!"
                NODEJS_NEEDS_UPDATE=true
                if [ "$NODEJS_CHECKED" = true ]; then
                    error "[ERROR]     Update was not possible. Aborting Photobooth installation!"
                    exit 1
                else
                    update_nodejs
                fi
            else
                error "[ERROR]     Unable to handle Node.js version string (micro)"
                exit 1
            fi
        elif [[ -n "$minor" ]]; then
            warn "[WARN]      Node.js needs to be updated, minor version not matching our requirements!"
            warn "[WARN]      Found Node.js $NODE_VERSION, but $NEEDED_NODE_VERSION is needed!"
            NODEJS_NEEDS_UPDATE=true
            if [ "$NODEJS_CHECKED" = true ]; then
                error "[ERROR]     Update was not possible. Aborting Photobooth installation!"
                exit 1
            else
                update_nodejs
            fi
        else
            error "[ERROR]     Unable to handle Node.js version string (minor)"
            exit 1
        fi
    elif [[ -n "$major" ]]; then
        warn "[WARN]      Node.js needs to be updated, major version not matching our requirements!"
        warn "[WARN]      Found Node.js $NODE_VERSION, but $NEEDED_NODE_VERSION is needed!"
        if [ "$NODEJS_CHECKED" = true ]; then
            error "[ERROR]     Update was not possible. Aborting Photobooth installation!"
            exit 1
        else
            update_nodejs
        fi
    else
        error "[ERROR]     Unable to handle Node.js version string (major)"
        exit 1
    fi
}

update_nodejs() {
    if [ $(dpkg-query -W -f='${Status}' "nodejs" 2>/dev/null | grep -c "ok installed") -eq 1 ]; then
        info "[Cleanup]   Removing nodejs package"
        apt purge -y nodejs
    fi

    if [ $(dpkg-query -W -f='${Status}' "nodejs-doc" 2>/dev/null | grep -c "ok installed") -eq 1 ]; then
        info "[Cleanup]   Removing nodejs-doc package"
        apt purge -y nodejs-doc
    fi

    if [ "$RUNNING_ON_PI" = true ]; then
        info "[Package]   Installing Node.js v12.22.12"
        wget -O - https://raw.githubusercontent.com/audstanley/NodeJs-Raspberry-Pi/master/Install-Node.sh | bash
        node-install -v 12.22.12
        NODEJS_CHECKED=true
        check_nodejs
    else
        info "[Package]   Installing latest Node.js v12"
        curl -fsSL https://deb.nodesource.com/setup_12.x | bash -
        apt-get install -y nodejs
        NODEJS_CHECKED=true
        check_nodejs
    fi
}

common_software() {
    info "### First we update your system. That's not worth mentioning."
    apt update
    apt upgrade -y

    if [ "$RUN_UPDATE" = false ]; then
        info "### Photobooth needs some software to run."
        if [ "$WEBSERVER" == "nginx" ]; then
            nginx_webserver
        elif [ "$WEBSERVER" == "lighttpd" ]; then
            lighttpd_webserver
        else
            apache_webserver
        fi
    fi

    if [ $GIT_INSTALL = true ]; then
        EXTRA_PACKAGES+=(
            'git'
            'yarn'
        )
    else
        EXTRA_PACKAGES+=(
            'jq'
        )
    fi

    # Note: raspberrypi-kernel-header are needed before v4l2loopback-dkms
    if [ "$RUNNING_ON_PI" = true ]; then
        EXTRA_PACKAGES+=('raspberrypi-kernel-headers')
    fi

    # Additional packages
    INSTALL_PACKAGES+=("${EXTRA_PACKAGES[@]}")

    # All required packages independend of Raspberry Pi.
    INSTALL_PACKAGES+=("${COMMON_PACKAGES[@]}")

    info "### Installing common software:"
    for required in "${INSTALL_PACKAGES[@]}"; do
        info "[Required]  ${required}"
    done

    for package in "${INSTALL_PACKAGES[@]}"; do
        if [ $(dpkg-query -W -f='${Status}' ${package} 2>/dev/null | grep -c "ok installed") -eq 1 ]; then
            info "[Package]   ${package} installed already"
        else
            info "[Package]   Installing missing common package: ${package}"
            if [[ ${package} == "yarn" ]]; then
                curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
                echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list
                apt update
            fi
            if [[ ${package} == "gphoto2" ]]; then
                info "            Installing latest stable release."
                wget https://raw.githubusercontent.com/gonzalo/gphoto2-updater/master/gphoto2-updater.sh
                wget https://raw.githubusercontent.com/gonzalo/gphoto2-updater/master/.env
                chmod +x gphoto2-updater.sh
                ./gphoto2-updater.sh --stable
                if [ -f "gphoto2-updater.sh" ]; then
                    rm gphoto2-updater.sh
                fi
                if [ -f ".env" ]; then
                    rm .env
                fi
            else
                apt install -y ${package}
            fi
        fi
    done

    if [ "$NEEDS_NODEJS_CHECK" = true ]; then
        check_nodejs
    fi
}

apache_webserver() {
    info "### Installing Apache Webserver..."
    apt install -y libapache2-mod-php
}

nginx_webserver() {
    nginx_site_conf="/etc/nginx/sites-enabled/default"
    nginx_conf="/etc/nginx/nginx.conf"

    info "### Installing NGINX Webserver..."
    apt install -y nginx php${PHP_VERSION} php${PHP_VERSION}-fpm

    if [ -f "${nginx_site_conf}" ]; then
        info "### Enable PHP in NGINX"
        cp "${nginx_conf}" ~/nginx-default.bak
        sed -i 's/^\(\s*\)index index\.html\(.*\)/\1index index\.php index\.html\2/g' "${nginx_site_conf}"
        sed -i '/location ~ \\.php$ {/s/^\(\s*\)#/\1/g' "${nginx_site_conf}"
        sed -i '/include snippets\/fastcgi-php.conf/s/^\(\s*\)#/\1/g' "${nginx_site_conf}"
        sed -i '/fastcgi_pass unix:\/run\/php\//s/^\(\s*\)#/\1/g' "${nginx_site_conf}"
        sed -i '/.*fastcgi_pass unix:\/run\/php\//,// { /}/s/^\(\s*\)#/\1/g; }' "${nginx_site_conf}"
        sed -i "/fastcgi_pass unix:/s/php\([[:digit:]].*\)-fpm/php${PHP_VERSION}-fpm/g" "${nginx_site_conf}"
        sed -i '/^include \/etc\/nginx\/sites-enabled\/\*;/a client_max_body_size 100M;' "${nginx_conf}"

        info "### Testing NGINX config"
        /usr/sbin/nginx -t -c ${nginx_conf}

        info "### Restarting NGINX"
        systemctl reload-or-restart nginx
        systemctl enable nginx
        systemctl reload-or-restart php${PHP_VERSION}-fpm
        systemctl enable php${PHP_VERSION}-fpm
    else
        error "Can not find ${nginx_conf} !"
        info "Using Apache Webserver !"
        apt remove -y nginx php${PHP_VERSION}-fpm
        apache_webserver
    fi
}

lighttpd_webserver() {
    info "### Installing Lighttpd Webserver..."
    apt install -y lighttpd php${PHP_VERSION}-fpm
    lighttpd-enable-mod fastcgi
    lighttpd-enable-mod fastcgi-php

    lighttpd_php_conf="/etc/lighttpd/conf-available/15-fastcgi-php.conf"

    if [ -f "${lighttpd_php_conf}" ]; then
        info "### Enable PHP for Lighttpd"
        cp ${php_conf} ${php_conf}.bak

        cat > ${php_conf} <<EOF
# -*- depends: fastcgi -*-
# /usr/share/doc/lighttpd/fastcgi.txt.gz
# http://redmine.lighttpd.net/projects/lighttpd/wiki/Docs:ConfigurationOptions#mod_fastcgi-fastcgi

## Start an FastCGI server for php (needs the php5-cgi package)
fastcgi.server += ( ".php" =>
	((
		"socket" => "/var/run/php/php${PHP_VERSION}-fpm.sock",
		"broken-scriptfilename" => "enable"
	))
)
EOF

        systemctl reload-or-restart lighttpd
        systemctl enable lighttpd
        systemctl reload-or-restart php${PHP_VERSION}-fpm.service
        systemctl enable php${PHP_VERSION}-fpm.service
    else
        error "Can not find ${php_conf} !"
        info "Using Apache Webserver !"
        apt remove -y lighttpd php${PHP_VERSION}-fpm
        apache_webserver
    fi
}

general_setup() {
    if [ "$SUBFOLDER" = true ]; then
        cd /var/www/html/
        INSTALLFOLDER="photobooth"
        INSTALLFOLDERPATH="/var/www/html/$INSTALLFOLDER"
        URL="http://$IPADDRESS/$INSTALLFOLDER"
    else
        cd /var/www/
        INSTALLFOLDER="html"
        INSTALLFOLDERPATH="/var/www/html"
        URL="http://$IPADDRESS"
    fi

    if [ -d "$INSTALLFOLDERPATH" ]; then
        BACKUPFOLDER="html-$DATE"
        info "${INSTALLFOLDERPATH} found. Creating backup as ${BACKUPFOLDER}."
        mv "$INSTALLFOLDER" "$BACKUPFOLDER"
    else
        info "$INSTALLFOLDERPATH not found."
    fi
}

add_git_remote() {
    cd $INSTALLFOLDERPATH/
    info "### Checking needed remote information..."
    if git config remote.photoboothproject.url > /dev/null; then
        info "### photoboothproject remote exist already"
        if git config remote.origin.url == "git@github.com:andi34/photobooth" || git config remote.origin.url == "https://github.com/andi34/photobooth.git"; then
            info "origin remote is andi34"
        fi
    else
        info "### Adding photoboothproject remote..."
        git remote add photoboothproject https://github.com/PhotoboothProject/photobooth.git
    fi
}

check_git_install() {
    cd $INSTALLFOLDERPATH
    info "### Checking for git Installation"
    if [ "$(git rev-parse --is-inside-work-tree)" = true ]; then
        info "### Photobooth installed via git."
        GIT_INSTALL=true
        add_git_remote

        info "### Ignoring filemode changes."
        git config core.fileMode false
    else
        warn "WARN: Not a git Installation."
    fi
}

start_git_install() {
    cd $INSTALLFOLDERPATH
    info "### We are installing/updating Photobooth via git."
    git fetch photoboothproject $BRANCH
    git checkout photoboothproject/$BRANCH

    git submodule update --init

    if [ -f "0001-backup-changes.patch" ]; then
        info "### Trying to apply your local changes again..."
        git am --whitespace=nowarn "0001-backup-changes.patch" && PATCH_SUCCESS=true || PATCH_SUCCESS=false
        if [ "$PATCH_SUCCESS" = true ]; then
            info "### Changes applied successfully!"
            git reset --soft HEAD^
        else
            error "ERROR: can not apply your local changes automatically!"
            git am --abort
        fi

        mv 0001-backup-changes.patch $INSTALLFOLDERPATH/private/$DATE-backup-changes.patch
    fi

    info "### Get yourself a hot beverage. The following step can take up to 15 minutes."
    yarn install
    yarn build
}

start_install() {
    info "### Now we are going to install Photobooth."
    if [ $GIT_INSTALL = true ]; then
        git clone https://github.com/PhotoboothProject/photobooth $INSTALLFOLDER
        cd $INSTALLFOLDERPATH
        add_git_remote
        start_git_install
    else
        info "### We are downloading the latest release and extracting it to $INSTALLFOLDERPATH."
        curl -s https://api.github.com/repos/PhotoboothProject/photobooth/releases/latest |
            jq '.assets[].browser_download_url | select(endswith(".tar.gz"))' |
            xargs curl -L --output /tmp/photobooth-latest.tar.gz

        mkdir -p $INSTALLFOLDERPATH
        tar -xzvf /tmp/photobooth-latest.tar.gz -C $INSTALLFOLDERPATH
        cd $INSTALLFOLDERPATH
    fi
}

browser_shortcut() {
    if [ "$CHROME_FLAGS" = true ]; then
        if [ "$RUNNING_ON_PI" = true ]; then
            EXTRA_FLAGS="$CHROME_DEFAULT_FLAGS --use-gl=egl"
        else
            EXTRA_FLAGS="$CHROME_DEFAULT_FLAGS"
        fi
    else
        EXTRA_FLAGS=""
    fi

    echo "[Desktop Entry]" > "$AUTOSTART_FILE"
    echo "Version=1.3" >> "$AUTOSTART_FILE"
    echo "Terminal=false" >> "$AUTOSTART_FILE"
    echo "Type=Application" >> "$AUTOSTART_FILE"
    echo "Name=Photobooth" >> "$AUTOSTART_FILE"
    if [ "$SUBFOLDER" = true ]; then
        echo "Exec=$WEBBROWSER $KIOSK_FLAG/$INSTALLFOLDER $EXTRA_FLAGS" >> "$AUTOSTART_FILE"
    else
        echo "Exec=$WEBBROWSER $KIOSK_FLAG $EXTRA_FLAGS" >> "$AUTOSTART_FILE"
    fi
    echo "Icon=$INSTALLFOLDERPATH/resources/img/favicon-96x96.png" >> "$AUTOSTART_FILE"
    echo "StartupNotify=false" >> "$AUTOSTART_FILE"
    echo "Terminal=false" >> "$AUTOSTART_FILE"
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

raspberry_permission() {
    if [ "$WEBBROWSER" != "unknown" ]; then
        info "### Adding photobooth shortcut to Desktop"
        if [ ! -d "/home/$USERNAME/Desktop" ]; then
            mkdir -p /home/$USERNAME/Desktop
            chown -R $USERNAME:$USERNAME /home/$USERNAME/Desktop
        fi
        AUTOSTART_FILE="/home/$USERNAME/Desktop/photobooth.desktop"
        browser_shortcut
        chmod +x /home/$USERNAME/Desktop/photobooth.desktop
        chown $USERNAME:$USERNAME /home/$USERNAME/Desktop/photobooth.desktop
    else
        info "### Browser unknown or not installed. Can not add shortcut to Desktop."
    fi

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
        if [ "$DESKTOP_OS" = true ]; then
            info "### Disabling automount for user $USERNAME."

            mkdir -p /home/$USERNAME/.config/pcmanfm/LXDE-pi/
            cat >> /home/$USERNAME/.config/pcmanfm/LXDE-pi/pcmanfm.conf <<EOF
[volume]
mount_on_startup=0
mount_removable=0
autorun=0
EOF

            chown -R $USERNAME:$USERNAME /home/$USERNAME/.config
        else
            info "### lxde is not installed. Can not add automount config for user $USERNAME."
        fi

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
}

general_permissions() {
    info "### Setting permissions."
    chown -R www-data:www-data $INSTALLFOLDERPATH/
    gpasswd -a www-data plugdev
    gpasswd -a www-data video

    touch "/var/www/.yarnrc"
    info "### Fixing permissions on .yarnrc"
    chown www-data:www-data "/var/www/.yarnrc"

    info "### Fixing permissions on cache folder."
    mkdir -p "/var/www/.cache"
    chown -R www-data:www-data "/var/www/.cache"

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
}

kioskbooth_desktop() {
    if [ "$KIOSK_MODE" = true ]; then
        AUTOSTART_FILE="/etc/xdg/autostart/photobooth.desktop"
        browser_shortcut
    else
        info "### lxde is not installed. Can not setup Photobooth in KIOSK-Mode."
    fi

    if [ "$HIDE_MOUSE" = true ]; then
        if [ -f "/etc/xdg/lxsession/LXDE-pi/autostart" ]; then
            sed -i '/Photobooth/,/Photobooth End/d' /etc/xdg/lxsession/LXDE-pi/autostart
        fi

cat >> /etc/xdg/lxsession/LXDE-pi/autostart <<EOF
# Photobooth
# turn off display power management system
@xset -dpms
# turn off screen blanking
@xset s noblank
# turn off screen saver
@xset s off

# Hide mousecursor
@unclutter -idle 3
# Photobooth End

EOF
fi
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

gphoto_preview() {
    if [ -d "/etc/systemd/system" ] && [ -d "/usr" ]; then
        wget https://raw.githubusercontent.com/PhotoboothProject/photobooth/dev/gphoto/ffmpeg-webcam.service -O "/etc/systemd/system/ffmpeg-webcam.service"
        wget https://raw.githubusercontent.com/PhotoboothProject/photobooth/dev/gphoto/ffmpeg-webcam.sh -O "/usr/ffmpeg-webcam.sh"
        chmod +x "/usr/ffmpeg-webcam.sh"
        systemctl start ffmpeg-webcam.service
        systemctl enable ffmpeg-webcam.service
    else
        warn "WARNING: Couldn't install gphoto2 Webcam Service! Skipping!"
    fi
}

fix_git_modules() {
    cd $INSTALLFOLDERPATH

    git config --global --add safe.directory $INSTALLFOLDERPATH

    for submodule in "${PHOTOBOOTH_SUBMODULES[@]}"; do
        if [ -d "${INSTALLFOLDERPATH}/${submodule}" ]; then
            if grep -q ${submodule} "./.gitmodules"; then
                info "### Adding global safe.directory: ${INSTALLFOLDERPATH}/${submodule}"
                git config --global --add safe.directory ${INSTALLFOLDERPATH}/${submodule}
            else
              warn "### ${INSTALLFOLDERPATH}/${submodule} does not belong to our modules anymore."
              rm -rf  ${INSTALLFOLDERPATH}/${submodule}
            fi
        fi
    done

    git submodule foreach --recursive git reset --hard
    git submodule deinit -f .
    git submodule update --init --recursive
}

commit_git_changes() {
    cd $INSTALLFOLDERPATH

    fix_git_modules

    if [ -z "$(git status --porcelain)" ]; then
        info "### Nothing to commit."
    else
        BACKUPBRANCH="backup-$DATE"

        echo -e "\033[0;33m### Uncommited changes detected. Continue update? [y/N]"
        echo -e "### NOTE: If typing y, your changes will be commited and will be kept"
        echo -e "          inside a local branch ($BACKUPBRANCH)."
        echo -e "          We will try to apply these changes after update. If applying fails,"
        ask_yes_no "          your changes can be applied manually after update." "N"
        echo -e "\033[0m"
        if [ "$REPLY" != "${REPLY#[Yy]}" ]; then
            info "### We will commit your changes and keep them inside a local backup branch."
            if [ -z "$(git config user.name)" ]; then
                warn "WARN: git user.name not set!"
                info "### Setting git user.name."
                $(git config user.name Photobooth)
            fi

            if [ -z "$(git config user.email)" ]; then
                warn "WARN: git user.email not set!"
                info "### Setting git user.email."
                $(git config user.email Photobooth@localhost)
            fi

            echo "git user.name: $(git config user.name)"
            echo "git user.email: $(git config user.email)"

            git add --all
            git commit -a -m "backup changes"
            git checkout -b $BACKUPBRANCH
            info "### Backup done to branch: $BACKUPBRANCH"
            git format-patch -1
        else
            error "ERROR: Uncommited changes detected. Please commit your changes."
            if [ "$SILENT_INSTALL" = true ]; then
                info "### You can also rerun the installer without silent mode to update anyway."
                info "### We will try to apply your changes again after update."
            fi
            exit
        fi
    fi
}

detect_photobooth_install() {
    for path in "${PHOTOBOOTH_PATH[@]}"; do
        info "### Checking for install at ${path}"
        if [ "$PHOTOBOOTH_FOUND" = false ]; then
            if [ -d "${path}" ]; then
                if [ -f "${path}/lib/configsetup.inc.php" ]; then
                    PHOTOBOOTH_FOUND=true
                    INSTALLFOLDERPATH="${path}"
                    info "### Photobooth installation found in path ${path}."
                fi
            fi
        fi
    done
}

start_update() {
    detect_photobooth_install
    if [ "$PHOTOBOOTH_FOUND" = true ]; then
        check_git_install
    fi
    if [ "$GIT_INSTALL" = true ]; then
        common_software
        commit_git_changes
        start_git_install
        general_permissions
        fix_git_modules
        print_spaces
        print_logo
        info "###"
        if [ ! -z $BACKUPBRANCH ]; then
            if [ "$PATCH_SUCCESS" = true ]; then
                info "### Your uncommited changes have been applied successfully!"
            else
                error "### Uncommited changes couldn't be applied automatically!"
            fi
            info "### Backup done to branch: $BACKUPBRANCH"
        fi
        info "###"
        info "### Update completed!"
        info "###"
        info "### Please clear your Browser Cache to"
        info "### avoid graphical issues."
        info "###"
        info "### Have fun with your Photobooth!"
    else
         error "ERROR: Can not Update!"
    fi
    exit
}

############################################################
#                                                          #
# General checks before the installation process can start #
#                                                          #
############################################################

if [ $UID != 0 ]; then
    error "ERROR: Only root is allowed to execute the installer. Forgot sudo?"
    exit 1
fi

if [ "$FORCE_RASPBERRY_PI" = false ]; then
    if [ ! -f /proc/device-tree/model ]; then
        no_raspberry 2
    else
        PI_MODEL=$(tr -d '\0' </proc/device-tree/model)

        if [[ $PI_MODEL != Raspberry* ]]; then
            no_raspberry 3
        fi
    fi
fi

info "### Checking internet connection..."
ping -c 1 -q google.com >&/dev/null

if [ $? -eq 0 ]; then
    info "    connected!"
else
    error "ERROR: No internet connection!"
    error "       Please connect to the internet and rerun the installer."
    exit 1
fi

############################################################
#                                                          #
# Try updating Photobooth                                  #
#                                                          #
############################################################

if [ "$RUN_UPDATE" = true ]; then
    start_update
fi

############################################################
#                                                          #
# Ask all questions before installing Photobooth           #
#                                                          #
############################################################

if [ "$RUNNING_ON_PI" = true ]; then
    if [ ! -z $USERNAME ]; then
        check_username
    else
        error "ERROR: An valid OS username is needed! Please re-run the installer."
        view_help
        exit
    fi
    print_spaces
fi

echo -e "\033[0;33m### Is Photobooth the only website on this system?"
echo -e "### NOTE: If typing y, the whole /var/www/html folder will be renamed"
ask_yes_no "          to /var/www/html-$DATE if exists! [y/N] " "Y"
echo -e "\033[0m"
if [ "$REPLY" != "${REPLY#[Yy]}" ]; then
    info "### We will install Photobooth into /var/www/html."
    SUBFOLDER=false
else
    info "### We will install Photobooth into /var/www/html/$INSTALLFOLDER."
fi

print_spaces

echo -e "\033[0;33m### You probably like to use a printer."
ask_yes_no "### You like to install CUPS and set needing printer permissions? [y/N] " "Y"
echo -e "\033[0m"
if [[ $REPLY =~ ^[Yy]$ ]]
then
    SETUP_CUPS=true
    EXTRA_PACKAGES+=('cups')
    info "### We will install CUPS if not installed already."
    print_spaces

    echo -e "\033[0;33m### By default CUPS can only be accessed via localhost."
    ask_yes_no "### You like to allow remote access to CUPS over IP from all devices inside your network? [y/N] " "N"
    echo -e "\033[0m"
    if [[ $REPLY =~ ^[Yy]$ ]]
    then
        CUPS_REMOTE_ANY=true
        info "### We will allow remote access to CUPS over IP from all devices inside your network."
    else
        info "### We won't allow remote access to CUPS over IP from all devices inside your network."
    fi

    print_spaces

    echo -e "\033[0;33m### You might need some additional drivers to use the print function."
    echo -e "### You like to install a collection of free-software printer drivers"
    ask_yes_no "### (Gutenprint for use with UNIX spooling systems, such as CUPS, lpr and LPRng)? [y/N] " "Y"
    echo -e "\033[0m"
    if [[ $REPLY =~ ^[Yy]$ ]]
    then
        EXTRA_PACKAGES+=('printer-driver-gutenprint')
        info "### We will install Gutenprint drivers if not installed already."
    else
        info "### We won't install Gutenprint drivers if not installed already."
    fi
fi

print_spaces

if [ -d "/etc/xdg/autostart" ]; then

    if [ $(dpkg-query -W -f='${Status}' "chromium-browser" 2>/dev/null | grep -c "ok installed") -eq 1 ]; then
        WEBBROWSER="chromium-browser"
        CHROME_FLAGS=true
    elif [ $(dpkg-query -W -f='${Status}' "google-chrome" 2>/dev/null | grep -c "ok installed") -eq 1 ]; then
        WEBBROWSER="google-chrome"
        CHROME_FLAGS=true
    elif [ $(dpkg-query -W -f='${Status}' "google-chrome-stable" 2>/dev/null | grep -c "ok installed") -eq 1 ]; then
        WEBBROWSER="google-chrome-stable"
        CHROME_FLAGS=true
    elif [ $(dpkg-query -W -f='${Status}' "google-chrome-beta" 2>/dev/null | grep -c "ok installed") -eq 1 ]; then
        WEBBROWSER="google-chrome-beta"
        CHROME_FLAGS=true
    elif [ $(dpkg-query -W -f='${Status}' "firefox" 2>/dev/null | grep -c "ok installed") -eq 1 ]; then
        WEBBROWSER="firefox"
        CHROME_FLAGS=false
    else
        WEBBROWSER="unknown"
        CHROME_FLAGS=false
    fi

    if [ "$WEBBROWSER" != "unknown" ]; then
        echo -e "\033[0;33m### You probably like to start $WEBBROWSER on every start."
        ask_yes_no "### Open $WEBBROWSER in Kiosk Mode at every boot? [y/N] " "Y"
        echo -e "\033[0m"
        if [[ $REPLY =~ ^[Yy]$ ]]
        then
            KIOSK_MODE=true
            info "### We will open $WEBBROWSER in Kiosk Mode at every boot."
        else
            info "### We won't open $WEBBROWSER in Kiosk Mode at every boot."
        fi
    else
        warn "### No supported webbrowser found!"
    fi
    print_spaces
fi

# Pi specific setup start
if [ "$RUNNING_ON_PI" = true ]; then
    if [ "$DESKTOP_OS" = true ]; then
        echo -e "\033[0;33m### You probably like hide the mouse cursor on every start and disable the screen saver."
        ask_yes_no "### Disable screen saver and hide the mouse cursor? [y/N] " "Y"
        echo -e "\033[0m"
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            HIDE_MOUSE=true
            EXTRA_PACKAGES+=('unclutter')
            info "### We will hide the mouse cursor on every start and disable the screen saver."
        else
            info "### We won't hide the mouse cursor on every start and won't disable the screen saver."
        fi
    else
        info "### lxde is not installed. Can not hide the mouse cursor on every start."
    fi
    print_spaces

    echo -e "\033[0;33m### Do you like to use a Raspberry Pi (HQ) Camera to take pictures?"
    ask_yes_no "### If yes, this will generate a personal configuration with all needed changes. [y/N] " "N"
    echo -e "\033[0m"
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        PI_CAMERA=true
        info "### We will generate a personal configuration with all needed"
        info "    changes to use a Raspberry Pi (HQ) Camera to take pictures."
    else
        info "### We won't generate a personal configuration file to use a Raspberry Pi (HQ) Camera to take pictures."
    fi

    print_spaces

    echo -e "\033[0;33m### Sync to USB - this feature will automatically copy (sync) new pictures to a USB stick."
    echo -e "### The actual configuration will be done in the admin panel but we need to setup Raspberry Pi OS first."
    ask_yes_no "### Would you like to setup Raspberry Pi OS to use the USB sync file backup? [y/N] " "Y"
    echo -e "\033[0m"
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        USB_SYNC=true
        info "### We will setup Raspberry Pi OS to be able to use the USB sync file backup."
    else
        info "### We won't setup Raspberry Pi OS to use the USB sync file backup."
    fi

    print_spaces
fi
# Pi specific setup end

    echo -e "\033[0;33m### Do you like to install a service to set up a virtual webcam that gphoto2 can stream video to"
    echo -e "### (needed for preview from gphoto2)? Your camera must be supported by gphoto2 for liveview."
    echo -e "### Note: This will disable other webcam interfaces on a Raspberry Pi (e.g. Pi Camera)."
    ask_yes_no "### If unsure, type N. [y/N] " "N"
    echo -e "\033[0m"
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        GPHOTO_PREVIEW=true
        info "### We will install a service to set up a virtual webcam for gphoto2."
    else
        GPHOTO_PREVIEW=false
        info "### We won't install a service to set up a virtual webcam for gphoto2."
    fi

############################################################
#                                                          #
# Go through the installation steps of Photobooth          #
#                                                          #
############################################################


print_spaces
info "### Starting installation..."
print_spaces

common_software
general_setup
start_install
if [ "$PI_CAMERA" = true ]; then
    pi_camera
fi
general_permissions
if [ "$RUNNING_ON_PI" = true ]; then
raspberry_permission
fi
if [ "$KIOSK_MODE" = true ] || [ "$HIDE_MOUSE" = true ] ; then
    kioskbooth_desktop
fi
if [ "$SETUP_CUPS" = true ]; then
    cups_setup
fi

if [ "$GPHOTO_PREVIEW" = true ]; then
    gphoto_preview
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
if [ "$SETUP_CUPS" = true ]; then
    info "    In order to use the print function,"
    info "    you'll have to setup your printer inside CUPS:"
    info "        http://localhost:631"
    info ""
fi
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


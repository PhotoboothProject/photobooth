#!/bin/bash

# Stop on the first sign of trouble
set -e

SILENT_INSTALL=false

if [ "silent" = "$1" ]; then
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

echo -e "\033[0;33m"
ask_yes_no "### Do you like to reboot now? [y/N] " "y"
echo -e "\033[0m"
if [[ $REPLY =~ ^[Yy]$ ]]
then
    info "### Your device will reboot now."
    shutdown -r now
fi

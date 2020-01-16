#!/bin/bash

# Stop on the first sign of trouble
set -e

function info {
    echo -e "\033[0;36m${1}\033[0m"
}

function error {
    echo -e "\033[0;31m${1}\033[0m"
}

if [ $UID != 0 ]; then
    error "ERROR: Only root is allowed to execute the reset script. Forgot sudo?"
    exit 1
fi

if [ ! -f /proc/device-tree/model ]; then
    error "ERROR: This reset script is only intended to run on a Raspberry Pi."
    exit 2
fi

PI_MODEL=$(tr -d '\0' </proc/device-tree/model)

if [[ $PI_MODEL != Raspberry* ]]; then
    error "ERROR: This reset script is only intended to run on a Raspberry Pi."
    exit 3
fi

info "### The Photobooth reset script for your Raspberry Pi."

echo -e "\033[0;33m### Do you want to reset to factory settings?"
read -p "### Warning: This will remove EVERY data (pictures, qr codes) and admin settings! [y/N] " -n 1 -r factoryReset
echo -e "\033[0m"

if [ "$factoryReset" != "${factoryReset#[Yy]}" ] ;then
    info "### Ok, lets go and remove some things."

    info "### Remove your data ..."
    rm -rf data
    mkdir data
    chown -R www-data:www-data data

    info "### Remove your config file ..."
    rm -rf config/my.config.inc.php

    info "### Successful removed your data."
else
    echo -e "\033[0;33m### Do you want to only remove your data (picturey, qr codes)?"
    read -p "### Your configuration file (admin settigns) will be preserved! [y/N] " -n 1 -r dataReset
    echo -e "\033[0m"

    if [ "$dataReset" != "${dataReset#[Yy]}" ] ;then
        info "### Ok, lets go and remove your data."
        rm -rf data
        mkdir data
        chown -R www-data:www-data data

        info "### Successful removed your data."
    fi
fi

info "### Congratulations you finished the reset process."
info "### Have fun with your resetted booth."

#!/bin/bash

COMMON_PACKAGES=(
    'gphoto2'
    'ffmpeg'
    'v4l2loopback-dkms'
    'v4l-utils'
    'python3'
    'python3-gphoto2'
    'python3-psutil'
    'python3-zmq'
)

function info {
    echo -e "\033[0;36m${1}\033[0m"
}

function error {
    echo -e "\033[0;31m${1}\033[0m"
}

if [ "$UID" != 0 ]; then
    error "ERROR: Only root is allowed to execute the installer. Forgot sudo?"
    exit 1
fi

#Param 1: Question / Param 2: Default / silent answer
function ask_yes_no {
    read -p "${1}: " -n 1 -r
}

info "This script installs some dependencies and simplifies the setup for using gphoto2 as webcam."
info "It installs required dependencies and sets up a virtual webcam that gphoto2 can stream video to."
info "It can remove the gphoto2 webcam setup, as well."
info ""
echo "Your options are:"
echo "1 Install gphoto2 webcam"
echo "2 Remove gphoto2 webcam"
echo "3 Migrate from systemd service to modprobe config"
echo "4 Nothing"
info ""
ask_yes_no "Please enter your choice" "3"
info ""
if [[ $REPLY =~ ^[1]$ ]]; then
    info "### Installing required software..."
    for package in "${COMMON_PACKAGES[@]}"; do
        if [ "$(dpkg-query -W -f='${Status}' "$package" 2>/dev/null | grep -c "ok installed")" -eq 1 ]; then
            info "[Package]   ${package} installed already"
        else
            info "[Package]   Installing missing package: ${package}"
            apt install -y "$package"
        fi
    done

    info "All required software was installed."
    info ""
    info "Note: Installing gphoto2 as webcam disables other webcams."
    info ""
    ask_yes_no "Do you want to setup gphoto2 as a webcam? (y/n)" "n"
    info ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        info "### Installing gphoto2 webcam"
        # make it persistent
        [[ ! -d /etc/modprobe.d ]] && mkdir /etc/modprobe.d
        cat >/etc/modprobe.d/v4l2loopback.conf <<EOF
options v4l2loopback exclusive_caps=1 card_label="GPhoto2 Webcam"
blacklist bcm2835-isp
EOF
        # adjust runtime
        modprobe v4l2loopback exclusive_caps=1 card_label="GPhoto2 Webcam"
        rmmod bcm2835-isp || true
    fi
elif [[ $REPLY =~ ^[2]$ ]]; then
    info "### Stopping and removing gphoto2 webcam service."
    [[ -f /etc/modprobe.d/v4l2loopback.conf ]] && rm /etc/modprobe.d/v4l2loopback.conf
    rmmod v4l2loopback || true
    info "gphoto2 webcam removed..."
elif [[ $REPLY =~ ^[3]$ ]]; then
    info "### Migrating to modprobe config"
    if [[ -f /etc/systemd/system/ffmpeg-webcam.service ]]; then
        # clean old files
        info "### Old ffmpeg-webcam.service detected. Uninstalling..."
        systemctl disable --now ffmpeg-webcam.service
        rm /etc/systemd/system/ffmpeg-webcam.service
        systemctl daemon-reload
        if [[ -f /usr/ffmpeg-webcam.sh ]]; then
            info "### Also removing the /usr/ffmpeg-webcam.sh file"
            rm /usr/ffmpeg-webcam.sh
        fi

        # install via new method
        info "### Installing new modprobe config"
        gphoto_preview
    else
        info "### You seem to already be migrated"
        exit 1
    fi
else
    info "Okay... doing nothing!"
fi
exit 0

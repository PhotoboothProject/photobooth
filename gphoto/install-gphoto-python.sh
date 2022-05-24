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

if [ $UID != 0 ]; then
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
echo "3 Nothing"
info ""
ask_yes_no "Please enter your choice" "3"
info ""
if [[ $REPLY =~ ^[1]$ ]]; then
    info "### Installing required software..."
    for package in "${COMMON_PACKAGES[@]}"; do
        if [ "$(dpkg-query -W -f='${Status}' "${package}" 2>/dev/null | grep -c "ok installed")" -eq 1 ]; then
            info "[Package]   ${package} installed already"
        else
            info "[Package]   Installing missing package: ${package}"
            apt install -y "${package}"
        fi
    done

    info "All required software was installed."
    info ""
    info "Note: Installing gphoto2 as webcam disables other webcams."
    info ""
    ask_yes_no "Do you want to setup gphoto2 as a webcam? (y/n)" "n"
    info ""
    if [[ $REPLY =~ ^[Yy]$ ]]
    then
        info "### Installing gphoto2 webcam service."
        wget https://raw.githubusercontent.com/andi34/photobooth/dev/gphoto/ffmpeg-webcam.service -O "/etc/systemd/system/ffmpeg-webcam.service"
        wget https://raw.githubusercontent.com/andi34/photobooth/dev/gphoto/ffmpeg-webcam.sh -O "/usr/ffmpeg-webcam.sh"
        chmod +x /usr/ffmpeg-webcam.sh
        systemctl start ffmpeg-webcam.service
        systemctl enable ffmpeg-webcam.service
        info "gphoto2 webcam service installed and running..."
    fi
elif [[ $REPLY =~ ^[2]$ ]]; then
    info "### Stopping and removing gphoto2 webcam service."
    systemctl stop ffmpeg-webcam.service
    systemctl disable ffmpeg-webcam.service
    rm '/usr/ffmpeg-webcam.sh'
    rm '/etc/systemd/system/ffmpeg-webcam.service'
    info "gphoto2 webcam service stopped and removed..."
else
    info "Okay... doing nothing!"
fi
exit 0


#!/bin/bash

# This script provides common customization options for the ISO
# 
# Usage: Copy this file to config.sh and make changes there.  Keep this file (default_config.sh) as-is
#   so that subsequent changes can be easily merged from upstream.  Keep all customiations in config.sh

# The version of Ubuntu to generate.  Successfully tested: focal, groovy
# See https://wiki.ubuntu.com/DevelopmentCodeNames for details
export TARGET_UBUNTU_VERSION="focal"

# The packaged version of the Linux kernel to install on target image.
# See https://wiki.ubuntu.com/Kernel/LTSEnablementStack for details
export TARGET_KERNEL_PACKAGE="linux-generic"

# The Ubuntu Mirror URL. It's better to change for faster download.
# More mirrors see: https://launchpad.net/ubuntu/+archivemirrors
export TARGET_UBUNTU_MIRROR="http://us.archive.ubuntu.com/ubuntu/"

# The file (no extension) of the ISO containing the generated disk image,
# the volume id, and the hostname of the live environment are set from this name.
export TARGET_NAME="photobooth"

# The text label shown in GRUB for booting into the live environment
export GRUB_LIVEBOOT_LABEL="Start photobooth without installing"

# The text label shown in GRUB for starting installation
export GRUB_INSTALL_LABEL="Install photobooth"

# Packages to be removed from the target system after installation completes succesfully
export TARGET_PACKAGE_REMOVE="
    ubiquity \
    casper \
    discover \
    laptop-detect \
    os-prober \
"

# Package customisation function.  Update this function to customize packages
# present on the installed system.
function customize_image() {
    # install graphics and desktop
    apt-get install -y \
    plymouth-theme-ubuntu-logo \
    ubuntu-gnome-desktop \
    ubuntu-gnome-wallpapers

    # useful tools
    apt-get install -y \
    clamav-daemon \
    terminator \
    apt-transport-https \
    curl \
    vim \
    nano \
    less \
    openssh-server \
    vlc \
    ffmpeg \
    g++ \
    clang \
    make

    # purge
    apt-get purge -y \
    transmission-gtk \
    transmission-common \
    gnome-mahjongg \
    gnome-mines \
    gnome-sudoku \
    aisleriot \
    hitori \
    libreoffice* \
    thunderbird


    #install photobooth
    pwd
    ls -l
    ls -l root/
    chmod +x root/install-raspbian.sh
    root/install-raspbian.sh -s

    #automatic loading of v4l loopback kernal module for live preview
    apt-get install -y v4l2loopback-dkms
    echo "v4l2loopback" >> /etc/modules
    echo "options v4l2loopback exclusive_caps=1 card_label=\"GPhoto2 Webcam\"" >> /etc/modprobe.d/v4l2loopback_options.conf

    #autostart firefox
    tee /etc/xdg/autostart/photobooth-kiosk.desktop << END
[Desktop Entry]
Type=Application
Name=Photobooth
Exec=firefox --kiosk http://localhost
OnlyShowIn=GNOME;
AutostartCondition=GSettings org.gnome.desktop.background show-desktop-icons
END
    

    # configure OS
    gsettings set org.gnome.settings-daemon.plugins.power idle-dim false
    gsettings set org.gnome.desktop.session idle-delay 0
}

# Used to version the configuration.  If breaking changes occur, manual
# updates to this file from the default may be necessary.
export CONFIG_FILE_VERSION="0.4"

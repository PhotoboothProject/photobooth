#!/bin/bash -e

# Disable raspiconfig at boot
on_chroot << EOF
if [ -e /etc/profile.d/raspi-config.sh ]; then
    rm -f /etc/profile.d/raspi-config.sh
    if [ -e /etc/systemd/system/getty@tty1.service.d/raspi-config-override.conf ]; then
        rm /etc/systemd/system/getty@tty1.service.d/raspi-config-override.conf
    fi
    telinit q
fi
EOF

# Disable users creation on first boot
on_chroot << EOF
systemctl stop userconfig
systemctl disable userconfig
systemctl mask userconfig

if [ -e /etc/xdg/autostart/piwiz.desktop ]; then
    rm -f /etc/xdg/autostart/piwiz.desktop
fi
EOF


#!/bin/bash -e

# Modify /usr/lib/os-release
sed -i "s/Raspbian/photobooth-os/gI" ${ROOTFS_DIR}/usr/lib/os-release
sed -i "s/^HOME_URL=.*$/HOME_URL=\"https:\/\/github.com\/andi34\/photobooth\/\"/g" ${ROOTFS_DIR}/usr/lib/os-release
sed -i "s/^SUPPORT_URL=.*$/SUPPORT_URL=\"https:\/\/github.com\/andi34\/photobooth\/\"/g" ${ROOTFS_DIR}/usr/lib/os-release
sed -i "s/^BUG_REPORT_URL=.*$/BUG_REPORT_URL=\"https:\/\/github.com\/andi34\/photobooth\/\"/g" ${ROOTFS_DIR}/usr/lib/os-release

# Custom motd
# Replace message of the day (ssh greeting text)
rm "${ROOTFS_DIR}"/etc/motd
rm "${ROOTFS_DIR}"/etc/update-motd.d/10-uname
install -m 755 files/motd-photobooth "${ROOTFS_DIR}"/etc/update-motd.d/10-photobooth

# Copy install script into chroot environment
install -m 755 files/install-raspbian.sh "${ROOTFS_DIR}"/home/photobooth/install-raspbian.sh

# Autostart file
# install -m 755 files/photobooth.desktop "${ROOTFS_DIR}"/etc/xdg/autostart/photobooth.desktop

# Remove the "last login" information
sed -i "s/^#PrintLastLog yes.*/PrintLastLog no/" ${ROOTFS_DIR}/etc/ssh/sshd_config

on_chroot << EOF
echo '---> call photobooth install script'
cd /home/photobooth
./install-raspbian.sh -u="photobooth" -rs
EOF

rm "${ROOTFS_DIR}"/home/photobooth/install-raspbian.sh

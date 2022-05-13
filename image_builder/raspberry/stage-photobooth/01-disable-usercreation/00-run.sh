#!/bin/bash -e

# Disable users creation on first boot
on_chroot << EOF
systemctl stop userconfig
systemctl disable userconfig
systemctl mask userconfig
EOF


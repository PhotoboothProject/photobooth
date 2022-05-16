#!/bin/bash -e
#
#
# "B1 Console"            "Text console, requiring user to login"
# "B2 Console Autologin"  "Text console, automatically logged in as '$USER' user"
# "B3 Desktop"            "Desktop GUI, requiring user to login"
# "B4 Desktop Autologin"  "Desktop GUI, automatically logged in as '$USER' user"
#
# On Stage4 "B4 Desktop Autologin" is defined, let's change this and require user to login

on_chroot << EOF
	SUDO_USER="${FIRST_USER_NAME}" raspi-config nonint do_boot_behaviour B3
EOF

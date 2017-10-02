#/bin/sh

# Gphoto2 2.5.7 compiler and installer script v0.4.1
#
# This script is specifically created for Raspbian http://www.raspbian.org
# and Raspberry Pi http://www.raspberrypi.org but should work over any 
# Debian-based distribution

# Created and mantained by Gonzalo Cao Cabeza de Vaca
# Please send any feedback or comments to gonzalo.cao(at)gmail.com
# Updated for gPhoto2 2.5.1.1 by Peter Hinson
# Updated for gPhoto2 2.5.2 by Dmitri Popov
# Updated for gphoto2 2.5.5 by Mihai Doarna
# Updated for gphoto2 2.5.6 by Mathias Peter
# Updated for gphoto2 2.5.7 by Sijawusz Pur Rahnama

# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.

# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.


if [ "$(whoami)" != "root" ]; then
	echo "Sorry, this script must be executed with sudo or as root"
	exit 1
fi

echo 
echo "----------------"
echo "Updating sources"
echo "----------------"
echo

apt-get update

echo 
echo "--------------------------"
echo "Removing gphoto2 if exists"
echo "--------------------------"
echo

apt-get remove -y gphoto2

echo 
echo "-----------------------"
echo "Installing dependencies"
echo "-----------------------"
echo

apt-get install -y libltdl-dev libusb-dev libexif-dev libpopt-dev libudev-dev

echo 
echo "-------------------------"
echo "Creating temporary folder"
echo "-------------------------"
echo

mkdir gphoto2-temp-folder
cd gphoto2-temp-folder

echo "gphoto2-temp-folder created"


echo 
echo "-------------------------"
echo "Downloading libusb 1.0.17"
echo "-------------------------"
echo

if wget -q http://ftp.de.debian.org/debian/pool/main/libu/libusbx/libusbx_1.0.17.orig.tar.bz2
	then
		tar xjvf libusbx_1.0.17.orig.tar.bz2
		cd libusbx-1.0.17/
	else
		echo "Unable to get libusbx_1.0.17"
		echo "Cleaning and exiting..."
		exit 1
fi

echo 
echo "--------------------------------------"
echo "Compiling and installing libusb 1.0.17"
echo "--------------------------------------"

./configure
make
make install
cd ..


echo 
echo "----------------------------"
echo "Downloading libgphoto2 2.5.7"
echo "----------------------------"
echo

if wget -q http://downloads.sourceforge.net/project/gphoto/libgphoto/2.5.7/libgphoto2-2.5.7.tar.bz2
	then
		tar xjf libgphoto2-2.5.7.tar.bz2
		cd libgphoto2-2.5.7
	else
		echo "Unable to get libgphoto2-2.5.7"
		echo "Cleaning and exiting..."
		exit 1
fi


echo 
echo "-----------------------------------------"
echo "Compiling and installing libgphoto2 2.5.7"
echo "-----------------------------------------"
echo

./configure
make
make install
cd ..

echo 
echo "-------------------------"
echo "Downloading gphoto2 2.5.6"
echo "-------------------------"
echo

if wget -q http://downloads.sourceforge.net/project/gphoto/gphoto/2.5.6/gphoto2-2.5.6.tar.gz
	then
		tar xzvf gphoto2-2.5.6.tar.gz
		cd gphoto2-2.5.6
	else
		echo "Unable to get gphoto2-2.5.6"
		echo "Cleaning and exiting..."
		exit 1
fi


echo 
echo "--------------------------------"
echo "Compiling and installing gphoto2"
echo "--------------------------------" 
echo

./configure
make
make install
cd ..

echo 
echo "-----------------"
echo "Linking libraries"
echo "-----------------"  
echo

ldconfig

echo
echo "---------------------------------------------------------------------------------"
echo "Generating udev rules, see http://www.gphoto.org/doc/manual/permissions-usb.html"
echo "---------------------------------------------------------------------------------"
echo

udev_version=$(udevd --version)

if   [ "$udev_version" -ge "201" ]
then
  udev_rules=201
elif [ "$udev_version" -ge "175" ]
then
  udev_rules=175
elif [ "$udev_version" -ge "136" ]
then
  udev_rules=136
else
  udev_rules=0.98
fi

/usr/local/lib/libgphoto2/print-camera-list udev-rules version $udev_rules group plugdev mode 0660 > /etc/udev/rules.d/90-libgphoto2.rules

if   [ "$udev_rules" = "201" ]
then
  echo
  echo "------------------------------------------------------------------------"
  echo "Generating hwdb file in /etc/udev/hwdb.d/20-gphoto.hwdb. Ignore the NOTE"
  echo "------------------------------------------------------------------------"
  echo
  /usr/local/lib/libgphoto2/print-camera-list hwdb > /etc/udev/hwdb.d/20-gphoto.hwdb
fi


echo 
echo "-------------------"
echo "Removing temp files"
echo "-------------------"
echo

cd ..
rm -r gphoto2-temp-folder



echo 
echo "--------------------"
echo "Finished!! Enjoy it!"
echo "--------------------"
echo

gphoto2 --version


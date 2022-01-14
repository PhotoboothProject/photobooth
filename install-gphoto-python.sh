#!/bin/bash
sudo apt install -y libgphoto2-dev python3 python3-pip
pip3 install zmq psutil
pip3 install gphoto2 --no-binary :all:
sudo mkdir /var/www/.gphoto
sudo chown www-data:www-data /var/www/.gphoto

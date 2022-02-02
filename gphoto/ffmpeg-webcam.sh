#!/bin/bash

# setup ffmpeg webcam
modprobe v4l2loopback exclusive_caps=1 card_label="GPhoto2 Webcam"
# disable any other webcam but ignore if it exits with an error because there was no other webcam
rmmod bcm2835-isp || true

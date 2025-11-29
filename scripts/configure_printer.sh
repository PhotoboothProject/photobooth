#!/bin/bash

# Start avahi-daemon
echo "Starting avahi-daemon..."
/usr/sbin/avahi-daemon --no-chroot &

# Wait for avahi-daemon to initialize
sleep 10

# Detect the printer's IPP URI
echo "Detecting printer's IPP URI..."
PRINTER_URI=$(lpinfo -v | grep "ipp://" | awk '{print $2}')

if [ -z "$PRINTER_URI" ]; then
  echo "No IPP printer detected. Exiting."
  exit 1
fi

echo "Printer detected at: $PRINTER_URI"

# Add and configure the Canon SELPHY CP1500 printer using IPP
echo "Configuring the printer..."
lpadmin -p canon -E -v "$PRINTER_URI" -m everywhere
lpoptions -d canon

# Verify the printer configuration
echo "Verifying the printer configuration..."
lpstat -p

# Test lpinfo -v and lpstat -p

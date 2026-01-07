# Photobooth Setup Wizard

To make the installation and optional feature setup as simple as possible, we have created the "Photobooth Setup Wizard" for you. It can setup your Raspberry Pi, Computer or Laptop as a full blown Photobooth.

This means:

- Photobooth and all needed packages and dependencies get installed and the automatic camera mount is disabled on installaion.
- Optional permissions can be set/unset
- Shortcuts and autostart in kiosk mode can be setup/removed
- ...

## Running the Photobooth Setup Wizard

Download latest Setup Wizard and execute it:
```
wget -O install-photobooth.sh https://raw.githubusercontent.com/PhotoboothProject/photobooth/dev/install-photobooth.sh
sudo bash install-photobooth.sh
```

On Photobooth installation by default Apache is used for an easy and no-hassle setup.

- Adjust your installation setup if needed: 1 Installation configuration
- To get to know all options you can simply run `sudo bash install-photobooth.sh --help`.

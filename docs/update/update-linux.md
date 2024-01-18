# Updating Photobooth on Linux

Node.js **must** be installed in v18. Currently only v16 is tested. Our installer will check your Node.js version and suggest an update/downgrade if needed.
Update will fail if Node.js is installed in a version below v16!


## Updating from v2.x or older
To update from an old version to latest Photobooth it's recommend to [make a clean installalation](../install/install-debian.md).


## Updating from v3.x or v4.x to latest stable release

**Note:** You must have the git-version of Photobooth installed.

First, make sure there's no old installer available:
```
rm -f install-photobooth.sh
```

To update an existing Photobooth-Installation via git, run below commands in your terminal. A valid OS username must be passed to the installer:
```
wget https://raw.githubusercontent.com/PhotoboothProject/photobooth/dev/install-photobooth.sh
sudo bash install-photobooth.sh --update --username='<YourUsername>'
```

**Special note:**

If you have accessed Photobooth earlier from your Browser,
please clear your Browsers Cache once to avoid graphical glitches or being unable to save config changes using the Adminpanel.

**Troubleshooting**

In some cases, the v4l2loopback doesn't seem to be working after an update and breaking the preview from DSLR.

After a reboot run `v4l2-ctl --list-devices` from your terminal to see if everything is fine.

If it works you get the following output:

```
GPhoto2 Webcam (platform:v4l2loopback-000):
        /dev/video0
```

If it doesn't work:

```
Cannot open device /dev/video0, exiting
```

If it doesn't work, you might need to compile the v4l2loopback Module yourself by running the following commands:

```sh
curl -LO https://github.com/umlaeute/v4l2loopback/archive/refs/tags/v0.12.7.tar.gz
tar xzf v0.12.7.tar.gz && cd v4l2loopback-0.12.7
make && sudo make install
sudo depmod -a
sudo modprobe v4l2loopback exclusive_caps=1 card_label="GPhoto2 Webcam"
```

Now again check if everything is fine (`v4l2-ctl --list-devices`).

If you're still having trouble feel free to join us at Telegram to get further support.

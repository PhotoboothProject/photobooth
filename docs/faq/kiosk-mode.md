# Enable kiosk mode

Use the Photobooth Setup Wizard first: **7 Misc → 1 Autostart and shortcut**. If you need manual steps, use the OS-specific guidance below.

## Autostart on Pi OS Bookworm (Wayland/labwc by default)
Create `~/.config/labwc/autostart` (or add to the existing `[autostart]` section):

```
[autostart]
chromium --kiosk --disable-features=Translate --noerrdialogs --disable-infobars --no-first-run --ozone-platform=wayland --touch-events=enabled --start-maximized http://localhost
```

For Wayland/Wayfire, edit `~/.config/wayfire.ini`:

```
[autostart]
chromium = chromium-browser --kiosk --disable-features=Translate --noerrdialogs --disable-infobars --no-first-run --ozone-platform=wayland --touch-events=enabled --start-maximized http://localhost
```

## Autostart on Pi OS Bullseye and earlier
Create `/etc/xdg/autostart/photobooth.desktop`:

```
[Desktop Entry]
Version=1.3
Terminal=false
Type=Application
Name=Photobooth
Exec=chromium-browser --noerrdialogs --disable-infobars --disable-features=Translate --no-first-run --check-for-update-interval=31536000 --kiosk http://localhost --touch-events=enabled --use-gl=egl
Icon=/var/www/html/resources/img/favicon-96x96.png
StartupNotify=false
Terminal=false
```

Adjust the kiosk URL and icon path if Photobooth is in a subdirectory (e.g. `http://localhost/photobooth` and `/var/www/html/photobooth/resources/img/favicon-96x96.png`). The `--use-gl=egl` flag is primarily for Raspberry Pi; remove it if it causes issues on other hardware.

# Enable kiosk mode

Use the Photobooth Setup Wizard first: **7 Misc → 1 Autostart and shortcut**. If you need manual steps, use the OS-specific guidance below.

---

## Firefox

### Basic Kiosk Mode

```
firefox --kiosk http://localhost
```

**Notes**

* Launches Firefox fullscreen
* Hides UI elements and prevents normal window controls

---

## Chrome / Chromium

Use `chromium` or `google-chrome` depending on your distribution/installed package.

### Basic Kiosk Mode

```
chromium --kiosk http://localhost
```

---

### Recommended Kiosk Flags

```
chromium \
  --kiosk http://localhost \
  --noerrdialogs \
  --disable-infobars \
  --disable-features=Translate \
  --no-first-run \
  --check-for-update-interval=31536000 \
  --touch-events=enabled \
  --password-store=basic
```

**Flag Descriptions**

| Flag                                   | Purpose                               |
| -------------------------------------- | ------------------------------------- |
| `--kiosk`                              | Fullscreen kiosk mode                 |
| `--noerrdialogs`                       | Suppresses crash and error dialogs    |
| `--disable-infobars`                   | Hides automation and warning banners  |
| `--disable-features=Translate`         | Disables translation prompts          |
| `--no-first-run`                       | Skips first-run UI                    |
| `--check-for-update-interval=31536000` | Effectively disables update checks    |
| `--touch-events=enabled`               | Enables touchscreen input             |
| `--password-store=basic`               | Avoids dependency on desktop keyrings |

---

## Raspberry Pi–Specific Options

### Wayland (Recommended on Newer Raspberry Pi OS)

```
chromium \
  --kiosk http://localhost \
  --ozone-platform=wayland \
  --start-maximized
```

**Notes**

* `--ozone-platform=wayland` enables native Wayland support
* `--start-maximized` ensures correct initial sizing

---

### X11 (Legacy / Fallback)

```
chromium \
  --kiosk http://localhost \
  --use-gl=egl
```

**Notes**

* `--use-gl=egl` improves GPU acceleration on Raspberry Pi under X11

---

## Full Example (Raspberry Pi + Wayland)

```
chromium \
  --kiosk http://localhost \
  --noerrdialogs \
  --disable-infobars \
  --disable-features=Translate \
  --no-first-run \
  --check-for-update-interval=31536000 \
  --touch-events=enabled \
  --password-store=basic \
  --ozone-platform=wayland \
  --start-maximized
```

---

## Example `.desktop` File

The following example assumes:

* Application name: **Photobooth**
* The icon (`photobooth`) was installed by the SetupWizard
* Chromium is used as the kiosk browser

### `photobooth.desktop`

```
[Desktop Entry]
Version=1.3
Type=Application
Name=Photobooth
Comment=Photobooth Kiosk Application
Terminal=false
Exec=chromium --kiosk http://localhost \
  --noerrdialogs \
  --disable-infobars \
  --disable-features=Translate \
  --no-first-run \
  --check-for-update-interval=31536000 \
  --touch-events=enabled \
  --password-store=basic
Icon=photobooth
StartupNotify=false
Categories=Utility;
```

---

### Raspberry Pi Wayland Variant (`Exec` Line Only)

```
Exec=chromium --kiosk http://localhost \
  --noerrdialogs \
  --disable-infobars \
  --disable-features=Translate \
  --no-first-run \
  --check-for-update-interval=31536000 \
  --touch-events=enabled \
  --password-store=basic \
  --ozone-platform=wayland \
  --start-maximized
```

---

### Raspberry Pi X11 Variant (`Exec` Line Only)

```
Exec=chromium --kiosk http://localhost \
  --noerrdialogs \
  --disable-infobars \
  --disable-features=Translate \
  --no-first-run \
  --check-for-update-interval=31536000 \
  --touch-events=enabled \
  --password-store=basic \
  --use-gl=egl
```

---

## Installation Locations

**System-wide**

```
/usr/share/applications/photobooth.desktop
```

**Per-user**

```
~/.local/share/applications/photobooth.desktop
```

After installing or modifying the file:

```
update-desktop-database
```

## System-Wide Autostart with XDG

Using `/etc/xdg/autostart/photobooth.desktop` ensures the application launches automatically for all users when a graphical session starts (LXDE, XFCE, GNOME, etc.).

---

## Autostart on Raspberry Pi OS Bookworm (Wayland)

Raspberry Pi OS Bookworm uses **Wayland** by default. The two supported compositors documented here are **labwc** (default) and **Wayfire**.

---

### labwc (default)

Create `~/.config/labwc/autostart`
(or add to the existing `[autostart]` section) as follows:

```ini
[autostart]
chromium --kiosk http://localhost \
  --noerrdialogs \
  --disable-infobars \
  --disable-features=Translate \
  --no-first-run \
  --check-for-update-interval=31536000 \
  --touch-events=enabled \
  --password-store=basic \
  --ozone-platform=wayland \
  --start-maximized
```

---

### Wayfire

Edit `~/.config/wayfire.ini` and add or update the `[autostart]` section as follows:

```ini
[autostart]
chromium = chromium --kiosk http://localhost \
  --noerrdialogs \
  --disable-infobars \
  --disable-features=Translate \
  --no-first-run \
  --check-for-update-interval=31536000 \
  --touch-events=enabled \
  --password-store=basic \
  --ozone-platform=wayland \
  --start-maximized
```

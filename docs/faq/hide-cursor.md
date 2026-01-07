# Hide cursor, screen blanking and screen saver

> Note: Tools like `unclutter` do not work on Wayland.

## Pi OS Trixie (Wayland)

- Hide the cursor by renaming the pointer icon:
  ```
  sudo mv /usr/share/icons/PiXtrix/cursors/left_ptr /usr/share/icons/PiXtrix/cursors/left_ptr.bak
  ```
  Restore it later:
  ```
  sudo mv /usr/share/icons/PiXtrix/cursors/left_ptr.bak /usr/share/icons/PiXtrix/cursors/left_ptr
  ```

## Pi OS Bookworm (Wayland)
- Hide the cursor:
  ```
  sudo mv /usr/share/icons/PiXflat/cursors/left_ptr /usr/share/icons/PiXflat/cursors/left_ptr.bak
  ```

- Disable screen blanking:
  ```
  sudo raspi-config nonint do_blanking 1
  ```
  Re-enable:
  ```
  sudo raspi-config nonint do_blanking 0
  ```

- Turn off the screensaver by editing `/etc/xdg/lxsession/LXDE-pi/autostart` and removing `@xscreensaver -no-splash`.
- Prevent DPMS screen blanking by creating `/etc/X11/xorg.conf.d/99-dpms.conf`:
  ```
  Section "Monitor"
      Identifier "HDMI-1"
      Option "DPMS" "false"
  EndSection
  ```
  Restart LXDE to apply:
  ```
  lxsession-logout
  ```

## Pi OS Bullseye and earlier (X11)

- Disable screensaver and screen blanking with `sudo raspi-config`:
  - 1 System Options → S6 Boot / Auto Login → choose desktop auto-login.
  - 1 System Options → S6 Screen Blanking → disable.
- If needed, add to `/etc/xdg/lxsession/LXDE-pi/autostart`:
  ```
  @xset s off
  @xset -dpms
  @xset s noblank
  @unclutter -idle 0
  ```

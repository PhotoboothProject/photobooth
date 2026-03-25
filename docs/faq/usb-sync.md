# USB Sync & Move2USB

Photobooth offers two ways to get your photos and videos onto a USB stick:

| Feature | Purpose | Trigger |
|---------|---------|---------|
| **USB Sync** | Automatic background sync at a regular interval | Runs continuously while Photobooth is open |
| **Move2USB** | One-shot copy **or** move of all media | Manual — HTTP endpoint, socket.io command, or hardware button |

Both features share the same USB device identifier (Adminpanel → **USB Sync → USB device identifier**) and the same Linux mount permissions.

---

## Prerequisites

- **Linux only** — Windows is not supported.
- **rsync** must be installed (`sudo apt install rsync`).
- A **USB stick** formatted as **FAT32** or **exFAT** (recommended for cross-platform compatibility). The volume label should match the identifier configured in the admin panel (default: `photobooth`).
- Mount permissions for the `www-data` user (see [Permissions setup](#permissions-setup) below).

---

## Permissions setup

The web-server user (`www-data`) needs permission to mount and unmount USB drives.
Photobooth ships two complementary mechanisms that are installed via the **Photobooth Setup Wizard**:

1. **Polkit rule** — allows `www-data` to call `udisksctl mount` / `udisksctl unmount` without a password.
2. **sudoers rule** (`/etc/sudoers.d/021_www-data-usb-sync`) — passwordless `sudo mount`, `sudo umount` and `sudo mkdir -p /media/*` as fallback when udisksctl is unavailable.

### Installing via the Setup Wizard

Download and run the Setup Wizard:

```
wget -O install-photobooth.sh https://raw.githubusercontent.com/PhotoboothProject/photobooth/dev/install-photobooth.sh
sudo bash install-photobooth.sh
```

Then choose:

- **6 Permissions → 4 USB Sync policy**

The wizard will:

- Install the Polkit rule for udisksctl.
- Install the sudoers rule for mount/umount/mkdir.
- Try to disable desktop auto-mount (pcmanfm volume handling) to avoid permission conflicts.
- Remove any legacy `020_www-data-usb` sudoers file.

Run the same menu entry again after OS upgrades to re-apply the rules.

### Removing permissions

Run **6 Permissions → 4 USB Sync policy** again and answer **No** when asked to set up the policy. The wizard removes Polkit rules, sudoers rules, and the legacy files.

---

## USB Sync (automatic)

USB Sync runs as a background Node.js process (`sync-to-drive.js`) that is started automatically when Photobooth is opened in the browser (provided the feature is enabled).

### How it works

1. The process looks for a USB device matching the configured identifier (label, device path, or device name).
2. It mounts the device if it is not already mounted:
   - First via `udisksctl mount`.
   - Fallback: `sudo mount` to `/media/<label>` with automatic FAT32/exFAT uid/gid options.
3. It runs `rsync` to incrementally copy new `jpg`, `gif`, `mp4` files from the Photobooth data directory to the USB stick.
4. Deleted images are backed up into a `deleted/` folder on the USB stick (rsync `--backup-dir`).
5. After sync completes, the process sleeps for the configured interval and starts over.

### Device detection

The device is matched (case-insensitive) by:

- **Volume label** (recommended) — e.g. `photobooth`
- **Block device path** — e.g. `/dev/sda1`
- **Device short name** — e.g. `sda1`

If `lsblk` does not report the label (e.g. in LXC containers or after a USB reconnect), Photobooth falls back to `blkid` which reads the label directly from the block device.

### Configuration

Open the Admin Panel → **USB Sync**:

| Setting | Description |
|---------|-------------|
| **Enable** | Turn automatic USB Sync on or off. |
| **Automated syncing interval** | Seconds between sync attempts (default: 300). |
| **USB device identifier** | The value used to find the USB stick — typically the volume label (default: `photobooth`). |

### File types synced

`jpg`, `gif`, `mp4` (and the internal `chk` verification files).

---

## Move2USB (manual copy / move)

Move2USB lets you copy — or move — **all** current photos and videos to the USB stick with a single action.

### Modes

| Mode | Behaviour |
|------|-----------|
| **copy** | Copies all media to the USB stick. Originals remain on the Photobooth. |
| **move** | Copies all media to the USB stick **and** deletes the originals plus the Photobooth image database after a successful, verified sync. |

### Enabling Move2USB

1. Adminpanel → **Hardware Button → Enable Hardware Buttons** must be active.
2. Adminpanel → **Hardware Button → Move2USB mode** — choose `copy` or `move` (set to `disabled` to hide the feature).
3. The **USB device identifier** under USB Sync must point to a valid USB stick.

### Triggering Move2USB

- **HTTP endpoint:** `http://<IP>:<Port>/commands/start-move2usb`
- **Socket.io:** emit `photobooth-socket` → `move2usb`
- **Hardware button:** assign a key code to the Move2USB action (same input-device mechanism as picture/collage/print).

### Copy verification

Move2USB creates a `copy.chk` marker file in the Photobooth data folder before rsync and expects it to appear on the USB stick after sync. If the marker is missing on the USB stick after rsync, the sync is treated as **failed** and no files are deleted (even in `move` mode).

### What gets deleted in "move" mode

Only after a verified successful sync:

1. All `jpg`, `gif`, `mp4` files in the Photobooth data directory.
2. The Photobooth image database file (`<dbname>.txt`).

---

## Mount strategy (both features)

Photobooth uses a layered approach to mount USB drives:

1. **udisksctl** — preferred, works with the Polkit rule.
2. **findmnt check** — if udisksctl output was ambiguous, verify via `findmnt`.
3. **sudo mount** — fallback using the sudoers rule; mounts to `/media/<label>`.

For **FAT32** (`vfat`) and **exFAT** volumes the mount options `umask=0000,uid=<www-data>,gid=<www-data>` are set automatically so the web-server process can write to the stick.

If a drive is already mounted but **not writable** (e.g. auto-mounted by the desktop with different uid/gid), Photobooth unmounts and re-mounts it with the correct options.

Unmounting follows the same order: udisksctl first, `sudo umount` as fallback.

---

## Preparing a USB stick

1. Format the stick as **FAT32** (for sticks ≤ 32 GB) or **exFAT** (for larger sticks).
2. Set the volume label to `photobooth` (or whatever you configure as the USB device identifier).
   - Linux: `sudo fatlabel /dev/sdX1 photobooth` (FAT32) or `sudo exfatlabel /dev/sdX1 photobooth` (exFAT).
   - Windows: right-click the drive → Rename.
   - macOS: Disk Utility → rename the volume.
3. Plug the stick into the Photobooth host.

---

## Troubleshooting

### USB stick not detected

- Verify the label: `lsblk -o NAME,LABEL,FSTYPE,MOUNTPOINT` — the label must match the configured identifier (case-insensitive).
- If `lsblk` shows no label, try `blkid` — Photobooth uses this as a fallback.
- Confirm the device is a **USB** device (`lsblk -o NAME,SUBSYSTEMS` should contain `usb`).

### Permission errors / mount failures

- Re-run the Setup Wizard → **Permissions → USB Sync policy**.
- Check that Polkit and sudoers are in place:
  - `ls -l /etc/polkit-1/localauthority/50-local.d/photobooth.pkla` or `/etc/polkit-1/rules.d/photobooth.rules`
  - `ls -l /etc/sudoers.d/021_www-data-usb-sync`
- Disable desktop auto-mount (e.g. pcmanfm volume handling) — it can race with Photobooth and mount with the wrong uid.

### Sync not running / nothing copied

- Is USB Sync enabled in the Admin Panel?
- Check the server logs at the Debug panel: [http://localhost/admin/debugpanel](http://localhost/admin/debugpanel).
- Look for `[synctodrive]` log lines — they show device detection, mount status, and rsync output.

### Move2USB fails or does not delete files

- Check Debug panel for `[remotebuzzer]` log lines mentioning `move2usb`.
- If the log says "sync verification failed (copy.chk missing)", the rsync did not complete successfully — check USB stick space and permissions.
- Files are only deleted in `move` mode **and** only after verified sync.

### FAT32 / exFAT write issues

- Ensure the stick is not write-protected (physical switch on some USB sticks).
- Re-format the stick and re-label it.
- If mounted by another process, Photobooth will unmount and re-mount with proper options — check the log for "not writable, unmounting" messages.

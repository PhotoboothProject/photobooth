# Auto copy zu USB NTFS formatiert

Auto copy script to copy images and temp files to an USB drive formatted with NTFS when plugged in.
This script uses `rsync` to copy files from the Photobooth data folder to the USB drive when it is plugged in. It also creates a status file that can be used to monitor the copy progress via a web
page.
The script is triggered via a udev rule when an USB drive with NTFS filesystem is plugged in.

## Requires:

- python3
- rsync
- ntfs-3g
- udev

## Installation

`sudo touch /usr/local/bin/fotobox-copy.sh`
`sudo chmod +x /usr/local/bin/fotobox-copy.sh`
`sudo nano /usr/local/bin/fotobox-copy.sh`

```
#!/usr/bin/env bash
set -Eeuo pipefail

# Configuration

PART="${1}"                   # e.g. sdb1
DEV="/dev/${PART}"
SRC="/var/www/html/data"
SUB1="images"
SUB2="tmp"
STATUS="/var/www/html/private/copystatus.json"

# ---------- Status helper ----------

write_status() {
local state="$1" pct="$2" msg="$3"
local tmp="${STATUS}.tmp"

# Create JSON safely

printf '{"state":"%s","percent":%s,"message":%s}\n' \
"$state" "$pct" "$(python3 -c "import json; print(json.dumps('$msg'))")" | sudo tee "$tmp" >/dev/null

sudo mv "$tmp" "$STATUS"
}

fail() {
local code="$1"
local line="$2"
write_status "error" 0 "Error (code ${code}) in line ${line}."
exit "$code"
}

trap 'fail $? $LINENO' ERR

# ---------- Start ----------

write_status "starting" 0 "USB detected: ${DEV}. Prepare..."

# Check if device exists

if [[ ! -b "$DEV" ]]; then
write_status "error" 0 "Device ${DEV} not found."
exit 1
fi

# Mount device if not already mounted

MNT="$(lsblk -no MOUNTPOINT "$DEV" | head -n1 || true)"
if [[ -z "${MNT}" ]]; then
sudo mkdir -p /mnt/usbdrive

# mount mit 'flush' Option für exFAT hilft, Puffer schneller zu leeren

sudo mount -o flush "$DEV" /mnt/usbdrive
MNT="/mnt/usbdrive"
fi

DEST="${MNT}/images"
sudo mkdir -p "$DEST"

# Existenz der Quellordner prüfen

[[ -d "${SRC}/${SUB1}" ]] || { write_status "error" 0 "Source ${SUB1} missing"; exit 2; }
[[ -d "${SRC}/${SUB2}" ]] || { write_status "error" 0 "Source ${SUB2} missing"; exit 2; }

# ---------- Prepare Rsync ----------

# --no-inc-recursive: No jumping progress percentage
# --info=progress2: Compact progress output

RSYNC_OPTS=(
-rltD
--info=progress2
--no-inc-recursive
--no-owner --no-group --no-perms
--no-acls --no-xattrs
--omit-dir-times
--modify-window=2
)

write_status "copying" 1 "Calculate copy size..."

# Run rdync in dry-run to get total size

# stdbuf & tr to handle progress output line by line

set +e
sudo stdbuf -oL rsync "${RSYNC_OPTS[@]}" \
"${SRC}/${SUB1}" "${SRC}/${SUB2}" \
"$DEST/" 2>&1 | tr '\r' '\n' | while IFS= read -r line; do

    if [[ "$line" =~ ([0-9]{1,3})% ]]; then
      p="${BASH_REMATCH[1]}"

      # Scale to 95% to leave room for final sync step
      # Prevents user from thinking it hung at 100%
      display_p=$(( p * 95 / 100 ))

      # Only update every percent to reduce writes
      write_status "copying" "$display_p" "Copied ${p}%..."
    fi

done
RSYNC_EXIT=${PIPESTATUS[0]}
set -e

if [ "$RSYNC_EXIT" -ne 0 ] && [ "$RSYNC_EXIT" -ne 24 ]; then
fail "$RSYNC_EXIT" "rsync failed"
fi

# ---------- Final sync ----------

write_status "syncing" 96 "Finalise copy..."

# Sync to ensure all data is written to stick to avoid corruption

sync

write_status "done" 100 "Done. You can remove the USB drive now."

# Unmount only if we mounted it

if [[ "${MNT}" == "/mnt/usbdrive" ]]; then

# Give some time to ensure all writes are done

sleep 1
sudo umount /mnt/usbdrive || sudo umount -l /mnt/usbdrive
fi

exit 0

```

Setup udev rule

```
echo 'ACTION=="add", SUBSYSTEM=="block", ENV{DEVTYPE}=="partition", ENV{ID_FS_TYPE}=="ntfs", RUN+="/usr/local/bin/fotobox-copy.sh %k"' | sudo tee /etc/udev/rules.d/99-fotobox-autocopy.rules

sudo udevadm control --reload-rules
sudo udevadm trigger
```

Create status file and set permissions

```
sudo touch /var/www/html/private/copystatus.json
sudo chown root:www-data /var/www/html/private/copystatus.json
sudo chmod 664 /var/www/html/private/copystatus.json
sudo chown -R www-data:www-data /var/www/html/private
```

Create status page to monitor progress in folder `private`
`sudo -u www-data nano /var/www/html/private/status.php`

```
<?php
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kopiervorgang…</title>
</head>
<body style="
  background-image: url('/private/images/background/Copy_Background.jpg');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  margin: 0;
  padding: 0;
  overflow-x:hidden;
  overflow-y:hidden;">

<div id="wrap" style="min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px;">
  <div style="background:#fff; padding:20px; border-radius:12px; width:min(520px,90vw); box-shadow:0 10px 30px rgba(0,0,0,.3);">
    <div id="copyMsg" style="margin-bottom:10px;">Warte auf Status…</div>
    <div style="height:18px; background:#eee; border-radius:10px; overflow:hidden;">
      <div id="copyBar" style="height:100%; width:0%; background:#3b82f6;"></div>
    </div>
    <div id="copyPct" style="margin-top:8px; font-size:14px; opacity:.8;">0%</div>
  </div>
</div>

<script>
(async function(){
  const bar = document.getElementById('copyBar');
  const msg = document.getElementById('copyMsg');
  const pct = document.getElementById('copyPct');

  // Passe ggf. den Pfad an:
  const STATUS_URL = '/private/copystatus.json';
  const BACK_URL   = '../index.php';

  async function poll(){
    try {
      const r = await fetch(STATUS_URL, { cache: 'no-store' });
      if(!r.ok) throw new Error('Status page not reachable: ' + r.status);
      const s = await r.json();

      const state = (s.state || '').toLowerCase();
      const p = Math.max(0, Math.min(100, Number(s.percent ?? 0)));

      bar.style.width = p + '%';
      msg.textContent = s.message || (state ? ('Status: ' + state) : 'Kopiere ');
      pct.textContent = p + '%';

      if (state === 'done') {
        bar.style.width = '100%';
        msg.textContent = s.message || 'Fertig.';
        pct.textContent = '100%';
        setTimeout(() => window.location.href = BACK_URL, 3000);
        return;
      }

      // Optional: handle error state
      if (state === 'error') {
        msg.textContent = s.message || 'Copy error.';
        return;
      }

    } catch(e) {
      msg.textContent = 'No status available.';
      // do not jump to status page, it will flicker otherwise
    } finally {
      setTimeout(poll, 400);
    }
  }

  poll();
})();
</script>
</body>
</html>
```

Create auto-refresh script to redirect to status page when copy is running
`sudo nano /var/www/html/private/copyrun.js`

```
(async function(){
  const STATUS_URL  = '/private/copystatus.json';
  const STATUS_PAGE = '/private/status.php';

  async function check(){
    try {
      const r = await fetch(STATUS_URL + '?t=' + Date.now(), { cache: 'no-store' });
      if (!r.ok) throw new Error('no status');
      const s = await r.json();
      const state = (s.state || '').toLowerCase();

      if (state === 'starting' || state === 'copying') {
        // >>> HIER passiert der Sprung <<<
        window.location.href = STATUS_PAGE;
        return;
      }
    } catch(e) {
      // no status file or error - do nothing
    }
    setTimeout(check, 600);
  }

  check();
})();
```

# Remote Buttons & Triggers

Everything about triggering Photobooth via hardware buttons or remote calls.

## 1) Local browser hotkeys (simple HID to browser)

- Connect the HID to the device running the browser.
- Adminpanel → set per-action key codes: Picture/Collage/Print/Video/Custom → “Key code which triggers …”.
- Browser window must be focused; Photobooth listens for JavaScript `keyup` keyCode.

## 2) Direct USB HID on the Photobooth host (no browser focus)

The remotebuzzer server reads a Linux input device and maps `EV_KEY` codes to your action key codes.

How it works

- Remotebuzzer opens `/dev/input/...` (e.g. `/dev/input/by-id/...-event-kbd`).
- Incoming key codes are matched against `picture.key`, `collage.key`, `print.key`, `video.key`, `custom.key`.
- On match, the corresponding action is triggered for all connected clients.

Requirements

- Remotebuzzer server enabled (Adminpanel → Hardware Button → Start remote buzzer Server).
- Hardware Buttons enabled and the target action enabled.
- HID device visible under `/dev/input/...` and readable by the remotebuzzer process.

Configure

1) Find device: `ls -l /dev/input/by-id` → choose the `-event-kbd` (or your HID).
2) Set `Hardware Button → Input device` to that path.
3) Set per-action key codes (Linux key codes, e.g. `sudo showkey -k` → 108).
4) Save and restart the remotebuzzer server.

Permissions

- Many devices are `root:input` with `crw-r-----`. Give the Photobooth user read access:
  - add user to `input` group (`sudo usermod -a -G input www-data` + restart), or
  - udev rule to set group/mode for this device
  - find the device under `/dev/input/by-id` (e.g. `ls -l /dev/input/by-id | grep event`) and pick the matching `-event-kbd` (keyboard-like HID) symlink

udev rule example

- Create `/etc/udev/rules.d/99-photobooth-input.rules` (adjust as needed):
  - General rule (applies to all input event nodes):
    - `KERNEL=="event*", SUBSYSTEM=="input", MODE="660", GROUP="input"`

- Apply: `sudo udevadm control --reload && sudo udevadm trigger`
- Ensure your Photobooth user is in the chosen group (e.g. `input`) or change GROUP to your service user.

Test

- Remotebuzzer log: `Listening for USB input on [...] keycodes [...]`.
- Press button → log `triggering action [picture]` → workflow starts.

Notes

- Remotebuzzer must be running and trigger must be armed (not already taking a picture).
- Uses key **press** (value 1); key repeat will also trigger.

## 3) Remote trigger via Socket.io

- Channel: `photobooth-socket`
- Commands: `start-picture`, `start-collage`, `collage-next`, `start-custom`, `start-video`, `print`, `rotary-cw`, `rotary-ccw`, `rotary-btn-press`, `move2usb`
- Response: `completed` after the workflow finishes.

## 4) Remote trigger via simple web requests (HTTP)

- Depends on the socket.io implementation; requires Hardware Button → Enable Hardware Buttons and Hardware Button → Remote buzzer Server IP to be set.
- Photobooth can start the Remotebuzzer server itself (enable Hardware Button → Start remote buzzer Server in Adminpanel).
- Enable Hardware Buttons and set Remotebuzzer IP/Port.
- Base URL: `http://[Server IP]:[Port]`
- Available endpoints (depending on enabled features and buttons):
  - `[Base URL]/` Simple help page with all available endpoints
  - `[Base URL]/commands/start-picture`
  - `[Base URL]/commands/start-collage`
  - `[Base URL]/commands/start-custom`
  - `[Base URL]/commands/start-print`
  - `[Base URL]/commands/start-video`
  - `[Base URL]/commands/start-move2usb`
  - `[Base URL]/commands/reboot-now`
  - `[Base URL]/commands/shutdown-now`
  - `[Base URL]/commands/rotary-cw`
  - `[Base URL]/commands/rotary-ccw`
  - `[Base URL]/commands/rotary-btn-press`
- Example: `curl http://<IP>:<PORT>/commands/start-picture`
- IP/Port notes:
  - `[Hardware Button Server IP]` must match Hardware Button → Remote buzzer Server IP (typically the Photobooth host IP).
  - `[Hardware Button Server Port]` is the Hardware Button → Enable Hardware Buttons value.
- Common hardware for HTTP triggers: [myStrom WiFi Button](https://mystrom.com/wifi-button/), [Shelly Button](https://shelly.cloud/products/shelly-button-1-smart-home-automation-device/), ESP32/ESP8266, Raspberry Pi Pico/Pico W (see examples below).

## 5) Hardware button examples (HTTP)

- myStrom WiFi Button:
    - `curl --location -g --request POST http://[Button IP]/api/v1/action/single --data-raw get://[Photobooth IP]:[Port]/commands/start-picture`
    - `curl --location -g --request POST http://[Button IP]/api/v1/action/long   --data-raw get://[Photobooth IP]:[Port]/commands/start-collage`
- ESP32/ESP8266: [github.com/PhotoboothProject/photobooth-ino](https://github.com/PhotoboothProject/photobooth-ino)
- Raspberry Pi Pico / Pico W:
  - [github.com/frogro/PhotoboothProject_Pico_as_HID_Button_and_rotary_encoder](https://github.com/frogro/PhotoboothProject_Pico_as_HID_Button_and_rotary_encoder)
  - [github.com/frogro/PhotoboothProject_Pico_W_as_remote_button_and_rotary_encoder](https://github.com/frogro/PhotoboothProject_Pico_W_as_remote_button_and_rotary_encoder)

## Troubleshooting

- Remotebuzzer running and reachable; avoid 127.0.0.1 for remote clients—use the host IP.
- Trigger affects all connected clients simultaneously.
- Increase Photobooth loglevel (Adminpanel → General) and check Debugpanel; for HTTP/web triggers also check the browser console/network tab.
- Fallback re-arms after a timeout if no `completed` arrives (fixed 60s; not configurable via UI).
- Avoid loopback for cross-device use; use host IP/hostname. Every trigger hits all connected clients at once—plan accordingly.

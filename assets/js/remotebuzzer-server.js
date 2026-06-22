const fs = require('fs');
const path = require('path');

/* VARIABLES */
// eslint-disable-next-line no-unused-vars
let collageInProgress = false,
    triggerArmed = true,
    copySuccess = false,
    rearmTimer = null;

// Sync destination: photos are synced directly to the USB stick root (no subdirectory)
const { execSync, spawnSync } = require('child_process');
const { pid: PID, platform: PLATFORM } = process;
const REARM_TIMEOUT_MS = 60000; // Fallback to re-arm trigger if no completion arrives
const shellQuote = (s) => '\'' + String(s).replace(/'/g, '\'\\\'\'') + '\'';

/* LOGGING FUNCTION */
const log = function (...optionalParams) {
    const currentDate = new Date();
    const formattedDate = currentDate.toISOString().replace(/T/, ' ').replace(/\..+/, '');
    console.log('[' + formattedDate + `][remotebuzzer][DEBUG] ${PID}:`, ...optionalParams);
};

/* SOURCE PHOTOBOOTH CONFIG */
/*const {execSync} = require('child_process');*/
const cmdConfig = 'bin/photobooth photobooth:config:list json';
const stdoutConfig = execSync(cmdConfig).toString();
const config = JSON.parse(stdoutConfig);

const cmdEnvironmentg = 'bin/photobooth photobooth:environment:list json';
const stdoutEnvironment = execSync(cmdEnvironmentg).toString();
const environment = JSON.parse(stdoutEnvironment);

/* USB INPUT LISTENER CONFIG */
const inputDevicePath = config.remotebuzzer.input_device;

/* WRITE PROCESS PID FILE */
const writePIDFile = (filename) => {
    try {
        fs.writeFileSync(filename, parseInt(PID, 10).toString(), { flag: 'w' });
        log(`PID file created [${filename}]`);
    } catch (err) {
        throw new Error(`Unable to write PID file [${filename}] - ${err.message}`, { cause: err });
    }
};

const pidFilename = path.join(environment.absoluteFolders.var, 'run/remotebuzzer.pid');
writePIDFile(pidFilename);

/* HANDLE EXCEPTIONS */
process.on('uncaughtException', function (err) {
    log('Error: ', err.message);
    fs.unlink(pidFilename, function (error) {
        if (error) {
            log('Error deleting PID file ', error.message);
        }
    });
    log('Exiting');

    /* got to exit now and here - can not recover from error */
    process.exit();
});

/* START HTTP & WEBSOCKET SERVER */
const baseUrl = 'http://' + config.remotebuzzer.serverip + ':' + config.remotebuzzer.port;
log('Server starting on ' + baseUrl);

function triggerPictureFromInput() {
    if (!config.remotebuzzer.usebuttons || !config.remotebuzzer.picturebutton) {
        log('USB input ignored, hardware buttons or picture button disabled in config');

        return;
    }

    if (!triggerArmed) {
        log('USB input ignored, trigger not armed');

        return;
    }

    if (!config.picture.enabled) {
        log('USB input ignored, taking pictures disabled');

        return;
    }

    photoboothAction('picture');
}

function triggerActionFromInput(action) {
    switch (action) {
        case 'picture':
            triggerPictureFromInput();
            break;
        case 'collage':
            if (!config.remotebuzzer.usebuttons || !config.remotebuzzer.collagebutton) {
                log('USB input ignored, hardware buttons or collage button disabled in config');
            } else if (!triggerArmed) {
                log('USB input ignored, trigger not armed');
            } else if (!config.collage.enabled) {
                log('USB input ignored, collage disabled');
            } else {
                photoboothAction('collage');
            }
            break;
        case 'custom':
            if (!config.remotebuzzer.usebuttons || !config.remotebuzzer.custombutton) {
                log('USB input ignored, hardware buttons or custom button disabled in config');
            } else if (!triggerArmed) {
                log('USB input ignored, trigger not armed');
            } else if (!config.custom.enabled) {
                log('USB input ignored, custom action disabled');
            } else {
                photoboothAction('custom');
            }
            break;
        case 'video':
            if (!config.remotebuzzer.usebuttons || !config.remotebuzzer.videobutton) {
                log('USB input ignored, hardware buttons or video button disabled in config');
            } else if (!triggerArmed) {
                log('USB input ignored, trigger not armed');
            } else if (!config.video.enabled) {
                log('USB input ignored, video disabled');
            } else {
                photoboothAction('video');
            }
            break;
        case 'print':
            if (!config.remotebuzzer.usebuttons || !config.remotebuzzer.printbutton) {
                log('USB input ignored, hardware buttons or print button disabled in config');
            } else if (!triggerArmed) {
                log('USB input ignored, trigger not armed');
            } else {
                photoboothAction('print');
            }
            break;
        default:
            log(`USB input ignored, unsupported action [${action}]`);
    }
}

function startUsbInputListener() {
    const bindings = [];

    const keyToInt = (key) => (key ? parseInt(key, 10) : 0);

    const pushBinding = (code, action) => {
        if (code && !Number.isNaN(code)) {
            bindings.push({ code, action });
        }
    };

    pushBinding(keyToInt(config.picture.key), 'picture');
    pushBinding(keyToInt(config.collage.key), 'collage');
    pushBinding(keyToInt(config.custom.key), 'custom');
    pushBinding(keyToInt(config.video.key), 'video');
    pushBinding(keyToInt(config.print.key), 'print');

    if (!inputDevicePath || bindings.length === 0) {
        return;
    }

    const EVENT_SIZE = 24; // timeval (16 bytes) + type (2) + code (2) + value (4)
    const stream = fs.createReadStream(inputDevicePath, { highWaterMark: EVENT_SIZE * 8 });

    stream.on('data', (chunk) => {
        for (let offset = 0; offset + EVENT_SIZE <= chunk.length; offset += EVENT_SIZE) {
            const type = chunk.readUInt16LE(offset + 16);
            const code = chunk.readUInt16LE(offset + 18);
            const value = chunk.readInt32LE(offset + 20);

            // EV_KEY press events use type 1, value 1 for key down, 0 for up
            if (type === 1 && value === 1) {
                const binding = bindings.find((b) => b.code === code);
                if (binding) {
                    log(`USB input matched keycode ${code}, triggering action [${binding.action}]`);
                    triggerActionFromInput(binding.action);
                }
            }
        }
    });

    stream.on('error', (err) => {
        log(`USB input listener error on [${inputDevicePath}]: ${err.message}`);
    });

    const codes = bindings.map((b) => b.code).join(', ');
    log(`Listening for USB input on [${inputDevicePath}] keycodes [${codes}]`);
}

if (inputDevicePath) {
    startUsbInputListener();
}

function armTrigger() {
    triggerArmed = true;
    if (rearmTimer) {
        clearTimeout(rearmTimer);
        rearmTimer = null;
    }
}

function disarmTrigger() {
    triggerArmed = false;
    if (rearmTimer) {
        clearTimeout(rearmTimer);
    }
    rearmTimer = setTimeout(() => {
        triggerArmed = true;
        log(`Re-arming trigger after timeout (${REARM_TIMEOUT_MS}ms)`);
        rearmTimer = null;
    }, REARM_TIMEOUT_MS);
}

function photoboothAction(type) {
    switch (type) {
        case 'picture':
            disarmTrigger();
            collageInProgress = false;
            log('Photobooth trigger PICTURE : [ photobooth-socket ] => [ All Clients ]: command [ picture ]');
            ioServer.emit('photobooth-socket', 'start-picture');
            break;

        case 'custom':
            disarmTrigger();
            collageInProgress = false;
            log('Photobooth trigger CUSTOM : [ photobooth-socket ]  => [ All Clients ]: command [ custom ]');
            ioServer.emit('photobooth-socket', 'start-custom');
            break;

        case 'video':
            disarmTrigger();
            collageInProgress = false;
            log('Photobooth trigger VIDEO : [ photobooth-socket ]  => [ All Clients ]: command [ video ]');
            ioServer.emit('photobooth-socket', 'start-video');
            break;

        case 'move2usb':
            disarmTrigger();
            collageInProgress = false;
            log('Photobooth trigger MOVE2USB : [ photobooth-socket ]  => [ All Clients ]: command [ move2usb ]');
            move2usbAction();
            break;

        case 'collage':
            disarmTrigger();
            collageInProgress = true;
            log('Photobooth trigger COLLAGE : [ photobooth-socket ]  => [ All Clients ]: command [ collage ]');
            ioServer.emit('photobooth-socket', 'start-collage');
            break;

        case 'collage-next':
            log('Photobooth COLLAGE : [ photobooth-socket ]  => [ All Clients ]: command [ collage-next ]');
            ioServer.emit('photobooth-socket', 'collage-next');
            break;

        case 'completed':
            armTrigger();
            collageInProgress = false;
            log('Photobooth activity completed : [ photobooth-socket ] => [ All Clients ]: command [ completed ]');
            ioServer.emit('photobooth-socket', 'completed');
            break;

        case 'print':
            disarmTrigger();
            log('Photobooth trigger PRINT : [ photobooth-socket ]  => [ All Clients ]: command [ print ]');
            ioServer.emit('photobooth-socket', 'print');
            break;

        case 'rotary-cw':
            ioServer.emit('photobooth-socket', 'rotary-cw');
            break;

        case 'rotary-ccw':
            ioServer.emit('photobooth-socket', 'rotary-ccw');
            break;

        case 'rotary-btn-press':
            ioServer.emit('photobooth-socket', 'rotary-btn-press');
            break;

        case 'reset':
            photoboothAction('completed');
            break;

        default:
            log('Photobooth action [', type, '] not implemented - ignoring');
            break;
    }
}

/* CONFIGURE HTTP ENDPOINTS */
const requestListener = function (req, res) {
    function sendText(content, contentType) {
        res.setHeader('Content-Type', contentType || 'text/plain');
        res.setHeader('Access-Control-Allow-Origin', '*');
        res.writeHead(200);
        res.end(content);
    }

    const urlObj = new URL(req.url, 'http://' + config.webserver.ip);
    const queryParams = urlObj.searchParams;

    switch (urlObj.pathname) {
        case '/':
            log('http: GET /');
            sendText(
                `<h1>Trigger Endpoints</h1>
            <ul>
                <li>Trigger photo: <a href="${baseUrl}/commands/start-picture" target="_blank">${baseUrl}/commands/start-picture</a></li>
                <li>Trigger collage: <a href="${baseUrl}/commands/start-collage" target="_blank">${baseUrl}/commands/start-collage</a></li>
                <li>Trigger custom: <a href="${baseUrl}/commands/start-custom" target="_blank">${baseUrl}/commands/start-custom</a></li>
                <li>Trigger print: <a href="${baseUrl}/commands/start-print" target="_blank">${baseUrl}/commands/start-print</a></li>
                <li>Trigger video: <a href="${baseUrl}/commands/start-video" target="_blank">${baseUrl}/commands/start-video</a></li>
                <li>Trigger picture move to USB: <a href="${baseUrl}/commands/start-move2usb" target="_blank">${baseUrl}/commands/start-move2usb</a></li>
                <li>Increase the printlimit by i <a href="${baseUrl}/commands/increase-print-limit?i=1" target="_blank">${baseUrl}/commands/increase-print-limit?i=1</a></li>
            </ul>
            <h1>Rotary Endpoints</h1>
            <ul>
                <li>Focus next: <a href="${baseUrl}/commands/rotary-cw" target="_blank">${baseUrl}/commands/rotary-cw</a></li>
                <li>Focus previous: <a href="${baseUrl}/commands/rotary-ccw" target="_blank">${baseUrl}/commands/rotary-ccw</a></li>
                <li>Click: <a href="${baseUrl}/commands/rotary-btn-press" target="_blank">${baseUrl}/commands/rotary-btn-press</a></li>
            </ul>
            <h1>Power</h1>
            <ul>
                <li>Shutdwon now: <a href="${baseUrl}/commands/shutdown-now" target="_blank">${baseUrl}/commands/shutdown-now</a></li>
                <li>Reboot now: <a href="${baseUrl}/commands/reboot-now" target="_blank">${baseUrl}/commands/reboot-now</a></li>
            </ul>`,
                'text/html'
            );
            break;
        case '/commands/increase-print-limit':
            log('http: GET /commands/increase-print-limit');
            if (config.remotebuzzer.usebuttons) {
                if (config.print.from_result || config.print.from_gallery) {
                    let i = 1;
                    let j = parseInt(queryParams.get('i'), 10);
                    if (j) {
                        i = j;
                    }
                    http.get(config.webserver.url + 'api/printLimit.php?increaseCount=' + i);
                    sendText(`Increased print limit by ${i}`);
                } else {
                    sendText('Please enable print from results screen or print from gallery.');
                }
            } else {
                sendText('Please enable Hardware Button support!');
            }
            break;

        case '/commands/start-picture':
            log('http: GET /commands/start-picture');
            if (config.remotebuzzer.usebuttons && config.remotebuzzer.picturebutton) {
                if (triggerArmed) {
                    if (config.picture.enabled) {
                        photoboothAction('picture');
                        sendText('TAKE PHOTO TRIGGERED.');
                    } else {
                        sendText('PHOTO DISABLED.');
                    }
                } else {
                    sendText('ALREADY TRIGGERED AN ACTION');
                }
            } else {
                sendText('Please enable Hardware Button support and Picture Button!');
            }
            break;
        case '/commands/start-collage':
            log('http: GET /commands/start-collage');
            if (config.remotebuzzer.usebuttons && config.remotebuzzer.collagebutton) {
                if (triggerArmed) {
                    if (config.collage.enabled) {
                        photoboothAction('collage');
                        sendText('TAKE COLLAGE TRIGGERED');
                    } else {
                        sendText('COLLAGE DISABLED.');
                    }
                } else {
                    sendText('ALREADY TRIGGERED AN ACTION');
                }
            } else {
                sendText('Please enable Hardware Button support and Collage Button!');
            }
            break;
        case '/commands/start-custom':
            log('http: GET /commands/start-custom');
            if (config.remotebuzzer.usebuttons && config.remotebuzzer.custombutton) {
                if (triggerArmed) {
                    if (config.custom.enabled) {
                        photoboothAction('custom');
                        sendText('TAKE CUSTOM TRIGGERED');
                    } else {
                        sendText('CUSTOM DISABLED.');
                    }
                } else {
                    sendText('ALREADY TRIGGERED AN ACTION');
                }
            } else {
                sendText('Please enable Hardware Button support and Custom Button!');
            }
            break;
        case '/commands/start-print':
            log('http: GET /commands/start-print');
            if (config.remotebuzzer.usebuttons && config.remotebuzzer.printbutton) {
                if (triggerArmed) {
                    photoboothAction('print');
                    sendText('PRINT TRIGGERED');
                } else {
                    sendText('ALREADY TRIGGERED AN ACTION');
                }
            } else {
                sendText('Please enable Hardware Button support and Print Button!');
            }
            break;
        case '/commands/start-move2usb':
            log('http: GET /commands/start-move2usb');
            if (config.remotebuzzer.usebuttons && config.remotebuzzer.move2usb != 'disabled') {
                if (triggerArmed) {
                    photoboothAction('move2usb');
                    sendText('MOVE2USB TRIGGERED');
                } else {
                    sendText('ALREADY TRIGGERED AN ACTION');
                }
            } else {
                sendText('Please enable Hardware Button support and Move2USB Button!');
            }
            break;
        case '/commands/start-video':
            log('http: GET /commands/start-video');
            if (config.remotebuzzer.usebuttons && config.remotebuzzer.videobutton) {
                if (triggerArmed) {
                    if (config.video.enabled) {
                        photoboothAction('video');
                        sendText('TAKE VIDEO TRIGGERED');
                    } else {
                        sendText('VIDEO DISABLED.');
                    }
                } else {
                    sendText('ALREADY TRIGGERED AN ACTION');
                }
            } else {
                sendText('Please enable Hardware Button support and Video Button!');
            }
            break;
        case '/commands/rotary-cw':
            log('http: GET /commands/rotary-cw');
            if (config.remotebuzzer.userotary) {
                photoboothAction('rotary-cw');
                sendText('FOCUS NEXT ELEMENT');
            } else {
                sendText('Please enable rotary Controller support!');
            }
            break;
        case '/commands/rotary-ccw':
            log('http: GET /commands/rotary-ccw');
            if (config.remotebuzzer.userotary) {
                photoboothAction('rotary-ccw');
                sendText('FOCUS PREVIOUS ELEMENT');
            } else {
                sendText('Please enable rotary Controller support!');
            }
            break;
        case '/commands/rotary-btn-press':
            log('http: GET /commands/rotary-btn-press');
            if (config.remotebuzzer.userotary) {
                photoboothAction('rotary-btn-press');
                sendText('CLICK ELEMENT');
            } else {
                sendText('Please enable rotary Controller support!');
            }
            break;
        case '/commands/shutdown-now':
            log('http: GET /commands/shutdown-now');
            if (config.remotebuzzer.usebuttons && config.remotebuzzer.shutdownbutton) {
                sendText('SHUTTING DOWN');
                /*  Initiate system shutdown */
                const cmd = 'sudo ' + config.commands.shutdown;
                execSync(cmd);
            } else {
                sendText('Please enable Hardware Button support and Shutdown Button!');
            }
            break;
        case '/commands/reboot-now':
            log('http: GET /commands/reboot-now');
            if (config.remotebuzzer.usebuttons && config.remotebuzzer.rebootbutton) {
                sendText('REBOOTING NOW');
                /*  Initiate system shutdown */
                const cmd = 'sudo ' + config.commands.reboot;
                execSync(cmd);
            } else {
                sendText('Please enable Hardware Button support and Reboot Button!');
            }
            break;
        default:
            res.writeHead(404);
            res.end();
    }
};

const http = require('http');
const server = new http.Server(requestListener);

/* CONFIGURE WEBSOCKET SERVER */
const ioServer = require('socket.io')(server, {
    cors: {
        origin: '*',
        methods: ['GET', 'POST']
    }
});

/* NEW CLIENT CONNECTED */
ioServer.on('connection', function (client) {
    log('New client connected - ID', client.id);

    client.on('photobooth-socket', function (data) {
        log('Data from client ID ', client.id, ': [ photobooth-socket ] =>  [' + data + ']');

        /* CLIENT COMMANDS RECEIVED */
        switch (data) {
            case 'completed':
                photoboothAction('completed');
                break;

            case 'in-progress':
                triggerArmed = false;
                break;

            case 'start-picture':
                photoboothAction('picture');
                break;

            case 'start-collage':
                photoboothAction('collage');
                break;

            case 'start-custom':
                photoboothAction('custom');
                break;

            case 'start-video':
                photoboothAction('video');
                break;

            case 'start-move2usb':
                photoboothAction('move2usb');
                break;

            case 'collage-wait-for-next':
                armTrigger();
                break;

            default:
                log('Received unknown command [', data, '] - ignoring');
                break;
        }
    });

    /* CLIENT DISCONNECTED */
    client.on('disconnect', function () {
        log('Client disconnected - ID ', client.id);

        if (ioServer.engine.clientsCount == 0) {
            log('No more clients connected - removing lock and arming trigger');
            triggerArmed = true;
            collageInProgress = false;
        }
    });
});

/* STARTUP COMPLETED */
server.listen(config.remotebuzzer.port, () => {
    log('socket.io server started');
});

function move2usbAction() {
    const parseConfig = () => {
        try {
            return {
                dataAbsPath: environment.absoluteFolders.data,
                drive: config.synctodrive.target,
                dbName: config.database.file
            };
        } catch (err) {
            log('ERROR: unable to parse sync-to-drive config', err);
        }

        return null;
    };

    /* PARSE PHOTOBOOTH CONFIG */
    const parsedConfig = parseConfig();
    if (!parsedConfig) {
        log('ERROR: Could not parse config, aborting move2usb');
        photoboothAction('completed');
        return;
    }
    copySuccess = false;
    log('USB target ', parsedConfig.drive);

    const getDriveInfo = ({ drive }) => {
        let json;
        const requiredColumns = 'NAME,KNAME,PATH,LABEL,MOUNTPOINT,MOUNTPOINTS,SUBSYSTEMS,TYPE';

        drive = drive.toLowerCase();

        try {
            // Try -ablJO first (all columns), fall back to explicit column list
            let output;
            try {
                output = execSync('LC_ALL=C lsblk -ablJO 2>/dev/null').toString();
            } catch {
                log('lsblk -O not supported, falling back to explicit columns');
                output = execSync(`LC_ALL=C lsblk -ablJ -o ${requiredColumns} 2>/dev/null`).toString();
            }
            json = JSON.parse(output);
        } catch (err) {
            log(
                'ERROR: Could not parse the output of lsblk! Please make sure its installed and that it offers JSON output!',
                err.message
            );

            return null;
        }

        if (!json || !json.blockdevices) {
            log('ERROR: The output of lsblk was malformed!');

            return null;
        }

        const device = json.blockdevices.find(
            (blk) =>
                blk.subsystems &&
                blk.subsystems.includes('usb') &&
                ((blk.name && drive === blk.name.toLowerCase()) ||
                    (blk.kname && drive === blk.kname.toLowerCase()) ||
                    (blk.path && drive === blk.path.toLowerCase()) ||
                    (blk.label && drive === blk.label.toLowerCase()))
        );

        if (device) {
            // Normalize mountpoints (array) to mountpoint (string) for compatibility
            // Newer lsblk versions use "mountpoints" array instead of "mountpoint" string
            if (!device.mountpoint && Array.isArray(device.mountpoints)) {
                device.mountpoint = device.mountpoints.find((mp) => mp !== null) || null;
            }

            return device;
        }

        // Fallback: lsblk may not report labels (e.g. in LXC containers or after USB reconnect).
        // Use blkid which reads directly from the block device and always works.
        log('lsblk did not find device "' + drive + '" by label, trying blkid fallback...');

        return findDeviceByBlkid(drive, json.blockdevices);
    };

    const findDeviceByBlkid = (drive, lsblkDevices) => {
        let blkidOutput;
        try {
            blkidOutput = execSync('LC_ALL=C blkid 2>/dev/null').toString();
        } catch (err) {
            log('blkid fallback failed: ' + err.message);
            log('ERROR: Device ' + drive + ' was not detected (blkid fallback also failed)');
            return null;
        }

        const lines = blkidOutput.split('\n').filter((line) => line.trim());

        for (const line of lines) {
            const labelMatch = line.match(/\bLABEL="([^"]+)"/);
            if (!labelMatch || labelMatch[1].toLowerCase() !== drive) {
                continue;
            }

            const pathMatch = line.match(/^(\/dev\/[^:]+):/);
            if (!pathMatch) {
                continue;
            }

            const devicePath = pathMatch[1];
            log('blkid found device with label "' + drive + '" at ' + devicePath);

            // Cross-reference with lsblk to verify it is a USB device
            const lsblkEntry = lsblkDevices.find((blk) => blk.path === devicePath);
            if (lsblkEntry && lsblkEntry.subsystems && !lsblkEntry.subsystems.includes('usb')) {
                log(
                    'Device ' +
                        devicePath +
                        ' is not a USB device (subsystems: ' +
                        lsblkEntry.subsystems +
                        '), skipping'
                );
                continue;
            }

            // Determine current mountpoint
            let mountpoint = null;
            if (lsblkEntry && lsblkEntry.mountpoint) {
                mountpoint = lsblkEntry.mountpoint;
            } else if (lsblkEntry && Array.isArray(lsblkEntry.mountpoints)) {
                mountpoint = lsblkEntry.mountpoints.find((mp) => mp !== null) || null;
            } else {
                try {
                    mountpoint =
                        execSync('findmnt -n -o TARGET ' + devicePath + ' 2>/dev/null')
                            .toString()
                            .trim() || null;
                    // eslint-disable-next-line no-unused-vars
                } catch (e) {
                    // Device is not currently mounted
                }
            }

            const name = path.basename(devicePath);

            const device = {
                name: name,
                kname: name,
                path: devicePath,
                label: labelMatch[1],
                mountpoint: mountpoint,
                subsystems: lsblkEntry && lsblkEntry.subsystems ? lsblkEntry.subsystems : 'block:scsi:usb:pci'
            };

            log('blkid fallback found device: ' + JSON.stringify(device));

            return device;
        }

        log('ERROR: Device ' + drive + ' was not detected');
        return null;
    };

    const isWritable = (directory) => {
        try {
            fs.accessSync(directory, fs.constants.W_OK);
            return true;
            // eslint-disable-next-line no-unused-vars
        } catch (err) {
            return false;
        }
    };

    /**
     * Find the current mountpoint of a device using findmnt (most reliable).
     * Returns the mountpoint string or null.
     */
    const findCurrentMountpoint = (drive) => {
        // First check the mountpoint from lsblk data
        if (drive.mountpoint) {
            try {
                execSync(`mountpoint -q '${drive.mountpoint.replace(/'/g, '\'\\\'\'')}'`, { stdio: 'ignore' });
                return drive.mountpoint;
                // eslint-disable-next-line no-unused-vars
            } catch (err) {
                // lsblk mountpoint is stale
            }
        }

        // Use findmnt as the authoritative source
        try {
            const mp = execSync('findmnt -n -o TARGET ' + drive.path + ' 2>/dev/null')
                .toString()
                .trim();
            return mp || null;
            // eslint-disable-next-line no-unused-vars
        } catch (err) {
            return null;
        }
    };

    /**
     * Get mount options for FAT/exFAT filesystems that ensure all users can read/write.
     * Returns null for non-FAT filesystems.
     */
    const getFATMountOptions = (devicePath) => {
        try {
            const fstype = execSync('lsblk -n -o FSTYPE ' + devicePath + ' 2>/dev/null')
                .toString()
                .trim();
            if (fstype === 'vfat' || fstype === 'exfat') {
                return 'umask=0000,uid=' + process.getuid() + ',gid=' + process.getgid();
            }
            // eslint-disable-next-line no-unused-vars
        } catch (err) {
            // ignore
        }
        return null;
    };

    const mountDrive = (drive) => {
        if (!drive || !drive.path) {
            log('ERROR: No drive or drive path provided for mounting');
            return null;
        }

        // Check if already mounted (via lsblk data or findmnt)
        const currentMountpoint = findCurrentMountpoint(drive);
        if (currentMountpoint) {
            drive.mountpoint = currentMountpoint;

            if (isWritable(currentMountpoint)) {
                log('Device ' + drive.path + ' is already mounted at ' + currentMountpoint + ' and writable');
                return drive;
            }

            // Mounted but not writable (e.g. FAT32 mounted by another user with their uid/gid)
            log('Device ' + drive.path + ' is mounted at ' + currentMountpoint + ' but not writable, unmounting...');
            unmountDrive(drive);
        }

        // Try udisksctl first
        try {
            const mountCmd = `LC_ALL=C udisksctl mount -b ${drive.path}`;
            log('Mounting device ' + drive.path + ' via udisksctl');
            const mountRes = execSync(mountCmd, { timeout: 30000 }).toString();
            log('udisksctl output: ' + mountRes.trim());
            const mountMatch = mountRes.match(/Mounted\s+\S+\s+at\s+(.+)/);
            if (mountMatch) {
                const mountPoint = mountMatch[1].trim().replace(/[\n.]+$/gu, '');
                if (mountPoint) {
                    drive.mountpoint = mountPoint;
                    log('Mounted via udisksctl at ' + drive.mountpoint);
                    return drive;
                }
            }
            log('udisksctl mount returned unexpected output: ' + mountRes.trim());
        } catch (udisksErr) {
            log('udisksctl mount failed: ' + udisksErr.message);
        }

        // udisksctl may have succeeded but output was not parseable.
        // Check with findmnt if the device actually got mounted.
        try {
            const findmntRes = execSync('findmnt -n -o TARGET ' + drive.path + ' 2>/dev/null')
                .toString()
                .trim();
            if (findmntRes) {
                if (isWritable(findmntRes)) {
                    drive.mountpoint = findmntRes;
                    log('Device already mounted (detected via findmnt) at ' + drive.mountpoint);
                    return drive;
                }

                // Mounted but not writable, unmount and continue to fallback
                log('Device mounted at ' + findmntRes + ' but not writable, unmounting...');
                drive.mountpoint = findmntRes;
                unmountDrive(drive);
            }
            // eslint-disable-next-line no-unused-vars
        } catch (findmntErr) {
            // Device is not mounted
        }

        // Ensure the device is fully unmounted before attempting fallback mount
        try {
            const remainingMount = execSync('findmnt -n -o TARGET ' + drive.path + ' 2>/dev/null')
                .toString()
                .trim();
            if (remainingMount) {
                log('Device still mounted at ' + remainingMount + ' (automount?), forcing unmount...');
                try {
                    execSync('sudo umount ' + shellQuote(drive.path), { timeout: 30000 });
                    log('Forced unmount successful');
                } catch (forceErr) {
                    log('Forced unmount failed: ' + forceErr.message);
                }
            }
            // eslint-disable-next-line no-unused-vars
        } catch (err) {
            // not mounted, good
        }

        // Fallback: direct mount command with sudo
        const label = drive.label || path.basename(drive.path);
        const fallbackMountpoint = path.join('/media', label);

        try {
            if (!fs.existsSync(fallbackMountpoint)) {
                execSync('sudo mkdir -p ' + shellQuote(fallbackMountpoint));
            }

            // Detect filesystem type to apply appropriate mount options
            const mountOpts = getFATMountOptions(drive.path);
            const mountCmd = mountOpts
                ? `sudo mount -o ${mountOpts} ${shellQuote(drive.path)} ${shellQuote(fallbackMountpoint)}`
                : `sudo mount ${shellQuote(drive.path)} ${shellQuote(fallbackMountpoint)}`;
            log('Trying fallback: ' + mountCmd);
            execSync(mountCmd, { timeout: 30000 });
            drive.mountpoint = fallbackMountpoint;
            log('Mounted via direct mount at ' + drive.mountpoint);
            return drive;
        } catch (mountErr) {
            log('Direct mount also failed: ' + mountErr.message);
        }

        log('ERROR: Unable to mount device ' + drive.path);
        return null;
    };

    const startSync = ({ dataAbsPath, drive }) => {
        if (!fs.existsSync(dataAbsPath)) {
            log(`ERROR: Folder [${dataAbsPath}] does not exist!`);

            return;
        }

        log('Starting sync to USB drive ...');
        log(`Source data folder [${dataAbsPath}]`);
        log(`Syncing to drive [${drive.path}] -> [${drive.mountpoint}]`);

        fs.writeFileSync(path.join(dataAbsPath, 'copy.chk'), '', { flag: 'w' });

        const usbCheckfile = path.join(drive.mountpoint, 'copy.chk');
        if (fs.existsSync(usbCheckfile)) {
            log(' ');
            log('[WARNING] Move2USB: stale copy.chk already on USB before rsync:', usbCheckfile);
            log(' ');
        }

        const cmd = (() => {
            switch (process.platform) {
                case 'win32':
                    return null;
                case 'linux':
                    // prettier-ignore
                    return [
                        'rsync',
                        '-a',
                        '--delete-before',
                        '-b',
                        `--backup-dir=${path.join(drive.mountpoint, 'deleted')}`,
                        '--ignore-existing',
                        '--include=\'*.\'{jpg,chk,gif,mp4}',
                        '--include=\'*/\'',
                        '--exclude=\'*\'',
                        '--prune-empty-dirs',
                        dataAbsPath + '/',
                        drive.mountpoint
                    ].join(' ');
                default:
                    return null;
            }
        })();

        if (!cmd) {
            log('ERROR: No command for syncing!');

            return;
        }

        log('Executing command: <', cmd, '>');

        const rsyncResult = spawnSync(cmd, {
            shell: '/bin/bash',
            stdio: 'ignore'
        });
        if (rsyncResult.error) {
            log('ERROR: Could not start rsync:', rsyncResult.error.toString());

            return;
        }
        if (rsyncResult.status !== 0) {
            log('ERROR: rsync exited with code ' + rsyncResult.status);

            return;
        }

        log('Sync completed');

        if (fs.existsSync(usbCheckfile)) {
            copySuccess = true;
        } else {
            log(' ');
            log(
                '[ERROR] Move2USB: sync verification failed (copy.chk missing on USB after rsync, expected at):',
                usbCheckfile
            );
            log(' ');
            copySuccess = false;

            return;
        }

        try {
            fs.unlinkSync(usbCheckfile);
        } catch (err) {
            log('Warning: Could not remove checkfile from USB: ' + err.message);
        }
        try {
            fs.unlinkSync(path.join(dataAbsPath, 'copy.chk'));
        } catch (err) {
            log('Warning: Could not remove local checkfile: ' + err.message);
        }
    };

    const unmountDrive = (drive) => {
        if (!drive || !drive.path) {
            log('Nothing to unmount');
            return;
        }

        // Use findmnt to check if actually mounted (don't rely on drive.mountpoint alone)
        const currentMountpoint = findCurrentMountpoint(drive);
        if (!currentMountpoint) {
            log('Device ' + drive.path + ' is not mounted, skipping unmount');
            return;
        }

        // Try udisksctl first
        try {
            execSync(`LC_ALL=C udisksctl unmount -b ${drive.path}`, { timeout: 30000 });
            log('Unmounted drive ' + drive.path + ' via udisksctl');
            drive.mountpoint = null;
            return;
        } catch (udisksErr) {
            log('udisksctl unmount failed: ' + udisksErr.message);
        }

        // Fallback: sudo umount (needed when device was mounted by another user)
        try {
            execSync('sudo umount ' + shellQuote(drive.path), { timeout: 30000 });
            log('Unmounted drive ' + drive.path + ' via sudo umount');
            drive.mountpoint = null;
        } catch (umountErr) {
            log('sudo umount also failed: ' + umountErr.message);
        }
    };

    const deleteFiles = ({ dataAbsPath }) => {
        if (!fs.existsSync(dataAbsPath)) {
            log(`ERROR: Folder [${dataAbsPath}] does not exist!`);

            return;
        }
        if (!copySuccess) {
            log('[Warning] Sync was unsuccessful. No files will be deleted.');

            return;
        }

        log('Deleting Files...');

        const cmd = (() => {
            switch (process.platform) {
                case 'win32':
                    return null;
                case 'linux':
                    // prettier-ignore
                    return [
                        'find',
                        dataAbsPath,
                        '-type f \\(',
                        '-name \'*.jpg\'',
                        '-o',
                        '-name \'*.gif\'',
                        '-o',
                        '-name \'*.mp4\'',
                        '\\)',
                        '-exec rm -rv {}',
                        '\\;'
                    ].join(' ');
                default:
                    return null;
            }
        })();

        if (!cmd) {
            log('ERROR: No delete command for this platform');

            return;
        }

        log('Executing command: <', cmd, '>');
        try {
            execSync(cmd);
        } catch (err) {
            log('ERROR: Failed to delete files: ' + err.message);
        }
    };

    const deleteDatabase = ({ dataAbsPath, dbName }) => {
        if (!fs.existsSync(dataAbsPath)) {
            log(`ERROR: Folder [${dataAbsPath}] does not exist!`);

            return;
        }
        if (!copySuccess) {
            log('[Warning] Sync was unsuccessful. No files will be deleted.');

            return;
        }
        const dbPath = path.join(dataAbsPath, dbName + '.txt');
        if (!fs.existsSync(dbPath)) {
            log('Error: Database not found: ' + dbPath + ' - nothing to delete');

            return;
        }

        log('Deleting Database...');

        try {
            fs.unlinkSync(dbPath);
            log('Deleted database: ' + dbPath);
        } catch (err) {
            log('ERROR: Could not delete database ' + dbPath + ': ' + err.message);
        }
    };

    /* Execution starts here */

    if (PLATFORM === 'win32') {
        log('Windows is currently not supported!');
        process.exit();
    }

    log('Checking for USB drive');

    const driveInfo = getDriveInfo(parsedConfig);
    if (!driveInfo) {
        log('ERROR: USB drive not found, aborting move2usb');
        photoboothAction('completed');
        return;
    }
    log(`Processing drive ${driveInfo.label || driveInfo.name} -> ${driveInfo.path}`);

    const mountedDrive = mountDrive(driveInfo);
    if (!mountedDrive || !mountedDrive.mountpoint) {
        log('ERROR: Could not mount USB drive, aborting move2usb');
        photoboothAction('completed');
        return;
    }
    log(`Mounted drive ${mountedDrive.name || mountedDrive.label} -> ${mountedDrive.mountpoint}`);

    startSync({
        dataAbsPath: parsedConfig.dataAbsPath,
        drive: mountedDrive
    });

    unmountDrive(mountedDrive);

    if (copySuccess && config.remotebuzzer.move2usb == 'move') {
        deleteFiles({ dataAbsPath: parsedConfig.dataAbsPath });
    } else {
        log('[Info] move2USB mode "copy" or Sync unsuccessful. No files will be deleted.');
    }

    if (copySuccess && config.remotebuzzer.move2usb == 'move') {
        deleteDatabase({
            dataAbsPath: parsedConfig.dataAbsPath,
            dbName: parsedConfig.dbName
        });
    } else {
        log('[Info] move2USB mode "copy" or Sync unsuccessful. Database will not be deleted.');
    }

    photoboothAction('completed');
}

log('Initialization completed');

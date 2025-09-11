const fs = require('fs');
const path = require('path');

/* VARIABLES */
// eslint-disable-next-line no-unused-vars
let collageInProgress = false,
    triggerArmed = true,
    copySucess = false;

const SYNC_DESTINATION_DIR = 'photobooth-pic-sync';
const { execSync, spawnSync } = require('child_process');
const { pid: PID, platform: PLATFORM } = process;

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

/* WRITE PROCESS PID FILE */
const writePIDFile = (filename) => {
    try {
        fs.writeFileSync(filename, parseInt(PID, 10).toString(), { flag: 'w' });
        log(`PID file created [${filename}]`);
    } catch (err) {
        throw new Error(`Unable to write PID file [${filename}] - ${err.message}`);
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

function photoboothAction(type) {
    switch (type) {
        case 'picture':
            triggerArmed = false;
            collageInProgress = false;
            log('Photobooth trigger PICTURE : [ photobooth-socket ] => [ All Clients ]: command [ picture ]');
            ioServer.emit('photobooth-socket', 'start-picture');
            break;

        case 'custom':
            triggerArmed = false;
            collageInProgress = false;
            log('Photobooth trigger CUSTOM : [ photobooth-socket ]  => [ All Clients ]: command [ custom ]');
            ioServer.emit('photobooth-socket', 'start-custom');
            break;

        case 'video':
            triggerArmed = false;
            collageInProgress = false;
            log('Photobooth trigger VIDEO : [ photobooth-socket ]  => [ All Clients ]: command [ video ]');
            ioServer.emit('photobooth-socket', 'start-video');
            break;

        case 'move2usb':
            triggerArmed = false;
            collageInProgress = false;
            log('Photobooth trigger MOVE2USB : [ photobooth-socket ]  => [ All Clients ]: command [ move2usb ]');
            move2usbAction();
            break;

        case 'collage':
            triggerArmed = false;
            collageInProgress = true;
            log('Photobooth trigger COLLAGE : [ photobooth-socket ]  => [ All Clients ]: command [ collage ]');
            ioServer.emit('photobooth-socket', 'start-collage');
            break;

        case 'collage-next':
            log('Photobooth COLLAGE : [ photobooth-socket ]  => [ All Clients ]: command [ collage-next ]');
            ioServer.emit('photobooth-socket', 'collage-next');
            break;

        case 'completed':
            triggerArmed = true;
            collageInProgress = false;
            log('Photobooth activity completed : [ photobooth-socket ] => [ All Clients ]: command [ completed ]');
            ioServer.emit('photobooth-socket', 'completed');
            break;

        case 'print':
            triggerArmed = false;
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
                triggerArmed = true;
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
    log('USB target ', ...parsedConfig.drive);

    const getDriveInfo = ({ drive }) => {
        let json = null;
        let device = false;

        drive = drive.toLowerCase();

        try {
            //Assuming that the lsblk version supports JSON output!
            const output = execSync('export LC_ALL=C; lsblk -ablJO 2>/dev/null; unset LC_ALL').toString();
            json = JSON.parse(output);

            // eslint-disable-next-line no-unused-vars
        } catch (err) {
            log(
                'ERROR: Could not parse the output of lsblk! Please make sure its installed and that it offers JSON output!'
            );

            return null;
        }

        if (!json || !json.blockdevices) {
            log('ERROR: The output of lsblk was malformed!');

            return null;
        }

        try {
            device = json.blockdevices.find(
                (blk) =>
                    blk.subsystems.includes('usb') &&
                    ((blk.name && drive === blk.name.toLowerCase()) ||
                        (blk.kname && drive === blk.kname.toLowerCase()) ||
                        (blk.path && drive === blk.path.toLowerCase()) ||
                        (blk.label && drive === blk.label.toLowerCase()))
            );
            // eslint-disable-next-line no-unused-vars
        } catch (err) {
            device = false;
        }

        return device;
    };

    const mountDrive = (drive) => {
        if (typeof drive.mountpoint === 'undefined' || !drive.mountpoint) {
            try {
                const mountRes = execSync(`export LC_ALL=C; udisksctl mount -b ${drive.path}; unset LC_ALL`).toString();
                const mountPoint = mountRes
                    .substr(mountRes.indexOf('at') + 3)
                    .trim()
                    .replace(/[\n.]/gu, '');

                drive.mountpoint = mountPoint;
                // eslint-disable-next-line no-unused-vars
            } catch (error) {
                log('ERROR: unable to mount drive', drive.path);
                drive = null;
            }
        }

        return drive;
    };

    const startSync = ({ dataAbsPath, drive }) => {
        if (!fs.existsSync(dataAbsPath)) {
            log(`ERROR: Folder [${dataAbsPath}] does not exist!`);

            return;
        }

        log('Starting sync to USB drive ...');
        log(`Source data folder [${dataAbsPath}]`);
        log(`Syncing to drive [${drive.path}] -> [${drive.mountpoint}]`);

        execSync('touch ' + dataAbsPath + '/copy.chk');

        if (fs.existsSync(path.join(drive.mountpoint, SYNC_DESTINATION_DIR + '/data/copy.chk'))) {
            log(' ');
            log(
                '[WARNING] Last sync might not completed, Checkfile exists:',
                path.join(drive.mountpoint, SYNC_DESTINATION_DIR + '/copy.chk')
            );
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
                        dataAbsPath,
                        path.join(drive.mountpoint, SYNC_DESTINATION_DIR)
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

        try {
            spawnSync(cmd, {
                shell: '/bin/bash',
                stdio: 'ignore'
            });
        } catch (err) {
            log('ERROR: Could not start rsync:', err.toString());

            return;
        }

        log('Sync completed');

        if (fs.existsSync(path.join(drive.mountpoint, SYNC_DESTINATION_DIR + '/data/copy.chk'))) {
            copySucess = true;
        } else {
            log(' ');
            log(
                '[ERROR] Sync error, sync might be not sucessfull. Checkfile does not exist:',
                path.join(drive.mountpoint, SYNC_DESTINATION_DIR + '/data/copy.chk')
            );
            log(' ');
            copySucess = false;

            return;
        }

        execSync('rm ' + path.join(drive.mountpoint, SYNC_DESTINATION_DIR + '/data/copy.chk'));
        execSync('rm ' + dataAbsPath + '/copy.chk');
    };

    const unmountDrive = () => {
        const driveInfo = getDriveInfo(parsedConfig);
        const mountedDrive = mountDrive(driveInfo);

        if (mountedDrive) {
            try {
                execSync(`export LC_ALL=C; udisksctl unmount -b ${mountedDrive.path}; unset LC_ALL`).toString();
                log('Unmounted drive', mountedDrive.path);
                // eslint-disable-next-line no-unused-vars
            } catch (error) {
                log('ERROR: unable to unmount drive', mountedDrive.path);
            }
        } else {
            log('Nothing to umount');
        }
    };

    const deleteFiles = ({ dataAbsPath }) => {
        if (!fs.existsSync(dataAbsPath)) {
            log(`ERROR: Folder [${dataAbsPath}] does not exist!`);

            return;
        }
        if (!copySucess) {
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

        log('Executing command: <', cmd, '>');
        execSync(cmd);
    };

    const deleteDatabase = ({ dataAbsPath, dbName }) => {
        if (!fs.existsSync(dataAbsPath)) {
            log(`ERROR: Folder [${dataAbsPath}] does not exist!`);

            return;
        }
        if (!copySucess) {
            log('[Warning] Sync was unsuccessful. No files will be deleted.');

            return;
        }
        if (!fs.existsSync(path.join(dataAbsPath, dbName + '.txt'))) {
            const cmd = path.join(dataAbsPath, dbName + '.txt');
            log('Error: Database not found: ', cmd, ' - nothing to delete');

            return;
        }

        log('Deleting Database...');

        const cmd = 'rm ' + path.join(dataAbsPath, dbName + '.txt');
        log('Executing command: <', cmd, '>');
        execSync(cmd);
    };

    /* Execution starts here */

    if (PLATFORM === 'win32') {
        log('Windows is currently not supported!');
        process.exit();
    }

    log('Checking for USB drive');

    const driveInfo = getDriveInfo(parsedConfig);
    try {
        log(`Processing drive ${driveInfo.label} -> ${driveInfo.path}`);
        // eslint-disable-next-line no-unused-vars
    } catch (error) {
        return;
    }

    const mountedDrive = mountDrive(driveInfo);
    try {
        log(`Mounted drive ${mountedDrive.name} -> ${mountedDrive.mountpoint}`);
        // eslint-disable-next-line no-unused-vars
    } catch (error) {
        return;
    }

    if (mountedDrive) {
        startSync({
            dataAbsPath: parsedConfig.dataAbsPath,
            drive: mountedDrive
        });
    }

    unmountDrive();

    if (copySucess && config.remotebuzzer.move2usb == 'move') {
        deleteFiles({ dataAbsPath: parsedConfig.dataAbsPath });
    } else {
        log('[Info] move2USB mode "copy" or Sync unsuccessful. No files will be deleted.');
    }

    if (copySucess && config.remotebuzzer.move2usb == 'move') {
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

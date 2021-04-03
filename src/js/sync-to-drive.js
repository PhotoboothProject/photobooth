/* This script needs to be run from within the photobooth directory */

/* Imports */
const {execSync, spawn} = require('child_process');
const fs = require('fs');
const path = require('path');
const events = require('events');

/* Variables */
const API_DIR_NAME = 'api';
const API_FILE_NAME = 'config.php';
const SYNC_DESTINATION_DIR = 'photobooth-pic-sync';
const {pid: PID, platform: PLATFORM} = process;
const myEmitter = new events.EventEmitter();
let rsyncSemaphore = null;
let rsyncStartTime = 0;

/* Functions */

const log = (...optionalParams) => console.log(`Sync-To-Drive server [${PID}]:`, ...optionalParams);

const getConfigFromPHP = () => {
    const cmd = `cd ${API_DIR_NAME} && php ./${API_FILE_NAME}`;

    try {
        const stdout = execSync(cmd).toString();

        return JSON.parse(stdout.slice(stdout.indexOf('{'), -1));
    } catch (err) {
        log('ERROR: Unable to load photobooth config', err);
    }

    return null;
};

const parseConfig = (config) => {
    if (!config) {
        return null;
    }

    try {
        return {
            dataAbsPath: config.foldersAbs.data,
            drive: config.synctodrive.target
        };
    } catch (err) {
        log('ERROR: unable to parse sync-to-drive config', err);
    }

    return null;
};

const getDriveInfo = ({drive}) => {
    let json = null;

    drive = drive.toLowerCase();

    try {
        //Assuming that the lsblk version supports JSON output!
        const output = execSync('export LC_ALL=C; lsblk -ablJO 2>/dev/null; unset LC_ALL').toString();
        json = JSON.parse(output);
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

    return json.blockdevices.find(
        (blk) =>
            // eslint-disable-next-line implicit-arrow-linebreak
            blk.subsystems.includes('usb') &&
            ((blk.name && drive === blk.name.toLowerCase()) ||
                (blk.kname && drive === blk.kname.toLowerCase()) ||
                (blk.path && drive === blk.path.toLowerCase()) ||
                (blk.label && drive === blk.label.toLowerCase()))
    );
};

const mountDrive = (drive) => {
    if (!drive.mountpoint) {
        try {
            const mountRes = execSync(`export LC_ALL=C; udisksctl mount -b ${drive.path}; unset LC_ALL`).toString();
            const mountPoint = mountRes
                .substr(mountRes.indexOf('at') + 3)
                .trim()
                .replace(/[\n.]/gu, '');

            drive.mountpoint = mountPoint;
        } catch (error) {
            log('ERROR: unable to mount drive', drive.path);
            drive = null;
        }
    }

    return drive;
};

const startSync = ({dataAbsPath, drive}) => {
    if (!fs.existsSync(dataAbsPath)) {
        log(`ERROR: Folder [${dataAbsPath}] does not exist!`);

        return;
    }

    log('Starting sync to USB drive ...');
    log(`Source data folder [${dataAbsPath}]`);
    log(`Syncing to drive [${drive.path}] -> [${drive.mountpoint}]`);

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
                    '--include=\'*.jpg\'',
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
        rsyncSemaphore = spawn(cmd, {
            shell: true,
            stdio: 'ignore'
        });
    } catch (err) {
        log('ERROR: Could not start rsync:', err.toString());

        return;
    }

    log('Rsync child process PID:', rsyncSemaphore.pid, 'started');

    rsyncSemaphore.on('exit', () => {
        myEmitter.emit('rsync-completed', rsyncSemaphore.pid);
    });

    rsyncStartTime = new Date();
};

const writePIDFile = (filename) => {
    try {
        fs.writeFileSync(filename, PID, {flag: 'w'});
        log(`PID file created [${filename}]`);
    } catch (err) {
        throw new Error(`Unable to write PID file [${filename}] - ${err.message}`);
    }
};

const unmountDrive = () => {
    const driveInfo = getDriveInfo(parsedConfig);
    const mountedDrive = mountDrive(driveInfo);

    try {
        execSync(`export LC_ALL=C; udisksctl unmount -b ${mountedDrive.path}; unset LC_ALL`).toString();
        log('Unmounted drive', mountedDrive.path);
    } catch (error) {
        log('ERROR: unable to unmount drive', mountedDrive.path);
    }
};

/* Execution starts here */

if (PLATFORM === 'win32') {
    log('Windows is currently not supported!');
    process.exit();
}

/* GET PHOTOBOOTH CONFIG */
const phpConfig = getConfigFromPHP();

if (!phpConfig) {
    process.exit();
} else if (!phpConfig.synctodrive.enabled) {
    log('WARN: Sync script disabled by config - exiting');
    process.exit();
}

/* PARSE PHOTOBOOTH CONFIG */
const parsedConfig = parseConfig(phpConfig);
log('USB target ', ...parsedConfig.drive);

/* WRITE PROCESS PID FILE */
writePIDFile(path.join(phpConfig.foldersRoot.tmp, 'synctodrive_server.pid'));

/* INSTALL HANDLER TO MONITOR CHILD PROCESS EXITS */
myEmitter.on('rsync-completed', (childPID) => {
    log('Rsync child process PID:', childPID, 'finished after', new Date() - rsyncStartTime, 'ms');
    rsyncSemaphore = false;
    log('... finished sync, going back to sleep');
});

/* INSTALL HANDLER ON SERVER PROCESS SIGHUP SIGTERM, SIGINT */
const signalHandler = async (signal) => {
    log(
        'SignalHandler: received signal',
        signal,
        '- wait for possible rsync to complete, umount USB stick and gracefully terminate'
    );

    if (rsyncSemaphore) {
        log('SignalHandler: rsync in progress - waiting for termination for max 60 seconds');

        setTimeout(() => {
            log('SignalHandler: rsync seems stale, terminate child process (SIGKILL)');
            rsyncSemaphore.kill('SIGKILL');
            rsyncSemaphore = false;
        }, 60000);

        const sleep = (milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds));

        // eslint-disable-next-line no-unmodified-loop-condition
        while (rsyncSemaphore) {
            // eslint-disable-next-line no-await-in-loop
            await sleep(1000);
        }
    }

    /* umount drives here */
    unmountDrive();

    log('SignalHandler: gracefully terminating now - bye bye');
    process.exit();
};

['SIGTERM', 'SIGHUP', 'SIGINT'].forEach((term) => process.on(term, signalHandler.bind(null, term)));

/* START FOREVER LOOP */
log('Starting server process');
log(`Interval is [${phpConfig.synctodrive.interval}] seconds`);

const syncLoop = () => {
    if (rsyncSemaphore) {
        log(`WARN: Sync in progress, waiting for [${phpConfig.synctodrive.interval}] seconds`);

        return;
    }

    log('Checking for USB drive');

    const driveInfo = getDriveInfo(parsedConfig);
    try {
        log(`Processing drive ${driveInfo.label} -> ${driveInfo.path}`);
    } catch (error) {
        return;
    }

    const mountedDrive = mountDrive(driveInfo);
    try {
        log(`Mounted drive ${mountedDrive.name} -> ${mountedDrive.mountpoint}`);
    } catch (error) {
        return;
    }

    if (mountedDrive) {
        startSync({
            dataAbsPath: parsedConfig.dataAbsPath,
            drive: mountedDrive
        });
    }
};

setInterval(syncLoop, phpConfig.synctodrive.interval * 1000);

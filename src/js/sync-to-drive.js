#!/usr/bin/env node
/* eslint-disable node/shebang */

const {execSync, spawn} = require('child_process');
const fs = require('fs');
const path = require('path');

//This script needs to be run from within the photobooth directory
const API_DIR_NAME = 'api';
const API_FILE_NAME = 'config.php';
const {pid: PID, platform: PLATFORM} = process;

const customLog = (...optionalParams) => console.log(`Sync-To-Drive server [${PID}]:`, ...optionalParams);

const getConfigFromPHP = () => {
    const cmd = `cd ${API_DIR_NAME} && php ./${API_FILE_NAME}`;

    try {
        const stdout = execSync(cmd).toString();

        return JSON.parse(stdout.slice(stdout.indexOf('{'), -1));
    } catch (err) {
        customLog('ERROR: Couldnt get config from PHP', err);
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
            drives: config.synctodrive_targets.split(';')
        };
    } catch (err) {
        customLog('ERROR: Couldt parse config', err);
    }

    return null;
};

const getDriveInfos = ({drives}) => {
    let json = null;

    try {
        //Assuming that the lsblk version supports JSON output!
        const output = execSync('export LC_ALL=C; lsblk -ablJO 2>/dev/null; unset LC_ALL').toString();
        json = JSON.parse(output);
    } catch (err) {
        customLog(
            'ERROR: Could not parse the output of lsblk! Please make sure its installed and that it offers JSON output!'
        );

        return null;
    }

    if (!json || !json.blockdevices) {
        customLog('ERROR: The output of lsblk was malformed!');

        return null;
    }

    return json.blockdevices.reduce((arr, blk) => {
        if (
            drives.some(
                (drive) => drive === blk.name || drive === blk.kname || drive === blk.path || drive === blk.label
            )
        ) {
            arr.push(blk);
        }

        return arr;
    }, []);
};

const mountDrives = (drives) => {
    const result = [];

    for (const drive of drives) {
        if (!drive.mountpoint) {
            try {
                const mountRes = execSync(`export LC_ALL=C; udisksctl mount -b ${drive.path}; unset LC_ALL`).toString();
                const mountPoint = mountRes
                    .substr(mountRes.indexOf('at') + 3)
                    .trim()
                    .replace(/[\n.]/gu, '');

                drive.mountpoint = mountPoint;
            } catch (error) {
                customLog('ERROR: Couldnt mount', drive.path);
            }
        }

        if (drive.mountpoint) {
            result.push(drive);
        }
    }

    return result;
};

const startSync = ({dataAbsPath, drives}) => {
    if (!fs.existsSync(dataAbsPath)) {
        customLog(`ERROR: Folder [${dataAbsPath}] does not exist!`);

        return;
    }

    customLog(`Source data folder [${dataAbsPath}]`);

    for (const drive of drives) {
        customLog(`Synching to drive [${drive.path}] -> [${drive.mountpoint}]`);

        const cmd = (() => {
            switch (process.platform) {
                case 'win32':
                    return null;
                case 'linux':
                    return [
                        'rsync',
                        '-a',
                        '--delete-before',
                        '-b',
                        `--backup-dir=${path.join(drive.mountpoint, 'deleted')}`,
                        '--ignore-existing',
                        dataAbsPath,
                        path.join(drive.mountpoint, 'sync')
                    ].join(' ');
                default:
                    return null;
            }
        })();

        if (!cmd) {
            customLog('ERROR: No command for syncing!');

            return;
        }

        customLog('Executing command:', cmd);

        try {
            const spwndCmd = spawn(cmd, {
                detached: true,
                shell: true,
                stdio: 'ignore'
            });
            spwndCmd.unref();
        } catch (err) {
            customLog('ERROR! Couldnt start sync!');
        }
    }
};

// https://stackoverflow.com/a/58844917
const isProcessRunning = (processName) => {
    const cmd = (() => {
        switch (process.platform) {
            case 'win32':
                return 'tasklist';
            case 'darwin':
                return `ps -ax | grep ${processName}`;
            case 'linux':
                return 'ps -A';
            default:
                return false;
        }
    })();

    try {
        const result = execSync(cmd).toString();

        return result.toLowerCase().indexOf(processName.toLowerCase()) > -1;
    } catch (error) {
        return null;
    }
};

if (PLATFORM === 'win32') {
    customLog('Windows is currently not supported!');
    process.exit();
}

if (isProcessRunning('rsync')) {
    customLog('WARN: Sync in progress');
    process.exit();
}

const phpConfig = getConfigFromPHP();

if (!phpConfig) {
    process.exit();
} else if (!phpConfig.synctodrive_enabled) {
    customLog('WARN: Sync script was disabled by config! Aborting!');
    process.exit();
}

/* PARSE PHOTOBOOTH CONFIG */
const parsedConfig = parseConfig(phpConfig);
customLog('Drive names', ...parsedConfig.drives);

/* WRITE PROCESS PID FILE */
const pidFilename = path.join(phpConfig.folders.tmp, 'synctodrive_server.pid');

fs.writeFile(pidFilename, PID, (err) => {
    if (err) {
        throw new Error(`Unable to write PID file [${pidFilename}] - ${err.message}`);
    }

    customLog(`PID file created [${pidFilename}]`);
});

/* START LOOP */
customLog('Starting server for sync to drive');
customLog(`Interval is [${phpConfig.synctodrive_interval}] seconds`);

const foreverLoop = () => {
    customLog('Starting sync process');

    const driveInfos = getDriveInfos(parsedConfig);

    driveInfos.forEach((element) => {
        customLog(`Processing drive ${element.name} -> ${element.path}`);
    });

    const mountedDrives = mountDrives(driveInfos);

    mountedDrives.forEach((element) => {
        customLog(`Mounted drive ${element.name} -> ${element.mountpoint}`);
    });

    startSync({
        dataAbsPath: parsedConfig.dataAbsPath,
        drives: mountedDrives
    });

    setTimeout(foreverLoop, phpConfig.synctodrive_interval * 1000);
};
foreverLoop();

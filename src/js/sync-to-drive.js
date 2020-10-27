#!/usr/bin/env node
/* eslint-disable node/shebang */

const {execSync, spawn} = require('child_process');
const fs = require('fs');
const path = require('path');
const events = require('events');
const myEmitter = new events.EventEmitter();
var rsyncSemaphore = false;
var rsyncStartTime = 0;

//This script needs to be run from within the photobooth directory
const API_DIR_NAME = 'api';
const API_FILE_NAME = 'config.php';
const {pid: PID, platform: PLATFORM} = process;

const log = (...optionalParams) => console.log(`Sync-To-Drive server [${PID}]:`, ...optionalParams);

const getConfigFromPHP = () => {
    const cmd = `cd ${API_DIR_NAME} && php ./${API_FILE_NAME}`;

    try {
        const stdout = execSync(cmd).toString();

        return JSON.parse(stdout.slice(stdout.indexOf('{'), -1));
    } catch (err) {
        log('ERROR: Couldnt get config from PHP', err);
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
        log('ERROR: Couldt parse config', err);
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
        log(
            'ERROR: Could not parse the output of lsblk! Please make sure its installed and that it offers JSON output!'
        );

        return null;
    }

    if (!json || !json.blockdevices) {
        log('ERROR: The output of lsblk was malformed!');

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
                log('ERROR: Couldnt mount', drive.path);
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
        log(`ERROR: Folder [${dataAbsPath}] does not exist!`);

        return;
    }

    log(`Source data folder [${dataAbsPath}]`);

    for (const drive of drives) {
        log(`Synching to drive [${drive.path}] -> [${drive.mountpoint}]`);
	
        const cmd = (() => {
            switch (process.platform) {
            case 'win32':
                return null;
            case 'linux':
		return 'sleep 25';
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

	log('Rsync child process PID: ',rsyncSemaphore.pid);
	
        rsyncSemaphore.on('exit', () => {
            myEmitter.emit('rsync-completed', rsyncSemaphore.pid);
        });
	
	rsyncStartTime = new Date();
    }
};


const writePIDFile = (filename) => {
    try {
        fs.writeFileSync(filename, PID, {flag: 'w'});
        log(`PID file created [${filename}]`);
    } catch (err) {
        throw new Error(`Unable to write PID file [${filename}] - ${err.message}`);
    }
};

if (PLATFORM === 'win32') {
    log('Windows is currently not supported!');
    process.exit();
}

const phpConfig = getConfigFromPHP();

if (!phpConfig) {
    process.exit();
} else if (!phpConfig.synctodrive_enabled) {
    log('WARN: Sync script was disabled by config! Aborting!');
    process.exit();
}

/* PARSE PHOTOBOOTH CONFIG */
const parsedConfig = parseConfig(phpConfig);
log('Drive names', ...parsedConfig.drives);

/* WRITE PROCESS PID FILE */
writePIDFile(path.join(phpConfig.folders.tmp, 'synctodrive_server.pid'));

/* INSTALL HANDLER TO MONITOR CHILD PROCESS EXITS */
myEmitter.on('rsync-completed', (childPID) => {
    log('Rsync child process PID', childPID, 'finished after',(new Date() - rsyncStartTime),'ms');
    rsyncSemaphore = false;
});

/* INSTALL HANDLER ON PROCESS SIGHUP SIGTERM, SIGINT */
const signalHandler = async (signal) => {
    log('SignalHandler: received signal', signal, '- wait for possible rsync to exit, umount USB stick and gracefully terminate');

    if (rsyncSemaphore)
    {
	log ('SignalHandler: rsync in progress - waiting for termination for max 60 seconds');

	setTimeout( () => {
	    log ('SignalHandler: rsync seems stale, terminate child process (SIGKILL)');
	    rsyncSemaphore.kill('SIGKILL');
	    rsyncSemaphore = false;
	}, 60000);

	const Sleep = (milliseconds) =>  {
	    return new Promise(resolve => setTimeout(resolve, milliseconds));
	}
	
	while (rsyncSemaphore)
	{
	    await Sleep(1000);
	}
    } 

    /* umount drives here - eventually have to mountedDrives a global variable */
    log ('SignalHandler: FIXME - umount USB drives');
    
    log ('SignalHandler: Gracefully terminating now - bye bye');
    process.exit();
}

process.on('SIGTERM', signalHandler);
process.on('SIGHUP', signalHandler);
process.on('SIGINT', signalHandler);

/* START LOOP */
log('Starting server for sync to drive');
log(`Interval is [${phpConfig.synctodrive_interval}] seconds`);

const syncLoop = () => {

    if (rsyncSemaphore) {
        log(`WARN: Sync in progress, waiting for [${phpConfig.synctodrive_interval}] seconds`);
        return;
    }

    log('Starting sync process');

    const driveInfos = getDriveInfos(parsedConfig);

    driveInfos.forEach((element) => {
        log(`Processing drive ${element.name} -> ${element.path}`);
    });

    const mountedDrives = mountDrives(driveInfos);

    mountedDrives.forEach((element) => {
        log(`Mounted drive ${element.name} -> ${element.mountpoint}`);
    });

    startSync({
        dataAbsPath: parsedConfig.dataAbsPath,
        drives: mountedDrives
    });
};

setInterval(syncLoop, phpConfig.synctodrive_interval * 1000);

/*eslint no-unreachable: "warn"*/

/* This script needs to be run from within the photobooth directory */

/* Imports */
const { execSync, spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

const log = (...optionalParams) => {
    const currentDate = new Date();
    const formattedDate = currentDate.toISOString().replace(/T/, ' ').replace(/\..+/, '');
    console.log('[' + formattedDate + `][synctodrive][DEBUG] ${process.pid}:`, ...optionalParams);
}

const error = (...optionalParams) => {
    log(...optionalParams);
    log('Shutdown');
    throw new Error('Failed to execute the code');
}

if (process.platform === 'win32') {
    error('Windows is not supported.');
}

class SyncToDrive {
    constructor() {
        this.config = this.fetchConfig();
        if (!this.config.synctodrive.enabled) {
            error('Sync to drive is disabled.');
        }

        this.rsyncProcess = null;
        this.intervalInSeconds = Number(this.config.synctodrive.interval);
        this.intervalInMilliseconds = this.intervalInSeconds * 1000;
        this.source = this.config.foldersAbs.data;
        this.destination = 'photobooth-pic-sync';
        this.driveName = this.config.synctodrive.target.toLowerCase();

        log('Sync to drive starting...');
        log('Interval: ' + this.intervalInSeconds + 's');
        log('Source: ' + this.source);
        log('Drive Name: ' + this.driveName);

        this.createProcessFile();
        this.start();
    }

    start() {
        try {
            const device = this.findDevice(this.driveName);
            this.mountDevice(device);
            this.sync(device);
        } catch (error) {
            // Log all errors, do not kill the process.
            // The script will resume and try again.
            log(error.message);
            log('Retry in ' + this.intervalInSeconds + 's');
            setTimeout(() => {
                this.start();
            },this.intervalInMilliseconds);
        }
    }

    stop() {
        try {
            const device = this.findDevice(this.driveName);
            this.unmountDevice(device);
        } catch (error) {
            // Log all errors, do not kill the process.
            // The script will resume and try again.
            log(error.message);
        }

        log('Sync to drive stopped...');
        process.exit();
    }

    sync(device) {
        if (!fs.existsSync(this.source)) {
            throw new Error('Folder ' + this.source + ' does not exist!');
        }

        log('Starting sync to USB drive ...');
        const distinationPath = path.join(device.mountpoint, this.destination);
        if (!fs.existsSync(distinationPath)) {
            log('Creating target directory' + distinationPath);
            fs.mkdirSync(distinationPath, { recursive: true });
        }

        log('Source data folder ' + this.source);
        log('Syncing to drive ' + device.path + ' -> ' + distinationPath);

        const command = [
            'rsync',
            '-a',
            '--delete-before',
            '-b',
            '--backup-dir=' + path.join(device.mountpoint, 'deleted'),
            '--ignore-existing',
            '--include=\'*.\'{jpg,chk,gif,mp4}',
            '--include=\'*/\'',
            '--exclude=\'*\'',
            '--prune-empty-dirs',
            this.source,
            path.join(device.mountpoint, this.destination)
        ].join(' ');
        log('Executing command "' + command + '"');

        this.rsyncProcess = spawn(command, {
            'shell': '/bin/bash'
        });

        this.rsyncProcess.on('error', (error) => {
            this.rsyncProcess = null;
            throw new Error(error.message);
        });
        this.rsyncProcess.on('exit', () => {
            this.rsyncProcess = null;
            log('Sync finished');
            log('Next run in ' + this.intervalInSeconds + 's');
            setTimeout(() => {
                this.start();
            },this.intervalInMilliseconds);
        });
    }

    findDevice(driveName) {
        log('Finding device ' + driveName);

        let json = {};
        try {
            //Assuming that the lsblk version supports JSON output!
            json = JSON.parse(execSync('export LC_ALL=C; lsblk -ablJO 2>/dev/null; unset LC_ALL').toString());
        } catch (error) {
            log(error.message);
            throw new Error('Could not parse the output of lsblk! Please make sure its installed and that it offers JSON output!');
        }

        if (!json || !json.blockdevices) {
            throw new Error('The output of lsblk was malformed!');
        }

        const device = json.blockdevices.find(
            (blk) => blk.subsystems.includes('usb')
                && (
                    (blk.name && driveName === blk.name.toLowerCase())
                    || (blk.kname && driveName === blk.kname.toLowerCase())
                    || (blk.path && driveName === blk.path.toLowerCase())
                    || (blk.label && driveName === blk.label.toLowerCase())
                )
        );

        if (device === undefined) {
            throw new Error('Device ' + driveName + ' was not detected');
        }

        return device;
    }

    mountDevice(device) {
        try {
            if (!this.isDeviceMounted(device)) {
                const command = `export LC_ALL=C; udisksctl mount -b ${device.path}; unset LC_ALL`
                log('Mounting device ' + device.path + ', command: "' + command + '"');
                const mountRes = execSync(command).toString();
                const mountPoint = mountRes
                    .substr(mountRes.indexOf('at') + 3)
                    .trim()
                    .replace(/[\n.]/gu, '');
                device.mountpoint = mountPoint;
            }
        } catch (error) {
            throw new Error('Unable to mount device');
        }
    
        return device;
    }

    unmountDevice(device) {
        try {
            if (this.isDeviceMounted(device)) {
                try {
                    const command = `export LC_ALL=C; udisksctl unmount -b ${device.path}; unset LC_ALL`;
                    execSync(command);
                    log('Unmounted drive ' + device.path + ', command: "' + command + '"');
                } catch (error) {
                    throw new Error('Unable to unmount device');
                }
            }
        } catch (error) {
            // Log all errors, do not kill the process.
            // The script will resume and try again.
            log(error.message);
        }
    }

    isDeviceMounted(device) {
        if (device.mountpoint === undefined || !device.mountpoint) {
            return false;
        }

        return true;
    }

    async handleSignal(signal) {
        log('Termination requested through ' + signal);

        if (this.rsyncProcess) {
            log('Rsync in progress - waiting for termination for max 60 seconds');
            setTimeout(() => {
                log('Rsync seems stale, terminate child process (SIGKILL)');
                this.rsyncProcess.kill('SIGKILL');
                this.rsyncProcess = null;
            }, 60000);
    
            const sleep = (milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds));
            // eslint-disable-next-line no-unmodified-loop-condition
            while (this.rsyncProcess) {
                // eslint-disable-next-line no-await-in-loop
                await sleep(1000);
            }
        }

        this.stop();
    }

    fetchConfig() {
        try {
            const cmd = 'bin/photobooth photobooth:config:list json';
            const output = execSync(cmd).toString();
            return JSON.parse(output);
        } catch (error) {
            error('Unable to load photobooth config', error);
        }
    }

    createProcessFile() {
        const processFilename = path.join(this.config.foldersAbs.var, 'run/synctodrive.pid')
        try {
            fs.writeFileSync(processFilename, parseInt(process.pid, 10).toString(), { flag: 'w' });
            log(`Process file created successfully: ${processFilename}`);
        } catch (error) {
            error(`Failed to create the process file: ${processFilename} - ${error.message}`);
        }
    }
}

// eslint-disable-next-line no-unused-vars
const syncToDrive = new SyncToDrive();

['SIGTERM', 'SIGHUP', 'SIGINT'].forEach((term) => process.on(term, () => { 
    syncToDrive.handleSignal(term); 
}));

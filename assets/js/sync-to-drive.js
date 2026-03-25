/*eslint no-unreachable: "warn"*/

/* This script needs to be run from within the photobooth directory */

/* Imports */
const { execSync, spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

const shellQuote = (s) => "'" + String(s).replace(/'/g, "'\\''") + "'";

const log = (...optionalParams) => {
    const currentDate = new Date();
    const formattedDate = currentDate.toISOString().replace(/T/, ' ').replace(/\..+/, '');
    console.log('[' + formattedDate + `][synctodrive][DEBUG] ${process.pid}:`, ...optionalParams);
};

const error = (...optionalParams) => {
    log(...optionalParams);
    log('Shutdown');
    throw new Error('Failed to execute the code');
};

if (process.platform === 'win32') {
    error('Windows is not supported.');
}

class SyncToDrive {
    constructor() {
        this.validateRsync();
        this.config = this.fetchConfig();
        if (!this.config.synctodrive.enabled) {
            error('Sync to drive is disabled.');
        }
        this.environment = this.fetchEnvironment();

        this.rsyncProcess = null;
        this.retryTimer = null;
        this.intervalInSeconds = Number(this.config.synctodrive.interval);
        this.intervalInMilliseconds = this.intervalInSeconds * 1000;
        this.source = this.environment.absoluteFolders.data;
        this.destination = '';
        this.driveName = this.config.synctodrive.target.toLowerCase();

        log('Sync to drive starting...');
        log('Interval: ' + this.intervalInSeconds + 's');
        log('Source: ' + this.source);
        log('Drive Name: ' + this.driveName);

        this.createProcessFile();
        this.start();
    }

    validateRsync() {
        try {
            execSync('command -v rsync', { stdio: 'ignore' });
        } catch (err) {
            log(err.message);
            error('Error: rsync is not installed. Please install it and try again.');
        }
    }

    scheduleRetry() {
        if (this.retryTimer) {
            return;
        }
        log('Next run in ' + this.intervalInSeconds + 's');
        this.retryTimer = setTimeout(() => {
            this.retryTimer = null;
            this.start();
        }, this.intervalInMilliseconds);
    }

    start() {
        try {
            const device = this.findDevice(this.driveName);
            this.mountDevice(device);
            this.sync(device);
        } catch (error) {
            log(error.message);
            this.scheduleRetry();
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

        if (!device.mountpoint) {
            throw new Error('Error: USB device is not properly mounted.');
        }

        const destinationPath = path.join(device.mountpoint, this.destination);
        if (!fs.existsSync(destinationPath)) {
            log(`Creating target directory ${destinationPath}`);
            try {
                fs.mkdirSync(destinationPath, { recursive: true });
            } catch (err) {
                throw new Error(`Error: Failed to create directory ${destinationPath} - ${err.message}`);
            }
        }

        if (!this.isWritable(destinationPath)) {
            throw new Error(`Error: Destination ${destinationPath} is not writable.`);
        }

        const command = [
            'rsync',
            '-a',
            '--delete-before',
            '-b',
            '--backup-dir=' + path.join(device.mountpoint, 'deleted'),
            '--ignore-existing',
            '--exclude=\'deleted\'',
            '--include=\'*.\'{jpg,chk,gif,mp4}',
            '--include=\'*/\'',
            '--exclude=\'*\'',
            '--prune-empty-dirs',
            this.source + '/',
            destinationPath
        ].join(' ');

        log('Validating rsync command...');
        try {
            execSync(command + ' --dry-run', { shell: '/bin/bash', stdio: 'ignore' });
            // eslint-disable-next-line no-unused-vars
        } catch (err) {
            throw new Error('Error: Rsync validation failed. Check permissions and paths.');
        }

        log('Starting sync to USB drive ...');
        log('Source data folder ' + this.source);
        log('Syncing to drive ' + device.path + ' -> ' + destinationPath);
        log(`Executing command: "${command}"`);

        this.rsyncProcess = spawn(command, {
            shell: '/bin/bash'
        });

        this.rsyncProcess.on('error', (err) => {
            this.rsyncProcess = null;
            log('Rsync process error: ' + err.message);
            this.scheduleRetry();
        });
        this.rsyncProcess.on('exit', (code) => {
            this.rsyncProcess = null;
            if (code !== 0) {
                log('Rsync exited with error code ' + code);
            } else {
                log('Sync finished successfully.');
            }
            this.scheduleRetry();
        });
    }

    findDevice(driveName) {
        log('Finding device ' + driveName);

        const requiredColumns = 'NAME,KNAME,PATH,LABEL,MOUNTPOINT,MOUNTPOINTS,SUBSYSTEMS,TYPE';
        let json = {};
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
            log(err.message);
            throw new Error(
                'Could not parse the output of lsblk! Please make sure its installed and that it offers JSON output!'
            );
        }

        if (!json || !json.blockdevices) {
            throw new Error('The output of lsblk was malformed!');
        }

        const device = json.blockdevices.find(
            (blk) =>
                blk.subsystems &&
                blk.subsystems.includes('usb') &&
                ((blk.name && driveName === blk.name.toLowerCase()) ||
                    (blk.kname && driveName === blk.kname.toLowerCase()) ||
                    (blk.path && driveName === blk.path.toLowerCase()) ||
                    (blk.label && driveName === blk.label.toLowerCase()))
        );

        if (device !== undefined) {
            // Normalize mountpoints (array) to mountpoint (string) for compatibility
            // Newer lsblk versions use "mountpoints" array instead of "mountpoint" string
            if (!device.mountpoint && Array.isArray(device.mountpoints)) {
                device.mountpoint = device.mountpoints.find((mp) => mp !== null) || null;
            }

            return device;
        }

        // Fallback: lsblk may not report labels (e.g. in LXC containers or after USB reconnect).
        // Use blkid which reads directly from the block device and always works.
        log('lsblk did not find device "' + driveName + '" by label, trying blkid fallback...');

        return this.findDeviceByBlkid(driveName, json.blockdevices);
    }

    findDeviceByBlkid(driveName, lsblkDevices) {
        let blkidOutput;
        try {
            blkidOutput = execSync('LC_ALL=C blkid 2>/dev/null').toString();
        } catch (err) {
            log('blkid fallback failed: ' + err.message);
            throw new Error('Device ' + driveName + ' was not detected (blkid fallback also failed)');
        }

        // Parse blkid output lines
        // Format: /dev/sda1: LABEL_FATBOOT="photobooth" LABEL="photobooth" UUID="..." TYPE="vfat" ...
        const lines = blkidOutput.split('\n').filter((line) => line.trim());

        for (const line of lines) {
            const labelMatch = line.match(/\bLABEL="([^"]+)"/);
            if (!labelMatch || labelMatch[1].toLowerCase() !== driveName) {
                continue;
            }

            const pathMatch = line.match(/^(\/dev\/[^:]+):/);
            if (!pathMatch) {
                continue;
            }

            const devicePath = pathMatch[1];
            log('blkid found device with label "' + driveName + '" at ' + devicePath);

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

            // Derive the short device name (e.g. "sda1" from "/dev/sda1")
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

        throw new Error('Device ' + driveName + ' was not detected');
    }

    mountDevice(device) {
        if (!device.path) {
            throw new Error('Device has no path property');
        }

        // Check if already mounted (via lsblk data or findmnt)
        const currentMountpoint = this.findCurrentMountpoint(device);
        if (currentMountpoint) {
            device.mountpoint = currentMountpoint;

            if (this.isWritable(currentMountpoint)) {
                log(`Device ${device.path} is already mounted at ${currentMountpoint} and writable`);

                return device;
            }

            // Mounted but not writable (e.g. FAT32 mounted by another user with their uid/gid)
            log(`Device ${device.path} is mounted at ${currentMountpoint} but not writable, unmounting...`);
            this.unmountDevice(device);
        }

        // Try udisksctl first
        try {
            const mountCmd = `LC_ALL=C udisksctl mount -b ${device.path}`;
            log('Mounting device ' + device.path + ' via udisksctl');
            const mountRes = execSync(mountCmd, { timeout: 30000 }).toString();
            log('udisksctl output: ' + mountRes.trim());
            // Parse "Mounted /dev/sdb1 at /media/user/label."
            const mountMatch = mountRes.match(/Mounted\s+\S+\s+at\s+(.+)/);
            if (mountMatch) {
                const mountPoint = mountMatch[1].trim().replace(/[\n.]+$/gu, '');
                if (mountPoint) {
                    device.mountpoint = mountPoint;
                    log('Mounted via udisksctl at ' + device.mountpoint);

                    return device;
                }
            }
            log('udisksctl mount returned unexpected output: ' + mountRes.trim());
        } catch (udisksErr) {
            log('udisksctl mount failed: ' + udisksErr.message);
        }

        // udisksctl may have succeeded but output was not parseable.
        // Check with findmnt if the device actually got mounted.
        try {
            const findmntRes = execSync('findmnt -n -o TARGET ' + device.path + ' 2>/dev/null')
                .toString()
                .trim();
            if (findmntRes) {
                if (this.isWritable(findmntRes)) {
                    device.mountpoint = findmntRes;
                    log('Device already mounted (detected via findmnt) at ' + device.mountpoint);

                    return device;
                }

                // Mounted but not writable, unmount and continue to fallback
                log('Device mounted at ' + findmntRes + ' but not writable, unmounting...');
                device.mountpoint = findmntRes;
                this.unmountDevice(device);
            }
            // eslint-disable-next-line no-unused-vars
        } catch (findmntErr) {
            // Device is not mounted
        }

        // Ensure the device is fully unmounted before attempting fallback mount
        try {
            const remainingMount = execSync('findmnt -n -o TARGET ' + device.path + ' 2>/dev/null')
                .toString()
                .trim();
            if (remainingMount) {
                log('Device still mounted at ' + remainingMount + ' (automount?), forcing unmount...');
                try {
                    execSync('sudo umount ' + shellQuote(device.path), { timeout: 30000 });
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
        const label = device.label || path.basename(device.path);
        const fallbackMountpoint = path.join('/media', label);

        try {
            if (!fs.existsSync(fallbackMountpoint)) {
                execSync('sudo mkdir -p ' + shellQuote(fallbackMountpoint));
            }

            // Detect filesystem type to apply appropriate mount options
            const mountOpts = this.getFATMountOptions(device.path);
            const mountCmd = mountOpts
                ? `sudo mount -o ${mountOpts} ${shellQuote(device.path)} ${shellQuote(fallbackMountpoint)}`
                : `sudo mount ${shellQuote(device.path)} ${shellQuote(fallbackMountpoint)}`;
            log('Trying fallback: ' + mountCmd);
            execSync(mountCmd, { timeout: 30000 });
            device.mountpoint = fallbackMountpoint;
            log('Mounted via direct mount at ' + device.mountpoint);

            return device;
        } catch (mountErr) {
            log('Direct mount also failed: ' + mountErr.message);
        }

        throw new Error('Unable to mount device ' + device.path);
    }

    /**
     * Get mount options for FAT/exFAT filesystems that ensure all users can read/write.
     * Returns null for non-FAT filesystems.
     */
    getFATMountOptions(devicePath) {
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
    }

    /**
     * Find the current mountpoint of a device using findmnt (most reliable).
     * Returns the mountpoint string or null.
     */
    findCurrentMountpoint(device) {
        // First check the mountpoint from lsblk data
        if (device.mountpoint) {
            try {
                execSync(`mountpoint -q '${device.mountpoint.replace(/'/g, '\'\\\'\'')}'`, { stdio: 'ignore' });

                return device.mountpoint;
                // eslint-disable-next-line no-unused-vars
            } catch (err) {
                // lsblk mountpoint is stale
            }
        }

        // Use findmnt as the authoritative source
        try {
            const mp = execSync('findmnt -n -o TARGET ' + device.path + ' 2>/dev/null')
                .toString()
                .trim();

            return mp || null;
            // eslint-disable-next-line no-unused-vars
        } catch (err) {
            return null;
        }
    }

    unmountDevice(device) {
        if (!device.path) {
            log('Device has no path, skipping unmount');

            return;
        }

        // Use findmnt to check if actually mounted (don't rely on device.mountpoint alone)
        const currentMountpoint = this.findCurrentMountpoint(device);
        if (!currentMountpoint) {
            log('Device ' + device.path + ' is not mounted, skipping unmount');

            return;
        }

        // Try udisksctl first
        try {
            const command = `LC_ALL=C udisksctl unmount -b ${device.path}`;
            execSync(command, { timeout: 30000 });
            log('Unmounted drive ' + device.path + ' via udisksctl');
            device.mountpoint = null;

            return;
        } catch (udisksErr) {
            log('udisksctl unmount failed: ' + udisksErr.message);
        }

        // Fallback: sudo umount (needed when device was mounted by another user)
        try {
            execSync('sudo umount ' + shellQuote(device.path), { timeout: 30000 });
            log('Unmounted drive ' + device.path + ' via sudo umount');
            device.mountpoint = null;
        } catch (umountErr) {
            log('sudo umount also failed: ' + umountErr.message);
        }
    }

    async handleSignal(signal) {
        log('Termination requested through ' + signal);

        if (this.rsyncProcess) {
            log('Rsync in progress - waiting for termination for max 60 seconds');
            setTimeout(() => {
                log('Rsync seems stale, terminate child process (SIGKILL)');
                if (this.rsyncProcess) {
                    this.rsyncProcess.kill('SIGKILL');
                    this.rsyncProcess = null;
                }
            }, 60000);

            const sleep = (milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds));

            while (this.rsyncProcess) {
                await sleep(1000);
            }
        }

        this.stop();
    }

    isWritable(directory) {
        try {
            fs.accessSync(directory, fs.constants.W_OK);
            return true;
            // eslint-disable-next-line no-unused-vars
        } catch (err) {
            return false;
        }
    }

    fetchConfig() {
        try {
            const cmd = 'bin/photobooth photobooth:config:list json';
            const output = execSync(cmd).toString();
            return JSON.parse(output);
        } catch (err) {
            error('Unable to load photobooth config', err);
        }
    }

    fetchEnvironment() {
        try {
            const cmd = 'bin/photobooth photobooth:environment:list json';
            const output = execSync(cmd).toString();
            return JSON.parse(output);
        } catch (err) {
            error('Unable to load photobooth environment', err);
        }
    }

    createProcessFile() {
        const processFilename = path.join(this.environment.absoluteFolders.var, 'run/synctodrive.pid');
        try {
            fs.writeFileSync(processFilename, parseInt(process.pid, 10).toString(), { flag: 'w' });
            log(`Process file created successfully: ${processFilename}`);
        } catch (err) {
            error(`Failed to create the process file: ${processFilename} - ${err.message}`);
        }
    }
}

const syncToDrive = new SyncToDrive();

['SIGTERM', 'SIGHUP', 'SIGINT'].forEach((term) =>
    process.on(term, () => {
        syncToDrive.handleSignal(term);
    })
);

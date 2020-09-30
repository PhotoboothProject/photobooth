#!/usr/bin/env node
/* eslint-disable node/shebang */

const {execSync, spawn} = require('child_process');
const fs = require('fs');
const path = require('path');

//This script needs to be run from within the photobooth directory
const BASE_DIR = __dirname;
const CONFIG_DIR_NAME = 'config';
const CONFIG_FILE_NAME = 'drivename.conf';
const DATA_DIR_NAME = 'data';
const PLATFORM = process.platform;

const parseConfig = () => {
  const confPath = path.join(BASE_DIR, CONFIG_DIR_NAME, CONFIG_FILE_NAME);

  if (!fs.existsSync(confPath)) {
    console.log(`ERROR: Couldn't find the config file: ${confPath}`);

    return null;
  }
  //Should be checked, becuase the behavior of fs.readFileSync() is platform-specific https://nodejs.org/api/fs.html#fs_fs_readfile_path_options_callback
  if (fs.lstatSync(confPath).isDirectory()) {
    console.log(`ERROR: ${confPath} is a directory!`);

    return null;
  }

  const fileContent = fs.readFileSync(confPath, {encoding: 'utf8'});
  const split = fileContent.split('\n').map((line) => line.replace('\r', ''));

  return split.reduce((arr, line) => {
    if (line && !line.startsWith('#')) {
      const trimmed = line.trim();
      if (trimmed.includes('#')) {
        arr.push(trimmed.substr(0, trimmed.indexOf('#')));
      } else {
        arr.push(trimmed);
      }
    }

    return arr;
  }, []);
};

const getDriveInfos = (drives) => {
  let json = null;

  try {
    //Assuming that the lsblk version supports JSON output!
    const output = execSync('export LC_ALL=C; lsblk -ablJO 2>/dev/null; unset LC_ALL').toString();
    json = JSON.parse(output);
  } catch (err) {
    console.log(
      'ERROR: Could not parse the output of lsblk! Please make sure its installed and that it offers JSON output!'
    );

    return null;
  }

  if (!json || !json.blockdevices) {
    console.log('ERROR: The output of lsblk was malformed!');

    return null;
  }

  return json.blockdevices.reduce((arr, blk) => {
    if (
      drives.some((drive) => drive === blk.name || drive === blk.kname || drive === blk.path || drive === blk.label)
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
          .replace('\n');

        drive.mountpoint = mountPoint;
      } catch (error) {
        console.log(`ERROR: Count mount ${drive.path}`);
      }
    }

    if (drive.mountpoint) {
      result.push(drive);
    }
  }

  return result;
};

const startSync = (drives) => {
  const dataPath = path.join(BASE_DIR, DATA_DIR_NAME);

  if (!fs.existsSync(dataPath)) {
    console.log(`ERROR: Folder ${dataPath} doesn't exist!`);

    return;
  }

  for (const drive of drives) {
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
            `--backup-dir=${path.join(drive.mountpoint, 'backup')}`,
            dataPath,
            path.join(drive.mountpoint)
          ].join(' ');
        default:
          return null;
      }
    })();

    if (!cmd) {
      console.log('ERROR: No command for syncing!');

      return;
    }

    console.log('Executing:', cmd);

    try {
      const spwndCmd = spawn(cmd, {
        detached: true,
        shell: true,
        stdio: 'ignore'
      });
      spwndCmd.unref();
    } catch (err) {
      console.log('ERROR! Count start sync!');
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
  console.error('Windows is currently not supported!');

  return;
}

if (isProcessRunning('rsync')) {
  console.log('WARN: Sync in progress! Aborting!');

  return;
}

const parsedConfig = parseConfig();

if (!parsedConfig) {
  return;
}

const driveInfos = getDriveInfos(parsedConfig);
const mountedDrives = mountDrives(driveInfos);
startSync(mountedDrives);

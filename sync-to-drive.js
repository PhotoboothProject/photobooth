#!/usr/bin/env node
/* eslint-disable node/shebang */

const {execSync, spawn} = require('child_process');
const fs = require('fs');
const path = require('path');

//This script needs to be run from within the photobooth directory
const API_DIR_NAME = 'api';
const API_FILE_NAME = 'config.php';
const PLATFORM = process.platform;

const getConfigFromPHP = () => {
  const cmd = `cd ${API_DIR_NAME} && php ./${API_FILE_NAME}`;

  try {
    const stdout = execSync(cmd).toString();

    return JSON.parse(stdout.slice(stdout.indexOf('{'), -1));
  } catch (err) {
    console.log('ERROR: Couldnt get config from PHP', err);
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
      drives: [...config.sync_script_targets]
    };
  } catch (err) {
    console.log('ERROR: Couldt parse config!', err);
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

const startSync = ({dataAbsPath, drives}) => {
  if (!fs.existsSync(dataAbsPath)) {
    console.log(`ERROR: Folder ${dataAbsPath} doesn't exist!`);

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
            `--backup-dir=${path.join(drive.mountpoint, 'deleted')}`,
            dataAbsPath,
            path.join(drive.mountpoint, 'sync')
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
      console.log('ERROR! Couldnt start sync!');
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

const phpConfig = getConfigFromPHP();

if (!phpConfig) {
  return;
} else if (!phpConfig.sync_script_enabled) {
  console.log('WARN: Sync script was disabled by config! Aborting!');

  return;
}

const parsedConfig = parseConfig(phpConfig);
const driveInfos = getDriveInfos(parsedConfig);
const mountedDrives = mountDrives(driveInfos);
startSync({
  dataAbsPath: parsedConfig.dataAbsPath,
  drives: mountedDrives
});

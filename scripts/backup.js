/* eslint no-sync: 0 */
/* eslint-env node */
require('colors').setTheme({
    verbose: 'cyan',
    warn: 'yellow',
    error: 'red',
});

const path = require('path');
const fs = require('fs');
const archiver = require('archiver');
const currentDate = new Date().toISOString().replace(/[:.]/g, '-'); // Format: yyyy-mm-dd-hh-mm-ss
const archiveDir = path.join(__dirname, '..', 'archives');
const basename = `${currentDate}-backup`;

if (!fs.existsSync(archiveDir)) {
    fs.mkdirSync(archiveDir);
}

createArchive(basename + '.zip', archiver('zip', {
    zlib: {
        level: 9
    }
}));

function createArchive(fileName, archive) {
    const filePath = path.normalize(path.join(archiveDir, fileName));
    const output = fs.createWriteStream(filePath);
    const configFile = 'config/my.config.inc.php';

    archive.on('warning', function (err) {
        if (err.code === 'ENOENT') {
            console.warn('Archive warning: '.warn, err);
        } else {
            throw err;
        }
    });

    archive.on('error', function (err) {
        throw err;
    });

    archive.pipe(output);

    archive.directory('private');
    if (fs.existsSync(configFile)) {
        archive.file(configFile);
    } else {
        console.warn(`Warning: ${configFile} does not exist, ignoring`.warn);
    }

    output.on('close', function () {
        console.log(`Wrote ${archive.pointer()} bytes to ${fileName}`.verbose);
    });

    archive.finalize();
}

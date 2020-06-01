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
const gitVersion = require('git-tag-version')({
    uniqueSnapshot: true,
});
const package = require('../package.json');
const archiveDir = path.join(__dirname, '..', 'archives');
const basename = `${package.name}-${gitVersion}`;

if (!fs.existsSync(archiveDir)) {
    fs.mkdirSync(archiveDir);
}

createArchive(basename + '.tar.gz', archiver('tar', {
    gzip: true,
}));

createArchive(basename + '.zip', archiver('zip', {
    zlib: {
        level: 9
    }
}));

function createArchive(fileName, archive) {
    const filePath = path.normalize(path.join(archiveDir, fileName));
    const output = fs.createWriteStream(filePath);

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

    archive.directory('admin');
    archive.directory('api');
    archive.directory('lib');
    archive.directory('login');
    archive.directory('manual');
    archive.directory('resources');
    archive.directory('slideshow');
    archive.directory('template');
    archive.directory('vendor');
    archive.file('button.py');
    archive.file('config/.htaccess');
    archive.file('config/config.inc.php');
    archive.file('chromakeying.php');
    archive.file('gallery.php');
    archive.file('index.php');
    archive.file('LICENSE');
    archive.file('package.json');
    archive.file('phpinfo.php');
    archive.file('README.md');
    archive.file('update-booth.sh');
    archive.file('node_modules/normalize.css/LICENSE.md');
    archive.file('node_modules/normalize.css/normalize.css');
    archive.directory('node_modules/font-awesome/');
    archive.file('node_modules/photoswipe/LICENSE');
    archive.directory('node_modules/photoswipe/dist/');
    archive.file('node_modules/jquery/LICENSE.txt');
    archive.directory('node_modules/jquery/dist/');
    archive.file('node_modules/marvinj/LICENSE');
    archive.directory('node_modules/marvinj/marvinj/release/');

    output.on('close', function () {
        console.log(`Wrote ${archive.pointer()} bytes to ${fileName}`.verbose);
    });

    archive.finalize();
}

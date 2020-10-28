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
    archive.file('photobooth.desktop');
    archive.file('phpinfo.php');
    archive.file('README.md');
    archive.file('update-booth.sh');
    archive.directory('node_modules/@andreasremdt/simple-translator/');
    archive.directory('node_modules/font-awesome/');
    archive.file('node_modules/github-markdown-css/github-markdown.css');
    archive.file('node_modules/github-markdown-css/license');
    archive.file('node_modules/jquery/LICENSE.txt');
    archive.directory('node_modules/jquery/dist/');
    archive.file('node_modules/marvinj/LICENSE');
    archive.directory('node_modules/marvinj/marvinj/release/');
    archive.file('node_modules/normalize.css/LICENSE.md');
    archive.file('node_modules/normalize.css/normalize.css');
    archive.file('node_modules/whatwg-fetch/LICENSE');
    archive.file('node_modules/whatwg-fetch/dist/fetch.umd.js');

    archive.directory('node_modules/accepts');
    archive.directory('node_modules/after');
    archive.directory('node_modules/arraybuffer.slice');
    archive.directory('node_modules/async-limiter');
    archive.directory('node_modules/backo2');
    archive.directory('node_modules/base64-arraybuffer');
    archive.directory('node_modules/base64-arraybuffer');
    archive.directory('node_modules/base64id');
    archive.directory('node_modules/better-assert');
    archive.directory('node_modules/bindings');
    archive.directory('node_modules/blob');
    archive.directory('node_modules/callsite');
    archive.directory('node_modules/component-bind');
    archive.directory('node_modules/component-emitter');
    archive.directory('node_modules/component-inherit');
    archive.directory('node_modules/cookie');
    archive.directory('node_modules/debug');
    archive.directory('node_modules/engine.io');
    archive.directory('node_modules/engine.io-client');
    archive.directory('node_modules/engine.io-parser');
    archive.directory('node_modules/has-binary2');
    archive.directory('node_modules/has-cors');
    archive.directory('node_modules/indexof');
    archive.directory('node_modules/isarray');
    archive.directory('node_modules/mime-db');
    archive.directory('node_modules/mime-types');
    archive.directory('node_modules/ms');
    archive.directory('node_modules/nan');
    archive.directory('node_modules/negotiator');
    archive.directory('node_modules/object-component');
    archive.directory('node_modules/parseqs');
    archive.directory('node_modules/parseuri');
    archive.directory('node_modules/rpio');
    archive.directory('node_modules/socket.io');
    archive.directory('node_modules/socket.io-adapter');
    archive.directory('node_modules/socket.io-client');
    archive.directory('node_modules/socket.io-parser');
    archive.directory('node_modules/to-array');
    archive.directory('node_modules/file-uri-to-path');
    archive.directory('node_modules/ws');
    archive.directory('node_modules/xmlhttprequest-ssl');
    archive.directory('node_modules/yeast');
    
    output.on('close', function () {
        console.log(`Wrote ${archive.pointer()} bytes to ${fileName}`.verbose);
    });

    archive.finalize();
}

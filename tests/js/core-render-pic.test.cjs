const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { test } = require('node:test');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../../assets/js/core.js'), 'utf8');
// Run the real result handler without initializing camera and hardware listeners.
const renderPic = source.slice(
    source.indexOf('    api.renderPic = function'),
    source.indexOf('    api.addImage = function')
);

function render(filename, filter = 'plain') {
    const images = [];
    const galleryImages = [];
    function element() {
        return {
            styles: {},
            classes: new Set(['stage--active', 'showBackgroundImage']),
            find() {
                return this;
            },
            off() {
                return this;
            },
            on() {
                return this;
            },
            attr() {
                return this;
            },
            css(name, value) {
                Object.assign(this.styles, typeof name === 'object' ? name : { [name]: value });
                return this;
            },
            addClass(name) {
                this.classes.add(name);
            },
            removeClass(names) {
                names.split(' ').forEach((name) => this.classes.delete(name));
            },
            hasClass(name) {
                return this.classes.has(name);
            }
        };
    }
    const resultPage = element();
    resultPage.classes.clear();
    const loader = element();
    const api = {
        addImage: (name, size) => galleryImages.push({ name, width: size.width, height: size.height }),
        resetTimeOut() {},
        screensaver: { resetTimer() {} }
    };
    vm.runInNewContext(renderPic, {
        api,
        resultPage,
        loader,
        startPage: element(),
        buttonPrint: element(),
        filternav: element(),
        config: { print: {}, filters: {}, qr: {}, commands: {}, preview: {} },
        environment: { publicFolders: { api: '/api', images: '/images' } },
        imgFilter: filter,
        photoboothTools: { isVideoFile: (name) => name.endsWith('.mp4'), console: { logDev() {} } },
        remoteBuzzerClient: { inProgress() {} },
        rotaryController: { focusSet() {} },
        Image: class {
            constructor() {
                this.naturalWidth = 1920;
                this.naturalHeight = 1080;
                images.push(this);
            }
        }
    });
    api.renderPic(filename, [filename]);
    return { image: images[0], galleryImages, resultPage, loader };
}

test('video preloads a valid QR URL and reveals the result after loading', () => {
    const result = render('clip.mp4', 'sepia');
    assert.equal(result.image.src, '/api/qrcode.php?filename=clip.mp4');
    result.image.onload();
    assert.equal(result.resultPage.styles['--stage-background-image'], `url(${result.image.src})`);
    assert.equal(result.resultPage.classes.has('stage--active'), true);
    assert.equal(result.loader.classes.has('stage--active'), false);
    assert.equal(result.galleryImages.length, 0);
});

test('photos preload and display the same filtered URL and reuse decoded dimensions', () => {
    for (const filter of ['plain', 'sepia']) {
        const result = render('photo.jpg', filter);
        assert.equal(result.image.src, `/images/photo.jpg?filter=${filter}`);
        result.image.onload();
        assert.equal(result.resultPage.styles['--stage-background-image'], `url(${result.image.src})`);
        assert.deepEqual(result.galleryImages, [{ name: 'photo.jpg', width: 1920, height: 1080 }]);
        assert.equal(result.loader.classes.has('stage--active'), false);
    }
});

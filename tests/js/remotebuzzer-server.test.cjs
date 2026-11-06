const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { test } = require('node:test');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../../assets/js/remotebuzzer-server.js'), 'utf8');
// Exercise the real handlers without starting hardware listeners or writing a PID file.
const handlers = source.slice(source.indexOf('function armTrigger()'), source.indexOf("const http = require('http')"));

function createServer() {
    const events = [];
    const ioServer = { engine: { clientsCount: 0 }, emit: (...args) => events.push(args) };
    const context = vm.createContext({
        URL,
        config: {
            webserver: { ip: '127.0.0.1' },
            remotebuzzer: { usebuttons: true, picturebutton: true, move2usb: 'enabled' },
            picture: { enabled: true }
        },
        ioServer,
        log() {},
        move2usbAction() {},
        setTimeout: () => 1,
        clearTimeout() {}
    });
    vm.runInContext(
        'let triggerArmed = true, rearmTimer = null, collageInProgress = false; const REARM_TIMEOUT_MS = 60000;\n' +
            handlers +
            '\nthis.requestListener = requestListener;',
        context
    );
    return {
        events,
        ioServer,
        request(url) {
            const response = {};
            context.requestListener(
                { url },
                {
                    setHeader() {},
                    writeHead(status) {
                        response.status = status;
                    },
                    end(body) {
                        response.body = body;
                    }
                }
            );
            return response;
        }
    };
}

test('capture and print reject missing clients without broadcasting', () => {
    const server = createServer();
    for (const action of ['picture', 'collage', 'custom', 'video', 'print']) {
        const response = server.request('/commands/start-' + action);
        assert.equal(response.status, 503);
        assert.match(response.body, /No Photobooth client connected/);
    }
    assert.equal(server.events.length, 0);
});

test('retry after client connects works immediately and preserves busy lock', () => {
    const server = createServer();
    assert.equal(server.request('/commands/start-picture').status, 503);
    assert.equal(server.request('/commands/start-picture').status, 503);
    server.ioServer.engine.clientsCount = 1;
    assert.equal(server.request('/commands/start-picture').body, 'TAKE PHOTO TRIGGERED.');
    assert.equal(server.events.length, 1);
    assert.equal(server.events[0][1], 'start-picture');
    assert.equal(server.request('/commands/start-picture').body, 'ALREADY TRIGGERED AN ACTION');
    assert.equal(server.events.length, 1);
});

test('unrelated endpoints keep their existing response without clients', () => {
    const server = createServer();
    assert.equal(server.request('/unknown').status, 404);
    assert.equal(server.request('/commands/start-move2usb').status, 200);
    assert.equal(server.events.length, 0);
});

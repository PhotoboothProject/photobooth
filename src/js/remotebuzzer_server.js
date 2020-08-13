/* VARIABLES */
const myPid = process.pid;
let mTimeTrigger = 0,
    collageInProgress = false;

let triggerArmed = true,
    buttonIsPressed = false;

const rpio = require('rpio');

/* HANDLE EXCEPTIONS */

process.on('uncaughtException', function (err) {
    console.log('socket.io server [', myPid, ']: Error: ', err.message);
    fs.unlink(pidFilename, function (error) {
        if (error) {
            console.log('socket.io server [', myPid, ']: Error deleting PID file ', error.message);
        }
    });
    console.log('socket.io server [', myPid, ']: Exiting');

    /* got to exit now and here - can not recover from error */

    process.exit();
});

/* SOURCE PHOTOBOOTH CONFIG */

const {execSync} = require('child_process');
const stdout = execSync('cd api && php ./config.php').toString();
const config = JSON.parse(stdout.slice(stdout.indexOf('{'), -1));

/* WRITE PROCESS PID FILE */

const pidFilename = config.folders.tmp + '/remotebuzzer_server.pid';

const fs = require('fs');

fs.writeFile(pidFilename, myPid, function (err) {
    if (err) {
        throw new Error('Unable to write PID file [' + pidFilename + '] - ' + err.message);
    }

    console.log('socket.io server [', myPid, ']: PID file created [', pidFilename, ']');
});

/* START WEBSOCKET SERVER */

console.log(
    'socket.io server [',
    myPid,
    ']: Requested to start on port ',
    config.remotebuzzer_port,
    ', Pin ',
    config.remotebuzzer_pin
);

function photoboothAction(type) {
    switch (type) {
        case 'picture':
            triggerArmed = false;
            collageInProgress = false;
            console.log(
                'socket.io server [',
                myPid,
                ']: Photobooth trigger picture : [ photobooth-socket ] => [ All Clients ]: command [ picture ]'
            );
            ioServer.emit('photobooth-socket', 'start-picture');
            break;

        case 'collage':
            triggerArmed = false;
            collageInProgress = true;
            console.log(
                'socket.io server [',
                myPid,
                ']: Photobooth trigger collage : [ photobooth-socket ]  => [ All Clients ]: command [ collage ]'
            );
            ioServer.emit('photobooth-socket', 'start-collage');
            break;

        case 'completed':
            triggerArmed = true;
            collageInProgress = false;
            console.log(
                'socket.io server [',
                myPid,
                ']: Photobooth activity completed : [ photobooth-socket ] => [ All Clients ]: command [ completed ]'
            );
            ioServer.emit('photobooth-socket', 'completed');
            break;

        case 'reset':
            photoboothAction('completed');
            break;

        default:
            console.log('socket.io server [', myPid, ']: Photobooth action [', type, '] not implemented - ignoring');
            break;
    }
}

const ioServer = require('socket.io')(config.remotebuzzer_port);

ioServer.on('connection', function (client) {
    console.log('socket.io server [', myPid, ']: New client connected - ID', client.id);
    client.on('photobooth-socket', function (data) {
        console.log(
            'socket.io server [',
            myPid,
            ']: Data from client ID ',
            client.id,
            ': [ photobooth-socket ] =>  [',
            data,
            ']'
        );

        /* COMMANDS RECEIVED */

        switch (data) {
            case 'completed':
                photoboothAction('completed');
                break;

            case 'in progress':
                triggerArmed = false;
                break;

            case 'start-picture':
                photoboothAction('picture');
                break;

            case 'start-collage':
                photoboothAction('collage');
                break;

            case 'collage-wait-for-next':
                triggerArmed = true;
                break;

            default:
                console.log('socket.io server [', myPid, ']: Received unknown command [', data, '] - ignoring');
                break;
        }
    });
    client.on('disconnect', function () {
        console.log('socket.io server [', myPid, ']: Client disconnected - ID ', client.id);

        if (ioServer.engine.clientsCount == 0) {
            console.log('socket.io server [', myPid, ']: No more clients connected - removing lock and arming trigger');
            triggerArmed = true;
            collageInProgress = false;
        }
    });
});
console.log('socket.io server [', myPid, ']: socket.io server started');

/* LISTEN TO GPIO STATUS https://www.npmjs.com/package/rpio */

if (config.remotebuzzer_pin >= 1 && config.remotebuzzer_pin <= 40) {
    const pollcb = function pollcb(pin) {
        /* if there is some activity in progress ignore GPIO pin for now */
        if (!triggerArmed) {
            return;
        }

        if (rpio.read(pin)) {
            if (!buttonIsPressed) {
                return;
            }
            buttonIsPressed = false;

            /* Button released - action following upwards flank transition of GPIO pin 1 -> 0 */
            const dTimeTrigger = Date.now('millis') - mTimeTrigger;

            if (dTimeTrigger > 10000) {
                /* Too long button press - timeout - reset server state machine */
                console.log(
                    'socket.io server [',
                    myPid,
                    ']: Reset server state machine - Time since button press [ms] ',
                    dTimeTrigger
                );
                photoboothAction('reset');
            } else if (
                !config.use_collage ||
                (dTimeTrigger <= config.remotebuzzer_collagetime * 1000 && !collageInProgress)
            ) {
                /* Picture */
                console.log(
                    'socket.io server [',
                    myPid,
                    ']: GPIO button released - normal press - time since button press [ms] ',
                    dTimeTrigger
                );
                photoboothAction('picture');
            } else {
                /* Collage */
                console.log(
                    'socket.io server [',
                    myPid,
                    ']: GPIO button released - long press - time since button press [ms] ',
                    dTimeTrigger
                );
                photoboothAction('collage');
            }
        } else {
            /* Button pressed - prepare state machine */

            if (buttonIsPressed) {
                return;
            }
            buttonIsPressed = true;

            console.log('socket.io server [', myPid, ']: GPIO button pressed on pin P', pin);
            mTimeTrigger = Date.now('millis');
        }

        /* Hysteresis to filter false positives */
        rpio.msleep(200);
    };

    console.log('socket.io server [', myPid, ']: Connecting to Raspberry pin P', config.remotebuzzer_pin);
    rpio.open(config.remotebuzzer_pin, rpio.INPUT, rpio.PULL_UP);
    rpio.poll(config.remotebuzzer_pin, pollcb, rpio.POLL_BOTH);
}

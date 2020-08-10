'use strict';

/* PARSE COMMAND LINE */
var myArgs = process.argv.slice(2);

var socketPort = myArgs[0],
    pinNum = myArgs[1],
    $myPIDFileFolder = myArgs[2];

var myPid = process.pid,
    mTimeTrigger = 0,
    collageInProgress = false,
    ListOfClientIDs = [];

var trigger_armed = true,
    buttonDown = false;

console.log('socket.io server [', myPid, ']: Requested to start on port ', socketPort, ', Pin ', pinNum);

/* WRITE PROCESS PID FILE */

var fs = require('fs');

var $filename = $myPIDFileFolder + '/remotebuzzer_server.pid';
fs.writeFile($filename, myPid, function (err) {
    if (err) {
        console.log('socket.io server [', myPid, ']: Unable to write PID file ', $filename, ' Error:', err.message);
        process.exit();
    } else {
        console.log('socket.io server [', myPid, ']: PID file created ', $filename);
    }
});
/* HANDLE EXCEPTIONS */

process.on('uncaughtException', function (err, origin) {
    console.log('socket.io server [', myPid, ']: uncaught error: ', err.message);
    fs.unlink($filename, function (error) {
        if (error) {
            console.log('socket.io server [', myPid, ']: Error while trying to delete PID file ', error.message);
        } else {
            console.log('socket.io server [', myPid, ']: Removed PID file ', $filename);
        }
    });
    console.log('socket.io server [', myPid, ']: Exiting process');
    process.exit();
});
/* START WEBSOCKET SERVER */

var io_server = require('socket.io')(socketPort);

io_server.on('connection', function (client) {
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
                trigger_armed = true;
                collageInProgress = false;
                ListOfClientIDs.splice(ListOfClientIDs.indexOf(client.id), 1);
                if (!ListOfClientIDs.length) io_server.emit('photobooth-socket', 'completed');
                break;

            case 'in progress':
                trigger_armed = false;
                if (ListOfClientIDs.indexOf(client.id) < 0) ListOfClientIDs.push(client.id);
                break;

            case 'start-picture':
                trigger_armed = false;
                io_server.emit('photobooth-socket', 'start-picture');
                break;

            case 'start-collage':
                trigger_armed = false;
                io_server.emit('photobooth-socket', 'start-collage');
                break;

            case 'collage-wait-for-next':
                console.log('socket.io server [', myPid, ']: COLLAGE - received event "collage-wait-for-next"');
                trigger_armed = true;
                break;
        }
    });
    client.on('disconnect', function () {
        var pos = ListOfClientIDs.indexOf(client.id);

        if (pos != -1) {
            ListOfClientIDs.splice(pos, 1);
            console.log(
                'socket.io server [',
                myPid,
                ']: Active client disconnected - ',
                client.id,
                ' - removed from array pos ',
                pos,
                ' - remaining ',
                ListOfClientIDs
            );

            if (!ListOfClientIDs.length) {
                console.log(
                    'socket.io server [',
                    myPid,
                    ']: No more active clients connected - removing lock and arming trigger'
                );
                trigger_armed = true;
                collageInProgress = false;
            }
        } else {
            console.log('socket.io server [', myPid, ']: Inactive client disconnected - ID ', client.id);
        }
    });
});
console.log('socket.io server [', myPid, ']: socket.io server started');
/* LISTEN TO GPIO STATUS https://www.npmjs.com/package/rpio */

if (pinNum >= 1 && pinNum <= 40) {
    var pollcb = function pollcb(pin) {
        /* Hysteresis to filter false positives */
        rpio.msleep(20);
        if (!trigger_armed) return;

        if (rpio.read(pin)) {
            /* Button released */
            var dTimeTrigger = Date.now('millis') - mTimeTrigger;
            if (!buttonDown) return;

            if (dTimeTrigger > 10000) {
                /* reset server state machine */
                console.log(
                    'socket.io server [',
                    myPid,
                    ']: Reset server state machine - Time since button press [ms] ',
                    dTimeTrigger
                );
                trigger_armed = true;
                collageInProgress = false;
                return;
            } else if (dTimeTrigger <= 2000 && !collageInProgress) {
                /* Picture */
                console.log(
                    'socket.io server [',
                    myPid,
                    ']: Button released - Normal  Press - Time since button press [ms] ',
                    dTimeTrigger
                );
                console.log('socket.io server [', myPid, ']: THRILL PICTURE - Notify all clients');
                io_server.emit('photobooth-socket', 'start-picture');
                trigger_armed = false;
            } else {
                /* Collage */
                console.log(
                    'socket.io server [',
                    myPid,
                    ']: Button released - Long Press - Time since button press [ms] ',
                    dTimeTrigger
                );
                console.log('socket.io server [', myPid, ']: THRILL COLLAGE - Notify all clients');
                io_server.emit('photobooth-socket', 'start-collage');
                collageInProgress = true;
                trigger_armed = false;
            }

            buttonDown = false;
            return;
        } else {
            /* Button pressed - notify clients */
            if (trigger_armed) {
                console.log('socket.io server [', myPid, ']: Button pressed on pin P', pin);
                mTimeTrigger = Date.now('millis');
                buttonDown = true;
            }
        }
    };

    console.log('socket.io server [', myPid, ']: Connecting to Raspberry pin P', pinNum);

    var rpio = require('rpio');

    buttonDown = false;
    rpio.open(pinNum, rpio.INPUT, rpio.PULL_UP);
    rpio.poll(pinNum, pollcb, rpio.POLL_BOTH);
}

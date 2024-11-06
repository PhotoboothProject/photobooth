const fs = require('fs');

/* VARIABLES */
let collageInProgress = false,
    triggerArmed = true,
    photolight,
    pictureled,
    collageled,
    shutdownled,
    rebootled,
    printled,
    videoled,
    customled,
    move2usbled,
    copySucess = false;

const SYNC_DESTINATION_DIR = 'photobooth-pic-sync';
let rotaryClkPin, rotaryDtPin;
const { execSync, spawnSync } = require('child_process');
const path = require('path');
const { pid: PID, platform: PLATFORM } = process;

/* LOGGING FUNCTION */
const log = function (...optionalParams) {
    const currentDate = new Date();
    const formattedDate = currentDate.toISOString().replace(/T/, ' ').replace(/\..+/, '');
    console.log('[' + formattedDate + `][remotebuzzer][DEBUG] ${PID}:`, ...optionalParams);
};

/* SOURCE PHOTOBOOTH CONFIG */
/*const {execSync} = require('child_process');*/
const cmdConfig = 'bin/photobooth photobooth:config:list json';
const stdoutConfig = execSync(cmdConfig).toString();
const config = JSON.parse(stdoutConfig);

const cmdEnvironmentg = 'bin/photobooth photobooth:environment:list json';
const stdoutEnvironment = execSync(cmdEnvironmentg).toString();
const environment = JSON.parse(stdoutEnvironment);

/* WRITE PROCESS PID FILE */
const writePIDFile = (filename) => {
    try {
        fs.writeFileSync(filename, parseInt(PID, 10).toString(), { flag: 'w' });
        log(`PID file created [${filename}]`);
    } catch (err) {
        throw new Error(`Unable to write PID file [${filename}] - ${err.message}`);
    }
};

const pidFilename = path.join(environment.absoluteFolders.var, 'run/remotebuzzer.pid');
writePIDFile(pidFilename);

/* HANDLE EXCEPTIONS */
process.on('uncaughtException', function (err) {
    log('Error: ', err.message);
    fs.unlink(pidFilename, function (error) {
        if (error) {
            log('Error deleting PID file ', error.message);
        }
    });
    log('Exiting');

    /* got to exit now and here - can not recover from error */
    process.exit();
});

/* START HTTP & WEBSOCKET SERVER */
const baseUrl = 'http://' + config.remotebuzzer.serverip + ':' + config.remotebuzzer.port;
log('Server starting on ' + baseUrl);

function photoboothAction(type) {
    switch (type) {
        case 'picture':
            triggerArmed = false;
            collageInProgress = false;
            log('Photobooth trigger PICTURE : [ photobooth-socket ] => [ All Clients ]: command [ picture ]');
            if (config.remotebuzzer.useleds && config.remotebuzzer.pictureled) {
                pictureled.writeSync(1);
            }
            if (config.remotebuzzer.useleds && config.remotebuzzer.photolight) {
                photolight.writeSync(1);
            }
            ioServer.emit('photobooth-socket', 'start-picture');
            break;

        case 'custom':
            triggerArmed = false;
            collageInProgress = false;
            log('Photobooth trigger CUSTOM : [ photobooth-socket ]  => [ All Clients ]: command [ custom ]');
            if (config.remotebuzzer.useleds && config.remotebuzzer.customled) {
                customled.writeSync(1);
            }
            if (config.remotebuzzer.useleds && config.remotebuzzer.photolight) {
                photolight.writeSync(1);
            }
            ioServer.emit('photobooth-socket', 'start-custom');
            break;

        case 'video':
            triggerArmed = false;
            collageInProgress = false;
            log('Photobooth trigger VIDEO : [ photobooth-socket ]  => [ All Clients ]: command [ video ]');
            if (config.remotebuzzer.useleds && config.remotebuzzer.videoled) {
                videoled.writeSync(1);
            }
            if (config.remotebuzzer.useleds && config.remotebuzzer.photolight) {
                photolight.writeSync(1);
            }
            ioServer.emit('photobooth-socket', 'start-video');
            break;

        case 'move2usb':
            triggerArmed = false;
            collageInProgress = false;
            log('Photobooth trigger MOVE2USB : [ photobooth-socket ]  => [ All Clients ]: command [ move2usb ]');
            if (config.remotebuzzer.useleds && config.remotebuzzer.move2usbled) {
                move2usbled.writeSync(1);
            }
            move2usbAction();
            break;

        case 'collage':
            triggerArmed = false;
            collageInProgress = true;
            log('Photobooth trigger COLLAGE : [ photobooth-socket ]  => [ All Clients ]: command [ collage ]');
            if (config.remotebuzzer.useleds && config.remotebuzzer.collageled) {
                collageled.writeSync(1);
            }
            if (config.remotebuzzer.useleds && config.remotebuzzer.photolight) {
                photolight.writeSync(1);
            }
            ioServer.emit('photobooth-socket', 'start-collage');
            break;

        case 'collage-next':
            log('Photobooth COLLAGE : [ photobooth-socket ]  => [ All Clients ]: command [ collage-next ]');
            ioServer.emit('photobooth-socket', 'collage-next');
            break;

        case 'completed':
            triggerArmed = true;
            collageInProgress = false;
            log('Photobooth activity completed : [ photobooth-socket ] => [ All Clients ]: command [ completed ]');
            if (config.remotebuzzer.useleds && config.remotebuzzer.pictureled) {
                pictureled.writeSync(0);
            }
            if (config.remotebuzzer.useleds && config.remotebuzzer.photolight) {
                photolight.writeSync(0);
            }
            if (config.remotebuzzer.useleds && config.remotebuzzer.move2usbled) {
                move2usbled.writeSync(0);
            }
            if (config.remotebuzzer.useleds && config.remotebuzzer.collageled) {
                collageled.writeSync(0);
            }
            if (config.remotebuzzer.useleds && config.remotebuzzer.videoled) {
                videoled.writeSync(0);
            }
            if (config.remotebuzzer.useleds && config.remotebuzzer.customled) {
                customled.writeSync(0);
            }
            if (config.remotebuzzer.useleds && config.remotebuzzer.printled) {
                printled.writeSync(0);
            }
            ioServer.emit('photobooth-socket', 'completed');
            break;

        case 'print':
            triggerArmed = false;
            log('Photobooth trigger PRINT : [ photobooth-socket ]  => [ All Clients ]: command [ print ]');
            if (config.remotebuzzer.useleds && config.remotebuzzer.printled) {
                printled.writeSync(1);
            }
            ioServer.emit('photobooth-socket', 'print');
            break;

        case 'rotary-cw':
            ioServer.emit('photobooth-socket', 'rotary-cw');
            break;

        case 'rotary-ccw':
            ioServer.emit('photobooth-socket', 'rotary-ccw');
            break;

        case 'rotary-btn-press':
            ioServer.emit('photobooth-socket', 'rotary-btn-press');
            break;

        case 'reset':
            photoboothAction('completed');
            break;

        default:
            log('Photobooth action [', type, '] not implemented - ignoring');
            break;
    }
}

/* CONFIGURE HTTP ENDPOINTS */
const requestListener = function (req, res) {
    function sendText(content, contentType) {
        res.setHeader('Content-Type', contentType || 'text/plain');
        res.setHeader('Access-Control-Allow-Origin', '*');
        res.writeHead(200);
        res.end(content);
    }

    switch (req.url) {
        case '/':
            log('http: GET /');
            sendText(
                `<h1>Trigger Endpoints</h1>
            <ul>
                <li>Trigger photo: <a href="${baseUrl}/commands/start-picture" target="_blank">${baseUrl}/commands/start-picture</a></li>
                <li>Trigger collage: <a href="${baseUrl}/commands/start-collage" target="_blank">${baseUrl}/commands/start-collage</a></li>
                <li>Trigger custom: <a href="${baseUrl}/commands/start-custom" target="_blank">${baseUrl}/commands/start-custom</a></li>
                <li>Trigger print: <a href="${baseUrl}/commands/start-print" target="_blank">${baseUrl}/commands/start-print</a></li>
                <li>Trigger video: <a href="${baseUrl}/commands/start-video" target="_blank">${baseUrl}/commands/start-video</a></li>
                <li>Trigger picture move to USB: <a href="${baseUrl}/commands/start-move2usb" target="_blank">${baseUrl}/commands/start-move2usb</a></li>
            </ul>
            <h1>Rotary Endpoints</h1>
            <ul>
                <li>Focus next: <a href="${baseUrl}/commands/rotary-cw" target="_blank">${baseUrl}/commands/rotary-cw</a></li>
                <li>Focus previous: <a href="${baseUrl}/commands/rotary-ccw" target="_blank">${baseUrl}/commands/rotary-ccw</a></li>
                <li>Click: <a href="${baseUrl}/commands/rotary-btn-press" target="_blank">${baseUrl}/commands/rotary-btn-press</a></li>
            </ul>
            <h1>Power</h1>
            <ul>
                <li>Shutdwon now: <a href="${baseUrl}/commands/shutdown-now" target="_blank">${baseUrl}/commands/shutdown-now</a></li>
                <li>Reboot now: <a href="${baseUrl}/commands/reboot-now" target="_blank">${baseUrl}/commands/reboot-now</a></li>
            </ul>`,
                'text/html'
            );
            break;
        case '/commands/start-picture':
            log('http: GET /commands/start-picture');
            if (config.remotebuzzer.usebuttons && config.remotebuzzer.picturebutton) {
                if (triggerArmed) {
                    if (config.picture.enabled) {
                        photoboothAction('picture');
                        sendText('TAKE PHOTO TRIGGERED.');
                    } else {
                        sendText('PHOTO DISABLED.');
                    }
                } else {
                    sendText('ALREADY TRIGGERED AN ACTION');
                }
            } else {
                sendText('Please enable Hardware Button support and Picture Button!');
            }
            break;
        case '/commands/start-collage':
            log('http: GET /commands/start-collage');
            if (config.remotebuzzer.usebuttons && config.remotebuzzer.collagebutton) {
                if (triggerArmed) {
                    if (config.collage.enabled) {
                        photoboothAction('collage');
                        sendText('TAKE COLLAGE TRIGGERED');
                    } else {
                        sendText('COLLAGE DISABLED.');
                    }
                } else {
                    sendText('ALREADY TRIGGERED AN ACTION');
                }
            } else {
                sendText('Please enable Hardware Button support and Collage Button!');
            }
            break;
        case '/commands/start-custom':
            log('http: GET /commands/start-custom');
            if (config.remotebuzzer.usebuttons && config.remotebuzzer.custombutton) {
                if (triggerArmed) {
                    if (config.custom.enabled) {
                        photoboothAction('custom');
                        sendText('TAKE CUSTOM TRIGGERED');
                    } else {
                        sendText('CUSTOM DISABLED.');
                    }
                } else {
                    sendText('ALREADY TRIGGERED AN ACTION');
                }
            } else {
                sendText('Please enable Hardware Button support and Custom Button!');
            }
            break;
        case '/commands/start-print':
            log('http: GET /commands/start-print');
            if (config.remotebuzzer.usebuttons && config.remotebuzzer.printbutton) {
                if (triggerArmed) {
                    photoboothAction('print');
                    sendText('PRINT TRIGGERED');
                } else {
                    sendText('ALREADY TRIGGERED AN ACTION');
                }
            } else {
                sendText('Please enable Hardware Button support and Print Button!');
            }
            break;
        case '/commands/start-move2usb':
            log('http: GET /commands/start-move2usb');
            if (config.remotebuzzer.usebuttons && config.remotebuzzer.move2usb != 'disabled') {
                if (triggerArmed) {
                    photoboothAction('move2usb');
                    sendText('MOVE2USB TRIGGERED');
                } else {
                    sendText('ALREADY TRIGGERED AN ACTION');
                }
            } else {
                sendText('Please enable Hardware Button support and Move2USB Button!');
            }
            break;
        case '/commands/start-video':
            log('http: GET /commands/start-video');
            if (config.remotebuzzer.usebuttons && config.remotebuzzer.videobutton) {
                if (triggerArmed) {
                    if (config.video.enabled) {
                        photoboothAction('video');
                        sendText('TAKE VIDEO TRIGGERED');
                    } else {
                        sendText('VIDEO DISABLED.');
                    }
                } else {
                    sendText('ALREADY TRIGGERED AN ACTION');
                }
            } else {
                sendText('Please enable Hardware Button support and Video Button!');
            }
            break;
        case '/commands/rotary-cw':
            log('http: GET /commands/rotary-cw');
            if (config.remotebuzzer.userotary) {
                photoboothAction('rotary-cw');
                sendText('FOCUS NEXT ELEMENT');
            } else {
                sendText('Please enable rotary Controller support!');
            }
            break;
        case '/commands/rotary-ccw':
            log('http: GET /commands/rotary-ccw');
            if (config.remotebuzzer.userotary) {
                photoboothAction('rotary-ccw');
                sendText('FOCUS PREVIOUS ELEMENT');
            } else {
                sendText('Please enable rotary Controller support!');
            }
            break;
        case '/commands/rotary-btn-press':
            log('http: GET /commands/rotary-btn-press');
            if (config.remotebuzzer.userotary) {
                photoboothAction('rotary-btn-press');
                sendText('CLICK ELEMENT');
            } else {
                sendText('Please enable rotary Controller support!');
            }
            break;
        case '/commands/shutdown-now':
            log('http: GET /commands/shutdown-now');
            if (config.remotebuzzer.usebuttons && config.remotebuzzer.shutdownbutton) {
                sendText('SHUTTING DOWN');
                /*  Initiate system shutdown */
                const cmd = 'sudo ' + config.commands.shutdown;
                execSync(cmd);
            } else {
                sendText('Please enable Hardware Button support and Shutdown Button!');
            }
            break;
        case '/commands/reboot-now':
            log('http: GET /commands/reboot-now');
            if (config.remotebuzzer.usebuttons && config.remotebuzzer.rebootbutton) {
                sendText('REBOOTING NOW');
                /*  Initiate system shutdown */
                const cmd = 'sudo ' + config.commands.reboot;
                execSync(cmd);
            } else {
                sendText('Please enable Hardware Button support and Reboot Button!');
            }
            break;
        default:
            res.writeHead(404);
            res.end();
    }
};

const http = require('http');
const server = new http.Server(requestListener);

/* CONFIGURE WEBSOCKET SERVER */
const ioServer = require('socket.io')(server, {
    cors: {
        origin: '*',
        methods: ['GET', 'POST']
    }
});

/* NEW CLIENT CONNECTED */
ioServer.on('connection', function (client) {
    log('New client connected - ID', client.id);

    client.on('photobooth-socket', function (data) {
        log('Data from client ID ', client.id, ': [ photobooth-socket ] =>  [' + data + ']');

        /* CLIENT COMMANDS RECEIVED */
        switch (data) {
            case 'completed':
                photoboothAction('completed');
                break;

            case 'in-progress':
                triggerArmed = false;
                break;

            case 'start-picture':
                photoboothAction('picture');
                break;

            case 'start-collage':
                photoboothAction('collage');
                break;

            case 'start-custom':
                photoboothAction('custom');
                break;

            case 'start-video':
                photoboothAction('video');
                break;

            case 'start-move2usb':
                photoboothAction('move2usb');
                break;

            case 'collage-wait-for-next':
                triggerArmed = true;
                break;

            case 'photo':
                if (config.remotebuzzer.useleds && config.remotebuzzer.photolight) {
                    photolight.writeSync(1);
                }
                if (config.remotebuzzer.useleds && config.remotebuzzer.pictureled) {
                    pictureled.writeSync(1);
                }
                break;

            case 'collage':
                if (config.remotebuzzer.useleds && config.remotebuzzer.photolight) {
                    photolight.writeSync(1);
                }
                if (config.remotebuzzer.useleds && config.remotebuzzer.collageled) {
                    collageled.writeSync(1);
                }
                break;

            case 'custom':
                if (config.remotebuzzer.useleds && config.remotebuzzer.photolight) {
                    photolight.writeSync(1);
                }
                if (config.remotebuzzer.useleds && config.remotebuzzer.customled) {
                    customled.writeSync(1);
                }
                break;

            case 'video':
                if (config.remotebuzzer.useleds && config.remotebuzzer.photolight) {
                    photolight.writeSync(1);
                }
                if (config.remotebuzzer.useleds && config.remotebuzzer.videoled) {
                    videoled.writeSync(1);
                }
                break;

            case 'print':
                if (config.remotebuzzer.useleds && config.remotebuzzer.printled) {
                    printled.writeSync(1);
                }
                break;

            default:
                log('Received unknown command [', data, '] - ignoring');
                break;
        }
    });

    /* CLIENT DISCONNECTED */
    client.on('disconnect', function () {
        log('Client disconnected - ID ', client.id);

        if (ioServer.engine.clientsCount == 0) {
            log('No more clients connected - removing lock and arming trigger');
            triggerArmed = true;
            collageInProgress = false;
        }
    });
});

/* STARTUP COMPLETED */
server.listen(config.remotebuzzer.port, () => {
    log('socket.io server started');
});

/*
 ** GPIO HANDLING
 */

/* SANITY CHECKS */
function gpioPuSanity(gpioconfig) {
    try {
        if (isNaN(gpioconfig)) {
            throw new Error(gpioconfig + ' is not a valid number');
        }

        const configPath = fs.existsSync('/boot/firmware/config.txt')
            ? '/boot/firmware/config.txt'
            : fs.existsSync('/boot/config.txt')
              ? '/boot/config.txt'
              : (() => {
                    throw new Error('Configuration file not found');
                })();

        const cmd = 'sed -n "s/^gpio=\\(.*\\)=pu/\\1/p" ' + configPath;
        const stdout = execSync(cmd).toString();

        if (!stdout.split(',').find((el) => el == gpioconfig)) {
            log('GPIO' + gpioconfig + ' is not configured as PULLUP in ' + configPath + ' - see FAQ for details');
        }

        return true;
    } catch (error) {
        log('Error: ', error.message);

        return false;
    }
}

function gpioOpSanity(gpioconfig) {
    try {
        if (isNaN(gpioconfig)) {
            throw new Error(gpioconfig + ' is not a valid number');
        }

        const configPath = fs.existsSync('/boot/firmware/config.txt')
            ? '/boot/firmware/config.txt'
            : fs.existsSync('/boot/config.txt')
              ? '/boot/config.txt'
              : (() => {
                    throw new Error('Configuration file not found');
                })();

        const cmd = 'sed -n "s/^gpio=\\(.*\\)=op/\\1/p" ' + configPath;
        const stdout = execSync(cmd).toString();

        if (!stdout.split(',').find((el) => el == gpioconfig)) {
            log('GPIO' + gpioconfig + ' is not configured as OUTPUT in ' + configPath + ' - see FAQ for details');
        }

        return true;
    } catch (error) {
        log('Error: ', error.message);

        return false;
    }
}

const Gpio = require('onoff').Gpio;
const useGpio = config.remotebuzzer.usegpio && Gpio.accessible;

/* BUTTON SEMAPHORE HELPER FUNCTION */
function buttonActiveCheck(gpio, value) {
    /*
     * value = 0 : button is pressed (connected to GND - pulled down)
     * value = 1 : button is not pressed (pull-up)
     */

    /* init */
    if (typeof buttonActiveCheck.buttonIsPressed == 'undefined') {
        buttonActiveCheck.buttonIsPressed = 0;
    }

    /* clean state - no button pressed - activate lock */
    if (buttonActiveCheck.buttonIsPressed == 0 && !value) {
        buttonActiveCheck.buttonIsPressed = gpio;
        buttonTimer(Date.now('millis'));

        return false;
    }

    /* clean state - locked button release - release lock */
    if (buttonActiveCheck.buttonIsPressed == gpio && value) {
        buttonActiveCheck.buttonIsPressed = 0;
        buttonTimer(Date.now('millis'));

        return false;
    }

    /* forced reset */
    if (gpio == -1 && value == -1) {
        buttonActiveCheck.buttonIsPressed = 0;
        buttonTimer(0);

        return false;
    }

    /* error state - do nothing */
    log(
        'buttonActiveCheck WARNING - requested GPIO ',
        gpio,
        ', for value ',
        value,
        'but buttonIsPressed:',
        buttonActiveCheck.buttonIsPressed,
        ' Please consider to add an external pull-up resistor to all your input GPIOs, this might help to eliminate this warning. Regardless of this warning, Photobooth should be fully functional.'
    );

    return true;
}

/* TIMER HELPER FUNCTION */
function buttonTimer(millis) {
    /* init */
    if (typeof buttonTimer.millis == 'undefined' || millis === 0) {
        buttonTimer.millis = 0;
        buttonTimer.duration = 0;
    }

    /* return timer value */
    if (typeof millis == 'undefined') {
        return buttonTimer.duration;
    }

    /* start timer */
    if (buttonTimer.millis === 0) {
        buttonTimer.millis = millis;

        return true;
    }

    /* too long button press */
    if (millis - buttonTimer.millis > 10000) {
        buttonTimer.millis = 0;
        buttonTimer.duration = 0;

        return false;
    }

    /* end timer */
    if (millis - buttonTimer.millis > 0) {
        buttonTimer.duration = millis - buttonTimer.millis;
        buttonTimer.millis = 0;

        return buttonTimer.duration;
    }

    /* error state */
    log('buttonTimer error state encountered - millis: ', millis);

    return false;
}

/* WATCH FUNCTION PICTURE BUTTON WITH LONGPRESS FOR COLLAGE*/
const watchPictureGPIOwithCollage = function watchPictureGPIOwithCollage(err, gpioValue) {
    if (err) {
        throw err;
    }

    /* if there is some activity in progress ignore GPIO pin for now */
    if (!triggerArmed || buttonActiveCheck(config.remotebuzzer.picturegpio, gpioValue)) {
        return;
    }

    if (gpioValue) {
        /* Button released - raising flank detected */
        const timeElapsed = buttonTimer();

        if (!timeElapsed) {
            /* Too long button press - timeout - reset server state machine */
            log('GPIO', config.remotebuzzer.picturegpio, '- too long button press - Reset server state machine');
            photoboothAction('reset');
            buttonActiveCheck(-1, -1);
        } else if (timeElapsed <= config.remotebuzzer.collagetime * 1000 && !collageInProgress) {
            /* Start Picture */
            log('GPIO', config.remotebuzzer.picturegpio, '- Picture button released - normal -', timeElapsed, ' [ms] ');
            photoboothAction('picture');
        } else if (collageInProgress) {
            /* Next Collage Picture*/
            log('GPIO', config.remotebuzzer.picturegpio, '- Picture button released - long -', timeElapsed, ' [ms] ');
            photoboothAction('collage-next');
        } else {
            /* Start Collage */
            log('GPIO', config.remotebuzzer.picturegpio, '- Picture button released - long -', timeElapsed, ' [ms] ');
            photoboothAction('collage');
        }
    } else {
        /* Button pressed - falling flank detected (pull to ground) */
        log('GPIO ', config.remotebuzzer.picturegpio, ' - Picture button pressed');
    }
};

/* WATCH FUNCTION PICTURE BUTTON */
const watchPictureGPIO = function watchPictureGPIO(err, gpioValue) {
    if (err) {
        throw err;
    }

    /* if there is some activity in progress ignore GPIO pin for now */
    if (!triggerArmed || buttonActiveCheck(config.remotebuzzer.picturegpio, gpioValue)) {
        return;
    }

    if (gpioValue) {
        /* Button released - raising flank detected */
        const timeElapsed = buttonTimer();

        if (timeElapsed) {
            log('GPIO', config.remotebuzzer.picturegpio, '- Picture button released - normal -', timeElapsed, ' [ms] ');
            /* Start Picture */
            if (!collageInProgress) {
                photoboothAction('picture');
            }
        } else {
            /* Too long button press - timeout - reset server state machine */
            log('GPIO', config.remotebuzzer.picturegpio, '- too long button press - Reset server state machine');
            photoboothAction('reset');
            buttonActiveCheck(-1, -1);
            if (config.remotebuzzer.useleds && config.remotebuzzer.pictureled) {
                pictureled.writeSync(0);
            }
        }
    } else {
        /* Button pressed - falling flank detected (pull to ground) */
        log('GPIO', config.remotebuzzer.picturegpio, '- Picture button pressed');
        if (config.remotebuzzer.useleds && config.remotebuzzer.pictureled) {
            pictureled.writeSync(1);
        }
    }
};

/* WATCH FUNCTION COLLAGE BUTTON */
const watchCollageGPIO = function watchCollageGPIO(err, gpioValue) {
    if (err) {
        throw err;
    }

    /* if there is some activity in progress ignore GPIO pin for now */
    if (!triggerArmed || buttonActiveCheck(config.remotebuzzer.collagegpio, gpioValue)) {
        return;
    }

    if (gpioValue) {
        /* Button released - raising flank detected */
        const timeElapsed = buttonTimer();

        if (timeElapsed) {
            log('GPIO', config.remotebuzzer.collagegpio, '- Collage button released ', timeElapsed, ' [ms] ');
            if (config.remotebuzzer.useleds && config.remotebuzzer.collageled) {
                collageled.writeSync(0);
            }

            /* Collage Trigger Next */
            if (collageInProgress) {
                photoboothAction('collage-next');
            } else {
                /* Start Collage */
                photoboothAction('collage');
            }
        } else {
            /* Too long button press - timeout - reset server state machine */
            log('GPIO', config.remotebuzzer.collagegpio, '- too long button press - Reset server state machine');
            photoboothAction('reset');
            buttonActiveCheck(-1, -1);
            if (config.remotebuzzer.useleds && config.remotebuzzer.collageled) {
                collageled.writeSync(0);
            }
        }
    } else {
        /* Button pressed - falling flank detected (pull to ground) */
        log('GPIO', config.remotebuzzer.collagegpio, '- Collage button pressed');
        if (config.remotebuzzer.useleds && config.remotebuzzer.collageled) {
            collageled.writeSync(1);
        }
    }
};

/* WATCH FUNCTION CUSTOM BUTTON */
const watchCustomGPIO = function watchCustomGPIO(err, gpioValue) {
    if (err) {
        throw err;
    }

    /* if there is some activity in progress ignore GPIO pin for now */
    if (!triggerArmed || buttonActiveCheck(config.remotebuzzer.customgpio, gpioValue)) {
        return;
    }

    if (gpioValue) {
        /* Button released - raising flank detected */
        const timeElapsed = buttonTimer();

        if (timeElapsed) {
            log('GPIO', config.remotebuzzer.customgpio, '- Custom button released ', timeElapsed, ' [ms] ');
            if (config.remotebuzzer.useleds && config.remotebuzzer.customled) {
                customled.writeSync(0);
            }

            /* Start Custom */
            photoboothAction('custom');
        } else {
            /* Too long button press - timeout - reset server state machine */
            log('GPIO', config.remotebuzzer.customgpio, '- too long button press - Reset server state machine');
            photoboothAction('reset');
            buttonActiveCheck(-1, -1);
            if (config.remotebuzzer.useleds && config.remotebuzzer.customled) {
                customled.writeSync(0);
            }
        }
    } else {
        /* Button pressed - falling flank detected (pull to ground) */
        log('GPIO', config.remotebuzzer.customgpio, '- Custom button pressed');
        if (config.remotebuzzer.useleds && config.remotebuzzer.customled) {
            customled.writeSync(1);
        }
    }
};

/* WATCH FUNCTION VIDEO BUTTON */
const watchVideoGPIO = function watchVideoGPIO(err, gpioValue) {
    if (err) {
        throw err;
    }

    /* if there is some activity in progress ignore GPIO pin for now */
    if (!triggerArmed || buttonActiveCheck(config.remotebuzzer.videogpio, gpioValue)) {
        return;
    }

    if (gpioValue) {
        /* Button released - raising flank detected */
        const timeElapsed = buttonTimer();

        if (timeElapsed) {
            log('GPIO', config.remotebuzzer.videogpio, '- Video button released ', timeElapsed, ' [ms] ');
            if (config.remotebuzzer.useleds && config.remotebuzzer.videoled) {
                videoled.writeSync(0);
            }

            /* Start Video */
            photoboothAction('video');
        } else {
            /* Too long button press - timeout - reset server state machine */
            log('GPIO', config.remotebuzzer.videogpio, '- too long button press - Reset server state machine');
            photoboothAction('reset');
            buttonActiveCheck(-1, -1);
            if (config.remotebuzzer.useleds && config.remotebuzzer.videoled) {
                videoled.writeSync(0);
            }
        }
    } else {
        /* Button pressed - falling flank detected (pull to ground) */
        log('GPIO', config.remotebuzzer.videogpio, '- Video button pressed');
        if (config.remotebuzzer.useleds && config.remotebuzzer.videoled) {
            videoled.writeSync(1);
        }
    }
};

/* WATCH FUNCTION SHUTDOWN BUTTON */
const watchShutdownGPIO = function watchShutdownGPIO(err, gpioValue) {
    if (err) {
        throw err;
    }

    /* if there is some activity in progress ignore GPIO pin for now */
    if (!triggerArmed || buttonActiveCheck(config.remotebuzzer.shutdowngpio, gpioValue)) {
        return;
    }

    if (gpioValue) {
        /* Button released - raising flank detected */
        const timeElapsed = buttonTimer();

        if (timeElapsed) {
            log('GPIO', config.remotebuzzer.shutdowngpio, '- Shutdown button released ', timeElapsed, ' [ms] ');
            if (config.remotebuzzer.useleds && config.remotebuzzer.shutdownled) {
                shutdownled.writeSync(0);
            }

            if (timeElapsed >= config.remotebuzzer.shutdownholdtime * 1000) {
                log('System shutdown initiated - bye bye');
                /*  Initiate system shutdown */
                const cmd = 'sudo ' + config.commands.shutdown;
                execSync(cmd);
            }
        } else {
            /* Too long button press - timeout - reset server state machine */
            log('GPIO', config.remotebuzzer.shutdowngpio, '- too long button press - Reset server state machine');
            photoboothAction('reset');
            buttonActiveCheck(-1, -1);
            if (config.remotebuzzer.useleds && config.remotebuzzer.shutdownled) {
                shutdownled.writeSync(0);
            }
        }
    } else {
        /* Button pressed - falling flank detected (pull to ground) */
        log('GPIO', config.remotebuzzer.shutdowngpio, '- Shutdown button pressed');
        if (config.remotebuzzer.useleds && config.remotebuzzer.shutdownled) {
            shutdownled.writeSync(1);
        }
    }
};

/* WATCH FUNCTION REBOOT BUTTON */
const watchRebootGPIO = function watchRebootGPIO(err, gpioValue) {
    if (err) {
        throw err;
    }

    /* if there is some activity in progress ignore GPIO pin for now */
    if (!triggerArmed || buttonActiveCheck(config.remotebuzzer.rebootgpio, gpioValue)) {
        return;
    }

    if (gpioValue) {
        /* Button released - raising flank detected */
        const timeElapsed = buttonTimer();

        if (timeElapsed) {
            log('GPIO', config.remotebuzzer.rebootgpio, '- Reboot button released ', timeElapsed, ' [ms] ');
            if (config.remotebuzzer.useleds && config.remotebuzzer.rebootled) {
                rebootled.writeSync(0);
            }

            if (timeElapsed >= config.remotebuzzer.rebootholdtime * 1000) {
                log('System reboot initiated - bye bye');
                /*  Initiate system reboot */
                const cmd = 'sudo ' + config.commands.reboot;
                execSync(cmd);
            }
        } else {
            /* Too long button press - timeout - reset server state machine */
            log('GPIO', config.remotebuzzer.rebootgpio, '- too long button press - Reset server state machine');
            photoboothAction('reset');
            buttonActiveCheck(-1, -1);
            if (config.remotebuzzer.useleds && config.remotebuzzer.rebootled) {
                rebootled.writeSync(0);
            }
        }
    } else {
        /* Button pressed - falling flank detected (pull to ground) */
        log('GPIO', config.remotebuzzer.rebootgpio, '- Reboot button pressed');
        if (config.remotebuzzer.useleds && config.remotebuzzer.rebootled) {
            rebootled.writeSync(1);
        }
    }
};

/* WATCH FUNCTION PRINT BUTTON */
const watchPrintGPIO = function watchPrintGPIO(err, gpioValue) {
    if (err) {
        throw err;
    }

    /* if there is some activity in progress ignore GPIO pin for now */
    if (!triggerArmed || buttonActiveCheck(config.remotebuzzer.printgpio, gpioValue)) {
        return;
    }

    if (gpioValue) {
        /* Button released - raising flank detected */
        const timeElapsed = buttonTimer();

        if (timeElapsed) {
            log('GPIO', config.remotebuzzer.printgpio, '- Print button released ', timeElapsed, ' [ms] ');
            if (config.remotebuzzer.useleds && config.remotebuzzer.printled) {
                printled.writeSync(0);
            }

            /* Start Print */
            photoboothAction('print');
        } else {
            /* Too long button press - timeout - reset server state machine */
            log('GPIO', config.remotebuzzer.printgpio, '- too long button press - Reset server state machine');
            photoboothAction('reset');
            buttonActiveCheck(-1, -1);
            if (config.remotebuzzer.useleds && config.remotebuzzer.printled) {
                printled.writeSync(0);
            }
        }
    } else {
        /* Button pressed - falling flank detected (pull to ground) */
        log('GPIO', config.remotebuzzer.printgpio, '- Print button pressed');
        if (config.remotebuzzer.useleds && config.remotebuzzer.printled) {
            printled.writeSync(1);
        }
    }
};

/* WATCH FUNCTION MOVE2USB BUTTON */
const watchMove2usbGPIO = function watchMove2usbGPIO(err, gpioValue) {
    if (err) {
        throw err;
    }

    /* if there is some activity in progress ignore GPIO pin for now */
    if (!triggerArmed || buttonActiveCheck(config.remotebuzzer.move2usbgpio, gpioValue)) {
        return;
    }

    if (gpioValue) {
        /* Button released - raising flank detected */
        const timeElapsed = buttonTimer();

        if (timeElapsed) {
            log('GPIO', config.remotebuzzer.move2usbgpio, '- Move2USB button released ', timeElapsed, ' [ms] ');
            photoboothAction('move2usb');
        } else {
            /* Too long button press - timeout - reset server state machine */
            log('GPIO', config.remotebuzzer.move2usbgpio, '- too long button press - Reset server state machine');
            photoboothAction('reset');
            buttonActiveCheck(-1, -1);
            if (config.remotebuzzer.useleds && config.remotebuzzer.move2usbled) {
                move2usbled.writeSync(0);
            }
        }
    } else {
        /* Button pressed - falling flank detected (pull to ground) */
        log('GPIO', config.remotebuzzer.move2usbgpio, '- Move2USB button pressed');
        if (config.remotebuzzer.useleds && config.remotebuzzer.move2usbled) {
            move2usbled.writeSync(1);
        }
    }
};

/* WATCH FUNCTION ROTARY CLK */
const watchRotaryClk = function watchRotaryClk(err, gpioValue) {
    if (err) {
        throw err;
    }

    /* if there is some activity in progress ignore GPIO pin for now */
    if (!triggerArmed) {
        return;
    }

    if (gpioValue) {
        if (rotaryDtPin) {
            /* rotation */
            photoboothAction('rotary-cw');
        } else {
            rotaryClkPin = true;
        }
    } else {
        rotaryClkPin = false;
    }
};

/* WATCH FUNCTION ROTARY DT */
const watchRotaryDt = function watchRotaryDt(err, gpioValue) {
    if (err) {
        throw err;
    }

    /* if there is some activity in progress ignore GPIO pin for now */
    if (!triggerArmed) {
        return;
    }

    if (gpioValue) {
        if (rotaryClkPin) {
            /* rotation */
            photoboothAction('rotary-ccw');
        } else {
            rotaryDtPin = true;
        }
    } else {
        rotaryDtPin = false;
    }
};

/* WATCH FUNCTION ROTARY BUTTON */
const watchRotaryBtn = function watchRotaryBtn(err, gpioValue) {
    if (err) {
        throw err;
    }

    /* if there is some activity in progress ignore GPIO pin for now */
    if (!triggerArmed) {
        return;
    }

    if (gpioValue) {
        photoboothAction('rotary-btn-press');
    }
};

/* INIT ONOFF LIBRARY AND LINK CALLBACK FUNCTIONS */
if (useGpio) {
    /* ROTARY ENCODER MODE */
    if (
        config.remotebuzzer.userotary &&
        gpioPuSanity(config.remotebuzzer.rotaryclkgpio) &&
        gpioPuSanity(config.remotebuzzer.rotarydtgpio) &&
        gpioPuSanity(config.remotebuzzer.rotarybtngpio)
    ) {
        /* ROTARY ENCODER MODE */
        log('ROTARY support active');
        const rotaryClk = new Gpio(config.remotebuzzer.rotaryclkgpio, 'in', 'both');
        const rotaryDt = new Gpio(config.remotebuzzer.rotarydtgpio, 'in', 'both');
        const rotaryBtn = new Gpio(config.remotebuzzer.rotarybtngpio, 'in', 'both', {
            debounceTimeout: config.remotebuzzer.debounce
        });

        rotaryClkPin = 0;
        rotaryDtPin = 0;

        log(
            'Looking for Rotary Encoder connected to GPIOs ',
            config.remotebuzzer.rotaryclkgpio,
            '(CLK) ',
            config.remotebuzzer.rotarydtgpio,
            '(DT) ',
            config.remotebuzzer.rotarybtngpio,
            '(BTN)'
        );

        rotaryClk.watch(watchRotaryClk);
        rotaryDt.watch(watchRotaryDt);
        rotaryBtn.watch(watchRotaryBtn);
    }

    /* NORMAL BUTTON SUPPORT */
    if (config.remotebuzzer.usebuttons) {
        log('BUTTON support active');
        if (config.remotebuzzer.picturebutton && gpioPuSanity(config.remotebuzzer.picturegpio)) {
            const pictureButton = new Gpio(config.remotebuzzer.picturegpio, 'in', 'both', {
                debounceTimeout: config.remotebuzzer.debounce
            });
            log('Looking for Picture Button on Raspberry GPIO', config.remotebuzzer.picturegpio);
            if (!config.remotebuzzer.collagebutton && config.collage.enabled) {
                log('config: collage enabled for picture button');
                pictureButton.watch(watchPictureGPIOwithCollage);
            } else {
                pictureButton.watch(watchPictureGPIO);
            }
        }

        /* COLLAGE BUTTON */
        if (
            config.collage.enabled &&
            config.remotebuzzer.collagebutton &&
            gpioPuSanity(config.remotebuzzer.collagegpio)
        ) {
            const collageButton = new Gpio(config.remotebuzzer.collagegpio, 'in', 'both', {
                debounceTimeout: config.remotebuzzer.debounce
            });
            log('Looking for Collage Button on Raspberry GPIO', config.remotebuzzer.collagegpio);
            collageButton.watch(watchCollageGPIO);
        }

        /* CUSTOM BUTTON */
        if (config.remotebuzzer.custombutton && gpioPuSanity(config.remotebuzzer.customgpio)) {
            const customButton = new Gpio(config.remotebuzzer.customgpio, 'in', 'both', {
                debounceTimeout: config.remotebuzzer.debounce
            });
            log('Looking for Custom Button on Raspberry GPIO', config.remotebuzzer.customgpio);
            customButton.watch(watchCustomGPIO);
        }

        /* VIDEO BUTTON */
        if (config.remotebuzzer.videobutton && gpioPuSanity(config.remotebuzzer.videogpio)) {
            const videoButton = new Gpio(config.remotebuzzer.videogpio, 'in', 'both', {
                debounceTimeout: config.remotebuzzer.debounce
            });
            log('Looking for Video Button on Raspberry GPIO', config.remotebuzzer.videogpio);
            videoButton.watch(watchVideoGPIO);
        }

        /* SHUTDOWN BUTTON */
        if (config.remotebuzzer.shutdownbutton && gpioPuSanity(config.remotebuzzer.shutdowngpio)) {
            const shutdownButton = new Gpio(config.remotebuzzer.shutdowngpio, 'in', 'both', {
                debounceTimeout: config.remotebuzzer.debounce
            });
            log('Looking for Shutdown Button on Raspberry GPIO', config.remotebuzzer.shutdowngpio);
            shutdownButton.watch(watchShutdownGPIO);
        }

        /* REBOOT BUTTON */
        if (config.remotebuzzer.rebootbutton && gpioPuSanity(config.remotebuzzer.rebootgpio)) {
            const rebootButton = new Gpio(config.remotebuzzer.rebootgpio, 'in', 'both', {
                debounceTimeout: config.remotebuzzer.debounce
            });
            log('Looking for Reboot Button on Raspberry GPIO', config.remotebuzzer.rebootgpio);
            rebootButton.watch(watchRebootGPIO);
        }

        /* PRINT BUTTON */
        if (config.remotebuzzer.printbutton && gpioPuSanity(config.remotebuzzer.printgpio)) {
            const printButton = new Gpio(config.remotebuzzer.printgpio, 'in', 'both', {
                debounceTimeout: config.remotebuzzer.debounce
            });
            log('Looking for Print Button on Raspberry GPIO', config.remotebuzzer.printgpio);
            printButton.watch(watchPrintGPIO);
        }

        /* Move2USB BUTTON */
        if (config.remotebuzzer.move2usb != 'disabled' && gpioPuSanity(config.remotebuzzer.move2usbgpio)) {
            const move2usbButton = new Gpio(config.remotebuzzer.move2usbgpio, 'in', 'both', {
                debounceTimeout: config.remotebuzzer.debounce
            });
            log('Looking for Move2USB Button on Raspberry GPIO', config.remotebuzzer.move2usbgpio);
            move2usbButton.watch(watchMove2usbGPIO);
        }

        /* LED OUT SUPPORT */
        if (config.remotebuzzer.useleds) {
            /* Photo Light */
            if (config.remotebuzzer.photolight && gpioOpSanity(config.remotebuzzer.photolightgpio)) {
                log('OUT for Photo Light on Raspberry GPIO', config.remotebuzzer.photolightgpio);
                photolight = new Gpio(config.remotebuzzer.photolightgpio, 'out');
            }

            /* LED PICTURE BUTTON */
            if (config.remotebuzzer.pictureled && gpioOpSanity(config.remotebuzzer.pictureledgpio)) {
                pictureled = new Gpio(config.remotebuzzer.pictureledgpio, 'out');
                log('LED for Picture Button on Raspberry GPIO', config.remotebuzzer.pictureledgpio);
            }

            /* LED COLLAGE BUTTON */
            if (config.remotebuzzer.collageled && gpioOpSanity(config.remotebuzzer.collageledgpio)) {
                log('LED for Collage Button on Raspberry GPIO', config.remotebuzzer.collageledgpio);
                collageled = new Gpio(config.remotebuzzer.collageledgpio, 'out');
            }

            /* LED CUSTOM BUTTON */
            if (config.remotebuzzer.customled && gpioOpSanity(config.remotebuzzer.customledgpio)) {
                log('LED for Custom Button on Raspberry GPIO', config.remotebuzzer.customledgpio);
                customled = new Gpio(config.remotebuzzer.customledgpio, 'out');
            }

            /* LED VIDEO BUTTON */
            if (config.remotebuzzer.videoled && gpioOpSanity(config.remotebuzzer.videoledgpio)) {
                log('LED for Video Button on Raspberry GPIO', config.remotebuzzer.videoledgpio);
                videoled = new Gpio(config.remotebuzzer.videoledgpio, 'out');
            }

            /* LED SHUTDOWN BUTTON */
            if (config.remotebuzzer.shutdownled && gpioOpSanity(config.remotebuzzer.shutdownledgpio)) {
                log('LED for Shutdown Button on Raspberry GPIO', config.remotebuzzer.shutdownledgpio);
                shutdownled = new Gpio(config.remotebuzzer.shutdownledgpio, 'out');
            }

            /* LED REBOOT BUTTON */
            if (config.remotebuzzer.rebootled && gpioOpSanity(config.remotebuzzer.rebootledgpio)) {
                log('LED for Reboot Button on Raspberry GPIO', config.remotebuzzer.rebootledgpio);
                rebootled = new Gpio(config.remotebuzzer.rebootledgpio, 'out');
            }

            /* LED PRINT BUTTON */
            if (config.remotebuzzer.printled && gpioOpSanity(config.remotebuzzer.printledgpio)) {
                log('LED for Print Button on Raspberry GPIO', config.remotebuzzer.printledgpio);
                printled = new Gpio(config.remotebuzzer.printledgpio, 'out');
            }

            /* LED Move2USB BUTTON */
            if (config.remotebuzzer.move2usbled && gpioOpSanity(config.remotebuzzer.move2usbledgpio)) {
                log('LED for Move2USB Button on Raspberry GPIO', config.remotebuzzer.move2usbledgpio);
                move2usbled = new Gpio(config.remotebuzzer.move2usbledgpio, 'out');
            }
        }
    }
} else if (config.remotebuzzer.usegpio && !Gpio.accessible) {
    log('GPIO enabled but GPIO not accessible!');
}

/* Move2USB */
function move2usbAction() {
    if (config.remotebuzzer.useleds && config.remotebuzzer.move2usbled) {
        move2usbled.writeSync(1);
    }

    const parseConfig = () => {
        try {
            return {
                dataAbsPath: environment.absoluteFolders.data,
                drive: config.synctodrive.target,
                dbName: config.database.file
            };
        } catch (err) {
            log('ERROR: unable to parse sync-to-drive config', err);
        }

        return null;
    };

    /* PARSE PHOTOBOOTH CONFIG */
    const parsedConfig = parseConfig();
    log('USB target ', ...parsedConfig.drive);

    const getDriveInfo = ({ drive }) => {
        let json = null;
        let device = false;

        drive = drive.toLowerCase();

        try {
            //Assuming that the lsblk version supports JSON output!
            const output = execSync('export LC_ALL=C; lsblk -ablJO 2>/dev/null; unset LC_ALL').toString();
            json = JSON.parse(output);

            // eslint-disable-next-line no-unused-vars
        } catch (err) {
            log(
                'ERROR: Could not parse the output of lsblk! Please make sure its installed and that it offers JSON output!'
            );

            return null;
        }

        if (!json || !json.blockdevices) {
            log('ERROR: The output of lsblk was malformed!');

            return null;
        }

        try {
            device = json.blockdevices.find(
                (blk) =>
                    blk.subsystems.includes('usb') &&
                    ((blk.name && drive === blk.name.toLowerCase()) ||
                        (blk.kname && drive === blk.kname.toLowerCase()) ||
                        (blk.path && drive === blk.path.toLowerCase()) ||
                        (blk.label && drive === blk.label.toLowerCase()))
            );
            // eslint-disable-next-line no-unused-vars
        } catch (err) {
            device = false;
        }

        return device;
    };

    const mountDrive = (drive) => {
        if (typeof drive.mountpoint === 'undefined' || !drive.mountpoint) {
            try {
                const mountRes = execSync(`export LC_ALL=C; udisksctl mount -b ${drive.path}; unset LC_ALL`).toString();
                const mountPoint = mountRes
                    .substr(mountRes.indexOf('at') + 3)
                    .trim()
                    .replace(/[\n.]/gu, '');

                drive.mountpoint = mountPoint;
                // eslint-disable-next-line no-unused-vars
            } catch (error) {
                log('ERROR: unable to mount drive', drive.path);
                drive = null;
            }
        }

        return drive;
    };

    const startSync = ({ dataAbsPath, drive }) => {
        if (!fs.existsSync(dataAbsPath)) {
            log(`ERROR: Folder [${dataAbsPath}] does not exist!`);

            return;
        }

        log('Starting sync to USB drive ...');
        log(`Source data folder [${dataAbsPath}]`);
        log(`Syncing to drive [${drive.path}] -> [${drive.mountpoint}]`);

        execSync('touch ' + dataAbsPath + '/copy.chk');

        if (fs.existsSync(path.join(drive.mountpoint, SYNC_DESTINATION_DIR + '/data/copy.chk'))) {
            log(' ');
            log(
                '[WARNING] Last sync might not completed, Checkfile exists:',
                path.join(drive.mountpoint, SYNC_DESTINATION_DIR + '/copy.chk')
            );
            log(' ');
        }

        const cmd = (() => {
            switch (process.platform) {
                case 'win32':
                    return null;
                case 'linux':
                    // prettier-ignore
                    return [
                        'rsync',
                        '-a',
                        '--delete-before',
                        '-b',
                        `--backup-dir=${path.join(drive.mountpoint, 'deleted')}`,
                        '--ignore-existing',
                        '--include=\'*.\'{jpg,chk,gif,mp4}',
                        '--include=\'*/\'',
                        '--exclude=\'*\'',
                        '--prune-empty-dirs',
                        dataAbsPath,
                        path.join(drive.mountpoint, SYNC_DESTINATION_DIR)
                    ].join(' ');
                default:
                    return null;
            }
        })();

        if (!cmd) {
            log('ERROR: No command for syncing!');

            return;
        }

        log('Executing command: <', cmd, '>');

        try {
            spawnSync(cmd, {
                shell: '/bin/bash',
                stdio: 'ignore'
            });
        } catch (err) {
            log('ERROR: Could not start rsync:', err.toString());

            return;
        }

        log('Sync completed');

        if (fs.existsSync(path.join(drive.mountpoint, SYNC_DESTINATION_DIR + '/data/copy.chk'))) {
            copySucess = true;
        } else {
            log(' ');
            log(
                '[ERROR] Sync error, sync might be not sucessfull. Checkfile does not exist:',
                path.join(drive.mountpoint, SYNC_DESTINATION_DIR + '/data/copy.chk')
            );
            log(' ');
            copySucess = false;

            return;
        }

        execSync('rm ' + path.join(drive.mountpoint, SYNC_DESTINATION_DIR + '/data/copy.chk'));
        execSync('rm ' + dataAbsPath + '/copy.chk');
    };

    const unmountDrive = () => {
        const driveInfo = getDriveInfo(parsedConfig);
        const mountedDrive = mountDrive(driveInfo);

        if (mountedDrive) {
            try {
                execSync(`export LC_ALL=C; udisksctl unmount -b ${mountedDrive.path}; unset LC_ALL`).toString();
                log('Unmounted drive', mountedDrive.path);
                // eslint-disable-next-line no-unused-vars
            } catch (error) {
                log('ERROR: unable to unmount drive', mountedDrive.path);
            }
        } else {
            log('Nothing to umount');
        }
    };

    const deleteFiles = ({ dataAbsPath }) => {
        if (!fs.existsSync(dataAbsPath)) {
            log(`ERROR: Folder [${dataAbsPath}] does not exist!`);

            return;
        }
        if (!copySucess) {
            log('[Warning] Sync was unsuccessful. No files will be deleted.');

            return;
        }

        log('Deleting Files...');

        const cmd = (() => {
            switch (process.platform) {
                case 'win32':
                    return null;
                case 'linux':
                    // prettier-ignore
                    return [
                        'find',
                        dataAbsPath,
                        '-type f \\(',
                        '-name \'*.jpg\'',
                        '-o',
                        '-name \'*.gif\'',
                        '-o',
                        '-name \'*.mp4\'',
                        '\\)',
                        '-exec rm -rv {}',
                        '\\;'
                    ].join(' ');
                default:
                    return null;
            }
        })();

        log('Executing command: <', cmd, '>');
        execSync(cmd);
    };

    const deleteDatabase = ({ dataAbsPath, dbName }) => {
        if (!fs.existsSync(dataAbsPath)) {
            log(`ERROR: Folder [${dataAbsPath}] does not exist!`);

            return;
        }
        if (!copySucess) {
            log('[Warning] Sync was unsuccessful. No files will be deleted.');

            return;
        }
        if (!fs.existsSync(path.join(dataAbsPath, dbName + '.txt'))) {
            const cmd = path.join(dataAbsPath, dbName + '.txt');
            log('Error: Database not found: ', cmd, ' - nothing to delete');

            return;
        }

        log('Deleting Database...');

        const cmd = 'rm ' + path.join(dataAbsPath, dbName + '.txt');
        log('Executing command: <', cmd, '>');
        execSync(cmd);
    };

    /* Execution starts here */

    if (PLATFORM === 'win32') {
        log('Windows is currently not supported!');
        process.exit();
    }

    log('Checking for USB drive');

    const driveInfo = getDriveInfo(parsedConfig);
    try {
        log(`Processing drive ${driveInfo.label} -> ${driveInfo.path}`);
        // eslint-disable-next-line no-unused-vars
    } catch (error) {
        return;
    }

    const mountedDrive = mountDrive(driveInfo);
    try {
        log(`Mounted drive ${mountedDrive.name} -> ${mountedDrive.mountpoint}`);
        // eslint-disable-next-line no-unused-vars
    } catch (error) {
        return;
    }

    if (mountedDrive) {
        startSync({
            dataAbsPath: parsedConfig.dataAbsPath,
            drive: mountedDrive
        });
    }

    unmountDrive();

    if (copySucess && config.remotebuzzer.move2usb == 'move') {
        deleteFiles({ dataAbsPath: parsedConfig.dataAbsPath });
    } else {
        log('[Info] move2USB mode "copy" or Sync unsuccessful. No files will be deleted.');
    }

    if (copySucess && config.remotebuzzer.move2usb == 'move') {
        deleteDatabase({
            dataAbsPath: parsedConfig.dataAbsPath,
            dbName: parsedConfig.dbName
        });
    } else {
        log('[Info] move2USB mode "copy" or Sync unsuccessful. Database will not be deleted.');
    }

    if (config.remotebuzzer.useleds && config.remotebuzzer.move2usbled) {
        move2usbled.writeSync(0);
    }

    photoboothAction('completed');
}

log('Initialization completed');

/* VARIABLES */
let collageInProgress = false,
    triggerArmed = true,
    photolight,
    pictureled,
    collageled,
    shutdownled,
    rebootled,
    printled;

const API_DIR_NAME = 'api';
const API_FILE_NAME = 'config.php';
const PID = process.pid;
let rotaryClkPin, rotaryDtPin;

/* LOGGING FUNCTION */
const log = function (...optionalParams) {
    console.log('[', new Date().toISOString(), ']:', ` Remote Buzzer Server [${PID}]:`, ...optionalParams);
};

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

/* SOURCE PHOTOBOOTH CONFIG */
const {execSync} = require('child_process');
let cmd = `cd ${API_DIR_NAME} && php ./${API_FILE_NAME}`;
let stdout = execSync(cmd).toString();
const config = JSON.parse(stdout.slice(stdout.indexOf('{'), stdout.lastIndexOf(';')));

/* WRITE PROCESS PID FILE */
const pidFilename = config.foldersJS.tmp + '/remotebuzzer_server.pid';
const fs = require('fs');

fs.writeFile(pidFilename, parseInt(PID, 10).toString(), function (err) {
    if (err) {
        throw new Error('Unable to write PID file [' + pidFilename + '] - ' + err.message);
    }

    log('PID file created [', pidFilename, ']');
});

/* START HTTP & WEBSOCKET SERVER */
const baseUrl = 'http://' + config.webserver.ip + ':' + config.remotebuzzer.port;
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
            if (config.remotebuzzer.useleds && config.remotebuzzer.collageled) {
                collageled.writeSync(0);
            }
            ioServer.emit('photobooth-socket', 'completed');
            break;

        case 'print':
            triggerArmed = false;
            log('Photobooth trigger PRINT : [ photobooth-socket ]  => [ All Clients ]: command [ print ]');
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
                <li>Trigger print: <a href="${baseUrl}/commands/start-print" target="_blank">${baseUrl}/commands/start-print</a></li>
            </ul>
            <h1>Rotary Endpoints</h1>
            <ul>
                <li>Focus next: <a href="${baseUrl}/commands/rotary-cw" target="_blank">${baseUrl}/commands/rotary-cw</a></li>
                <li>Focus previous: <a href="${baseUrl}/commands/rotary-ccw" target="_blank">${baseUrl}/commands/rotary-ccw</a></li>
                <li>Click: <a href="${baseUrl}/commands/rotary-btn-press" target="_blank">${baseUrl}/commands/rotary-btn-press</a></li>
            </ul>`,
                'text/html'
            );
            break;
        case '/commands/start-picture':
            log('http: GET /commands/start-picture');
            if (config.remotebuzzer.usebuttons && config.remotebuzzer.picturebutton) {
                if (triggerArmed) {
                    if (config.collage.enabled && config.collage.only) {
                        photoboothAction('collage');
                        sendText('TAKE PHOTO DISABLED, COLLAGE TRIGGERED');
                    } else {
                        photoboothAction('picture');
                        sendText('TAKE PHOTO TRIGGERED');
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
                        photoboothAction('picture');
                        sendText('COLLAGE DISABLED. TAKE PICTURE TRIGGERED');
                    }
                } else {
                    sendText('ALREADY TRIGGERED AN ACTION');
                }
            } else {
                sendText('Please enable Hardware Button support and Collage Button!');
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
                if (config.remotebuzzer.useleds && config.remotebuzzer.pictureled) {
                    pictureled.writeSync(0);
                }
                if (config.remotebuzzer.useleds && config.remotebuzzer.photolight) {
                    photolight.writeSync(0);
                }
                break;

            case 'in-progress':
                triggerArmed = false;
                if (config.remotebuzzer.useleds && config.remotebuzzer.pictureled && collageInProgress == false) {
                    pictureled.writeSync(1);
                }
                if (config.remotebuzzer.useleds && config.remotebuzzer.photolight) {
                    photolight.writeSync(1);
                }
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
    if (isNaN(gpioconfig)) {
        throw new Error(gpioconfig + ' is not a valid number');
    }

    if (gpioconfig < 1 || gpioconfig > 27) {
        throw new Error('GPIO' + gpioconfig + ' number is out of range (1-27)');
    }

    cmd = 'sed -n "s/^gpio=\\(.*\\)=pu/\\1/p" /boot/config.txt';
    stdout = execSync(cmd).toString();

    if (!stdout.split(',').find((el) => el == gpioconfig)) {
        throw new Error('GPIO' + gpioconfig + ' is not configured as PULLUP in /boot/config.txt - see FAQ for details');
    }
}

function gpioOpSanity(gpioconfig) {
    if (isNaN(gpioconfig)) {
        throw new Error(gpioconfig + ' is not a valid number');
    }

    if (gpioconfig < 1 || gpioconfig > 27) {
        throw new Error('GPIO' + gpioconfig + ' number is out of range (1-27)');
    }

    cmd = 'sed -n "s/^gpio=\\(.*\\)=op/\\1/p" /boot/config.txt';
    stdout = execSync(cmd).toString();

    if (!stdout.split(',').find((el) => el == gpioconfig)) {
        throw new Error('GPIO' + gpioconfig + ' is not configured as OUTPUT in /boot/config.txt - see FAQ for details');
    }
}

const Gpio = require('onoff').Gpio;
const useGpio = Gpio.accessible && !config.remotebuzzer.usenogpio;

if (useGpio) {
    gpioPuSanity(config.remotebuzzer.picturegpio);
    gpioPuSanity(config.remotebuzzer.collagegpio);
    gpioPuSanity(config.remotebuzzer.shutdowngpio);
    gpioPuSanity(config.remotebuzzer.printgpio);
    gpioPuSanity(config.remotebuzzer.rotaryclkgpio);
    gpioPuSanity(config.remotebuzzer.rotarydtgpio);
    gpioPuSanity(config.remotebuzzer.rotarybtngpio);
    gpioPuSanity(config.remotebuzzer.rebootgpio);
    gpioOpSanity(config.remotebuzzer.photolightgpio);
    gpioOpSanity(config.remotebuzzer.pictureledgpio);
    gpioOpSanity(config.remotebuzzer.collageledgpio);
    gpioOpSanity(config.remotebuzzer.printledgpio);
    gpioOpSanity(config.remotebuzzer.shutdownledgpio);
    gpioOpSanity(config.remotebuzzer.rebootledgpio);
}

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
                cmd = 'sudo ' + config.shutdown.cmd;
                stdout = execSync(cmd);
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
                cmd = 'sudo ' + config.reboot.cmd;
                stdout = execSync(cmd);
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
    if (config.remotebuzzer.userotary) {
        /* ROTARY ENCODER MODE */
        log('ROTARY support active');
        const rotaryClk = new Gpio(config.remotebuzzer.rotaryclkgpio, 'in', 'both');
        const rotaryDt = new Gpio(config.remotebuzzer.rotarydtgpio, 'in', 'both');
        const rotaryBtn = new Gpio(config.remotebuzzer.rotarybtngpio, 'in', 'both', {
            debounceTimeout: config.remotebuzzer.debounce
        });

        rotaryClkPin = 0;
        rotaryDtPin = 0;

        rotaryClk.watch(watchRotaryClk);
        rotaryDt.watch(watchRotaryDt);
        rotaryBtn.watch(watchRotaryBtn);

        log(
            'Looking for Rotary Encoder connected to GPIOs ',
            config.remotebuzzer.rotaryclkgpio,
            '(CLK) ',
            config.remotebuzzer.rotarydtgpio,
            '(DT) ',
            config.remotebuzzer.rotarybtngpio,
            '(BTN)'
        );
    }

    /* NORMAL BUTTON SUPPORT */
    if (config.remotebuzzer.usebuttons) {
        log('BUTTON support active');
        if (config.remotebuzzer.picturebutton) {
            const pictureButton = new Gpio(config.remotebuzzer.picturegpio, 'in', 'both', {
                debounceTimeout: config.remotebuzzer.debounce
            });

            if (!config.remotebuzzer.collagebutton && config.collage.enabled) {
                pictureButton.watch(watchPictureGPIOwithCollage);
                log('config: collage enabled for picture button');
            } else {
                pictureButton.watch(watchPictureGPIO);
            }

            log('Looking for Picture Button on Raspberry GPIO', config.remotebuzzer.picturegpio);
        }

        /* COLLAGE BUTTON */
        if (config.remotebuzzer.collagebutton && config.collage.enabled) {
            const collageButton = new Gpio(config.remotebuzzer.collagegpio, 'in', 'both', {
                debounceTimeout: config.remotebuzzer.debounce
            });
            collageButton.watch(watchCollageGPIO);
            log('Looking for Collage Button on Raspberry GPIO', config.remotebuzzer.collagegpio);
        }

        /* SHUTDOWN BUTTON */
        if (config.remotebuzzer.shutdownbutton) {
            const shutdownButton = new Gpio(config.remotebuzzer.shutdowngpio, 'in', 'both', {
                debounceTimeout: config.remotebuzzer.debounce
            });
            shutdownButton.watch(watchShutdownGPIO);
            log('Looking for Shutdown Button on Raspberry GPIO', config.remotebuzzer.shutdowngpio);
        }

        /* REBOOT BUTTON */
        if (config.remotebuzzer.rebootbutton) {
            const rebootButton = new Gpio(config.remotebuzzer.rebootgpio, 'in', 'both', {
                debounceTimeout: config.remotebuzzer.debounce
            });
            rebootButton.watch(watchRebootGPIO);
            log('Looking for Reboot Button on Raspberry GPIO', config.remotebuzzer.rebootgpio);
        }

        /* PRINT BUTTON */
        if (config.remotebuzzer.printbutton) {
            const printButton = new Gpio(config.remotebuzzer.printgpio, 'in', 'both', {
                debounceTimeout: config.remotebuzzer.debounce
            });
            printButton.watch(watchPrintGPIO);
            log('Looking for Print Button on Raspberry GPIO', config.remotebuzzer.printgpio);
        }

        /* LED OUT SUPPORT */
        if (config.remotebuzzer.useleds) {
            /* Photo Light */
            if (config.remotebuzzer.photolight) {
                photolight = new Gpio(config.remotebuzzer.photolightgpio, 'out');
                log('OUT for Photo Light on Raspberry GPIO', config.remotebuzzer.photolightgpio);
            }

            /* LED PICTURE BUTTON */
            if (config.remotebuzzer.pictureled) {
                pictureled = new Gpio(config.remotebuzzer.pictureledgpio, 'out');
                log('LED for Picture Button on Raspberry GPIO', config.remotebuzzer.pictureledgpio);
            }

            /* LED COLLAGE BUTTON */
            if (config.remotebuzzer.collageled) {
                collageled = new Gpio(config.remotebuzzer.collageledgpio, 'out');
                log('LED for Collage Button on Raspberry GPIO', config.remotebuzzer.collageledgpio);
            }

            /* LED SHUTDOWN BUTTON */
            if (config.remotebuzzer.shutdownled) {
                shutdownled = new Gpio(config.remotebuzzer.shutdownledgpio, 'out');
                log('LED for Shutdown Button on Raspberry GPIO', config.remotebuzzer.shutdownledgpio);
            }

            /* LED REBOOT BUTTON */
            if (config.remotebuzzer.rebootled) {
                rebootled = new Gpio(config.remotebuzzer.rebootledgpio, 'out');
                log('LED for Reboot Button on Raspberry GPIO', config.remotebuzzer.rebootledgpio);
            }

            /* LED PRINT BUTTON */
            if (config.remotebuzzer.printled) {
                printled = new Gpio(config.remotebuzzer.printledgpio, 'out');
                log('LED for Print Button on Raspberry GPIO', config.remotebuzzer.printledgpio);
            }
        }
    }
} else if (!config.remotebuzzer.usenogpio && !Gpio.accessible) {
    log('GPIO enabled but GPIO not accessible!');
}

log('Initialization completed');

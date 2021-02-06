/* VARIABLES */
let collageInProgress = false,
    triggerArmed = true;
const API_DIR_NAME = 'api';
const API_FILE_NAME = 'config.php';
const PID = process.pid;

/* LOGGING FUNCTION */
const log = (...optionalParams) => console.log(`Remote Buzzer Server [${PID}]:`, ...optionalParams);

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
const config = JSON.parse(stdout.slice(stdout.indexOf('{'), -1));

/* WRITE PROCESS PID FILE */
const pidFilename = config.foldersRoot.tmp + '/remotebuzzer_server.pid';
const fs = require('fs');

fs.writeFile(pidFilename, PID, function (err) {
    if (err) {
        throw new Error('Unable to write PID file [' + pidFilename + '] - ' + err.message);
    }

    log('PID file created [', pidFilename, ']');
});

/* START WEBSOCKET SERVER */
log('Server starting on http://' + config.webserver_ip + ':' + config.remotebuzzer.port);

function photoboothAction(type) {
    switch (type) {
        case 'picture':
            triggerArmed = false;
            collageInProgress = false;
            log('Photobooth trigger picture : [ photobooth-socket ] => [ All Clients ]: command [ picture ]');
            ioServer.emit('photobooth-socket', 'start-picture');
            break;

        case 'collage':
            triggerArmed = false;
            collageInProgress = true;
            log('Photobooth trigger collage : [ photobooth-socket ]  => [ All Clients ]: command [ collage ]');
            ioServer.emit('photobooth-socket', 'start-collage');
            break;

        case 'completed':
            triggerArmed = true;
            collageInProgress = false;
            log('Photobooth activity completed : [ photobooth-socket ] => [ All Clients ]: command [ completed ]');
            ioServer.emit('photobooth-socket', 'completed');
            break;

        case 'reset':
            photoboothAction('completed');
            break;

        default:
            log('Photobooth action [', type, '] not implemented - ignoring');
            break;
    }
}

const ioServer = require('socket.io')(config.remotebuzzer.port, {
    cors: {
        origin: 'http://' + config.webserver.ip,
        methods: ['GET', 'POST']
    }
});

/* NEW CLIENT CONNECTED */
ioServer.on('connection', function (client) {
    log('New client connected - ID', client.id);
    client.on('photobooth-socket', function (data) {
        log('Data from client ID ', client.id, ': [ photobooth-socket ] =>  [', data, ']');

        /* CLIENT COMMANDS RECEIVED */
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
log('socket.io server started');

/*
 ** GPIO HANDLING
 */

/* SANITY CHECKS */
function gpioSanity(gpioconfig) {
    return !(isNaN(gpioconfig) || gpioconfig < 0 || gpioconfig > 21);
}

if (!gpioSanity(config.remotebuzzer.picturegpio)) {
    log('GPIO configuration for Picture Button is invalid: ', config.remotebuzzer.picturegpio);
}
if (!gpioSanity(config.remotebuzzer.collagegpio)) {
    log('GPIO configuration for Collage Button is invalid: ', config.remotebuzzer.collagegpio);
}
if (!gpioSanity(config.remotebuzzer.shutdowngpio)) {
    log('GPIO configuration for Shutdown Button is invalid: ', config.remotebuzzer.shutdowngpio);
}

/* BUTTON SEMAPHORE HELPER FUNCTION */
function buttonActiveCheck(gpio, value) {
    /* init */
    if (typeof buttonActiveCheck.buttonIsPressed == 'undefined') {
        buttonActiveCheck.buttonIsPressed = 0;
    }

    /* clean state - no button pressed - activate lock */
    if (buttonActiveCheck.buttonIsPressed == 0 && !value) {
        // log('buttonActiveCheck: LOCK gpio ', gpio, ', value ', value);
        buttonActiveCheck.buttonIsPressed = gpio;
        buttonTimer(Date.now('millis'));

        return false;
    }

    /* clean state - locked button release - release lock */
    if (buttonActiveCheck.buttonIsPressed == gpio && value) {
        // log('buttonActiveCheck: RELEASE gpio ', gpio, ', value ', value);
        buttonActiveCheck.buttonIsPressed = 0;
        buttonTimer(Date.now('millis'));

        return false;
    }

    /* forced reset */
    if (gpio == -1 && value == -1) {
        // log('buttonActiveCheck - forced state reset');
        buttonActiveCheck.buttonIsPressed = 0;
        buttonTimer(0);

        return false;
    }

    /* error state - do nothing */
    log(
        'buttonActiveCheck error state - requested GPIO ',
        gpio,
        ', for value ',
        value,
        'but buttonIsPressed:',
        buttonActiveCheck.buttonIsPressed
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
        // log('buttonTimer started - value saved: ', millis);

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
        // log('buttonTimer ended - ', buttonTimer.duration, ' ms');

        return buttonTimer.duration;
    }

    /* error state */
    log('buttonTimer error state encountered - millis: ', millis);

    return false;
}

/* WATCH FUNCTION PICTURE BUTTON WITH LONGPRESS FOR COLLAGE*/
const watchPictureGPIOwithCollage = function watchPictureGPIOwithCollage(err, gpioValue) {
    //log('FUNCTION: watchPictureGPIOwithCollage()');
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
    //log('FUNCTION: watchPictureGPIO()');
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
        }
    } else {
        /* Button pressed - falling flank detected (pull to ground) */
        log('GPIO', config.remotebuzzer.picturegpio, '- Picture button pressed');
    }
};

/* WATCH FUNCTION COLLAGE BUTTON */
const watchCollageGPIO = function watchCollageGPIO(err, gpioValue) {
    //log('FUNCTION: watchCollageGPIO()');
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

            /* Start Collage */
            if (!collageInProgress) {
                photoboothAction('collage');
            }
        } else {
            /* Too long button press - timeout - reset server state machine */
            log('GPIO', config.remotebuzzer.collagegpio, '- too long button press - Reset server state machine');
            photoboothAction('reset');
            buttonActiveCheck(-1, -1);
        }
    } else {
        /* Button pressed - falling flank detected (pull to ground) */
        log('GPIO', config.remotebuzzer.collagegpio, '- Collage button pressed');
    }
};

/* WATCH FUNCTION SHUTDOWN BUTTON */
const watchShutdownGPIO = function watchShutdownGPIO(err, gpioValue) {
    //log('FUNCTION: watchShutdownGPIO()');
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
            log('GPIO', config.remotebuzzer.collagegpio, '- Shutdown button released ', timeElapsed, ' [ms] ');

            if (timeElapsed >= config.remotebuzzer.shutdownholdtime * 1000) {
                log('System shutdown initiated - bye bye');
                /*  Initiate system shutdown */
                cmd = 'sudo /sbin/shutdown -r now';
                stdout = execSync(cmd);
            }
        } else {
            /* Too long button press - timeout - reset server state machine */
            log('GPIO', config.remotebuzzer.shutdowngpio, '- too long button press - Reset server state machine');
            photoboothAction('reset');
            buttonActiveCheck(-1, -1);
        }
    } else {
        /* Button pressed - falling flank detected (pull to ground) */
        log('GPIO', config.remotebuzzer.shutdowngpio, '- Shutdown button pressed');
    }
};

/* INIT ONOFF LIBRARY AND LINK CALLBACK FUNCTIONS */
const Gpio = require('onoff').Gpio;

/* PICTURE BUTTON */
if (config.remotebuzzer.picturebutton) {
    const pictureButton = new Gpio(config.remotebuzzer.picturegpio, 'in', 'both', {debounceTimeout: 20});

    if (!config.remotebuzzer.collagebutton && config.collage.enabled) {
        pictureButton.watch(watchPictureGPIOwithCollage);
        log('config: collage enabled for picture button');
    } else {
        pictureButton.watch(watchPictureGPIO);
    }

    log('Connecting Picture Button to Raspberry GPIO', config.remotebuzzer.picturegpio);
}

/* COLLAGE BUTTON */
if (config.remotebuzzer.collagebutton && config.collage.enabled) {
    const collageButton = new Gpio(config.remotebuzzer.collagegpio, 'in', 'both', {debounceTimeout: 20});
    collageButton.watch(watchCollageGPIO);
    log('Connecting Collage Button to Raspberry GPIO', config.remotebuzzer.collagegpio);
}

/* SHUTDOWN BUTTON */
if (config.remotebuzzer.shutdownbutton) {
    const shutdownButton = new Gpio(config.remotebuzzer.shutdowngpio, 'in', 'both', {debounceTimeout: 20});
    shutdownButton.watch(watchShutdownGPIO);
    log('Connecting Shutdown Button to Raspberry GPIO', config.remotebuzzer.shutdowngpio);
}

log('Initialization completed');

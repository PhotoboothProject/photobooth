import os
import psutil
import sys
import time
import argparse
import zmq
from subprocess import Popen, PIPE
import gphoto2 as gp


class CameraControl:
    def connect_to_camera(self):
        try:
            self.camera = gp.Camera()
            self.camera.init()
        except gp.GPhoto2Error:
            pass

    def handle_message(self, message):
        command = message[0]
        if command == 'captureImage' and len(message) == 2:
            try:
                print('capturing image')
                file_path = self.camera.capture(gp.GP_CAPTURE_IMAGE)
                # refresh images on camera
                gp.check_result(gp.gp_camera_wait_for_event(self.camera, 1000))
                print('Camera file path: {0}/{1}'.format(file_path.folder, file_path.name))
                file_jpg = str(file_path.name).replace('.CR2', '.JPG')
                target = message[1]
                print('Copying image to', target)
                camera_file = self.camera.file_get(file_path.folder, file_jpg, gp.GP_FILE_TYPE_NORMAL)
                camera_file.save(target)
                self.socket.send_string('Image captured')
                if self.bsm:
                    raise KeyboardInterrupt
            except gp.GPhoto2Error:
                self.socket.send_string('failure')
        elif command == 'startVideo':
            self.socket.send_string('Starting Service')
            self.showVideo = True
        elif command == 'stopVideo':
            self.socket.send_string('Stopping Video')
            self.showVideo = True
        elif command == 'stop':
            self.socket.send_string('Stopping Service')
            raise KeyboardInterrupt
        else:
            self.socket.send_string('failure')
            print('Received message that can\'t be handled')

    def __init__(self, args):
        self.device = args.device
        self.bsm = args.bsm
        self.showVideo = not args.noVideo
        self.camera = None
        os.environ['HOME'] = '/var/www/'

        context = zmq.Context()
        self.socket = context.socket(zmq.REP)
        self.socket.bind('tcp://*:5555')
        self.connect_to_camera()
        ffmpeg = Popen(['ffmpeg', '-i', '-', '-vcodec', 'rawvideo', '-pix_fmt', 'yuv420p', '-f', 'v4l2', self.device],
                       stdin=PIPE)
        try:
            while True:
                try:
                    message = self.socket.recv_string(flags=zmq.NOBLOCK)
                    print('Received: %s' % message)
                    self.handle_message(message.split())
                except zmq.Again:
                    pass
                try:
                    if self.showVideo:
                        capture = self.camera.capture_preview()
                        ffmpeg.stdin.write(memoryview(capture.get_data_and_size()).tobytes())
                    else:
                        time.sleep(0.1)
                except gp.GPhoto2Error:
                    time.sleep(1)
                    print('Not connected to camera. Trying to reconnect...')
                    self.connect_to_camera()
        except KeyboardInterrupt:
            print('Exiting...')
            sys.exit(0)


class MessageSender:
    def __init__(self, args):
        message = args.message
        try:
            context = zmq.Context()
            socket = context.socket(zmq.REQ)
            socket.setsockopt(zmq.RCVTIMEO, 10000)
            socket.connect('tcp://localhost:5555')
            print('Sending message: %s' % message)
            socket.send_string(message)
            response = socket.recv_string()
            print(response)
            if response == 'failure':
                exit(1)
        except zmq.Again:
            print('Message receival not confirmed')
            exit(1)


def limit_to_one_instance():
    instances = 0
    for p in psutil.process_iter(['name', 'cmdline']):
        if p.name() == 'python3':
            if p.cmdline()[1].endswith('cameracontrol.py'):
                instances += 1
    if instances > 1:
        print('Camera Control is already running...')
        sys.exit(0)


def main():
    parser = argparse.ArgumentParser(description='Simple Camera Control script using libgphoto2 and python-gphoto2. \
                                                 The script has two distinct modes: Sending a message or starting \
                                                 camera control. A message is sent when param "message" is set.',
                                     epilog='libgphoto2 automatically uses the config of the User calling the script. \
                                            This means the config has to be present at "~/.gphoto/settings" and owned \
                                            by the User. If this script is used by www-data this means there has to be \
                                            a config at "/var/www/.gphoto/settings" or the default config is used. \
                                            Which might lead to the camera not transferring any photos as they are \
                                            only stored in RAM (Canon). You can copy the config of any User but make \
                                            sure the target User has appropriate rights on the config file and the \
                                            path.')
    parser.add_argument('-d', '--device',
                        nargs='?', default='/dev/video0', help='virtual device the ffmpeg stream is sent to')
    parser.add_argument('-m', '--message',
                        nargs='?', default=None, help='send a message to running cameracontrol script \
                                                      (values: captureImage, startVideo, stopVideo, stop)')
    parser.add_argument('-b', '--bsm', action='store_true', help='start preview and quit after taking a photo')
    parser.add_argument('-n', '--noVideo', action='store_true', help='start without showing video')

    args = parser.parse_args()
    if args.message:
        MessageSender(args)
    else:
        limit_to_one_instance()
        CameraControl(args)


if __name__ == '__main__':
    main()

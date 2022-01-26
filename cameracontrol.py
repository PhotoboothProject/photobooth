import os
import signal
import sys
import time
import argparse
import psutil
import zmq
from subprocess import Popen, PIPE
import gphoto2 as gp


class CameraControl:
    def connect_to_camera(self):
        try:
            self.camera = gp.Camera()
            self.camera.init()
            print('Connected to camera')
            if self.args.set_config is not None:
                for c in self.args.set_config:
                    cs = c.split("=")
                    if len(cs) == 2:
                        print('Setting config %s' % c)
                        self.set_config(cs[0], cs[1])
                    else:
                        print('Invalid config %s' % c)
        except gp.GPhoto2Error:
            pass

    def capture_image(self, path):
        print('Capturing image')
        file_path = self.camera.capture(gp.GP_CAPTURE_IMAGE)
        # refresh images on camera
        gp.check_result(gp.gp_camera_wait_for_event(self.camera, 1000))
        print('Camera file path: {0}/{1}'.format(file_path.folder, file_path.name))
        file_jpg = str(file_path.name).replace('.CR2', '.JPG')
        print('Copying image to', path)
        camera_file = self.camera.file_get(file_path.folder, file_jpg, gp.GP_FILE_TYPE_NORMAL)
        camera_file.save(path)

    def set_config(self, name, value):
        try:
            config = self.camera.get_config()
            setting = config.get_child_by_name(name)
            setting_type = setting.get_type()
            if setting_type == gp.GP_WIDGET_RADIO \
                    or setting_type == gp.GP_WIDGET_MENU \
                    or setting_type == gp.GP_WIDGET_TEXT:
                try:
                    int_value = int(value)
                    count = setting.count_choices()
                    if int_value < 0 or int_value >= count:
                        print('Parameter out of range')
                        raise KeyboardInterrupt
                    choice = setting.get_choice(int_value)
                    setting.set_value(choice)
                except ValueError:
                    setting.set_value(value)
            elif setting_type == gp.GP_WIDGET_TOGGLE:
                setting.set_value(int(value))
            elif setting_type == gp.GP_WIDGET_RANGE:
                setting.set_value(float(value))
            else:
                # unhandled types (most don't make any sense to handle)
                # GP_WIDGET_SECTION, GP_WIDGET_WINDOW, GP_WIDGET_BUTTON, GP_WIDGET_DATE
                print('Unhandled setting type %s for %s=%s' % (setting_type, name, value))
                raise KeyboardInterrupt
            self.camera.set_config(config)
            print('Config set %s=%s' % (name, value))
        except gp.GPhoto2Error or ValueError:
            print('Config error for %s=%s' % (name, value))
            raise KeyboardInterrupt

    def disable_video(self):
        self.showVideo = False
        self.set_config('viewfinder', 0)

    def handle_message(self, message):
        command = message[0]
        if command == 'captureImage' and len(message) == 2:
            try:
                path = message[1]
                self.capture_image(path)
                self.socket.send_string('Image captured')
                if self.args.bsm:
                    self.disable_video()
            except gp.GPhoto2Error as e:
                print('An error occured: %s' % e)
                self.socket.send_string('failure')
        elif command == 'startVideo':
            try:
                self.showVideo = True
                self.connect_to_camera()
                self.socket.send_string('Starting Video')
            except gp.GPhoto2Error:
                self.socket.send_string('failure')
        elif command == 'stopVideo':
            try:
                self.disable_video()
                self.socket.send_string('Stopping Video')
            except gp.GPhoto2Error:
                self.socket.send_string('failure')
        elif command == 'stop':
            self.socket.send_string('Stopping Service')
            raise KeyboardInterrupt
        else:
            self.socket.send_string('failure')
            print('Received message that can\'t be handled')

    def exit_gracefully(self, *args):
        if self.camera:
            self.camera.exit()
        print('Exiting...')
        sys.exit(0)

    def __init__(self, args):
        self.args = args
        self.showVideo = not args.no_video
        self.camera = None
        self.socket = None
        os.environ['HOME'] = '/var/www/'

        signal.signal(signal.SIGINT, self.exit_gracefully)
        signal.signal(signal.SIGTERM, self.exit_gracefully)

        self.connect_to_camera()

        if self.args.capture_image_and_download is not None:
            try:
                self.capture_image(self.args.capture_image_and_download)
            except gp.GPhoto2Error as e:
                print('An error occured: %s' % e)
                sys.exit(1)
        else:
            self.pipe_video_to_ffmpeg_and_wait_for_commands()

    def pipe_video_to_ffmpeg_and_wait_for_commands(self):
        context = zmq.Context()
        self.socket = context.socket(zmq.REP)
        self.socket.bind('tcp://*:5555')
        ffmpeg = Popen(
            ['ffmpeg', '-i', '-', '-vcodec', 'rawvideo', '-pix_fmt', 'yuv420p', '-f', 'v4l2', self.args.device],
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
                        img_bytes = memoryview(capture.get_data_and_size()).tobytes()
                        ffmpeg.stdin.write(img_bytes)
                    else:
                        time.sleep(0.1)
                except gp.GPhoto2Error:
                    time.sleep(1)
                    print('Not connected to camera. Trying to reconnect...')
                    self.connect_to_camera()
        except KeyboardInterrupt:
            self.exit_gracefully()


class MessageSender:
    def __init__(self, message):
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
                sys.exit(1)
        except zmq.Again:
            print('Message receival not confirmed')
            sys.exit(1)


def is_already_running():
    instances = 0
    for p in psutil.process_iter(['name', 'cmdline']):
        if p.name() == 'python3':
            if p.cmdline()[1].endswith('cameracontrol.py'):
                instances += 1
    return instances > 1


def main():
    parser = argparse.ArgumentParser(description='Simple Camera Control script using libgphoto2 through \
    python-gphoto2.', epilog='If you don\'t want images to be stored on the camera make sure that capturetarget \
    is set to internal ram (might be device dependent but it\'s 0 for Canon cameras. Additionally you should \
    configure your camera to capture only jpeg images.')
    parser.add_argument('-d', '--device', nargs='?', default='/dev/video0',
                        help='virtual device the ffmpeg stream is sent to')
    parser.add_argument('-s', '--set-config', action='append', default=None,
                        help='CONFIGENTRY=CONFIGVALUE analog to gphoto2 cli. Not tested for all configentries!')
    parser.add_argument('-c', '--capture-image-and-download', default=None, type=str, help='capture an image and \
    download it to the computer. If it stays stored on the camera as well depends on the camera config.')
    parser.add_argument('-m', '--message', nargs='?', default=None,
                        help='send a message to running cameracontrol script \
                        (values: captureImage "path", startVideo, stopVideo, stop)')
    parser.add_argument('-b', '--bsm', action='store_true', help='quit preview after taking an image and wait for \
    message to start ')
    parser.add_argument('-n', '--no-video', action='store_true', help='start without showing video')

    args = parser.parse_args()
    if not is_already_running():
        CameraControl(args)
    else:
        if args.capture_image_and_download is not None:
            MessageSender('captureImage %s' % args.capture_image_and_download)
        elif args.message:
            MessageSender(args.message)
        else:
            MessageSender('startVideo')


if __name__ == '__main__':
    main()

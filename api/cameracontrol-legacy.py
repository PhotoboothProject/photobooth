#!/usr/bin/env python

import os, signal, sys, time, psutil, zmq, argparse
from argparse import Namespace
from subprocess import Popen, PIPE
import gphoto2 as gp


class CameraControl:
    def __init__(self, args):
        self.running = True
        self.args = args
        self.showVideo = True
        self.chroma = {}
        self.camera = None
        self.socket = None
        self.ffmpeg = None

        signal.signal(signal.SIGINT, self.exit_gracefully)
        signal.signal(signal.SIGTERM, self.exit_gracefully)

        self.connect_to_camera()

        if args.imgpath is not None:
            try:
                self.capture_image(args.imgpath)
                if args.chroma_sensitivity is not None and args.chroma_sensitivity > 0:
                    self.handle_chroma_params(args)
                    self.chroma_key_image(args.imgpath)
                sys.exit(0)
            except gp.GPhoto2Error as e:
                print('An error occured: %s' % e)
                sys.exit(1)
        else:
            self.pipe_video_to_ffmpeg_and_wait_for_commands()

    def connect_to_camera(self):
        try:
            self.camera = gp.Camera()
            self.camera.init()
            print('Connected to camera')
            if self.args.config is not None:
                print('Setting config %s' % self.args.config)
                for c in self.args.config:
                    cs = c.split("=")
                    if len(cs) == 2:
                        self.set_config(cs[0], cs[1])
                    else:
                        print('Invalid config value %s' % c)
        except gp.GPhoto2Error:
            pass

    def capture_image(self, path):
        print('Capturing image')
        # capturetarget does not exist on Sony Cameras
        # self.print_config('capturetarget')
        file_path = self.camera.capture(gp.GP_CAPTURE_IMAGE)
        # refresh images on camera
        self.camera.wait_for_event(1000)
        print('Camera file path: {0}/{1}'.format(file_path.folder, file_path.name))
        file_jpg = str(file_path.name).replace('.CR2', '.JPG')
        print('Copying image to', path)
        camera_file = self.camera.file_get(file_path.folder, file_jpg, gp.GP_FILE_TYPE_NORMAL)
        camera_file.save(path)

    def print_config(self, name):
        config = self.camera.get_config()
        setting = config.get_child_by_name(name)
        print('%s=%s' % (name, setting.get_value()))

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
                        self.exit_gracefully()
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
                self.exit_gracefully()
            self.camera.set_config(config)
            print('Config set %s=%s' % (name, value))
        except gp.GPhoto2Error or ValueError:
            print('Config error for %s=%s' % (name, value))
            self.exit_gracefully()

    def disable_video(self):
        self.showVideo = False
        self.set_config('viewfinder', 0)
        print('Video disabled')

    def handle_message(self, msg):
        args = Namespace(**msg)
        if args.exit:
            self.socket.send_string('Exiting service!')
            self.exit_gracefully()
        self.handle_chroma_params(args)
        if args.device != self.args.device:
            self.args.device = args.device
            self.ffmpeg_open()
            print('Video output device changed')
        if args.config is not None and args.config != self.args.config:
            self.args.config = args.config
            self.connect_to_camera()
            print('Applied updated config')
        if args.imgpath is not None:
            try:
                self.capture_image(args.imgpath)
                if args.chroma_sensitivity is not None and args.chroma_sensitivity > 0:
                    self.chroma_key_image(args.imgpath)
                self.socket.send_string('Image captured')
                if self.args.bsm:
                    self.disable_video()
            except gp.GPhoto2Error as e:
                print('An error occured: %s' % e)
                self.socket.send_string('failure')
        else:
            self.args.bsm = args.bsm
            try:
                if not self.showVideo:
                    self.showVideo = True
                    self.connect_to_camera()
                    self.socket.send_string('Starting Video')
                else:
                    self.socket.send_string('Video already running')
            except gp.GPhoto2Error:
                self.socket.send_string('failure')

    def ffmpeg_open(self):
        input_chroma = []
        filters = []
        if self.chroma.get('active', False):
            filters, input_chroma = self.get_chroma_ffmpeg_params()
        input_gphoto = ['-i', '-', '-vcodec', 'rawvideo', '-pix_fmt', 'yuv420p']
        ffmpeg_output = ['-preset', 'ultrafast', '-f', 'v4l2', self.args.device]
        self.ffmpeg = Popen(['ffmpeg', *input_chroma, *input_gphoto, *filters, *ffmpeg_output], stdin=PIPE)

    def handle_chroma_params(self, args):
        chroma_color = args.chroma_color or self.chroma.get('color', '0xFFFFFF')
        chroma_image = args.chroma_image or self.chroma.get('image')
        chroma_sensitivity = float(args.chroma_sensitivity or self.chroma.get('sensitivity', 0.0))
        if chroma_sensitivity < 0.0 or chroma_sensitivity > 1.0:
            chroma_sensitivity = 0.0
        chroma_blend = float(args.chroma_blend or self.chroma.get('blend', 0.0))
        if chroma_blend < 0.0:
            chroma_blend = 0.0
        elif chroma_blend > 1.0:
            chroma_blend = 1.0
        chroma_active = chroma_sensitivity != 0.0 and chroma_image is not None
        print('chromakeying active: %s' % chroma_active)
        self.chroma = {
            'active': chroma_active,
            'image': chroma_image,
            'color': chroma_color,
            'sensitivity': str(chroma_sensitivity),
            'blend': str(chroma_blend)
        }

    def get_chroma_ffmpeg_params(self):
        input_chroma = ['-i', self.chroma['image']]
        filters = ['-filter_complex', '[0:v][1:v]scale2ref[i][v];' +
                   '[v]colorkey=%s:%s:%s:[ck];[i][ck]overlay' %
                   (self.chroma['color'], self.chroma['sensitivity'], self.chroma['blend'])]
        return filters, input_chroma

    def chroma_key_image(self, path):
        input_chroma = []
        filters = []
        if self.chroma.get('active', False):
            filters, input_chroma = self.get_chroma_ffmpeg_params()
        input_gphoto = ['-i', path]
        tmp_path = "%s-chroma.jpg" % path
        ffmpeg_output = [tmp_path]
        Popen(['ffmpeg', *input_chroma, *input_gphoto, *filters, *ffmpeg_output]).wait(5)
        Popen(['mv', tmp_path, path, '-f']).wait(1)

    def pipe_video_to_ffmpeg_and_wait_for_commands(self):
        context = zmq.Context()
        self.socket = context.socket(zmq.REP)
        self.socket.bind('tcp://*:5555')
        self.handle_chroma_params(self.args)
        self.ffmpeg_open()
        try:
            while True:
                try:
                    message = self.socket.recv_json(flags=zmq.NOBLOCK)
                    print('Received: %s' % message)
                    self.handle_message(message)
                except zmq.Again:
                    pass
                try:
                    if self.showVideo:
                        capture = self.camera.capture_preview()
                        img_bytes = memoryview(capture.get_data_and_size()).tobytes()
                        self.ffmpeg.stdin.write(img_bytes)
                    else:
                        time.sleep(0.1)
                except gp.GPhoto2Error:
                    time.sleep(1)
                    print('Not connected to camera. Trying to reconnect...')
                    self.connect_to_camera()
        except KeyboardInterrupt:
            self.exit_gracefully()

    def exit_gracefully(self, *_):
        if self.running:
            self.running = False
            print('Exiting...')
            if self.camera:
                self.disable_video()
                self.camera.exit()
                print('Closed camera connection')
            sys.exit(0)


class MessageSender:
    def __init__(self, message):
        try:
            context = zmq.Context()
            socket = context.socket(zmq.REQ)
            socket.setsockopt(zmq.RCVTIMEO, 10000)
            socket.connect('tcp://localhost:5555')
            print('Sending message: %s' % message)
            socket.send_json(message)
            response = socket.recv_string()
            print(response)
            if response == 'failure':
                sys.exit(1)
        except zmq.Again:
            print('Message receival not confirmed')
            sys.exit(1)
        except KeyboardInterrupt:
            print('Interrupted!')


def get_running_pid():
    for p in psutil.process_iter(['name', 'cmdline']):
        if p.name() == 'python3' and p.cmdline()[1].endswith('cameracontrol.py') and p.pid != os.getpid():
            return p.pid
    return -1


def main():
    parser = argparse.ArgumentParser(description='Simple Camera Control script using libgphoto2 through \
    python-gphoto2.', epilog='If you don\'t want images to be stored on the camera make sure that capturetarget \
    is set to internal ram (might be device dependent but it\'s 0 for Canon cameras. Additionally you should \
    configure your camera to capture only jpeg images.', allow_abbrev=False)
    parser.add_argument('-d', '--device', nargs='?', default='/dev/video0',
                        help='virtual device the ffmpeg stream is sent to')
    parser.add_argument('-s', '--set-config', action='append', default=None, dest='config',
                        help='CONFIGENTRY=CONFIGVALUE analog to gphoto2 cli. Not tested for all config entries!')
    parser.add_argument('-c', '--capture-image-and-download', default=None, type=str, dest='imgpath',
                        help='capture an image and download it to the computer. If it stays stored on the camera as \
                        well depends on the camera config')
    parser.add_argument('-b', '--bsm', action='store_true', help='start preview, but quit preview after taking an \
                        image and wait for message to start preview again')
    parser.add_argument('--chromaImage', type=str, help='chroma key background (full path)', dest='chroma_image')
    parser.add_argument('--chromaColor', type=str,
                        help='chroma key color (color name or format like "0xFFFFFF" for white)', dest='chroma_color')
    parser.add_argument('--chromaSensitivity', type=float,
                        help='chroma key sensitivity (value from 0.01 to 1.0 or 0.0 to disable). \
                             If this is set to a value distinct from 0.0 on capture immage command chroma keying using \
                             ffmpeg is applied on the image and only this modified image is stored on the pc.',
                        dest='chroma_sensitivity')
    parser.add_argument('--chromaBlend', type=float, help='chroma key blend (0.0 to 1.0)', dest='chroma_blend')
    parser.add_argument('--exit', action='store_true', help='exit the service')

    args = parser.parse_args()
    pid = get_running_pid()
    if pid > 0:
        MessageSender(vars(args))
        print(pid)
    else:
        CameraControl(args)


if __name__ == '__main__':
    main()

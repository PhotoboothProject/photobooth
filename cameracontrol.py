import gphoto2 as gp
import sys, os, time, zmq, psutil
from subprocess import Popen, PIPE

class CameraControl:
    def connectToCamera(self):
        try:
            self.camera = gp.Camera()
            self.camera.init()
        except gp.GPhoto2Error:
            pass

    def printSettings(self):
        config_tree = self.camera.get_config(context)
        print('=======')
        total_child = config_tree.count_children()
        for i in range(total_child):
            child = config_tree.get_child(i)
            text_child = '# ' + child.get_label() + ' ' + child.get_name()
            print(text_child)
            for a in range(child.count_children()):
                grandchild = child.get_child(a)
                text_grandchild = '    * ' + grandchild.get_label() + ' -- ' + grandchild.get_name()
                print(text_grandchild)
                try:
                    text_grandchild_value = '        Setted: ' + grandchild.get_value()
                    print(text_grandchild_value)
                    print('        Possibilities:')
                    for k in range(grandchild.count_choices()):
                        choice = grandchild.get_choice(k)
                        text_choice = '         - ' + choice
                        print(text_choice)
                except:
                    pass
                print()
            print()

    def handleMessage(self, message):
        global camera, socket
        if message[0] == "capture" and len(message) == 2:
            try:
                print("capturing image")
                file_path = self.camera.capture(gp.GP_CAPTURE_IMAGE)
                # refresh images on camera
                gp.check_result(gp.gp_camera_wait_for_event(self.camera, 1000))
                print("Camera file path: {0}/{1}".format(file_path.folder, file_path.name))
                file_jpg = str(file_path.name).replace(".CR2", ".JPG")
                target = message[1]
                print("Copying image to", target)
                camera_file = self.camera.file_get(file_path.folder, file_jpg, gp.GP_FILE_TYPE_NORMAL)
                camera_file.save(target)
            finally:
                self.socket.send_string("Image captured")
        else:
            print("received message that can't be handled")

    def __init__(self):
        os.environ["HOME"] = "/var/www/"
        context = zmq.Context()
        self.socket = context.socket(zmq.REP)
        self.socket.bind("tcp://*:5555")
        self.connectToCamera()
        ffmpeg = Popen(["ffmpeg", "-i", "-", "-vcodec", "rawvideo", "-pix_fmt", "yuv420p", "-f", "v4l2", "/dev/video0"], stdin=PIPE)
        try:
            while True:
                try:
                    message = self.socket.recv_json(flags=zmq.NOBLOCK)
                    print("Received: '%s'" % message)
                    self.handleMessage(message)
                except zmq.Again:
                    pass
                try:
                    capture = self.camera.capture_preview()
                    filedata = capture.get_data_and_size()
                    data = memoryview(filedata)
                    ffmpeg.stdin.write(data.tobytes())
                except gp.GPhoto2Error:
                    time.sleep(5)
                    print("Not connected to camera. Trying to reconnect...")
                    self.connectToCamera()
        except KeyboardInterrupt:
            print("Exiting...")
            sys.exit(0)

def limitToOneInstance():
    instances = 0
    for p in psutil.process_iter(["name", "cmdline"]):
        if p.name() == "python3":
            if p.cmdline()[1] == "cameracontrol.py":
                instances += 1
    if instances > 1:
        print("Camera Control is already running...")
        sys.exit(0)

if __name__ == "__main__":
    if len(sys.argv) == 1:
        limitToOneInstance()
        CameraControl()
    else:
        request = sys.argv
        request.pop(0)
        context = zmq.Context()
        socket = context.socket(zmq.REQ)
        socket.setsockopt(zmq.RCVTIMEO, 10000)
        socket.connect("tcp://localhost:5555")
        print("Sending message %s..." % request)
        socket.send_json(request)
        print(socket.recv_string())

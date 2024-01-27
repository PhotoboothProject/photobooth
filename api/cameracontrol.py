#!/usr/bin/env python

"""
This script does these things:

* if another instance is already running, send the new arguments to the ffmpeg process
* if this is the first instance, run a new ffmpeg process
"""

import logging
import argparse
import os
import signal
import subprocess
import sys
import time
import zmq
import socket
import re
from typing import Any, List
from argparse import Namespace
from subprocess import Popen, PIPE
from datetime import datetime, timedelta

import gphoto2 as gp

TEMP_VIDEO_FILE_APPENDIX = ".temp.mp4"

log = logging.getLogger(__name__)


class CouldNotConnectException(Exception):
    pass


class DeviceNotFoundException(Exception):
    pass


class UnsupportedConfigException(Exception):
    pass


def create_virtual_camera(video_nr=9):
    """
    Create a device and return device path

    raises subprocess.CalledProcessError if not working
    """

    subprocess.run(
        f'modprobe v4l2loopback video_nr={video_nr} card_label="Gphoto2 Webcam" exclusive_caps=1',
        shell=True,
        check=True,
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL,
    )

    return f"/dev/video{video_nr}"


def get_v4l2_devices() -> List[str]:
    """
    Detects v4l2 and returns them as a list of strings
    """
    devices = []

    try:
        output = subprocess.run(
            "v4l2-ctl --list-devices",
            capture_output=True,
            shell=True,
            check=True,
            text=True,
        )
        for dev in re.findall(
            r"platform:v4l2loopback-\d\d\d\):\n\s*([^\n]+)",
            output.stdout,
            re.M,
        ):
            devices.append(dev)

    except subprocess.CalledProcessError as e:
        log.error(e)

    return devices


class CameraControl:
    def __init__(self, args):
        self.running = True
        self.args = args
        self.show_video = True
        self.chroma = {}
        self.camera = None
        self.socket = None
        self.ffmpeg = None
        self.bsm_stopTime = None

        self.connect_to_camera()
        self.apply_configuration()

    def connect_to_camera(self, retry: int = 5, retry_timeout: float = 0.5):
        """
        Connect to the camera

        If failed, raise CouldNotConnectException
        """
        i = 0
        while i < retry:
            try:
                self.camera = gp.Camera()
                self.camera.init()
                log.info("Connected to camera")
                break
            except gp.GPhoto2Error:
                time.sleep(retry_timeout)
                i = i + 1
        else:
            raise CouldNotConnectException()

    def apply_configuration(self):
        """
        Apply the configuration

        If the configuration is invalid an ValueError is raised
        """
        if self.args.config is not None:
            log.debug("Setting config %s" % self.args.config)
            for c in self.args.config:
                cs = c.split("=")
                if len(cs) == 2:
                    self.set_config(cs[0], cs[1])
                else:
                    raise ValueError("Invalid config value %s" % c)

    def capture_image(self, path: str):
        """
        Capture an image and save it to `path`
        """

        try:
            # be sure the output mode is not set to PC
            # otherwise the flash is not triggered
            self.set_config("output", "Off")
        except UnsupportedConfigException as e:
            log.error(e)

        log.info("Capturing image")
        file_path = self.camera.capture(gp.GP_CAPTURE_IMAGE)
        self.camera.wait_for_event(1000)
        log.info("Camera file path: {0}/{1}".format(file_path.folder, file_path.name))
        file_jpg = str(file_path.name).replace(".CR2", ".JPG")
        log.info("Copying image to %s" % path)
        camera_file = self.camera.file_get(
            file_path.folder, file_jpg, gp.GP_FILE_TYPE_NORMAL
        )
        camera_file.save(path)

    def print_config(self, name: str):
        """
        Print the value of the given config name
        """
        config = self.camera.get_config()
        setting = config.get_child_by_name(name)
        print("%s=%s" % (name, setting.get_value()))

    def check_config_support(self, name: str):
        """
        Checks if the given config param is supported by the connected camera

        Returns a boolean value
        """
        config = self.camera.get_config()
        OK, _ = gp.gp_widget_get_child_by_name(config, name)
        return OK >= gp.GP_OK

    def set_config(self, name, value):
        """
        Set a new config value

        May raises UnsupportedConfigException
        """

        if not self.check_config_support(name):
            raise UnsupportedConfigException(f"The parameter {name} is not supported")

        try:
            config = self.camera.get_config()
            setting = config.get_child_by_name(name)
            setting_type = setting.get_type()
            if (
                setting_type == gp.GP_WIDGET_RADIO
                or setting_type == gp.GP_WIDGET_MENU
                or setting_type == gp.GP_WIDGET_TEXT
            ):
                try:
                    int_value = int(value)
                    count = setting.count_choices()
                    if int_value < 0 or int_value >= count:
                        log.error("Parameter out of range")
                        self.exit_gracefully(rc=1)
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
                log.error(
                    "Unhandled setting type %s for %s=%s" % (setting_type, name, value)
                )
                self.exit_gracefully(rc=1)
            self.camera.set_config(config)
            log.info("Config set %s=%s" % (name, value))
        except gp.GPhoto2Error or ValueError:
            log.error("Config error for %s=%s" % (name, value))
            self.exit_gracefully(rc=1)

    def disable_video(self):
        """
        Disables the video
        """
        self.bsm_stopTime = None
        self.show_video = False

        try:
            self.set_config("viewfinder", 0)
        except UnsupportedConfigException as e:
            log.error(e)

        log.info("Video disabled")

    def handle_message(self, message):
        """
        Evaluate message and adjust config
        """
        args = Namespace(**message)
        if args.exit:
            self.socket.send_string("Exiting service!")
            self.exit_gracefully()
        video_settings_were_updated = self.handle_chroma_params(args)
        video_settings_were_updated = (
            video_settings_were_updated or self.handle_video_params(args)
        )
        self.handle_bsm_timeout(args)
        if args.config is not None and args.config != self.args.config:
            self.args.config = args.config
            self.connect_to_camera()
            video_settings_were_updated = True
            log.info("Applied updated config")
        if args.device != self.args.device:
            self.args.device = args.device
            video_settings_were_updated = True
            log.info("Video output device changed")
        if video_settings_were_updated:
            self.ffmpeg_open()
            log.info("Restarted ffmpeg stream with updated video settings")
        if args.imgpath is not None:
            try:
                self.capture_image(args.imgpath)
                if args.chroma_sensitivity is not None and args.chroma_sensitivity > 0:
                    self.chroma_key_image(args.imgpath)
                self.socket.send_string("Image captured")
                if self.args.bsm:
                    self.disable_video()
            except gp.GPhoto2Error as e:
                log.error("An error occured: %s" % e)
                self.socket.send_string("failure")
        else:
            self.args.bsm = args.bsm
            try:
                if not self.show_video and not args.bsmx:
                    self.show_video = True
                    self.connect_to_camera()
                    self.socket.send_string("Starting Video")
                else:
                    if args.bsmx:
                        self.socket.send_string(
                            "Updated config. Video not starting because of option --bsmx"
                        )
                    else:
                        self.socket.send_string("Video already running")
            except gp.GPhoto2Error:
                self.socket.send_string("failure")
        return False

    def ffmpeg_open(self):
        """
        Starts the ffmpeg process
        """
        input_config = ["-i", "-", "-vcodec", "rawvideo", "-pix_fmt", "yuv420p"]
        stream = ["-preset", "ultrafast", "-f", "v4l2", self.args.device]
        pre_input = []
        filters = []
        file_output = []
        if self.chroma.get("active", False):
            filters, pre_input = self.get_chroma_ffmpeg_params()
        if self.args.video_path is not None:
            temp_video_path = self.args.video_path + TEMP_VIDEO_FILE_APPENDIX
            if os.path.exists(self.args.video_path) or os.path.exists(temp_video_path):
                log.error("Video recording stopped: file or temp file already exist")
            else:
                pre_input = ["-t", str(self.args.video_length)]
                file_output = [
                    "-vf",
                    "fps=" + str(self.args.video_fps),
                    temp_video_path,
                ]
                if self.args.video_frames > 0:
                    # 99 images should be more than enough
                    if self.args.video_frames > 99:
                        self.args.video_frames = 99
                    image_fps = self.args.video_frames / self.args.video_length
                    file_output.extend(
                        [
                            "-vf",
                            "fps=" + str(image_fps),
                            self.args.video_path + "-%02d.jpg",
                        ]
                    )
        commands = [
            "ffmpeg",
            *pre_input,
            *input_config,
            *filters,
            *stream,
            *file_output,
        ]
        log.debug(commands)
        if self.ffmpeg:
            log.info("end open ffmpeg stream to start a new one")
            self.ffmpeg.kill()

        # wait max 5s until device is available
        # then raise exception
        timeout = 10
        i = 0
        while not os.path.exists(self.args.device):
            if i == timeout:
                raise DeviceNotFoundException("Device not found: %s" % self.args.device)
            time.sleep(0.5)
            i = i + 1

        self.ffmpeg = Popen(commands, stdin=PIPE)

    def handle_bsm_timeout(self, args):
        """
        Sets new timeout from args
        """
        if args.bsm_timeOut > 0:
            self.bsm_stopTime = datetime.now() + timedelta(minutes=args.bsm_timeOut)
            log.info(
                "Set bsm stop time to ", self.bsm_stopTime.strftime("%d.%m.%Y %H:%M:%S")
            )
        else:
            self.bsm_stopTime = None

    def handle_chroma_params(self, args):
        """
        Sets new chroma params from args
        """
        chroma_color = args.chroma_color or self.chroma.get("color", "0xFFFFFF")
        chroma_image = args.chroma_image or self.chroma.get("image")
        chroma_sensitivity = float(
            args.chroma_sensitivity or self.chroma.get("sensitivity", 0.0)
        )
        if chroma_sensitivity < 0.0 or chroma_sensitivity > 1.0:
            chroma_sensitivity = 0.0
        chroma_blend = float(args.chroma_blend or self.chroma.get("blend", 0.0))
        if chroma_blend < 0.0:
            chroma_blend = 0.0
        elif chroma_blend > 1.0:
            chroma_blend = 1.0
        chroma_active = chroma_sensitivity != 0.0 and chroma_image is not None
        log.info("chromakeying active: %s" % chroma_active)
        new_chroma = {
            "active": chroma_active,
            "image": chroma_image,
            "color": chroma_color,
            "sensitivity": str(chroma_sensitivity),
            "blend": str(chroma_blend),
        }
        settings_changed = new_chroma != self.chroma
        self.chroma = new_chroma
        return settings_changed

    def handle_video_params(self, args):
        """
        Sets the video params from args
        """
        self.args.video_path = args.video_path
        self.args.video_frames = args.video_frames
        self.args.video_length = args.video_length
        self.args.video_fps = args.video_fps
        return args.video_path is not None

    def get_chroma_ffmpeg_params(self):
        """
        Creates some complex ffmpeg params
        """
        input_chroma = ["-i", self.chroma["image"]]
        filters = [
            "-filter_complex",
            "[0:v][1:v]scale2ref[i][v];"
            + "[v]colorkey=%s:%s:%s:[ck];[i][ck]overlay"
            % (self.chroma["color"], self.chroma["sensitivity"], self.chroma["blend"]),
        ]
        return filters, input_chroma

    def chroma_key_image(self, path):
        """
        Creates an chroma key image and saves it to path
        """
        input_chroma = []
        filters = []
        if self.chroma.get("active", False):
            filters, input_chroma = self.get_chroma_ffmpeg_params()
        input_gphoto = ["-i", path]
        tmp_path = "%s-chroma.jpg" % path
        if (
            subprocess.run(
                ["ffmpeg", *input_chroma, *input_gphoto, *filters, tmp_path]
            ).returncode
            != 0
        ):
            log.error("Chroma keying failed")
            return

        os.replace(tmp_path, path)

    def daemon(self):
        """
        Sends the camera output into ffmpeg which writes it into the virtual webcam
        """
        context = zmq.Context()
        self.socket = context.socket(zmq.REP)
        self.socket.bind("tcp://*:5555")
        self.handle_chroma_params(self.args)
        self.handle_bsm_timeout(self.args)
        self.ffmpeg_open()
        try:
            while True:
                try:
                    message = self.socket.recv_json(flags=zmq.NOBLOCK)
                    log.info("Received: %s" % message)
                    self.handle_message(message)
                except zmq.Again:
                    pass
                try:
                    if (
                        self.bsm_stopTime is not None
                        and datetime.now() > self.bsm_stopTime
                    ):
                        log.info("Camera stopped because of bsm stop time")
                        self.disable_video()
                    if self.show_video:
                        capture = self.camera.capture_preview()
                        img_bytes = memoryview(capture.get_data_and_size()).tobytes()
                        self.ffmpeg.stdin.write(img_bytes)
                    else:
                        time.sleep(0.1)
                except gp.GPhoto2Error:
                    time.sleep(1)
                    log.info("Not connected to camera. Trying to reconnect...")
                    self.connect_to_camera()
                except BrokenPipeError:
                    log.error(
                        "Broken pipe: check if video recording finished, restart ffmpeg"
                    )
                    if self.args.video_path is not None:
                        temp_video_path = (
                            self.args.video_path + TEMP_VIDEO_FILE_APPENDIX
                        )
                        if os.path.exists(temp_video_path):
                            log.info("Video recording successful")
                            os.rename(temp_video_path, self.args.video_path)
                            self.args.video_path = None
                        else:
                            log.error(
                                "Video recording failed. Restart camera connection and retry."
                            )
                            self.camera.exit()
                            self.connect_to_camera()
                    else:
                        log.info("No video recording. Restart camera connection")
                        self.camera.exit()
                        self.connect_to_camera()
                    self.ffmpeg_open()
        except KeyboardInterrupt:
            self.exit_gracefully()
        except Exception as ex:
            log.error(ex)
            return 1

        return 0

    def exit_gracefully(self, *_, rc=0):
        """
        Close the camera connection gracefully
        """
        if self.running:
            self.running = False
            log.info("Exiting...")
            if self.camera:
                self.disable_video()
                self.camera.exit()
                log.info("Closed camera connection")
        sys.exit(rc)

    @staticmethod
    def update_config(config: dict[str, Any]) -> int:
        """
        Sends the new configuration to the listener

        Returns 0 if successful otherwise 1
        """
        try:
            context = zmq.Context()
            socket = context.socket(zmq.REQ)
            socket.setsockopt(zmq.RCVTIMEO, 10000)
            socket.connect("tcp://localhost:5555")
            log.debug("Sending message: %s" % config)
            socket.send_json(config)
            response = socket.recv_string()
            log.debug(response)
            if response == "failure":
                return 1
        except zmq.Again:
            log.debug("Message receival not confirmed")
            return 1
        except KeyboardInterrupt:
            log.debug("Interrupted!")
        return 0


def check_port(port: int = 5555):
    """
    Checks if the given port is open
    """
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as sock:
        return sock.connect_ex(("localhost", port)) == 0


def main():
    parser = argparse.ArgumentParser(
        description="Simple Camera Control script using libgphoto2 through \
    python-gphoto2.",
        epilog=(
            "If you don't want images to be stored on the camera make"
            " sure that capturetarget is set to internal ram (might be device "
            "dependent but it's 0 for Canon cameras. Additionally you should "
            "configure your camera to capture only jpeg images. For RAW+JPEG: "
            "this is possible for Canon cameras (CR2 raw files) other RAW file "
            "extensions would need to be added as required. The RAW file stays "
            "on the camera."
        ),
        allow_abbrev=False,
    )
    parser.add_argument(
        "-d",
        "--device",
        nargs="?",
        help=(
            "virtual device the ffmpeg stream is sent to,"
            "if nothing is given it will be autodetected."
        ),
    )
    parser.add_argument(
        "-s",
        "--set-config",
        action="append",
        dest="config",
        help=(
            "CONFIGENTRY=CONFIGVALUE analog to gphoto2 cli."
            " Not tested for all config entries!"
        ),
    )
    parser.add_argument(
        "-c",
        "--capture-image-and-download",
        type=str,
        dest="imgpath",
        help=(
            "capture an image and download it to the computer."
            " If it stays stored on the camera as well depends on "
            "the camera config. If this param is set while the service "
            "is not already running the application will take a single "
            "image and exit after that. Chroma params are used for that "
            "image, but no video will be created"
        ),
    )
    parser.add_argument(
        "--debug",
        action="store_true",
        help="enable debug mode",
    )
    parser.add_argument(
        "-b",
        "--bsm",
        action="store_true",
        help="start preview, but quit preview after taking an \
                        image and wait for message to start preview again",
        dest="bsm",
    )
    parser.add_argument(
        "--bsmx",
        action="store_true",
        help="In bsm mode: prevent cameracontrol.py from restarting \
                        the video. Useful to just execute a command",
        dest="bsmx",
    )
    parser.add_argument(
        "--bsmtime",
        default=0,
        type=int,
        help=(
            "Keep preview active for the specified time in minutes"
            " before ending the preview video. Set to 0 to disable"
        ),
        dest="bsm_timeOut",
    )
    parser.add_argument(
        "-v",
        "--video",
        default=None,
        type=str,
        dest="video_path",
        help="save the next part of the preview as a video file",
    )
    parser.add_argument(
        "--vframes",
        default=4,
        type=int,
        help="saves shots from the video in an equidistant time",
        dest="video_frames",
    )
    parser.add_argument(
        "--vlen",
        default=3,
        type=int,
        help="duration of the video in seconds",
        dest="video_length",
    )
    parser.add_argument(
        "--vfps", default=10, type=int, help="fps of the video", dest="video_fps"
    )
    parser.add_argument(
        "--chromaImage",
        type=str,
        help="chroma key background (full path)",
        dest="chroma_image",
    )
    parser.add_argument(
        "--chromaColor",
        type=str,
        help='chroma key color (color name or format like "0xFFFFFF" for white)',
        dest="chroma_color",
    )
    parser.add_argument(
        "--chromaSensitivity",
        type=float,
        help=(
            "chroma key sensitivity (value from 0.01 to 1.0 or 0.0 to disable). "
            "If this is set to a value distinct from 0.0 on capture image "
            "command chroma keying using ffmpeg is applied on the image and only"
            " this modified image is stored on the pc. If this is set on a "
            "preview command you get actual live chroma keying"
        ),
        dest="chroma_sensitivity",
    )
    parser.add_argument(
        "--chromaBlend",
        type=float,
        help="chroma key blend (0.0 to 1.0)",
        dest="chroma_blend",
    )
    parser.add_argument("--exit", action="store_true", help="exit the service")

    args = parser.parse_args()

    logging.basicConfig(level=logging.DEBUG if args.debug else logging.ERROR)

    if not args.device:
        log.info("Not device set, try to autodetect")
        v4l2_devices = get_v4l2_devices()
        if len(v4l2_devices) > 1:
            log.info("Found multiple devices, selecting the first one.")
        if v4l2_devices:
            args.device = v4l2_devices[0]
            log.info("Device set to %s", args.device)
        else:
            log.error("Could not autodetect virtual camera.")
            try:
                args.device = create_virtual_camera(video_nr=9)
                log.info("Virtual camera created: %s", args.device)
            except subprocess.CalledProcessError as e:
                log.error(e)
                return 1

    if check_port(5555):
        return CameraControl.update_config(vars(args))
    else:
        cam = CameraControl(args)
        signal.signal(signal.SIGINT, cam.exit_gracefully)
        signal.signal(signal.SIGTERM, cam.exit_gracefully)

        if args.imgpath is not None:
            try:
                cam.capture_image(args.imgpath)
                if args.chroma_sensitivity is not None and args.chroma_sensitivity > 0:
                    cam.handle_chroma_params(args)
                    cam.chroma_key_image(args.imgpath)
                return 0
            except gp.GPhoto2Error as e:
                log.error("An error occured: %s" % e)
                return 1
        else:
            return cam.daemon()


if __name__ == "__main__":
    sys.exit(main())

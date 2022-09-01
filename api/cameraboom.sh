#!/bin/bash
#todo gif or mp4. Selectable? Boomerang optional.
ffmpeg -i test.mp4 -filter_complex "[0]trim=start_frame=1:end_frame=29,setpts=PTS-STARTPTS,reverse[r];[0][r]concat=n=2:v=1:a=0" testboom.mp4
#ffmpeg -i test.mp4 -filter_complex "[0]trim=start_frame=1:end_frame=29,setpts=PTS-STARTPTS,reverse[r];[0][r]concat=n=2:v=1:a=0,split[s0][s1];[s0]palettegen[p];[s1][p]paletteuse" testboom.gif

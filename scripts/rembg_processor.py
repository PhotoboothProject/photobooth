#!/usr/bin/env python3

import sys
import os
from PIL import Image
from rembg import remove, new_session


def apply_rembg(
    input_path,
    background_path,
    model,
    alpha_matting,
    max_size,
    output_path,
    alpha_matting_foreground_threshold,
    alpha_matting_background_threshold,
    alpha_matting_erode_size,
    post_processing,
):
    # Create a session for better performance
    session_kwargs = {"model_name": model, "alpha_matting": alpha_matting}
    if alpha_matting_foreground_threshold is not None:
        session_kwargs["alpha_matting_foreground_threshold"] = (
            alpha_matting_foreground_threshold
        )
    if alpha_matting_background_threshold is not None:
        session_kwargs["alpha_matting_background_threshold"] = (
            alpha_matting_background_threshold
        )
    if alpha_matting_erode_size is not None:
        session_kwargs["alpha_matting_erode_size"] = alpha_matting_erode_size

    session = new_session(**session_kwargs)

    # Load input image
    input_image = Image.open(input_path)

    # Resize if too large
    if max_size > 0 and (input_image.width > max_size or input_image.height > max_size):
        input_image.thumbnail((max_size, max_size), Image.Resampling.LANCZOS)

    # Remove background
    output_image = remove(input_image, session=session, post_process=post_processing)

    # Load background image
    if background_path and os.path.exists(background_path):
        background = Image.open(background_path).convert("RGBA")
        # Resize background to match output_image size
        background = background.resize(output_image.size, Image.Resampling.LANCZOS)
    else:
        # Default white background
        background = Image.new("RGBA", output_image.size, (255, 255, 255, 255))

    # Composite: paste the foreground (output_image) onto background
    # Since output_image has alpha, use alpha_composite
    result = Image.alpha_composite(background, output_image)

    # Convert to RGB and save as JPEG
    result = result.convert("RGB")
    # If input and output are the same, save to temp first
    if input_path == output_path:
        temp_path = output_path + ".tmp"
        result.save(temp_path, "JPEG", quality=95)
        os.rename(temp_path, output_path)
    else:
        result.save(output_path, "JPEG", quality=95)


if __name__ == "__main__":
    if len(sys.argv) != 11:
        print(
            "Usage: python rembg_processor.py <input_image> <background_image> <model> <alpha_matting> <max_size> <output_image> <alpha_matting_foreground_threshold> <alpha_matting_background_threshold> <alpha_matting_erode_size> <post_processing>"
        )
        sys.exit(1)

    input_path = sys.argv[1]
    background_path = sys.argv[2]
    model = sys.argv[3]
    alpha_matting = sys.argv[4] == "1"
    max_size = int(sys.argv[5])
    output_path = sys.argv[6]
    alpha_matting_foreground_threshold = int(sys.argv[7]) if sys.argv[7] else None
    alpha_matting_background_threshold = int(sys.argv[8]) if sys.argv[8] else None
    alpha_matting_erode_size = int(sys.argv[9]) if sys.argv[9] else None
    post_processing = sys.argv[10] == "1"

    apply_rembg(
        input_path,
        background_path,
        model,
        alpha_matting,
        max_size,
        output_path,
        alpha_matting_foreground_threshold,
        alpha_matting_background_threshold,
        alpha_matting_erode_size,
        post_processing,
    )

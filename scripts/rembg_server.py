#!/usr/bin/env python3

import os
import tempfile
from flask import Flask, request, send_file
from rembg import remove, new_session
from PIL import Image
import io

app = Flask(__name__)

# Create a session for better performance
session = new_session()


@app.route("/api/remove", methods=["POST"])
def remove_background():
    if "file" not in request.files:
        return {"error": "No file provided"}, 400

    file = request.files["file"]
    if file.filename == "":
        return {"error": "No file selected"}, 400

    try:
        # Read the uploaded image
        input_image = Image.open(file.stream)

        # Remove background
        output_image = remove(input_image, session=session)

        # Save to bytes buffer
        output_buffer = io.BytesIO()
        output_image.save(output_buffer, format="PNG")
        output_buffer.seek(0)

        return send_file(output_buffer, mimetype="image/png")

    except Exception as e:
        return {"error": str(e)}, 500


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=7000)
